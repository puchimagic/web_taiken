@echo off
setlocal enabledelayedexpansion

rem 体験授業の準備用bat。デスクトップに配置してダブルクリックすると、
rem 1) 実プロジェクト一式（%APP_BASE%\project、OneDriveと同期されない場所）に
rem    既存のリポジトリがあれば削除してから、改めてclone
rem    （.gitフォルダ・READMEなど体験授業に不要な開発用ファイルは取得後に削除する）
rem 2) VSCodeにPHP Server・SQLite Viewer・Japanese Language Pack拡張機能が
rem    無ければインストール
rem 3) VSCodeでプロジェクト専用プロファイル（明るいテーマ・日本語UI）を
rem    使ってフォルダを開く（毎回まっさらな画面になる）
rem 4) PHPサーバーを起動する
rem をまとめて行う。
rem
rem スライド(pptx)やサーバー起動用bat(Webプロサーバー起動.bat)、クリーンアップ用
rem bat(Webプロクリーン.bat)はデスクトップにはコピーしない。プロジェクトフォルダの
rem 中にあるものをそのまま使う（生徒の入れ替わりはこのbatの再実行だけで完結し、
rem 既存のVSCode・PHPサーバーは自動で終了・削除されるため、クリーンアップ用batは
rem 体験授業の最後にPCへの痕跡をゼロにしたい場合にのみ手動で使う想定）。
rem これにより、このbatを実行したデスクトップに最終的に残るのは
rem Webプロセットアップ.bat 1つだけになる。
rem
rem 実行内容はすべて %LOG_FILE% にも記録される（トラブル時の調査用）。

set REPO_URL=https://github.com/puchimagic/web_taiken.git
set EXTENSION_ID=brapifra.phpserver
set SQLITE_EXTENSION_ID=qwtel.sqlite-viewer
set JA_LANGUAGE_PACK_ID=ms-ceintl.vscode-language-pack-ja

rem プロジェクト本体・VSCode専用プロファイル・実行ログはすべて %LOCALAPPDATA%
rem （OneDriveと同期されない場所）にまとめる。デスクトップ（OneDriveの
rem 「デスクトップと同期」対象）にプロジェクトを置くと、clone直後や削除直前に
rem OneDriveが同期のためファイルを開いてロックし、生成・削除のどちらも失敗
rem することがあったため。
set APP_BASE=%LOCALAPPDATA%\web_taiken
set TARGET_DIR=%APP_BASE%\project
set VSCODE_USER_DATA=%APP_BASE%\vscode_data
set VSCODE_EXTENSIONS=%APP_BASE%\vscode_extensions
set LOG_FILE=%APP_BASE%\web_taiken_setup_log.txt
if not exist "%APP_BASE%" md "%APP_BASE%" >nul 2>nul

echo ==== Webプロセットアップ.bat 開始 %DATE% %TIME% ==== > "%LOG_FILE%"

call :log "==== 1/4: リポジトリを取得します ===="
where git >nul 2>nul
if errorlevel 1 (
    call :log "エラー: 「git」コマンドが見つかりません。Gitをインストールし、PATHに追加してください。"
    set "EXIT_CODE=1"
    goto :pause_and_exit
)

rem ポート8000を使っているプロセスがあれば、TARGET_DIRの有無に関係なく
rem 常に停止しておく（このbat以外の理由でPHPサーバーが8000番を使っていると、
rem 後続のPHPサーバー起動が失敗するため）。
for /f "tokens=5" %%P in ('netstat -ano ^| findstr /R /C:":8000 .*LISTENING"') do (
    call :log "ポート8000で待受中のプロセス（PID %%P）を終了します。"
    taskkill /PID %%P /F >> "%LOG_FILE%" 2>&1
)

