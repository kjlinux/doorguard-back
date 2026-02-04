# Guide de deploiement : Laravel (Reverb + MQTT) + Next.js sur VPS Hestia Panel

Guide base sur le deploiement reel de DoorGuard. Couvre les erreurs rencontrees et leurs solutions.

---

## Architecture

```
Internet
   |
   v
Nginx (Hestia)
   |
   +-- api.example.com -----> Apache :8443 --> Laravel (PHP-FPM)
   +-- app.example.com -----> PM2 :3003  --> Next.js
   +-- ws.example.com  -----> Reverb :8088 --> WebSocket
```

Hestia utilise **nginx en reverse proxy** devant **Apache** pour les domaines web.
Les WebSockets et Next.js utilisent nginx en proxy direct (sans Apache).

---

## Prerequis sur le VPS

```bash
# PHP 8.3+ avec extensions
sudo apt install php8.3-cli php8.3-pgsql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js 20+ (Next.js 16 exige >= 20.9.0)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash
source ~/.bashrc
nvm install 20
nvm use 20

# pnpm
npm install -g pnpm

# PM2
npm install -g pm2

# Supervisor (pour les daemons Laravel)
sudo apt install supervisor

# PostgreSQL (via Hestia ou manuellement)
```

---

## 1. Backend Laravel

### 1.1 Creer le domaine dans Hestia

