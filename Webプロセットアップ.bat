@echo off
setlocal enabledelayedexpansion

rem 体験授業の準備用bat。デスクトップに配置してダブルクリックすると、
rem 1) 実プロジェクト一式（%APP_BASE%\project、OneDriveと同期されない場所）に
rem    既存のリポジトリがあれば削除してから、改めてclone
rem    （.gitフォルダ・READMEなど体験授業に不要な開発用ファイルは取得後に削除する）
rem 2) VSCodeにPHP Server・SQLite Viewer・Japanese Language Pack拡張機能が
rem    無ければインストール
rem 3) VSCodeでプロジェクト専用プロファイル（明るいテーマ・日本語UI）を
rem    使ってフォルダを開き、ウィンドウを最大化する（毎回まっさらな画面になる）
rem 4) PHPサーバーを起動し、Google Chromeをゲストモード（--guest）で開く
rem    （前の参加者の入力履歴・ログイン情報などを引き継がないようにするため。
rem    ゲストモードは他のプロファイルが起動中だと無視されることがあるため、
rem    起動前に既存のChromeを終了する）
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

rem VSCodeが既に起動していると、後で --user-data-dir / --extensions-dir を
rem 指定して code コマンドを実行しても、既存の実行中インスタンス（個人プロファイル
rem 等）が使い回されてしまい、専用プロファイルが反映されないことがある
rem （VSCode CLIの既知の挙動）。TARGET_DIRの有無に関係なく、
rem 毎回いったんVSCodeを終了させてから起動し直す。
tasklist /FI "IMAGENAME eq Code.exe" 2>nul | find /I "Code.exe" >nul
if not errorlevel 1 (
    call :log "VSCode（Code.exe）を終了します。"
    taskkill /IM Code.exe /F >> "%LOG_FILE%" 2>&1
    timeout /t 1 /nobreak >nul
)

