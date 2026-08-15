@echo off
setlocal enabledelayedexpansion

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
    echo エラー: "code" コマンドが見つかりません。VSCodeがインストールされているか確認してください。
    pause
    exit /b 1
)

code --list-extensions | findstr /I /C:"%EXTENSION_ID%" >nul
if errorlevel 1 (
    echo "PHP Server" 拡張機能をインストールします...
    code --install-extension %EXTENSION_ID%
) else (
    echo "PHP Server" 拡張機能は既にインストールされています。
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
code "%TARGET_DIR%"

echo.
echo ==== 5/5: PHPサーバーを起動します ====
call :find_php
if errorlevel 1 (
    echo エラー: "php" コマンドが見つかりません。PHPをインストールしてください。
    pause
    exit /b 1
)

echo http://127.0.0.1:8000/login.php をブラウザで開いてください。
echo 停止するには Ctrl+C を押してください。
php -d upload_max_filesize=200M -d post_max_size=200M -S 127.0.0.1:8000 -t "%TARGET_DIR%\public"

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
