@echo off
setlocal enabledelayedexpansion

rem 体験授業終了後、各PCを元の状態に戻すためのクリーンアップ用bat。
rem Webプロセットアップ.bat と同じデスクトップに配置してダブルクリックする想定。
rem 1) ポート8000で起動中のPHPサーバーがあれば停止する
rem 2) デスクトップにcloneされた web_taiken フォルダを削除する
rem 3) デスクトップにコピーされたスライド(pptx)を削除する
rem 4) デスクトップに残るWebプロセットアップ.batのログファイルを削除する
rem
rem Webプロセットアップ.bat 自身とこのbat自身は削除しない。
rem 何度でも Webプロセットアップ.bat -> Webプロクリーン.bat を繰り返せる。
rem
rem 実行内容はすべて %LOG_FILE% にも記録される（トラブル調査用）。

rem OneDriveの「デスクトップと同期」が有効な環境では、実際にエクスプローラーで
rem 見えているデスクトップが %USERPROFILE%\Desktop ではなく OneDrive 配下に
rem 移動していることがある。Webプロセットアップ.bat と同じロジックで実際のデスクトップの
rem 場所を取得する（取得できない場合は %USERPROFILE%\Desktop にフォールバック）。
set "DESKTOP_DIR=%USERPROFILE%\Desktop"
for /f "usebackq tokens=2,*" %%A in (`reg query "HKCU\Software\Microsoft\Windows\CurrentVersion\Explorer\User Shell Folders" /v Desktop 2^>nul`) do set "DESKTOP_DIR=%%B"
call set "DESKTOP_DIR=%DESKTOP_DIR%"

set TARGET_DIR=%DESKTOP_DIR%\web_taiken
set SLIDE_NAME=Webプログラミング体験_資料.pptx
set SETUP_LOG=%DESKTOP_DIR%\web_taiken_setup_log.txt
set LOG_FILE=%DESKTOP_DIR%\web_taiken_cleanup_log.txt

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
call :log "==== 2/4: プロジェクトフォルダを削除します ===="
if exist "%TARGET_DIR%" (
    attrib -r "%TARGET_DIR%\*.*" /s /d >nul 2>nul
    rd /s /q "%TARGET_DIR%"
    if exist "%TARGET_DIR%" (
        call :log "警告: 「%TARGET_DIR%」の削除に失敗しました。VSCode等で開いたままになっていないか確認してください。"
    ) else (
        call :log "「%TARGET_DIR%」を削除しました。"
    )
) else (
    call :log "「%TARGET_DIR%」は見つかりませんでした。スキップします。"
)

call :log ""
call :log "==== 3/4: デスクトップのスライドを削除します ===="
if exist "%DESKTOP_DIR%\%SLIDE_NAME%" (
    del /f /q "%DESKTOP_DIR%\%SLIDE_NAME%"
    call :log "「%SLIDE_NAME%」を削除しました。"
) else (
    call :log "「%SLIDE_NAME%」は見つかりませんでした。スキップします。"
)

call :log ""
call :log "==== 4/4: セットアップ時のログを削除します ===="
if exist "%SETUP_LOG%" (
    del /f /q "%SETUP_LOG%"
    call :log "「%SETUP_LOG%」を削除しました。"
) else (
    call :log "「%SETUP_LOG%」は見つかりませんでした。スキップします。"
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
rem ログを保存した旨を表示してpauseし、指定した終了コードで終了する。
rem ---------------------------------------------------------
:pause_and_exit
echo.
echo ログを "%LOG_FILE%" に保存しました。
pause
endlocal & exit /b %EXIT_CODE%
