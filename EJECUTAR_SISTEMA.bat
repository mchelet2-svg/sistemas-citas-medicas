@echo off
title Sistema Medico Portable - Alex Avila
cd /d "%~dp0"
set "PHP_BIN=%~dp0php\php.exe"
set "PHP_INI=%~dp0php\php.ini"

echo ---------------------------------------------------
echo INICIANDO SISTEMA DE CITAS MEDICAS (PHP + SQLITE)
echo Usuario admin: admin / 1234
echo Usuario paciente: paciente / 1234
echo ---------------------------------------------------

:: Inicia el servidor con el driver SQLite (-c) y el router de seguridad (router.php)
start http://127.0.0.1:8080
"%PHP_BIN%" -c "%PHP_INI%" -S 127.0.0.1:8080 router.php
pause
