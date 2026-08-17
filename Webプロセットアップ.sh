#!/bin/bash
# 体験授業の準備用スクリプト（Webプロセットアップ.batのmac版）。
# デスクトップに配置してターミナルから実行すると、
# 1) 実プロジェクト一式（APP_BASE=~/Library/Application Support/web_taiken/project、
#    iCloud Driveの「デスクトップと書類」同期の対象外の場所）に
#    既存のリポジトリがあれば削除してから、改めてclone
#    （.gitフォルダ・READMEなど体験授業に不要な開発用ファイルは取得後に削除する）
# 2) VSCodeにPHP Server拡張機能・SQLite Viewer拡張機能が無ければインストール
# 3) VSCodeでプロジェクト専用プロファイルを使ってフォルダを開く（毎回まっさらな画面になる）
# 4) PHPサーバーを起動する
# をまとめて行う。
#
# スライド(pptx)やサーバー起動用スクリプト(Webプロサーバー起動.sh)、クリーンアップ用
# スクリプト(Webプロクリーン.sh)はデスクトップにはコピーしない。プロジェクトフォルダの
# 中にあるものをそのまま使う（生徒の入れ替わりはこのスクリプトの再実行だけで完結し、
# 既存のVSCode・PHPサーバーは自動で終了・削除されるため、クリーンアップ用スクリプトは
# 体験授業の最後にMacへの痕跡をゼロにしたい場合にのみ手動で使う想定）。
# これにより、このスクリプトを実行したデスクトップに最終的に残るのは
# Webプロセットアップ.sh 1つだけになる。
#
# 実行内容はすべてログファイルにも記録される（トラブル時の調査用）。

set -u

REPO_URL="https://github.com/puchimagic/web_taiken.git"
EXTENSION_ID="brapifra.phpserver"
SQLITE_EXTENSION_ID="qwtel.sqlite-viewer"
JA_LANGUAGE_PACK_ID="ms-ceintl.vscode-language-pack-ja"

# プロジェクト本体・VSCode専用プロファイル・実行ログはすべて
# ~/Library/Application Support/web_taiken（iCloud Driveの同期対象外）にまとめる。
APP_BASE="$HOME/Library/Application Support/web_taiken"
TARGET_DIR="$APP_BASE/project"
VSCODE_USER_DATA="$APP_BASE/vscode_data"
VSCODE_EXTENSIONS="$APP_BASE/vscode_extensions"
LOG_FILE="$APP_BASE/web_taiken_setup_log.txt"
mkdir -p "$APP_BASE"

echo "==== Webプロセットアップ.sh 開始 $(date) ====" > "$LOG_FILE"

log() {
    echo "$1"
    echo "$1" >> "$LOG_FILE"
}

pause_and_exit() {
    echo ""
    echo "ログを \"$LOG_FILE\" に保存しました。"
    read -n 1 -s -r -p "何かキーを押すと終了します..."
    echo ""
    exit "$1"
}

# ---------------------------------------------------------
# PHPコマンドの検出。PATHになければよくあるインストール先
# （Homebrew）を探し、見つかればこのスクリプト内のPATHに追加する。
# ---------------------------------------------------------
find_php() {
    if command -v php >/dev/null 2>&1; then
        return 0
    fi

    log "「php」コマンドが見つからないため、インストール先を探します..."

    for d in /opt/homebrew/bin /usr/local/bin /opt/homebrew/opt/php/bin; do
        if [ -x "$d/php" ]; then
            log "PHPを発見しました: $d"
            export PATH="$d:$PATH"
            return 0
        fi
    done

    log "PHPが見つかりませんでした。Homebrewで「brew install php」を実行するか、PHPをインストールしてください。"
    return 1
}

log "==== 1/4: リポジトリを取得します ===="
if ! command -v git >/dev/null 2>&1; then
    log "エラー: 「git」コマンドが見つかりません。Gitをインストールしてください（例: Xcodeコマンドラインツール、brew install git）。"
    pause_and_exit 1
fi

# ポート8000を使っているプロセスがあれば、project フォルダの有無に関係なく
# 常に停止しておく（このスクリプト以外の理由でPHPサーバーが8000番を
# 使っていると、後続のPHPサーバー起動が失敗するため）。
PORT_PID="$(lsof -ti tcp:8000 -sTCP:LISTEN 2>/dev/null || true)"
if [ -n "$PORT_PID" ]; then
    log "ポート8000で待受中のプロセス（PID ${PORT_PID}）を終了します。"
    kill -9 $PORT_PID >> "$LOG_FILE" 2>&1 || true
fi

# VSCodeが既に起動していると、後で --user-data-dir / --extensions-dir を
# 指定して code コマンドを実行しても、既存の実行中インスタンス（個人プロファイル
# 等）が使い回されてしまい、専用プロファイルが反映されないことがある
# （VSCode CLIの既知の挙動）。project フォルダの有無に関係なく、
# 毎回いったんVSCodeを終了させてから起動し直す。
if pgrep -x "Electron" >/dev/null 2>&1 || pgrep -f "Visual Studio Code" >/dev/null 2>&1; then
    log "VSCodeを終了します。"
    osascript -e 'quit app "Visual Studio Code"' >> "$LOG_FILE" 2>&1 || true
    sleep 1
    pkill -f "Visual Studio Code" >> "$LOG_FILE" 2>&1 || true
    sleep 1
