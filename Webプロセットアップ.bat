@echo off
setlocal enabledelayedexpansion

rem 体験授業の準備用bat。デスクトップに配置してダブルクリックすると、
rem 1) デスクトップに既存のリポジトリがあれば削除してから、改めてclone
rem 2) VSCodeにPHP Server拡張機能が無ければインストール
rem 3) スライド(pptx)をデスクトップにコピー
rem 4) VSCodeでプロジェクトフォルダを開く（前回のサイドバー等のレイアウト状態はリセットする）
rem 5) PHPサーバーを起動する
rem をまとめて行う。
rem
rem 接続が切れてサーバーだけ再起動したい場合は、cloneされたフォルダ内の
rem Webプロサーバー起動.bat を直接実行すればよい（このbatの5番目の処理と同じ内容）。
rem
rem 実行内容はすべて %LOG_FILE% にも記録される（トラブル時の調査用）。

set REPO_URL=https://github.com/puchimagic/web_taiken.git
set EXTENSION_ID=brapifra.phpserver

rem OneDriveの「デスクトップと同期」が有効な環境では、実際にエクスプローラーで
rem 見えているデスクトップが %USERPROFILE%\Desktop ではなく OneDrive 配下に
rem 移動していることがある。%USERPROFILE%\Desktop 決め打ちだとユーザーから
rem 見えない場所にファイルが作られてしまうため、レジストリから実際のデスクトップの
rem 場所を取得する（取得できない場合は %USERPROFILE%\Desktop にフォールバック）。
set "DESKTOP_DIR=%USERPROFILE%\Desktop"
for /f "usebackq tokens=2,*" %%A in (`reg query "HKCU\Software\Microsoft\Windows\CurrentVersion\Explorer\User Shell Folders" /v Desktop 2^>nul`) do set "DESKTOP_DIR=%%B"
call set "DESKTOP_DIR=%DESKTOP_DIR%"

set TARGET_DIR=%DESKTOP_DIR%\web_taiken
set LOG_FILE=%DESKTOP_DIR%\web_taiken_setup_log.txt

rem 過去バージョンが残した旧ログ（現在は使用しない）があれば削除しておく。
del /f /q "%DESKTOP_DIR%\web_taiken_cleanup_log.txt" >nul 2>nul

echo ==== Webプロセットアップ.bat 開始 %DATE% %TIME% ==== > "%LOG_FILE%"

call :log "==== 1/5: リポジトリを取得します ===="
where git >nul 2>nul
if errorlevel 1 (
    call :log "エラー: 「git」コマンドが見つかりません。Gitをインストールし、PATHに追加してください。"
    set "EXIT_CODE=1"
    goto :pause_and_exit
)

if exist "%TARGET_DIR%" (
    call :log "フォルダ「%TARGET_DIR%」が既に存在するため、削除してから改めて取得し直します。"

    rem 削除前にPHPサーバーとVSCodeを止めておく（ファイルが使用中だと削除に失敗するため）。
    for /f "tokens=5" %%P in ('netstat -ano ^| findstr /R /C:":8000 .*LISTENING"') do (
        call :log "ポート8000で待受中のプロセス（PID %%P）を終了します。"
        taskkill /PID %%P /F >> "%LOG_FILE%" 2>&1
    )
    tasklist /FI "IMAGENAME eq Code.exe" 2>nul | find /I "Code.exe" >nul
    if not errorlevel 1 (
        call :log "VSCode（Code.exe）を終了します。"
        taskkill /IM Code.exe /F >> "%LOG_FILE%" 2>&1
    )

    attrib -r "%TARGET_DIR%\*.*" /s /d >nul 2>nul
    rd /s /q "%TARGET_DIR%"
    if exist "%TARGET_DIR%" (
        call :log "エラー: 「%TARGET_DIR%」の削除に失敗しました。VSCode等で開いたままになっていないか確認してください。"
        set "EXIT_CODE=1"
        goto :pause_and_exit
    )
)