if exist "%TARGET_DIR%" (
    call :log "フォルダ「%TARGET_DIR%」が既に存在するため、削除してから改めて取得し直します。"

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
rem （説明資料作成用のスクリーンショット、いずれも体験授業では不要）・
rem その他フォルダ（開発用ドキュメント類）・
rem mac版のセットアップ・サーバー起動・クリーンアップ用sh（このPCでは使わない）は
rem 取得後にできるだけ削除しておく。残すのはスライド(pptx)、
rem public・src・db（import以外）・画像（アプリの動作に必要）、
rem Webプロサーバー起動.bat（サーバー再起動用）・Webプロクリーン.bat
rem （体験授業の最後にPCを片付けたいときに手動実行する用）のみ。
for %%F in (
    "CLAUDE.md"
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
if exist "%TARGET_DIR%\その他" rd /s /q "%TARGET_DIR%\その他"
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
rem このプロジェクト専用のuser-data-dirを使い、開く前に毎回設定をリセット
rem することで、常にVSCode本来の初期状態（プライマリサイドバーで
rem エクスプローラーにフォーカス、セカンダリサイドバーは閉じた状態、余計な
rem 拡張機能のパネルも出ない）で開かれるようにする。
rem
rem ただし %VSCODE_USER_DATA% フォルダ自体をrd /s /qで削除してからmdで
rem 作り直すと、Windows版VSCodeでは（--extensions-dirで入れた）言語パックが
rem 正しく認識されずUIが英語のままになる不具合を実機で確認した
rem （user-data-dirフォルダ自体は残したまま中のUserサブフォルダだけを
rem 作り直す場合は問題なく日本語化されることを確認済み）。そのため、
rem トップレベルの%VSCODE_USER_DATA%フォルダ自体は削除せず、
rem 設定が入っているUserサブフォルダだけを削除・再作成する。
rem
rem %VSCODE_USER_DATA%フォルダがまだ一度も存在しない場合（このPCで初めて
rem 実行したとき、またはWebプロクリーン.batで痕跡を消したあとの初回実行）は
rem 上記と同じ理由でいきなり日本語UIにならない。そのため、まず拡張機能や
rem 言語設定なしでVSCodeを一度ダミー起動してすぐ終了させ、
rem %VSCODE_USER_DATA%フォルダの実体を作ってから、改めてUserサブフォルダを
rem 日本語設定で作り直す（実機で、この手順なら初回から日本語化することを
rem 確認済み）。
if not exist "%VSCODE_USER_DATA%" (
    call :log "初回起動のため、VSCodeを一度ダミー起動して初期化します..."
    call code --user-data-dir "%VSCODE_USER_DATA%" --extensions-dir "%VSCODE_EXTENSIONS%" >> "%LOG_FILE%" 2>&1
    timeout /t 5 /nobreak >nul
    taskkill /IM Code.exe /F >> "%LOG_FILE%" 2>&1
    timeout /t 2 /nobreak >nul
)
if exist "%VSCODE_USER_DATA%\User" rd /s /q "%VSCODE_USER_DATA%\User"
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

call :log "VSCodeを起動します..."
call code --disable-workspace-trust --new-window --locale ja --user-data-dir "%VSCODE_USER_DATA%" --extensions-dir "%VSCODE_EXTENSIONS%" "%TARGET_DIR%"

rem VSCodeにはウィンドウを最大化した状態で起動する公式フラグが無く、
rem settings.jsonのwindow.newWindowDimensions（"maximized"）も、保存済みの
rem ウィンドウ状態が無い専用プロファイルの最初のウィンドウには効かない
rem （VSCode本体の既知の仕様）。そのため、起動後にウィンドウが実際に
rem 表示されるまで少し待ってから、PowerShellでCode.exeのメインウィンドウを
rem 探して手動で最大化する（タイトルバー・タスクバーは残る通常の最大化。
rem F11のような真のフルスクリーンではない）。cmd.exeはバックスラッシュに
rem よる引用符エスケープをサポートしないため、複雑な引用符を含む
rem PowerShellコマンドは直接--Commandに埋め込まず、一旦.ps1ファイルに
rem 書き出してから--Fileで呼び出す（>>による1行ずつの追記で、cmd.exe側の
rem 引用符解釈に一切依存しない形にしている）。
type nul > "%APP_BASE%\maximize_vscode.ps1"
echo $sig = 'using System;using System.Runtime.InteropServices;public class NativeMax{[DllImport("user32.dll")]public static extern bool ShowWindowAsync(IntPtr hWnd, int nCmdShow);[DllImport("user32.dll")]public static extern bool SetForegroundWindow(IntPtr hWnd);}' >> "%APP_BASE%\maximize_vscode.ps1"
echo Add-Type -TypeDefinition $sig >> "%APP_BASE%\maximize_vscode.ps1"
echo Add-Type -AssemblyName System.Windows.Forms >> "%APP_BASE%\maximize_vscode.ps1"
echo $p = Get-Process -Name Code -ErrorAction SilentlyContinue ^| Where-Object { $_.MainWindowHandle -ne 0 } ^| Select-Object -First 1 >> "%APP_BASE%\maximize_vscode.ps1"
echo if ($p) { [NativeMax]::ShowWindowAsync($p.MainWindowHandle, 3) ^| Out-Null; Start-Sleep -Milliseconds 500; [NativeMax]::SetForegroundWindow($p.MainWindowHandle) ^| Out-Null; Start-Sleep -Milliseconds 300; [System.Windows.Forms.SendKeys]::SendWait('{ESC}') } >> "%APP_BASE%\maximize_vscode.ps1"
timeout /t 3 /nobreak >nul
powershell -NoProfile -ExecutionPolicy Bypass -File "%APP_BASE%\maximize_vscode.ps1" >> "%LOG_FILE%" 2>&1

rem Welcome/Get StartedのウォークスルータブやRelease Notesなどの初回ポップアップは
rem 表示タイミングがまちまちなため、上のEsc送信だけでは間に合わないことがある。
rem 少し間を空けてもう一度Escを送って取りこぼしを減らす。
timeout /t 2 /nobreak >nul
powershell -NoProfile -ExecutionPolicy Bypass -Command "Add-Type -AssemblyName System.Windows.Forms; [System.Windows.Forms.SendKeys]::SendWait('{ESC}')" >> "%LOG_FILE%" 2>&1

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
    call :log "PHPサーバーの起動を待ってからGoogle Chromeで http://127.0.0.1:8000/index.php を開きます。"

    rem PHPサーバー（このあと本体がフォアグラウンドで起動する）がポート8000で
    rem 実際に待受を始める前にChromeでアクセスすると「接続できません」が
    rem 一瞬表示されてしまう。curlで疎通確認しながら待ってからChromeを開く
    rem 処理を別の.batファイルに書き出し、完全に非表示のウィンドウで裏起動しておく
    rem （フォアグラウンド側のPHPサーバー起動・Ctrl+Cでの停止には影響しない）。
    rem cmd.exeはバックスラッシュによる引用符エスケープをサポートしないため、
    rem 複雑な引用符を含むコマンドは直接startに埋め込まず.batに書き出す。
    type nul > "%APP_BASE%\open_chrome_when_ready.bat"
    echo @echo off >> "%APP_BASE%\open_chrome_when_ready.bat"
    echo set RETRY=0 >> "%APP_BASE%\open_chrome_when_ready.bat"
    echo :retry >> "%APP_BASE%\open_chrome_when_ready.bat"
    echo curl -s -o nul "http://127.0.0.1:8000/index.php" >> "%APP_BASE%\open_chrome_when_ready.bat"
    echo if not errorlevel 1 goto :ready >> "%APP_BASE%\open_chrome_when_ready.bat"
    echo set /a RETRY+=1 >> "%APP_BASE%\open_chrome_when_ready.bat"
    echo if %%RETRY%% GEQ 30 goto :ready >> "%APP_BASE%\open_chrome_when_ready.bat"
    echo timeout /t 1 /nobreak ^>nul >> "%APP_BASE%\open_chrome_when_ready.bat"
    echo goto :retry >> "%APP_BASE%\open_chrome_when_ready.bat"
    echo :ready >> "%APP_BASE%\open_chrome_when_ready.bat"
    rem ゲストモード（--guest）は他のプロファイルでChromeが起動中だと無視されて
    rem 通常ウィンドウが開いてしまうため、起動前に既存のChromeを終了しておく。
    echo taskkill /IM chrome.exe /F ^>nul 2^>nul >> "%APP_BASE%\open_chrome_when_ready.bat"
    echo timeout /t 1 /nobreak ^>nul >> "%APP_BASE%\open_chrome_when_ready.bat"
    echo start "" "%CHROME_EXE%" --guest --start-maximized "http://127.0.0.1:8000/index.php" >> "%APP_BASE%\open_chrome_when_ready.bat"
    powershell -NoProfile -WindowStyle Hidden -Command "Start-Process -FilePath '%APP_BASE%\open_chrome_when_ready.bat' -WindowStyle Hidden" >> "%LOG_FILE%" 2>&1
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