if exist "%TARGET_DIR%" (
    call :log "フォルダ「%TARGET_DIR%」が既に存在するため、削除してから改めて取得し直します。"

    rem 削除前にVSCodeも止めておく（ファイルが使用中だと削除に失敗するため）。
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

rem 体験授業ではgit操作やREADMEの閲覧は不要で、VSCode上に見えるとかえって
rem 紛らわしいため、clone直後に取り除いておく（.gitを消してもこのPCの手元の
rem 履歴が消えるだけで、GitHub側やこのリポジトリ自体には影響しない）。
if exist "%TARGET_DIR%\.git" (
    attrib -r "%TARGET_DIR%\.git\*.*" /s /d >nul 2>nul
    rd /s /q "%TARGET_DIR%\.git"
)
if exist "%TARGET_DIR%\README.md" (
    del /f /q "%TARGET_DIR%\README.md"
)

rem 上記以外にも、体験授業の実施に直接関係ない開発用ドキュメント・
rem スクリプト類・自己紹介画像（次回pptx作成用の講師写真）・パワポ用画像
rem （説明資料作成用のスクリーンショット、いずれも体験授業では不要）、
rem mac版のセットアップ・サーバー起動・クリーンアップ用sh（このPCでは使わない）は
rem 取得後にできるだけ削除しておく。残すのはスライド(pptx)、
rem public・src・db（import以外）・画像（アプリの動作に必要）、
rem Webプロサーバー起動.bat（サーバー再起動用）・Webプロクリーン.bat
rem （体験授業の最後にPCを片付けたいときに手動実行する用）のみ。
for %%F in (
    "CLAUDE.md"
    "体験内容.md"
    ".gitignore"
    "Webプロセットアップ.bat"
    "Webプロセットアップ.sh"
    "Webプロサーバー起動.sh"
    "Webプロクリーン.sh"
) do (
    if exist "%TARGET_DIR%\%%~F" del /f /q "%TARGET_DIR%\%%~F"
)
if exist "%TARGET_DIR%\scripts" rd /s /q "%TARGET_DIR%\scripts"
if exist "%TARGET_DIR%\自己紹介画像" rd /s /q "%TARGET_DIR%\自己紹介画像"
if exist "%TARGET_DIR%\パワポ用画像" rd /s /q "%TARGET_DIR%\パワポ用画像"
rem db\import は郵便番号CSV（utf_ken_all.csv）以外は開発用ファイルなので、
rem CSVだけ db\ 直下へ退避してから import フォルダごと削除する。
rem CSVは初回PHPアクセス時（PHPサーバー起動後）にsrc/db.phpが自動で
rem postal_codesテーブルへ取り込むため、db\ 直下に残しておく必要がある。
if exist "%TARGET_DIR%\db\import\utf_ken_all.csv" (
    move /y "%TARGET_DIR%\db\import\utf_ken_all.csv" "%TARGET_DIR%\db\utf_ken_all.csv" >nul
)
if exist "%TARGET_DIR%\db\import" rd /s /q "%TARGET_DIR%\db\import"

call :log ""
call :log "==== 2/4: VSCode拡張機能を確認します ===="
where code >nul 2>nul
if errorlevel 1 (
    call :log "エラー: 「code」コマンドが見つかりません。VSCodeがインストールされているか確認してください。"
    set "EXIT_CODE=1"
    goto :pause_and_exit
)

call code --extensions-dir "%VSCODE_EXTENSIONS%" --list-extensions | findstr /I /C:"%EXTENSION_ID%" >nul
if errorlevel 1 (
    call :log "「PHP Server」拡張機能をインストールします..."
    call code --extensions-dir "%VSCODE_EXTENSIONS%" --install-extension %EXTENSION_ID% >> "%LOG_FILE%" 2>&1
) else (
    call :log "「PHP Server」拡張機能は既にインストールされています。"
)

call code --extensions-dir "%VSCODE_EXTENSIONS%" --list-extensions | findstr /I /C:"%SQLITE_EXTENSION_ID%" >nul
if errorlevel 1 (
    call :log "「SQLite Viewer」拡張機能をインストールします..."
    call code --extensions-dir "%VSCODE_EXTENSIONS%" --install-extension %SQLITE_EXTENSION_ID% >> "%LOG_FILE%" 2>&1
) else (
    call :log "「SQLite Viewer」拡張機能は既にインストールされています。"
)

call code --extensions-dir "%VSCODE_EXTENSIONS%" --list-extensions | findstr /I /C:"%JA_LANGUAGE_PACK_ID%" >nul
if errorlevel 1 (
    call :log "「Japanese Language Pack」拡張機能をインストールします..."
    call code --extensions-dir "%VSCODE_EXTENSIONS%" --install-extension %JA_LANGUAGE_PACK_ID% >> "%LOG_FILE%" 2>&1
) else (
    call :log "「Japanese Language Pack」拡張機能は既にインストールされています。"
)

call :log ""
call :log "==== 3/4: VSCodeでプロジェクトを開きます ===="
rem 普段使っているVSCodeのプロファイル（%APPDATA%\Code）をそのまま使うと、
rem 前回このPCで開いていたときのサイドバー開閉状態などのレイアウト記憶や、
rem この端末に個人的に入れている拡張機能がそのまま引き継がれてしまう。
rem このプロジェクト専用のuser-data-dirを使い、開く前に毎回削除してから
rem 作り直すことで、常にVSCode本来の初期状態（プライマリサイドバーで
rem エクスプローラーにフォーカス、セカンダリサイドバーは閉じた状態、余計な
rem 拡張機能のパネルも出ない）で開かれるようにする。
if exist "%VSCODE_USER_DATA%" rd /s /q "%VSCODE_USER_DATA%"
md "%VSCODE_USER_DATA%\User" >nul 2>nul
> "%VSCODE_USER_DATA%\User\settings.json" (
    echo {
    echo   "workbench.startupEditor": "none",
    echo   "workbench.welcomePage.walkthroughs.openOnInstall": false,
    echo   "workbench.secondarySideBar.defaultVisibility": "hidden",
    echo   "chat.commandCenter.enabled": false,
    echo   "workbench.colorTheme": "Light 2026"
    echo }
)

rem argv.json の locale 設定でVSCode本体のUI表示言語を日本語にする
rem （settings.jsonのdisplay.languageではなくargv.jsonのlocale項目が必要）。
> "%VSCODE_USER_DATA%\argv.json" (
    echo {
    echo   "locale": "ja"
    echo }
)

rem 言語パックは、専用user-data-dirでのVSCode「初回起動」だけではUIに反映
rem されず、一度閉じて開き直した2回目の起動から反映されるという既知の挙動が
rem ある（VSCode本体側の未解決issue）。そのため、まず1回目を起動して
rem ウィンドウが立ち上がるまで待ってから閉じ、日本語化を確定させたうえで
rem 改めて2回目を起動し直す。
call :log "日本語UIを反映させるため、VSCodeを一度初期化します（1回目の起動）..."
call code --disable-workspace-trust --new-window --locale ja --user-data-dir "%VSCODE_USER_DATA%" --extensions-dir "%VSCODE_EXTENSIONS%" "%TARGET_DIR%" >> "%LOG_FILE%" 2>&1

rem ウィンドウ（Code.exeプロセス）が実際に立ち上がるまで待つ（最大15秒）。
set WAIT_COUNT=0
:wait_for_code_launch
tasklist /FI "IMAGENAME eq Code.exe" 2>nul | find /I "Code.exe" >nul
if not errorlevel 1 goto wait_for_code_launch_done
set /a WAIT_COUNT+=1
if %WAIT_COUNT% GEQ 15 goto wait_for_code_launch_done
timeout /t 1 /nobreak >nul
goto wait_for_code_launch
:wait_for_code_launch_done
timeout /t 1 /nobreak >nul

call :log "VSCodeを一旦終了します（日本語化を確定させるため）..."
taskkill /IM Code.exe /F >> "%LOG_FILE%" 2>&1
timeout /t 1 /nobreak >nul

call :log "VSCodeを改めて起動します（2回目の起動）..."
call code --disable-workspace-trust --new-window --locale ja --user-data-dir "%VSCODE_USER_DATA%" --extensions-dir "%VSCODE_EXTENSIONS%" "%TARGET_DIR%"

call :log ""
call :log "==== 4/4: PHPサーバーを起動します ===="
call :find_php
if errorlevel 1 (
    call :log "エラー: 「php」コマンドが見つかりません。PHPをインストールしてください。"
    set "EXIT_CODE=1"
    goto :pause_and_exit
)

call :log "停止するには Ctrl+C を押してください。"
echo PHPサーバーを起動しました >> "%LOG_FILE%"

rem Google Chromeでホームページ（index.php）を自動で開く。インストール先が
rem PATHに無いことが多いため、よくあるインストール先を順に探す。
rem 見つからない場合はエラーにせず、手動でアクセスするよう案内するだけにする。
set CHROME_EXE=
if exist "%ProgramFiles%\Google\Chrome\Application\chrome.exe" set "CHROME_EXE=%ProgramFiles%\Google\Chrome\Application\chrome.exe"
if not defined CHROME_EXE if exist "%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe" set "CHROME_EXE=%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe"
if not defined CHROME_EXE if exist "%LOCALAPPDATA%\Google\Chrome\Application\chrome.exe" set "CHROME_EXE=%LOCALAPPDATA%\Google\Chrome\Application\chrome.exe"
if defined CHROME_EXE (
    call :log "Google Chromeで http://127.0.0.1:8000/index.php を開きます。"
    start "" "%CHROME_EXE%" --start-maximized "http://127.0.0.1:8000/index.php"
) else (
    call :log "警告: Google Chromeが見つかりませんでした。ブラウザで http://127.0.0.1:8000/index.php を開いてください。"
)

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
