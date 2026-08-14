@echo off
setlocal

set EXTENSION_ID=brapifra.phpserver

echo VSCode拡張機能 "PHP Server" (%EXTENSION_ID%) の確認を行います...

where code >nul 2>nul
if errorlevel 1 (
    echo エラー: "code" コマンドが見つかりません。
    echo VSCodeのコマンドパレットから「Shell Command: Install 'code' command in PATH」を実行してから、
    echo 再度このバッチファイルを実行してください。
    pause
    exit /b 1
)

code --list-extensions | findstr /I /C:"%EXTENSION_ID%" >nul
if %errorlevel%==0 (
    echo "PHP Server" 拡張機能は既にインストールされています。
) else (
    echo "PHP Server" 拡張機能が見つかりません。インストールします...
    code --install-extension %EXTENSION_ID%
    if errorlevel 1 (
        echo インストールに失敗しました。ネットワーク接続やVSCodeの設定を確認してください。
        pause
        exit /b 1
    )
    echo インストールが完了しました。
)

pause
endlocal