fi

if [ -d "$TARGET_DIR" ]; then
    log "フォルダ「${TARGET_DIR}」が既に存在するため、削除してから改めて取得し直します。"

    rm -rf "$TARGET_DIR"
    if [ -d "$TARGET_DIR" ]; then
        log "エラー: 「${TARGET_DIR}」の削除に失敗しました。VSCode等で開いたままになっていないか確認してください。"
        pause_and_exit 1
    fi
fi

log "git clone $REPO_URL $TARGET_DIR"
if ! git clone "$REPO_URL" "$TARGET_DIR" >> "$LOG_FILE" 2>&1; then
    log "エラー: git clone に失敗しました。詳細は $LOG_FILE を確認してください。"
    pause_and_exit 1
fi

# 体験授業ではgit操作やREADMEの閲覧は不要で、VSCode上に見えるとかえって
# 紛らわしいため、clone直後に取り除いておく（.gitを消してもこのMacの手元の
# 履歴が消えるだけで、GitHub側やこのリポジトリ自体には影響しない）。
rm -rf "$TARGET_DIR/.git"
rm -f "$TARGET_DIR/README.md"

# 上記以外にも、体験授業の実施に直接関係ない開発用ドキュメント・
# スクリプト類・自己紹介画像（次回pptx作成用の講師写真）・パワポ用画像
# （説明資料作成用のスクリーンショット、いずれも体験授業では不要）・
# その他フォルダ（開発用ドキュメント類）・
# Windows版のセットアップ・サーバー起動・クリーンアップ用bat（このMacでは使わない）は
# 取得後にできるだけ削除しておく。残すのはスライド(pptx)、
# public・src・db（import以外）・画像（アプリの動作に必要）、
# Webプロサーバー起動.sh（サーバー再起動用）・Webプロクリーン.sh
# （体験授業の最後にMacを片付けたいときに手動実行する用）のみ。
for f in "CLAUDE.md" ".gitignore" \
         "Webプロセットアップ.bat" "Webプロサーバー起動.bat" "Webプロクリーン.bat" \
         "Webプロセットアップ.sh"; do
    rm -f "$TARGET_DIR/$f"
done
rm -rf "$TARGET_DIR/scripts"
rm -rf "$TARGET_DIR/自己紹介画像"
rm -rf "$TARGET_DIR/パワポ用画像"
rm -rf "$TARGET_DIR/その他"
# db/import は郵便番号CSV（utf_ken_all.csv）以外は開発用ファイルなので、
# CSVだけ db/ 直下へ退避してから import フォルダごと削除する。
# CSVは初回PHPアクセス時（PHPサーバー起動後）にsrc/db.phpが自動で
# postal_codesテーブルへ取り込むため、db/ 直下に残しておく必要がある。
if [ -f "$TARGET_DIR/db/import/utf_ken_all.csv" ]; then
    mv "$TARGET_DIR/db/import/utf_ken_all.csv" "$TARGET_DIR/db/utf_ken_all.csv"
fi
rm -rf "$TARGET_DIR/db/import"

log ""
log "==== 2/4: VSCode拡張機能を確認します ===="
if ! command -v code >/dev/null 2>&1; then
    log "エラー: 「code」コマンドが見つかりません。VSCodeがインストールされているか、シェルコマンド「code」がインストールされているか確認してください（VSCode: コマンドパレット→「シェルコマンド: PATH内に'code'コマンドをインストールします」）。"
    pause_and_exit 1
fi

if code --extensions-dir "$VSCODE_EXTENSIONS" --list-extensions 2>/dev/null | grep -qi "$EXTENSION_ID"; then
    log "「PHP Server」拡張機能は既にインストールされています。"
else
    log "「PHP Server」拡張機能をインストールします..."
    code --extensions-dir "$VSCODE_EXTENSIONS" --install-extension "$EXTENSION_ID" >> "$LOG_FILE" 2>&1
fi

if code --extensions-dir "$VSCODE_EXTENSIONS" --list-extensions 2>/dev/null | grep -qi "$SQLITE_EXTENSION_ID"; then
    log "「SQLite Viewer」拡張機能は既にインストールされています。"
else
    log "「SQLite Viewer」拡張機能をインストールします..."
    code --extensions-dir "$VSCODE_EXTENSIONS" --install-extension "$SQLITE_EXTENSION_ID" >> "$LOG_FILE" 2>&1
fi

if code --extensions-dir "$VSCODE_EXTENSIONS" --list-extensions 2>/dev/null | grep -qi "$JA_LANGUAGE_PACK_ID"; then
    log "「Japanese Language Pack」拡張機能は既にインストールされています。"
else
    log "「Japanese Language Pack」拡張機能をインストールします..."
    code --extensions-dir "$VSCODE_EXTENSIONS" --install-extension "$JA_LANGUAGE_PACK_ID" >> "$LOG_FILE" 2>&1
