# 🚀 Guide de Déploiement DoorGuard - Production

## Prérequis

- PHP 8.2+
- PostgreSQL 14+
- Composer
- Supervisor ou Systemd
- Accès au broker MQTT (HiveMQ Cloud)

---

## 📋 Étapes de déploiement

### 1. Cloner et installer

```bash
cd /var/www
git clone <votre-repo> doorguard-back
cd doorguard-back
composer install --no-dev --optimize-autoloader
```

### 2. Configuration

```bash
cp .env.example .env
nano .env
```

**Variables importantes :**

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=doorguard_db
DB_USERNAME=doorguard_user
DB_PASSWORD=votre_mot_de_passe_securise

QUEUE_CONNECTION=database

BROADCAST_CONNECTION=reverb

MQTT_HOST=fd286f0fca334917b338f6f5882a2763.s1.eu.hivemq.cloud
MQTT_PORT=8883
MQTT_CLIENT_ID=doorguard-api-prod
MQTT_TLS_ENABLED=true
MQTT_AUTH_USERNAME=perseus911
MQTT_AUTH_PASSWORD=Wemtinga2026@

REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=https
```

### 3. Générer la clé d'application

```bash
php artisan key:generate
```

### 4. Migrations et permissions

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permissions
chown -R www-data:www-data /var/www/doorguard-back
chmod -R 755 /var/www/doorguard-back
chmod -R 775 /var/www/doorguard-back/storage
chmod -R 775 /var/www/doorguard-back/bootstrap/cache
```

---

## 🔧 Configuration des services (Supervisor)

### Installation de Supervisor

```bash
sudo apt update
sudo apt install supervisor
```

### Configuration

```bash
sudo nano /etc/supervisor/conf.d/doorguard.conf
```

Copiez le contenu du fichier `supervisor-doorguard.conf` fourni, puis :

```bash
# Remplacez /path/to/doorguard-back par le vrai chemin
sudo sed -i 's|/path/to/doorguard-back|/var/www/doorguard-back|g' /etc/supervisor/conf.d/doorguard.conf

# Recharger et démarrer
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

### Vérifier le statut

```bash
sudo supervisorctl status

