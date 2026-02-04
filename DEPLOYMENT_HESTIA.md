# 🚀 Guide de Déploiement DoorGuard sur VPS avec Hestia Panel

## 📋 Informations du serveur

- **Domaine**: api.doorguard.tangagroup.com
- **IP**: 180.149.198.250
- **User Hestia**: Nycaise
- **Chemin**: /home/Nycaise/web/api.doorguard.tangagroup.com/
- **Base de données**: doorguard_db (PostgreSQL)
- **SSL**: Let's Encrypt (déjà configuré)

---

## 🎯 ÉTAPE 1 : Configuration initiale dans Hestia Panel

### 1.1 Corriger la configuration du domaine dans Hestia Panel

Actuellement, Hestia cherche le répertoire `public` alors qu'il n'existe pas encore.

**Dans l'interface Hestia Panel** :
1. Allez dans **Web** → **api.doorguard.tangagroup.com** → **Edit**
2. Dans **"Racine de document personnalisée"**, mettre : **VIDE** (laisser vide pour l'instant)
3. Sauvegarder

Une fois le code cloné, vous reviendrez pour mettre `public` comme répertoire racine.

---

## 🎯 ÉTAPE 2 : Connexion SSH et préparation du serveur

### 2.1 Se connecter en SSH

```bash
ssh root@180.149.198.250
# OU
ssh Nycaise@180.149.198.250
```

### 2.2 Vérifier les prérequis

```bash
# Vérifier PHP
php -v
# Doit afficher PHP 8.2 ou supérieur

# Vérifier Composer
composer --version
# Si pas installé, voir section installation ci-dessous

# Vérifier PostgreSQL
psql --version

# Vérifier Supervisor (pour les processus daemon)
supervisorctl version
# Si pas installé : sudo apt install supervisor -y
```

### 2.3 Installer Composer (si nécessaire)

```bash
cd ~
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

---

## 🎯 ÉTAPE 3 : Création de la base de données PostgreSQL

### 3.1 Se connecter à PostgreSQL

```bash
sudo -u postgres psql
```

### 3.2 Créer la base de données et l'utilisateur

```sql
-- Créer l'utilisateur (remplacer 'VotreMotDePasse' par un mot de passe sécurisé)
CREATE USER doorguard_user WITH PASSWORD 'VotreMotDePasse';

-- Créer la base de données
CREATE DATABASE doorguard_db OWNER doorguard_user;

-- Donner tous les privilèges
GRANT ALL PRIVILEGES ON DATABASE doorguard_db TO doorguard_user;

-- Quitter
\q
```

### 3.3 Tester la connexion

```bash
psql -U doorguard_user -d doorguard_db -h localhost
# Entrer le mot de passe quand demandé
# Si ça marche, taper \q pour quitter
```

---

## 🎯 ÉTAPE 4 : Cloner le projet depuis GitHub

### 4.1 Se placer dans le bon répertoire

```bash
cd /home/Nycaise/web/api.doorguard.tangagroup.com
```

### 4.2 Cloner le dépôt dans public_html

```bash
# Sauvegarder le contenu actuel si nécessaire
sudo rm -rf public_html

# Cloner le projet
sudo git clone https://github.com/kjlinux/doorguard-back.git public_html

# Aller dans le répertoire
cd public_html
```

### 4.3 Définir les permissions correctes

```bash
# Changer le propriétaire pour l'utilisateur Hestia
sudo chown -R Nycaise:Nycaise /home/Nycaise/web/api.doorguard.tangagroup.com/public_html

# Permissions sur les répertoires sensibles
sudo chmod -R 775 storage
sudo chmod -R 775 bootstrap/cache

# Le groupe www-data doit pouvoir écrire
sudo chgrp -R www-data storage bootstrap/cache
```

---

## 🎯 ÉTAPE 5 : Configuration de l'environnement (.env)

### 5.1 Copier le fichier .env.example

```bash
cd /home/Nycaise/web/api.doorguard.tangagroup.com/public_html
cp .env.example .env
```

### 5.2 Éditer le fichier .env

```bash
nano .env
```

**Mettre les valeurs suivantes** :

```env
APP_NAME=DoorGuard
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://api.doorguard.tangagroup.com

APP_LOCALE=fr
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=fr_FR

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

# Base de données PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=doorguard_db
DB_USERNAME=doorguard_user
DB_PASSWORD=VotreMotDePasse

# Session & Cache
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

CACHE_STORE=database

# Queue (base de données)
QUEUE_CONNECTION=database

# Broadcasting avec Reverb
BROADCAST_CONNECTION=reverb

# MQTT (HiveMQ Cloud)
MQTT_HOST=fd286f0fca334917b338f6f5882a2763.s1.eu.hivemq.cloud
MQTT_PORT=8883
MQTT_CLIENT_ID=doorguard-api-prod
MQTT_TLS_ENABLED=true
MQTT_AUTH_ENABLED=true
MQTT_AUTH_USERNAME=perseus911
MQTT_AUTH_PASSWORD=Wemtinga2026@

# Reverb WebSocket
REVERB_APP_ID=525199
REVERB_APP_KEY=adwexqlaq3a9k65en5g8
REVERB_APP_SECRET=iq1kp8weeelp4cfhlvqb
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=https

# Sanctum
SANCTUM_STATEFUL_DOMAINS=api.doorguard.tangagroup.com,doorguard.tangagroup.com
FRONTEND_URL=https://doorguard.tangagroup.com

# Mail (optionnel)
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="noreply@doorguard.tangagroup.com"
MAIL_FROM_NAME="${APP_NAME}"

FILESYSTEM_DISK=local
```

**Sauvegarder** : `Ctrl+X` → `Y` → `Enter`

---

## 🎯 ÉTAPE 6 : Installation des dépendances

### 6.1 Installer les dépendances PHP

```bash
cd /home/Nycaise/web/api.doorguard.tangagroup.com/public_html

# Installation sans les dépendances de développement
composer install --no-dev --optimize-autoloader
```

### 6.2 Générer la clé d'application

```bash
php artisan key:generate
```

### 6.3 Exécuter les migrations

```bash
php artisan migrate --force
```

### 6.4 Optimiser Laravel pour la production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 6.5 Créer le lien symbolique pour le storage (si nécessaire)

```bash
php artisan storage:link
```

---

## 🎯 ÉTAPE 7 : Configuration Apache/PHP-FPM dans Hestia

### 7.1 Retourner dans Hestia Panel

1. Allez dans **Web** → **api.doorguard.tangagroup.com** → **Edit**
2. Dans **"Racine de document personnalisée"**, mettre : `public`
3. Le chemin final sera : `/home/Nycaise/web/api.doorguard.tangagroup.com/public_html/public`
4. Vérifier que **"Activer le SSL pour ce domaine"** est coché
5. Vérifier que **"Utiliser Let's Encrypt"** est coché
6. Vérifier que **"Activer la redirection automatique en HTTPS"** est coché
7. Sauvegarder

### 7.2 Tester l'accès à l'API

Ouvrez votre navigateur :
```
https://api.doorguard.tangagroup.com
```

Vous devriez voir la page Laravel ou une réponse JSON si vous avez une route `/`.

---

## 🎯 ÉTAPE 8 : Configuration des services (Supervisor)

Pour faire tourner **Reverb**, **MQTT Listener** et **Queue Worker** en arrière-plan.

### 8.1 Copier le fichier de configuration Supervisor

```bash
cd /home/Nycaise/web/api.doorguard.tangagroup.com/public_html

# Copier le fichier de configuration
sudo cp supervisor-doorguard.conf /etc/supervisor/conf.d/doorguard.conf
```

### 8.2 Éditer le fichier pour adapter les chemins

```bash
sudo nano /etc/supervisor/conf.d/doorguard.conf
```

**Remplacer** toutes les occurrences de `/path/to/doorguard-back` par :
```
/home/Nycaise/web/api.doorguard.tangagroup.com/public_html
```

Et remplacer `www-data` par `Nycaise` pour l'utilisateur.

**Le fichier devrait ressembler à** :

```ini
[program:doorguard-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/Nycaise/web/api.doorguard.tangagroup.com/public_html/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=Nycaise
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/doorguard-queue.log
stopwaitsecs=3600

[program:doorguard-reverb]
process_name=%(program_name)s
command=php /home/Nycaise/web/api.doorguard.tangagroup.com/public_html/artisan reverb:start
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=Nycaise
redirect_stderr=true
stdout_logfile=/var/log/doorguard-reverb.log

[program:doorguard-mqtt-listener]
process_name=%(program_name)s
command=php /home/Nycaise/web/api.doorguard.tangagroup.com/public_html/artisan mqtt:listen
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=Nycaise
redirect_stderr=true
stdout_logfile=/var/log/doorguard-mqtt.log
```

**Sauvegarder** : `Ctrl+X` → `Y` → `Enter`

### 8.3 Créer les fichiers de logs

```bash
sudo touch /var/log/doorguard-queue.log
sudo touch /var/log/doorguard-reverb.log
sudo touch /var/log/doorguard-mqtt.log

sudo chown Nycaise:Nycaise /var/log/doorguard-*.log
sudo chmod 664 /var/log/doorguard-*.log
```

### 8.4 Recharger Supervisor et démarrer les services

```bash
# Recharger la configuration
sudo supervisorctl reread
sudo supervisorctl update

# Démarrer tous les services
sudo supervisorctl start all

# Vérifier le statut
sudo supervisorctl status
```

**Vous devriez voir** :

```
doorguard-mqtt-listener          RUNNING   pid 12345, uptime 0:00:10
doorguard-queue-worker:00        RUNNING   pid 12346, uptime 0:00:10
doorguard-queue-worker:01        RUNNING   pid 12347, uptime 0:00:10
doorguard-reverb                 RUNNING   pid 12348, uptime 0:00:10
```

### 8.5 Vérifier les logs

```bash
# MQTT Listener
sudo tail -f /var/log/doorguard-mqtt.log

# Queue Worker
sudo tail -f /var/log/doorguard-queue.log

# Reverb
sudo tail -f /var/log/doorguard-reverb.log
```

---

## 🎯 ÉTAPE 9 : Configuration du WebSocket (Reverb) avec proxy Apache

Hestia utilise Apache, donc il faut configurer un proxy pour Reverb.

### 9.1 Activer les modules Apache nécessaires

```bash
sudo a2enmod proxy
sudo a2enmod proxy_http
sudo a2enmod proxy_wstunnel
sudo systemctl restart apache2
```

### 9.2 Créer un sous-domaine pour WebSocket (optionnel mais recommandé)

**Option A : Sous-domaine dédié** (Recommandé)

Dans Hestia Panel :
1. Créer un nouveau domaine : `ws.doorguard.tangagroup.com`
2. Configurer le SSL Let's Encrypt
3. Ensuite, éditer manuellement le vhost Apache

**Option B : Utiliser le même domaine avec un chemin `/ws`**

### 9.3 Éditer le vhost Apache

```bash
# Trouver le fichier de configuration
sudo nano /home/Nycaise/conf/web/api.doorguard.tangagroup.com/apache2.ssl.conf
```

**Ajouter juste avant la ligne `</VirtualHost>` à la fin** :

```apache
# WebSocket Reverb Proxy
<Location /ws>
    ProxyPass ws://127.0.0.1:8080/
    ProxyPassReverse ws://127.0.0.1:8080/

    # WebSocket support
    RewriteEngine On
    RewriteCond %{HTTP:Upgrade} websocket [NC]
    RewriteCond %{HTTP:Connection} upgrade [NC]
    RewriteRule ^/ws/?(.*) "ws://127.0.0.1:8080/$1" [P,L]
</Location>

# HTTP Proxy pour Reverb
<Location /ws>
    ProxyPass http://127.0.0.1:8080/
    ProxyPassReverse http://127.0.0.1:8080/
    ProxyPreserveHost On
</Location>
```

**Sauvegarder** : `Ctrl+X` → `Y` → `Enter`

### 9.4 Redémarrer Apache

```bash
sudo systemctl restart apache2
```

### 9.5 Mettre à jour le .env

```bash
nano /home/Nycaise/web/api.doorguard.tangagroup.com/public_html/.env
```

Modifier :
```env
REVERB_HOST=api.doorguard.tangagroup.com/ws
REVERB_PORT=443
REVERB_SCHEME=https
```

Reconstruire le cache :
```bash
cd /home/Nycaise/web/api.doorguard.tangagroup.com/public_html
php artisan config:cache
sudo supervisorctl restart doorguard-reverb
```

---

## 🎯 ÉTAPE 10 : Tests finaux

### 10.1 Tester l'API

```bash
curl https://api.doorguard.tangagroup.com/api/health
# Ou tester une route de votre API
```

### 10.2 Tester MQTT

Depuis MQTTX (sur votre ordinateur local) :
- **Topic** : `doorguard/sensor/1/event`
- **Payload** :
```json
{
  "action": "open",
  "timestamp": "2026-02-04T16:00:00Z"
}
```

Publier le message et vérifier les logs :
```bash
sudo tail -f /var/log/doorguard-mqtt.log
```

Vous devriez voir :
```
Message reçu sur [doorguard/sensor/1/event]: {"action":"open",...}
Événement créé: capteur #1
```

### 10.3 Vérifier la queue

```bash
sudo tail -f /var/log/doorguard-queue.log
```

Vous devriez voir le traitement des jobs de broadcast.

### 10.4 Vérifier la base de données

```bash
psql -U doorguard_user -d doorguard_db -h localhost
```

```sql
SELECT COUNT(*) FROM sensor_events;
SELECT * FROM sensor_events ORDER BY detected_at DESC LIMIT 5;
\q
```

---

## 🎯 ÉTAPE 11 : Mise à jour future du code

Quand vous faites des modifications au code :

```bash
cd /home/Nycaise/web/api.doorguard.tangagroup.com/public_html

# Récupérer les dernières modifications
git pull origin main

# Installer les nouvelles dépendances
composer install --no-dev --optimize-autoloader

# Exécuter les nouvelles migrations
php artisan migrate --force

# Reconstruire les caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Redémarrer les services
sudo supervisorctl restart all
```

---

## 🐛 Dépannage

### Problème : Permission denied sur storage

```bash
cd /home/Nycaise/web/api.doorguard.tangagroup.com/public_html
sudo chmod -R 775 storage bootstrap/cache
sudo chgrp -R www-data storage bootstrap/cache
```

### Problème : Les services ne démarrent pas

```bash
# Vérifier les logs
sudo supervisorctl tail doorguard-mqtt-listener
sudo supervisorctl tail doorguard-queue-worker:00
sudo supervisorctl tail doorguard-reverb

# Redémarrer manuellement
sudo supervisorctl restart all
```

### Problème : MQTT ne se connecte pas

```bash
# Tester depuis le serveur
cd /home/Nycaise/web/api.doorguard.tangagroup.com/public_html
php artisan tinker

# Dans tinker :
use PhpMqtt\Client\MqttClient;
$client = new MqttClient('fd286f0fca334917b338f6f5882a2763.s1.eu.hivemq.cloud', 8883);
$client->connect();
```

### Problème : Queue ne traite pas les jobs

```bash
cd /home/Nycaise/web/api.doorguard.tangagroup.com/public_html

# Lister les jobs en attente
php artisan queue:monitor database

# Voir les jobs échoués
php artisan queue:failed

# Réessayer
php artisan queue:retry all
```

### Problème : WebSocket ne fonctionne pas

Vérifier :
1. Que Reverb tourne : `sudo supervisorctl status doorguard-reverb`
2. Les logs : `sudo tail -f /var/log/doorguard-reverb.log`
3. Que le port 8080 écoute : `sudo netstat -tlnp | grep 8080`
4. La configuration Apache

---

## 📊 Monitoring

### Redémarrer automatiquement les workers (éviter fuites mémoire)

Ajouter dans la crontab de l'utilisateur Nycaise :

```bash
crontab -e
```

Ajouter :
```
0 * * * * cd /home/Nycaise/web/api.doorguard.tangagroup.com/public_html && php artisan queue:restart > /dev/null 2>&1
```

### Logs rotation

```bash
sudo nano /etc/logrotate.d/doorguard
```

```
/var/log/doorguard-*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    missingok
    postrotate
        supervisorctl restart all > /dev/null 2>&1
    endscript
}
```

---

## ✅ Checklist finale de déploiement

- [ ] Base de données PostgreSQL créée (doorguard_db)
- [ ] Projet cloné dans `/home/Nycaise/web/api.doorguard.tangagroup.com/public_html`
- [ ] Fichier `.env` configuré avec les bonnes valeurs
- [ ] `composer install` exécuté
- [ ] `php artisan key:generate` exécuté
- [ ] Migrations exécutées (`php artisan migrate --force`)
- [ ] Caches générés (config, routes, views)
- [ ] Permissions correctes sur storage/ et bootstrap/cache/
- [ ] Configuration Hestia : racine = `public`
- [ ] SSL Let's Encrypt activé et fonctionnel
- [ ] Supervisor configuré et services démarrés (queue, reverb, mqtt)
- [ ] Apache proxy configuré pour WebSocket
- [ ] Tests MQTT réussis
- [ ] Queue worker traite les jobs
- [ ] Reverb broadcast fonctionne
- [ ] Frontend connecté au WebSocket (si applicable)

---

## 🎉 C'est terminé !

Votre API DoorGuard est maintenant déployée et fonctionnelle sur :
- **API** : https://api.doorguard.tangagroup.com
- **WebSocket** : wss://api.doorguard.tangagroup.com/ws

Les 3 processus daemon tournent en arrière-plan :
- ✅ MQTT Listener (écoute les messages MQTT)
- ✅ Queue Worker (traite les jobs de broadcast)
- ✅ Reverb (WebSocket server pour temps réel)

**Bon déploiement ! 🚀**
