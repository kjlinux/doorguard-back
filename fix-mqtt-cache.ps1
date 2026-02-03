# PowerShell script to clear caches and fix MQTT configuration

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Fix MQTT Configuration Cache" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Clearing Laravel caches..." -ForegroundColor Yellow
& "C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe" artisan cache:clear
& "C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe" artisan config:clear
& "C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe" artisan route:clear

Write-Host ""
Write-Host "✅ Caches cleared successfully!" -ForegroundColor Green
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Restart your Laravel server:" -ForegroundColor White
Write-Host "   php artisan serve" -ForegroundColor Gray
Write-Host ""
Write-Host "2. Test the MQTT endpoint (requires authentication token):" -ForegroundColor White
Write-Host "   POST http://localhost:8000/api/mqtt/test" -ForegroundColor Gray
Write-Host "   Body: {\"topic\":\"doorguard/test\"}" -ForegroundColor Gray
Write-Host ""
Write-Host "The connection timeout has been increased to 10 seconds." -ForegroundColor Cyan
Write-Host ""