# Vous devriez voir :
# doorguard-mqtt-listener          RUNNING   pid 1234, uptime 0:00:10
# doorguard-queue-worker:00        RUNNING   pid 1235, uptime 0:00:10
# doorguard-queue-worker:01        RUNNING   pid 1236, uptime 0:00:10
# doorguard-reverb                 RUNNING   pid 1237, uptime 0:00:10
```

### Logs

```bash
sudo tail -f /var/log/doorguard-mqtt.log
sudo tail -f /var/log/doorguard-queue.log
sudo tail -f /var/log/doorguard-reverb.log
```

---

## 🔧 Configuration des services (Systemd)

### Alternative à Supervisor

Si vous préférez systemd :

```bash
# Copier les fichiers service
sudo cp systemd/*.service /etc/systemd/system/

# Remplacer les chemins
sudo sed -i 's|/path/to/doorguard-back|/var/www/doorguard-back|g' /etc/systemd/system/doorguard-*.service

# Activer et démarrer
sudo systemctl daemon-reload
sudo systemctl enable doorguard-mqtt doorguard-queue doorguard-reverb
sudo systemctl start doorguard-mqtt doorguard-queue doorguard-reverb

# Vérifier le statut
sudo systemctl status doorguard-mqtt
sudo systemctl status doorguard-queue
sudo systemctl status doorguard-reverb
```

### Logs systemd

```bash
sudo journalctl -u doorguard-mqtt -f
sudo journalctl -u doorguard-queue -f
sudo journalctl -u doorguard-reverb -f
```

---

## 🌐 Configuration Nginx

```nginx
server {
    listen 80;
    server_name votre-domaine.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name votre-domaine.com;
    root /var/www/doorguard-back/public;

    ssl_certificate /etc/ssl/certs/votre-cert.crt;
    ssl_certificate_key /etc/ssl/private/votre-key.key;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

# WebSocket Reverb
upstream reverb {
    server 127.0.0.1:8080;
}

server {
    listen 443 ssl http2;
    server_name ws.votre-domaine.com;

    ssl_certificate /etc/ssl/certs/votre-cert.crt;
    ssl_certificate_key /etc/ssl/private/votre-key.key;

    location / {
        proxy_pass http://reverb;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

```bash
sudo nginx -t
sudo systemctl reload nginx
```

---

## ✅ Tests en production

### 1. Vérifier que tous les services tournent

```bash
# Supervisor
sudo supervisorctl status

# Ou systemd
sudo systemctl status doorguard-mqtt doorguard-queue doorguard-reverb
```

### 2. Tester la réception MQTT

Publiez un message test depuis MQTTX :

**Topic :** `doorguard/sensor/1/event`
**Payload :**
```json
{
  "action": "open",
  "timestamp": "2026-02-03T12:00:00Z"
}
```

### 3. Vérifier les logs

```bash
# MQTT Listener doit afficher
tail -f /var/log/doorguard-mqtt.log
# Message reçu sur [doorguard/sensor/1/event]: ...
# Événement créé: capteur #1 ...

# Queue Worker doit traiter
tail -f /var/log/doorguard-queue.log
# [timestamp] Processing: Illuminate\Broadcasting\BroadcastEvent
# [timestamp] Processed: Illuminate\Broadcasting\BroadcastEvent

# Reverb doit broadcaster
tail -f /var/log/doorguard-reverb.log
```

### 4. Vérifier la base de données

```bash
psql -U doorguard_user -d doorguard_db

SELECT COUNT(*) FROM jobs;  -- Devrait être 0 si tout est traité
SELECT COUNT(*) FROM sensor_events;  -- Devrait contenir vos événements
SELECT * FROM sensor_events ORDER BY detected_at DESC LIMIT 5;
```

---

## 🔄 Mise à jour du code

Lorsque vous mettez à jour le code :

```bash
cd /var/www/doorguard-back
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Redémarrer les services
sudo supervisorctl restart all

# Ou avec systemd
sudo systemctl restart doorguard-mqtt doorguard-queue doorguard-reverb
```

---

## 🐛 Dépannage

### Les jobs ne sont pas traités

```bash
# Vérifier que queue:work tourne
sudo supervisorctl status doorguard-queue-worker:*

# Vérifier les jobs en attente
php artisan queue:monitor database

# Vérifier les jobs échoués
php artisan queue:failed

# Réessayer les jobs échoués
php artisan queue:retry all
```

### MQTT ne reçoit pas les messages

```bash
# Vérifier les logs
tail -f /var/log/doorguard-mqtt.log

# Tester la connexion MQTT manuellement
php test-mqtt-debug.php

# Vérifier la config
php artisan config:show mqtt
```

### Reverb ne broadcast pas

```bash
# Vérifier que Reverb tourne
sudo supervisorctl status doorguard-reverb

# Vérifier les logs
tail -f /var/log/doorguard-reverb.log

# Redémarrer Reverb
sudo supervisorctl restart doorguard-reverb
```

### Vider la queue

```bash
# Purger tous les jobs en attente
php artisan queue:flush

# Supprimer les jobs échoués
php artisan queue:flush --failed
```

---

## 📊 Monitoring

### Surveiller l'utilisation mémoire

```bash
# Ajouter dans crontab
* * * * * cd /var/www/doorguard-back && php artisan queue:restart > /dev/null 2>&1
```

Cela redémarre gracieusement les workers toutes les heures pour éviter les fuites mémoire.

### Alertes

Configurez des alertes pour :
- Services qui s'arrêtent (Supervisor envoie des emails)
- Jobs qui échouent trop souvent
- Disque plein
- CPU/RAM élevée

---

## 🔐 Sécurité

### Pare-feu

```bash
# Autoriser uniquement les ports nécessaires
sudo ufw allow 22/tcp   # SSH
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS
sudo ufw enable
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

## 📱 Frontend (si applicable)

N'oubliez pas de mettre à jour les variables d'environnement du frontend :

```env
VITE_REVERB_APP_KEY=votre_reverb_key
VITE_REVERB_HOST=ws.votre-domaine.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

---

## ✅ Checklist finale

- [ ] `.env` configuré avec les bonnes valeurs
- [ ] Migrations exécutées
- [ ] Caches générés (config, routes, views)
- [ ] Permissions correctes sur storage/ et bootstrap/cache/
- [ ] Supervisor/Systemd configuré et services démarrés
- [ ] Nginx configuré avec SSL
- [ ] Tests MQTT réussis
- [ ] Queue worker traite les jobs
- [ ] Reverb broadcast fonctionne
- [ ] Logs rotation configurée
- [ ] Pare-feu configuré
- [ ] Frontend connecté au WebSocket

---

**Bon déploiement ! 🚀**