call :log "git clone %REPO_URL% %TARGET_DIR%"
git clone "%REPO_URL%" "%TARGET_DIR%" >> "%LOG_FILE%" 2>&1
if errorlevel 1 (
    call :log "エラー: git clone に失敗しました。詳細は %LOG_FILE% を確認してください。"
    set "EXIT_CODE=1"
    goto :pause_and_exit
)

call :log ""
call :log "==== 2/5: VSCode拡張機能「PHP Server」を確認します ===="
where code >nul 2>nul
if errorlevel 1 (
    call :log "エラー: 「code」コマンドが見つかりません。VSCodeがインストールされているか確認してください。"
    set "EXIT_CODE=1"
    goto :pause_and_exit
)

call code --list-extensions | findstr /I /C:"%EXTENSION_ID%" >nul
if errorlevel 1 (
    call :log "「PHP Server」拡張機能をインストールします..."
    call code --install-extension %EXTENSION_ID% >> "%LOG_FILE%" 2>&1
) else (
    call :log "「PHP Server」拡張機能は既にインストールされています。"
)

call :log ""
call :log "==== 3/5: スライドをデスクトップにコピーします ===="
set SLIDE_NAME=Webプログラミング体験_資料.pptx
if exist "%TARGET_DIR%\%SLIDE_NAME%" (
    copy /Y "%TARGET_DIR%\%SLIDE_NAME%" "%DESKTOP_DIR%\%SLIDE_NAME%" >nul
    call :log "デスクトップに「%SLIDE_NAME%」を配置しました。"
) else (
    call :log "警告: 「%SLIDE_NAME%」が見つかりませんでした。スキップします。"
)

call :log ""
call :log "==== 4/5: VSCodeでプロジェクトを開きます ===="
rem 前回このフォルダを開いた際のVSCodeのレイアウト状態（サイドバーの開閉状態
rem など）が残っていると、意図しない画面で開いてしまうことがある。VSCodeの
rem workspaceStorageから該当ワークスペースの保存状態を削除し、常に既定のレイ
rem アウト（プライマリサイドバーでエクスプローラーにフォーカス、セカンダリサイド
rem バーは閉じた状態）で開かれるようにする。
set "VSCODE_STORAGE=%APPDATA%\Code\User\workspaceStorage"
if exist "%VSCODE_STORAGE%" (
    for /f "delims=" %%D in ('dir /b "%VSCODE_STORAGE%" 2^>nul') do (
        if exist "%VSCODE_STORAGE%\%%D\workspace.json" (
            findstr /I /C:"web_taiken" "%VSCODE_STORAGE%\%%D\workspace.json" >nul
            if not errorlevel 1 (
                call :log "VSCodeの保存済みレイアウト状態を初期化します。"
                rd /s /q "%VSCODE_STORAGE%\%%D"
            )
        )
    )
)

call code --disable-workspace-trust "%TARGET_DIR%"

call :log ""
call :log "==== 5/5: PHPサーバーを起動します ===="
call :find_php
if errorlevel 1 (
    call :log "エラー: 「php」コマンドが見つかりません。PHPをインストールしてください。"
    set "EXIT_CODE=1"
    goto :pause_and_exit
)

call :log "http://127.0.0.1:8000/login.php をブラウザで開いてください。"
call :log "停止するには Ctrl+C を押してください。"
echo PHPサーバーを起動しました >> "%LOG_FILE%"
php -d upload_max_filesize=200M -d post_max_size=200M -S 127.0.0.1:8000 -t "%TARGET_DIR%\public"

set "EXIT_CODE=0"
goto :pause_and_exit

rem ---------------------------------------------------------
rem 画面表示とログファイルへの記録を同時に行うサブルーチン。
rem 引数はダブルクォートで囲んで渡す（空行を出したい場合は ""）。
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
rem PHPコマンドの検出。PATHになければよくあるインストール先を
rem 探し、見つかればこのbat内のPATHと「ユーザー環境変数PATH」
rem （システムPATHは触らない）の両方に追加する。
rem 次回以降はwhere phpで直接見つかるようになる。
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

call :log "PHPが見つかりませんでした。C:\php や C:\xampp\php などに配置してください。"
exit /b 1
