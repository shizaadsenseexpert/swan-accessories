@echo off
cd /d "%~dp0"
start "" http://127.0.0.1:8099/
"_local-server\php.exe" -c "_local-server\php.ini" -S 127.0.0.1:8099 router.php
