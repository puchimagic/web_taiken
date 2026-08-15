@echo off
setlocal enabledelayedexpansion

set HOST=127.0.0.1
set PORT=8000
set DOCROOT=%~dp0public

call :find_php
if errorlevel 1 (
    echo エラー: "php" コマンドが見つかりません。PHPをインストールしてください。
    pause
    exit /b 1
)

echo PHP組み込みサーバーを起動します: http://%HOST%:%PORT%/index.php
echo 停止するには Ctrl+C を押してください。

php -d upload_max_filesize=200M -d post_max_size=200M -S %HOST%:%PORT% -t "%DOCROOT%"

pause
endlocal
exit /b 0

rem ---------------------------------------------------------
rem PHPコマンドの検出。PATHになければよくあるインストール先を
rem 探し、見つかればこのbat内のPATHと「ユーザー環境変数PATH」
rem （システムPATHは触らない）の両方に追加する。
rem 次回以降はwhere phpで直接見つかるようになる。
rem ---------------------------------------------------------
:find_php
where php >nul 2>nul
if not errorlevel 1 exit /b 0

echo "php" コマンドが見つからないため、インストール先を探します...

set PHP_CANDIDATES=C:\php;C:\xampp\php;%LOCALAPPDATA%\Programs\php

for %%D in (%PHP_CANDIDATES%) do (
    if exist "%%D\php.exe" (
        echo PHPを発見しました: %%D
        set "PATH=%%D;%PATH%"

        rem ユーザー環境変数PATHのみを読み取って追記する（システムPATHには触れない）
        set "USER_PATH="
        for /f "usebackq tokens=2,*" %%A in (`reg query "HKCU\Environment" /v Path 2^>nul`) do set "USER_PATH=%%B"
        echo !USER_PATH! | findstr /I /C:"%%D" >nul
        if errorlevel 1 (
            if defined USER_PATH (
                setx PATH "%%D;!USER_PATH!" >nul
            ) else (
                setx PATH "%%D" >nul
            )
        )
        exit /b 0
    )
)

echo PHPが見つかりませんでした。C:\php や C:\xampp\php などに配置してください。
exit /b 1
