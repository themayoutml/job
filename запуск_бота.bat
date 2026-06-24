@echo off
echo ========================================
echo     Telegram Bot for Job Site
echo ========================================
echo.

:: Try to find PHP in common OSPanel paths
set PHP_PATH=

:: Check standard OSPanel locations
if exist "D:\OSPanel\modules\php\PHP_8.1\php.exe" (
    set "PHP_PATH=D:\OSPanel\modules\php\PHP_8.1\php.exe"
) else if exist "C:\OSPanel\modules\php\PHP_8.2\php.exe" (
    set "PHP_PATH=C:\OSPanel\modules\php\PHP_8.2\php.exe"
) else if exist "C:\OSPanel\modules\php\PHP_8.1\php.exe" (
    set "PHP_PATH=C:\OSPanel\modules\php\PHP_8.1\php.exe"
) else if exist "C:\OSPanel\modules\php\PHP_8.0\php.exe" (
    set "PHP_PATH=C:\OSPanel\modules\php\PHP_8.0\php.exe"
) else if exist "D:\OSPanel\modules\php\PHP_8.2\php.exe" (
    set "PHP_PATH=D:\OSPanel\modules\php\PHP_8.2\php.exe"
) else if exist "D:\OSPanel\modules\php\PHP_8.0\php.exe" (
    set "PHP_PATH=D:\OSPanel\modules\php\PHP_8.0\php.exe"
)

if "%PHP_PATH%"=="" (
    echo ERROR: PHP not found! Please specify path manually.
    pause
    exit /b 1
)

echo Found PHP at: %PHP_PATH%
echo Using custom php.ini: bot_php.ini
echo Bot is running... Press Ctrl+C to stop
echo.

"%PHP_PATH%" -c "%~dp0bot_php.ini" telegram_bot.php

pause