1. Creer le domaine `api.example.com` dans Hestia
2. Activer SSL (Let's Encrypt)
3. **Web Template** : laisser par defaut
4. Pointer la racine web vers `public/`

### 1.2 Cloner et configurer

```bash
cd /home/USER/web/api.example.com/public_html
git clone https://github.com/user/backend.git .

# Corriger le ownership git si necessaire
git config --global --add safe.directory /home/USER/web/api.example.com/public_html

cp .env.example .env
```

### 1.3 Configurer le .env

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.example.com

DB_CONNECTION=pgsql
DB_HOST=localhost
DB_DATABASE=mydb
DB_USERNAME=myuser
DB_PASSWORD=mypassword

# Broadcasting - le broadcaster se connecte en INTERNE a Reverb
BROADCAST_CONNECTION=reverb

# Reverb - Configuration publique (pour les clients WebSocket)
REVERB_APP_ID=doorguard
REVERB_APP_KEY=votre-cle-reverb
REVERB_APP_SECRET=votre-secret-reverb
REVERB_HOST=ws.example.com
REVERB_PORT=443
REVERB_SCHEME=https

# Reverb - Configuration serveur (ecoute locale)
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8088

# MQTT (HiveMQ Cloud ou autre broker)
MQTT_HOST=xxx.hivemq.cloud
MQTT_PORT=8883
MQTT_TLS_ENABLED=true
MQTT_AUTH_USERNAME=username
MQTT_AUTH_PASSWORD=password
MQTT_CLIENT_ID=my-api
```

### 1.4 Installer et migrer

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 1.5 Permissions

```bash
sudo chown -R USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 1.6 Configuration broadcasting.php (IMPORTANT)

Le broadcaster Laravel doit se connecter a Reverb **en interne** via localhost, pas via l'URL publique :

```php
// config/broadcasting.php
'reverb' => [
    'driver' => 'reverb',
    'key' => env('REVERB_APP_KEY'),
    'secret' => env('REVERB_APP_SECRET'),
    'app_id' => env('REVERB_APP_ID'),
    'options' => [
        'host' => '127.0.0.1',
        'port' => env('REVERB_SERVER_PORT', 8080),
        'scheme' => 'http',
        'useTLS' => false,
    ],
],
```

**Pourquoi ?** Si on utilise `env('REVERB_HOST')` (= `ws.example.com`), le serveur essaie de faire un POST HTTPS vers lui-meme via l'URL publique. Ca peut echouer a cause de la resolution DNS ou de la verification SSL.

### 1.7 Supervisor (daemons)

Creer `/etc/supervisor/conf.d/doorguard.conf` :

```ini
[program:doorguard-reverb]
command=php /home/USER/web/api.example.com/public_html/artisan reverb:start
autostart=true
autorestart=true
user=USER
redirect_stderr=true
stdout_logfile=/var/log/supervisor/doorguard-reverb.log

[program:doorguard-mqtt-listener]
command=php /home/USER/web/api.example.com/public_html/artisan mqtt:listen
autostart=true
autorestart=true
user=USER
redirect_stderr=true
stdout_logfile=/var/log/supervisor/doorguard-mqtt.log

[program:doorguard-queue-worker]
command=php /home/USER/web/api.example.com/public_html/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=USER
numprocs=2
process_name=%(program_name)s_%(process_num)02d
redirect_stderr=true
stdout_logfile=/var/log/supervisor/doorguard-queue.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

---

## 2. WebSocket (Reverb via nginx)

### 2.1 Creer le domaine ws.example.com

1. Creer `ws.example.com` dans Hestia
2. Activer SSL (Let's Encrypt)

### 2.2 Ajouter la directive map dans nginx.conf

Editer `/etc/nginx/nginx.conf`, ajouter dans le bloc `http {}` :

```nginx
map $http_upgrade $connection_upgrade {
    default upgrade;
    '' close;
}
```

```bash
sudo nginx -t && sudo systemctl reload nginx
```

### 2.3 Modifier la config nginx du domaine WS

Editer `/home/USER/conf/web/ws.example.com/nginx.ssl.conf` :

```nginx
server {
    listen      IP:443 ssl;
    server_name ws.example.com;

    ssl_certificate     /path/to/ssl/cert.pem;
    ssl_certificate_key /path/to/ssl/key.pem;

    location / {
        proxy_pass http://127.0.0.1:8088;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection $connection_upgrade;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 3600s;
        proxy_send_timeout 3600s;
    }

    location @fallback {
        proxy_pass http://127.0.0.1:8088;
    }
}
```

```bash
sudo nginx -t && sudo systemctl reload nginx
```

> **ATTENTION** : Ne jamais lancer `v-rebuild-web-domain USER ws.example.com` apres avoir modifie manuellement le nginx.ssl.conf. Hestia ecrase le fichier lors d'un rebuild.

### 2.4 Verifier que Reverb repond

```bash
curl -i -N \
  -H "Connection: Upgrade" \
  -H "Upgrade: websocket" \
  -H "Sec-WebSocket-Version: 13" \
  -H "Sec-WebSocket-Key: dGVzdA==" \
  https://ws.example.com/app/VOTRE_APP_KEY
```

Reponse attendue contenant `pusher:connection_established`.

---

## 3. Frontend Next.js

### 3.1 Creer le domaine dans Hestia

1. Creer `app.example.com` dans Hestia
2. Activer SSL

### 3.2 Cloner et configurer

```bash
cd /home/USER/web/app.example.com/public_html
git clone https://github.com/user/frontend.git .
```

Creer `.env.production` :

```env
NEXT_PUBLIC_API_URL=https://api.example.com/api
NEXT_PUBLIC_REVERB_APP_KEY=votre-cle-reverb
NEXT_PUBLIC_REVERB_HOST=ws.example.com
NEXT_PUBLIC_REVERB_PORT=443
NEXT_PUBLIC_REVERB_SCHEME=https
```

> **IMPORTANT** : Les variables `NEXT_PUBLIC_*` sont injectees au **build time**, pas au runtime. Toute modification necessite un `pnpm build`.

### 3.3 Build et lancement

```bash
pnpm install
pnpm build
```

Creer `ecosystem.config.js` :

```js
module.exports = {
  apps: [{
    name: 'doorguard-front',
    script: 'node_modules/next/dist/bin/next',
    args: 'start -p 3003',
    cwd: '/home/USER/web/app.example.com/public_html',
    instances: 1,
    autorestart: true,
    watch: false,
    max_memory_restart: '1G',
    env: {
      NODE_ENV: 'production',
      PORT: 3003
    }
  }]
}
```

```bash
pm2 start ecosystem.config.js
pm2 save
pm2 startup  # suivre les instructions pour le demarrage auto
```

### 3.4 Nginx reverse proxy pour Next.js

Editer `/home/USER/conf/web/app.example.com/nginx.ssl.conf` et ajouter dans le bloc `location /` :

```nginx
location / {
    proxy_pass http://127.0.0.1:3003;
    proxy_http_version 1.1;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

> Meme avertissement : ne pas faire `v-rebuild-web-domain` apres modification manuelle.

---

## 4. Configuration Echo (Frontend)

```typescript
// lib/echo.ts
import Echo from "laravel-echo"
import Pusher from "pusher-js"

window.Pusher = Pusher

const scheme = process.env.NEXT_PUBLIC_REVERB_SCHEME ?? "http"
const forceTLS = scheme === "https"

const echo = new Echo({
  broadcaster: "reverb" as const,
  key: process.env.NEXT_PUBLIC_REVERB_APP_KEY,
  wsHost: process.env.NEXT_PUBLIC_REVERB_HOST,
  wsPort: Number(process.env.NEXT_PUBLIC_REVERB_PORT ?? 80),
  wssPort: Number(process.env.NEXT_PUBLIC_REVERB_PORT ?? 443),
  forceTLS,
  enabledTransports: ["ws", "wss"],
})
```

**Points critiques :**
- `enabledTransports` doit TOUJOURS inclure `["ws", "wss"]`. Ne PAS filtrer selon `forceTLS` (ex: `["wss"]` seul cause `initialized -> failed` dans pusher-js v8).
- Ne pas utiliser `disableStats` (deprecated dans pusher-js v8, utiliser `enableStats: false` si necessaire).
- `cluster` n'est pas necessaire avec Reverb.

---

## 5. MQTT avec env() vs config()

```php
// MAUVAIS - env() retourne null quand le config est cache
$host = env('MQTT_HOST');

// BON - config() lit le cache correctement
$host = config('mqtt.host');
```

**Regle** : Dans les commandes Artisan et les classes applicatives, toujours utiliser `config()`. Reserver `env()` aux fichiers `config/*.php` uniquement.

---

## 6. Erreurs courantes et solutions

### composer.lock incompatible avec la version PHP du serveur

**Erreur** : `Your lock file does not contain a compatible set of packages`

**Solution** :
```bash
rm composer.lock
composer update --no-dev
```

### fake() undefined en production

**Erreur** : `Call to undefined function fake()` lors des seeders

**Solution** : Ne pas executer les seeders en production. Utiliser `php artisan migrate --force` au lieu de `migrate:fresh --seed`.

### Port Reverb deja utilise

**Erreur** : `Address already in use` pour le port 8080

**Cause** : Hestia utilise le port 8080 pour Apache en backend.

**Solution** : Utiliser un port different via `REVERB_SERVER_PORT=8088`.

### Nginx ecrase la config apres rebuild

**Cause** : `v-rebuild-web-domain` regenere les fichiers nginx.

**Solution** : Ne JAMAIS executer `v-rebuild-web-domain` pour les domaines avec une config nginx personnalisee (ws, frontend).

### Node.js trop ancien pour Next.js

**Erreur** : `You are using Node.js 18.x.x. For Next.js, Node.js version >= v20.9.0 is required.`

**Solution** :
```bash
nvm install 20
nvm use 20
nvm alias default 20
# Reinstaller PM2 avec le nouveau Node
npm install -g pm2
```

### PM2 avec pnpm echoue

**Erreur** : `ELIFECYCLE` quand PM2 lance `pnpm start`

**Solution** : Utiliser directement le binaire Next.js dans ecosystem.config.js :
```js
script: 'node_modules/next/dist/bin/next',
args: 'start -p 3003',
```

### WebSocket : initialized -> failed

**Cause** : `enabledTransports: ["wss"]` (uniquement `wss`) n'est pas un transport valide dans pusher-js v8.

**Solution** : Toujours utiliser `enabledTransports: ["ws", "wss"]`. Le TLS est gere par `forceTLS`.

### Broadcasting ne pousse pas les evenements en temps reel

**Cause** : `config/broadcasting.php` utilise `REVERB_HOST` (= URL publique). Le serveur n'arrive pas a se joindre via son propre domaine.

**Solution** : Configurer le broadcaster pour se connecter en interne :
```php
'options' => [
    'host' => '127.0.0.1',
    'port' => env('REVERB_SERVER_PORT', 8080),
    'scheme' => 'http',
    'useTLS' => false,
],
```

### Permission denied sur storage/

**Solution** :
```bash
sudo chown -R USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## 7. Commandes utiles

```bash
# Logs supervisor
sudo tail -f /var/log/supervisor/doorguard-reverb.log
sudo tail -f /var/log/supervisor/doorguard-mqtt.log
sudo supervisorctl status

# Logs PM2
pm2 logs doorguard-front

# Recacher la config Laravel (apres modification .env)
php artisan config:cache

# Tester le WebSocket depuis le serveur
curl -i -N -H "Connection: Upgrade" -H "Upgrade: websocket" \
  -H "Sec-WebSocket-Version: 13" -H "Sec-WebSocket-Key: dGVzdA==" \
  http://127.0.0.1:8088/app/VOTRE_APP_KEY
```

---

## 8. Deploiement rapide

Apres un push sur GitHub :

```bash
# Backend
ssh user@vps "bash /home/USER/web/api.example.com/public_html/deploy.sh"

# Frontend
ssh user@vps "bash /home/USER/web/app.example.com/public_html/deploy.sh"
```
