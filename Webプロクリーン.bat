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
rem
rem このbat自身は %APP_BASE%\project（プロジェクトフォルダ）の中にあるため、
rem 4)の削除によりこのbat自身も含めて丸ごと消える。デスクトップに配置している
rem Webプロセットアップ.bat だけはこの削除対象に含まれないので、実行後デスクトップに
rem 最終的に残るのは Webプロセットアップ.bat のみになる。何度でも
rem Webプロセットアップ.bat -> Webプロクリーン.bat を繰り返せる（次にWebプロセットアップ.bat
rem を実行すればプロジェクトフォルダとこのbatも新しく作り直される）。
rem
rem 実行内容はすべて %LOG_FILE% にも記録される（トラブル調査用。画面表示を
rem pauseで確認させたあと、このログファイル自身も含めて最後にまとめて削除する）。

rem プロジェクト本体・VSCode専用プロファイル・実行ログはすべて %LOCALAPPDATA%
rem （OneDriveと同期されない場所）にまとまっている。Webプロセットアップ.bat と
rem 同じ場所を指す。
set APP_BASE=%LOCALAPPDATA%\web_taiken
set LOG_FILE=%APP_BASE%\web_taiken_cleanup_log.txt

if not exist "%APP_BASE%" md "%APP_BASE%" >nul 2>nul
echo ==== Webプロクリーン.bat 開始 %DATE% %TIME% ==== > "%LOG_FILE%"

call :log "==== 1/3: PHPサーバーを停止します ===="
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
call :log "==== 2/3: VSCodeとブラウザを終了します ===="
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
call :log "==== 3/3: プロジェクト一式を削除します ===="
call :log "「%APP_BASE%」（プロジェクト本体・VSCode専用プロファイル・実行ログ）を削除します。このログファイル自身もここに含まれます。"

call :log ""
call :log "クリーンアップが完了しました。何かキーを押すと、このbat自身を含むプロジェクトフォルダ一式を削除します（デスクトップのWebプロセットアップ.batは対象外です）。"

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
rem （このログファイル・このbat自身を含む）を削除してから終了する。
rem ---------------------------------------------------------
:pause_and_exit
echo.
pause
rd /s /q "%APP_BASE%" >nul 2>nul
endlocal & exit /b %EXIT_CODE%
