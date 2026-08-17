@echo off
setlocal enabledelayedexpansion

rem 体験授業終了後、各PCを元の状態に戻すためのクリーンアップ用bat。
rem プロジェクトフォルダ内に残しておき、デスクトップには自動配置しない。体験授業の
rem 最後にPCへの痕跡をゼロにしたい場合、VSCodeのエクスプローラーやターミナルから
rem 手動で実行する想定。
rem 1) ポート8000で起動中のPHPサーバーがあれば停止する
rem 2) VSCode(Code.exe)が起動していれば終了する
rem 3) ブラウザ(Microsoft Edge / Google Chrome)が起動していれば終了する
rem 4) プロジェクト一式（%APP_BASE%、VSCode専用プロファイル・実行ログ含む）を
rem    まとめて削除する
rem 5) 過去バージョンがデスクトップに残したファイルがあれば削除する
rem
rem Webプロセットアップ.bat 自身とこのbat自身は削除しない。この2つだけが
rem デスクトップに残る。何度でも Webプロセットアップ.bat -> Webプロクリーン.bat
rem を繰り返せる。
rem
rem 実行内容はすべて %LOG_FILE% にも記録される（トラブル調査用。画面表示を
rem pauseで確認させたあと、このログファイル自身も含めて最後にまとめて削除する）。

rem OneDriveの「デスクトップと同期」が有効な環境では、実際にエクスプローラーで
rem 見えているデスクトップが %USERPROFILE%\Desktop ではなく OneDrive 配下に
rem 移動していることがある。Webプロセットアップ.bat と同じロジックで実際の
rem デスクトップの場所を取得する（取得できない場合は %USERPROFILE%\Desktop に
rem フォールバック）。これは過去バージョンの残留ファイルを掃除するためだけに使う。
set "DESKTOP_DIR=%USERPROFILE%\Desktop"
for /f "usebackq tokens=2,*" %%A in (`reg query "HKCU\Software\Microsoft\Windows\CurrentVersion\Explorer\User Shell Folders" /v Desktop 2^>nul`) do set "DESKTOP_DIR=%%B"
call set "DESKTOP_DIR=%DESKTOP_DIR%"

rem プロジェクト本体・VSCode専用プロファイル・実行ログはすべて %LOCALAPPDATA%
rem （OneDriveと同期されない場所）にまとまっている。Webプロセットアップ.bat と
rem 同じ場所を指す。
set APP_BASE=%LOCALAPPDATA%\web_taiken
set LOG_FILE=%APP_BASE%\web_taiken_cleanup_log.txt

if not exist "%APP_BASE%" md "%APP_BASE%" >nul 2>nul
echo ==== Webプロクリーン.bat 開始 %DATE% %TIME% ==== > "%LOG_FILE%"

call :log "==== 1/4: PHPサーバーを停止します ===="
set FOUND_PHP=0
for /f "tokens=5" %%P in ('netstat -ano ^| findstr /R /C:":8000 .*LISTENING"') do (
    set FOUND_PHP=1
    call :log "ポート8000で待受中のプロセス（PID %%P）を終了します。"
    taskkill /PID %%P /F >> "%LOG_FILE%" 2>&1
)
if "%FOUND_PHP%"=="0" (
    call :log "ポート8000で起動中のPHPサーバーは見つかりませんでした。"
)

call :log ""
call :log "==== 2/4: VSCodeとブラウザを終了します ===="
tasklist /FI "IMAGENAME eq Code.exe" 2>nul | find /I "Code.exe" >nul
if errorlevel 1 (
    call :log "VSCodeは起動していませんでした。"
) else (
    call :log "VSCode（Code.exe）を終了します。"
    taskkill /IM Code.exe /F >> "%LOG_FILE%" 2>&1
)
rem ブラウザは taskkill /F（強制終了）だとクラッシュ扱いになり、次回起動時に
rem 「ページを復元しますか？」という確認が出てしまう。/F無し（通常のウィンドウ
rem クローズ要求）にして、正常終了として閉じるようにする。
tasklist /FI "IMAGENAME eq msedge.exe" 2>nul | find /I "msedge.exe" >nul
if not errorlevel 1 (
    call :log "Microsoft Edge（msedge.exe）を終了します。"
    taskkill /IM msedge.exe >> "%LOG_FILE%" 2>&1
)
tasklist /FI "IMAGENAME eq chrome.exe" 2>nul | find /I "chrome.exe" >nul
if not errorlevel 1 (
    call :log "Google Chrome（chrome.exe）を終了します。"
    taskkill /IM chrome.exe >> "%LOG_FILE%" 2>&1
)

call :log ""
call :log "==== 3/4: プロジェクト一式を削除します ===="
call :log "「%APP_BASE%」（プロジェクト本体・VSCode専用プロファイル・実行ログ）を削除します。このログファイル自身もここに含まれます。"

call :log ""
call :log "==== 4/4: デスクトップの旧バージョン残留ファイルを削除します ===="
rem 注意: デスクトップの「web_taiken」フォルダ自体は削除しない。
rem セットアップスクリプトが実際にclone・生成するプロジェクト本体は常に
rem APP_BASE（%LOCALAPPDATA%\web_taiken\project）側であり、デスクトップの
rem 「web_taiken」という名前のフォルダは開発用リポジトリの作業コピーである
rem 可能性がある（誤って削除すると開発中の変更が失われる）。
set CLEANED=0
for %%F in (
    "Webプログラミング体験_資料.pptx"
    "Webプロサーバー起動.bat"
    "web_taiken_setup_log.txt"
    "web_taiken_cleanup_log.txt"
    "web_taiken_start_log.txt"
) do (
    if exist "%DESKTOP_DIR%\%%~F" (
        set CLEANED=1
        del /f /q "%DESKTOP_DIR%\%%~F"
        call :log "デスクトップの「%%~F」を削除しました。"
    )
)
if "%CLEANED%"=="0" (
    call :log "デスクトップに旧バージョンの残留ファイルは見つかりませんでした。"
)

call :log ""
call :log "クリーンアップが完了しました。Webプロセットアップ.bat とこのbat自身は削除していません。"

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
rem 画面表示をpauseで確認させたあと、プロジェクト一式
rem （このログファイルを含む）を削除してから終了する。
rem ---------------------------------------------------------
:pause_and_exit
echo.
pause
rd /s /q "%APP_BASE%" >nul 2>nul
endlocal & exit /b %EXIT_CODE%
