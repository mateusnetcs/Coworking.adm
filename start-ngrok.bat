@echo off
cd /d "%~dp0"
if exist public\hot del public\hot
echo.
echo [1/2] Build do frontend (obrigatorio para o tunel ngrok)...
call npm.cmd run build
if errorlevel 1 exit /b 1
echo.
echo [2/2] Inicie o servidor em outro terminal: start-local.bat
echo       Depois rode: ngrok http 8000
echo.
echo URL publica (exemplo): https://treacly-shana-iatrochemically.ngrok-free.dev
echo NAO use "npm run dev" enquanto o tunel estiver ativo (cria public/hot e tela branca).
pause
