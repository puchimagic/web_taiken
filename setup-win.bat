@echo off
setlocal

rem 体験授業の準備用bat。デスクトップに配置してダブルクリックすると、
rem 1) デスクトップにリポジトリをclone
rem 2) VSCodeにPHP Server拡張機能が無ければインストール
rem 3) スライド(pptx)をデスクトップにコピー
rem 4) VSCodeでプロジェクトフォルダを開く
rem 5) PHPサーバーを起動する
rem をまとめて行う。
rem
rem 接続が切れてサーバーだけ再起動したい場合は、cloneされたフォルダ内の
rem start-win.bat を直接実行すればよい（このbatの5番目の処理と同じ内容）。

set REPO_URL=https://github.com/puchimagic/web_taiken.git
set TARGET_DIR=%USERPROFILE%\Desktop\web_taiken
set EXTENSION_ID=brapifra.phpserver

echo ==== 1/5: リポジトリを取得します ====
where git >nul 2>nul
if errorlevel 1 (
    echo エラー: "git" コマンドが見つかりません。Gitをインストールし、PATHに追加してください。
    pause
    exit /b 1
)

if exist "%TARGET_DIR%" (
    echo フォルダ "%TARGET_DIR%" はすでに存在するため、cloneをスキップします。
) else (
    git clone "%REPO_URL%" "%TARGET_DIR%"
    if errorlevel 1 (
        echo エラー: git clone に失敗しました。
        pause
        exit /b 1
    )
)

echo.
echo ==== 2/5: VSCode拡張機能 "PHP Server" を確認します ====
where code >nul 2>nul
if errorlevel 1 (
    echo 警告: "code" コマンドが見つからないため、拡張機能の自動インストールをスキップします。
    echo VSCodeを開き、拡張機能タブから "PHP Server" ^(%EXTENSION_ID%^) を手動でインストールしてください。
) else (
    code --list-extensions | findstr /I /C:"%EXTENSION_ID%" >nul
    if errorlevel 1 (
        echo "PHP Server" 拡張機能をインストールします...
        code --install-extension %EXTENSION_ID%
    ) else (
        echo "PHP Server" 拡張機能は既にインストールされています。
    )
)

echo.
echo ==== 3/5: スライドをデスクトップにコピーします ====
set SLIDE_NAME=Webプログラミング体験_資料.pptx
if exist "%TARGET_DIR%\%SLIDE_NAME%" (
    copy /Y "%TARGET_DIR%\%SLIDE_NAME%" "%USERPROFILE%\Desktop\%SLIDE_NAME%" >nul
    echo デスクトップに "%SLIDE_NAME%" を配置しました。
) else (
    echo 警告: "%SLIDE_NAME%" が見つかりませんでした。スキップします。
)

echo.
echo ==== 4/5: VSCodeでプロジェクトを開きます ====
if exist "%TARGET_DIR%" (
    where code >nul 2>nul
    if errorlevel 1 (
        echo "code" コマンドが無いため自動で開けません。VSCodeで手動フォルダを開いてください:
        echo   %TARGET_DIR%
    ) else (
        code "%TARGET_DIR%"
    )
)

echo.
echo ==== 5/5: PHPサーバーを起動します ====
where php >nul 2>nul
if errorlevel 1 (
    echo エラー: "php" コマンドが見つかりません。PHPをインストールし、PATHに追加してください。
    pause
    exit /b 1
)

echo http://127.0.0.1:8000/login.php をブラウザで開いてください。
echo 停止するには Ctrl+C を押してください。
php -d upload_max_filesize=200M -d post_max_size=200M -S 127.0.0.1:8000 -t "%TARGET_DIR%\public"

pause
endlocal
