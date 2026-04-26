@echo off
echo Limpiando cache de Laravel...
php artisan view:clear
php artisan cache:clear
php artisan config:clear
echo Cache limpiado!
pause
