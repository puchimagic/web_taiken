@echo off
setlocal

set HOST=127.0.0.1
set PORT=8000
set DOCROOT=%~dp0public

where php >nul 2>nul
if errorlevel 1 (
    echo エラー: "php" コマンドが見つかりません。PHPをインストールし、PATHに追加してください。
    pause
    exit /b 1
)

echo PHP組み込みサーバーを起動します: http://%HOST%:%PORT%/index.php
echo 停止するには Ctrl+C を押してください。

php -S %HOST%:%PORT% -t "%DOCROOT%"

pause
endlocal
