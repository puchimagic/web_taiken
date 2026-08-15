@echo off
setlocal enabledelayedexpansion

rem 接続が切れた等でPHPサーバーだけ再起動したいときに使うbat。
rem Webプロセットアップ.bat の「5/5: PHPサーバーを起動する」と同じ処理を、
rem デスクトップに置いたこのファイル単体から実行できるようにしたもの。
rem 実行内容はすべて %LOG_FILE% にも記録される（トラブルの調査用）。

rem OneDriveの「デスクトップと同期」が有効な環境では、実際にエクスプローラーで
rem 見えているデスクトップが %USERPROFILE%\Desktop ではなく OneDrive 配下に
rem 移動していることがある。Webプロセットアップ.bat と同じロジックで実際の
rem デスクトップの場所を取得する（取得できない場合は %USERPROFILE%\Desktop に
rem フォールバック）。
set "DESKTOP_DIR=%USERPROFILE%\Desktop"
for /f "usebackq tokens=2,*" %%A in (`reg query "HKCU\Software\Microsoft\Windows\CurrentVersion\Explorer\User Shell Folders" /v Desktop 2^>nul`) do set "DESKTOP_DIR=%%B"
call set "DESKTOP_DIR=%DESKTOP_DIR%"

set HOST=127.0.0.1
set PORT=8000
set DOCROOT=%DESKTOP_DIR%\web_taiken\public
set LOG_FILE=%DESKTOP_DIR%\web_taiken_start_log.txt

echo ==== Webプロサーバー起動.bat 開始 %DATE% %TIME% ==== > "%LOG_FILE%"

if not exist "%DOCROOT%" (
    call :log "エラー: 「%DOCROOT%」が見つかりません。先に「Webプロセットアップ.bat」を実行してください。"
    set "EXIT_CODE=1"
    goto :pause_and_exit
)

call :find_php
if errorlevel 1 (
    call :log "エラー: 「php」コマンドが見つかりません。PHPをインストールしてください。"
    set "EXIT_CODE=1"
    goto :pause_and_exit
)

call :log "PHP組み込みサーバーを起動します: http://%HOST%:%PORT%/index.php"
call :log "停止するには Ctrl+C を押してください。"
echo PHPサーバーを起動しました >> "%LOG_FILE%"

php -d upload_max_filesize=200M -d post_max_size=200M -S %HOST%:%PORT% -t "%DOCROOT%"

set "EXIT_CODE=0"
goto :pause_and_exit

rem ---------------------------------------------------------
rem 画面表示とログファイルへの記録を同時に行うサブルーチン。
rem 引数は必ずダブルクォートで囲んで渡す（空行を出したい場合は ""）。
rem ---------------------------------------------------------
:log
echo(%~1
echo(%~1 >> "%LOG_FILE%"
exit /b 0

rem ---------------------------------------------------------
rem ログを保存した旨を表示してpauseし、指定した終了コードで終了する。
rem ---------------------------------------------------------
:pause_and_exit
echo.
echo ログを "%LOG_FILE%" に保存しました。
pause
endlocal & exit /b %EXIT_CODE%

rem ---------------------------------------------------------
rem PHPコマンドの検出。PATHになければよくあるインストール先
rem を探し、見つかればそのbatのPATHと「ユーザー環境変数PATH」
rem （システムPATHは触らない）の両方に追加する。
rem これ以降はwhere phpで直接検出できるようになる。
rem ---------------------------------------------------------
:find_php
where php >nul 2>nul
if not errorlevel 1 exit /b 0

call :log "「php」コマンドが見つからないため、インストール先を探します..."

set PHP_CANDIDATES=C:\php;C:\xampp\php;%LOCALAPPDATA%\Programs\php

for %%D in (%PHP_CANDIDATES%) do (
    if exist "%%D\php.exe" (
        call :log "PHPを発見しました: %%D"
        set "PATH=%%D;%PATH%"

        rem ユーザー環境変数PATHのみを読み取って追記する（システムPATHには触らない）
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

call :log "PHPが見つかりませんでした。C:\php や C:\xampp\php などに配置してください。"
exit /b 1