fi

log ""
log "==== 3/4: VSCodeでプロジェクトを開きます ===="
# 普段使っているVSCodeのプロファイル（~/Library/Application Support/Code）をそのまま
# 使うと、前回このMacで開いていたときのサイドバー開閉状態などのレイアウト記憶や、
# この端末に個人的に入れている拡張機能がそのまま引き継がれてしまう。
# このプロジェクト専用のuser-data-dirを使い、開く前に毎回削除してから
# 作り直すことで、常にVSCode本来の初期状態で開かれるようにする。
rm -rf "$VSCODE_USER_DATA"
mkdir -p "$VSCODE_USER_DATA/User"
cat > "$VSCODE_USER_DATA/User/settings.json" <<'EOS'
{
  "workbench.startupEditor": "none",
  "workbench.welcomePage.walkthroughs.openOnInstall": false,
  "workbench.secondarySideBar.defaultVisibility": "hidden",
  "chat.commandCenter.enabled": false,
  "workbench.colorTheme": "Light 2026"
}
EOS

# argv.json の locale 設定でVSCode本体のUI表示言語を日本語にする
# （settings.jsonのdisplay.languageではなくargv.jsonのlocale項目が必要）。
cat > "$VSCODE_USER_DATA/argv.json" <<'EOS'
{
  "locale": "ja"
}
EOS

# 言語パックは、専用user-data-dirでのVSCode「初回起動」だけではUIに反映
# されず、一度閉じて開き直した2回目の起動から反映されるという既知の挙動が
# ある（VSCode本体側の未解決issue）。そのため、まず1回目を起動して
# ウィンドウが立ち上がるまで待ってから閉じ、日本語化を確定させたうえで
# 改めて2回目を起動し直す。
log "日本語UIを反映させるため、VSCodeを一度初期化します（1回目の起動）..."
code --disable-workspace-trust --new-window --locale ja --user-data-dir "$VSCODE_USER_DATA" --extensions-dir "$VSCODE_EXTENSIONS" "$TARGET_DIR" >> "$LOG_FILE" 2>&1

# ウィンドウ（Electronプロセス）が実際に立ち上がるまで待つ（最大15秒）。
for i in $(seq 1 15); do
    if pgrep -f "$VSCODE_USER_DATA" >/dev/null 2>&1; then
        break
    fi
    sleep 1
done
sleep 1

log "VSCodeを一旦終了します（日本語化を確定させるため）..."
osascript -e 'quit app "Visual Studio Code"' >> "$LOG_FILE" 2>&1 || true
for i in $(seq 1 10); do
    if ! pgrep -f "$VSCODE_USER_DATA" >/dev/null 2>&1; then
        break
    fi
    sleep 1
done
pkill -f "$VSCODE_USER_DATA" >> "$LOG_FILE" 2>&1 || true
sleep 1

log "VSCodeを改めて起動します（2回目の起動）..."
code --disable-workspace-trust --new-window --locale ja --user-data-dir "$VSCODE_USER_DATA" --extensions-dir "$VSCODE_EXTENSIONS" "$TARGET_DIR"

log ""
log "==== 4/4: PHPサーバーを起動します ===="
if ! find_php; then
    log "エラー: 「php」コマンドが見つかりません。PHPをインストールしてください。"
    pause_and_exit 1
fi

log "停止するには Ctrl+C を押してください。"
echo "PHPサーバーを起動しました" >> "$LOG_FILE"

# Google Chromeでホームページ（index.php）を自動で開く。インストール先が
# 見つからない場合はエラーにせず、既定のブラウザで開く。
CHROME_APP="/Applications/Google Chrome.app"
if [ -d "$CHROME_APP" ]; then
    log "PHPサーバーの起動を待ってからGoogle Chromeで http://127.0.0.1:8000/index.php を開きます。"

    # PHPサーバー（このあと本体がフォアグラウンドで起動する）がポート8000で
    # 実際に待受を始める前にChromeでアクセスすると「接続できません」が
    # 一瞬表示されてしまう。curlで疎通確認しながら待ってからChromeを開く。
    (
        RETRY=0
        while ! curl -s -o /dev/null "http://127.0.0.1:8000/index.php"; do
            RETRY=$((RETRY + 1))
            if [ "$RETRY" -ge 30 ]; then
                break
            fi
            sleep 1
        done
        # ゲストモード（--guest）は他のプロファイルでChromeが起動中だと無視されて
        # 通常ウィンドウが開いてしまうため、起動前に既存のChromeを終了しておく
        # （前の参加者の入力履歴・ログイン情報などを引き継がないようにするため）。
        osascript -e 'quit app "Google Chrome"' >/dev/null 2>&1 || true
        sleep 1
        open -a "Google Chrome" --args --guest --start-maximized "http://127.0.0.1:8000/index.php"
    ) &
else
    log "警告: Google Chromeが見つかりませんでした。ブラウザで http://127.0.0.1:8000/index.php を開いてください。"
fi

php -d upload_max_filesize=200M -d post_max_size=200M -S 127.0.0.1:8000 -t "$TARGET_DIR/public"

pause_and_exit 0
