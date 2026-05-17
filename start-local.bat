@echo off
cd /d "%~dp0"
if exist public\hot del public\hot
echo Iniciando Coworking em http://127.0.0.1:8000
cd public
php -c ../.local/php.ini -S 127.0.0.1:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
