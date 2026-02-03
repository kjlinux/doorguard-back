# Script PowerShell pour tester l'envoi de messages MQTT
# Utilise mosquitto_pub (doit être installé)

param(
    [string]$CardId = "ABC123456",
    [string]$SensorId = "1",
    [string]$Action = "open"
)

$MQTT_HOST = "fd286f0fca334917b338f6f5882a2763.s1.eu.hivemq.cloud"
$MQTT_PORT = 8883
$MQTT_USERNAME = "perseus911"
$MQTT_PASSWORD = "Wemtinga2026@"
$TOPIC = "doorguard/sensor/$SensorId/event"

# Créer le message JSON avec timestamp ISO8601
$timestamp = Get-Date -Format "yyyy-MM-ddTHH:mm:ssZ"
$message = @{
    card_id = $CardId
    action = $Action
    timestamp = $timestamp
} | ConvertTo-Json -Compress

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Test MQTT - DoorGuard" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Host:     $MQTT_HOST" -ForegroundColor Yellow
Write-Host "Port:     $MQTT_PORT (TLS)" -ForegroundColor Yellow
Write-Host "Topic:    $TOPIC" -ForegroundColor Yellow
Write-Host "Message:  $message" -ForegroundColor Yellow
Write-Host ""

# Vérifier si mosquitto_pub est installé
$mosquittoPub = Get-Command mosquitto_pub -ErrorAction SilentlyContinue

if ($null -eq $mosquittoPub) {
    Write-Host "ERREUR: mosquitto_pub n'est pas installé!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Pour installer mosquitto sur Windows:" -ForegroundColor Yellow
    Write-Host "1. Téléchargez depuis: https://mosquitto.org/download/" -ForegroundColor White
    Write-Host "2. Ou utilisez: winget install mosquitto" -ForegroundColor White
    Write-Host ""
    Write-Host "Alternative: Utilisez MQTT Explorer (interface graphique)" -ForegroundColor Yellow
    Write-Host "Téléchargez depuis: http://mqtt-explorer.com/" -ForegroundColor White
    exit 1
}

Write-Host "Envoi du message..." -ForegroundColor Green

# Envoyer le message MQTT
& mosquitto_pub `
    -h $MQTT_HOST `
    -p $MQTT_PORT `
    -t $TOPIC `
    -m $message `
    -u $MQTT_USERNAME `
    -P $MQTT_PASSWORD `
    --capath "C:\Program Files\mosquitto\certs" `
    --insecure

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "✅ Message envoyé avec succès!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Vérifiez:" -ForegroundColor Cyan
    Write-Host "  1. Les logs du listener MQTT (php artisan mqtt:listen)" -ForegroundColor White
    Write-Host "  2. La base de données (table door_events)" -ForegroundColor White
    Write-Host "  3. Le frontend (doit afficher l'événement en temps réel)" -ForegroundColor White
} else {
    Write-Host ""
    Write-Host "❌ Erreur lors de l'envoi du message!" -ForegroundColor Red
    Write-Host "Code de sortie: $LASTEXITCODE" -ForegroundColor Red
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
