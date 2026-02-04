#!/bin/bash
# ============================================================
# DoorGuard Backend - Script de deploiement VPS (Hestia Panel)
# Usage: ssh user@vps "bash /path/to/deploy.sh"
# ============================================================

set -e

APP_DIR="/home/Nycaise/web/api.doorguard.tangagroup.com/public_html"
PHP_BIN="php"

echo "========================================"
echo " DoorGuard Backend - Deploiement"
echo "========================================"

cd "$APP_DIR"

# 1. Pull des derniers changements
echo ""
echo "[1/6] Git pull..."
git pull origin main

# 2. Installer les dependances (sans dev)
echo ""
echo "[2/6] Composer install..."
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Migrations
echo ""
echo "[3/6] Migrations..."
$PHP_BIN artisan migrate --force

# 4. Cache des configs
echo ""
echo "[4/6] Cache config/routes/views..."
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache

# 5. Redemarrer les workers supervisor
echo ""
echo "[5/6] Restart des services supervisor..."
sudo supervisorctl restart doorguard-reverb
sudo supervisorctl restart doorguard-mqtt-listener
sudo supervisorctl restart doorguard-queue-worker:*

# 6. Verification
echo ""
echo "[6/6] Verification des services..."
sleep 2
sudo supervisorctl status | grep doorguard

echo ""
echo "========================================"
echo " Deploiement backend termine !"
echo "========================================"
