#!/bin/bash
# 体験授業終了後、各Macを元の状態に戻すためのクリーンアップ用スクリプト
# （Webプロクリーン.batのmac版）。
# プロジェクトフォルダ内に残しておき、デスクトップには自動配置しない。体験授業の
# 最後にMacへの痕跡をゼロにしたい場合、VSCodeのエクスプローラーやターミナルから
# 手動で実行する想定。
# 1) ポート8000で起動中のPHPサーバーがあれば停止する
# 2) VSCodeが起動していれば終了する
# 3) ブラウザ(Safari / Google Chrome)が起動していれば終了する
# 4) プロジェクト一式（APP_BASE、VSCode専用プロファイル・実行ログ含む）を
#    まとめて削除する
#
# Webプロセットアップ.sh 自身とこのスクリプト自身は削除しない。この2つだけが
# デスクトップに残る。何度でも Webプロセットアップ.sh -> Webプロクリーン.sh
# を繰り返せる。
#
# 実行内容はすべてログファイルにも記録される（トラブル調査用。画面表示を
# 確認させたあと、このログファイル自身も含めて最後にまとめて削除する）。

set -u

# プロジェクト本体・VSCode専用プロファイル・実行ログはすべて
# ~/Library/Application Support/web_taiken にまとまっている。
# Webプロセットアップ.sh と同じ場所を指す。
APP_BASE="$HOME/Library/Application Support/web_taiken"
LOG_FILE="$APP_BASE/web_taiken_cleanup_log.txt"

mkdir -p "$APP_BASE"
echo "==== Webプロクリーン.sh 開始 $(date) ====" > "$LOG_FILE"

log() {
    echo "$1"
    echo "$1" >> "$LOG_FILE"
}

log "==== 1/3: PHPサーバーを停止します ===="
PORT_PID="$(lsof -ti tcp:8000 -sTCP:LISTEN 2>/dev/null || true)"
if [ -n "$PORT_PID" ]; then
    log "ポート8000で待受中のプロセス（PID ${PORT_PID}）を終了します。"
    kill -9 $PORT_PID >> "$LOG_FILE" 2>&1 || true
else
    log "ポート8000で起動中のPHPサーバーは見つかりませんでした。"
fi

log ""
log "==== 2/3: VSCodeとブラウザを終了します ===="
if pgrep -f "Visual Studio Code" >/dev/null 2>&1; then
    log "VSCodeを終了します。"
    osascript -e 'quit app "Visual Studio Code"' >> "$LOG_FILE" 2>&1 || true
    sleep 1
    pkill -f "Visual Studio Code" >> "$LOG_FILE" 2>&1 || true
else
    log "VSCodeは起動していませんでした。"
fi

# ブラウザは強制終了（pkill -9）だとクラッシュ扱いになり、次回起動時に
# 「ページを復元しますか？」という確認が出てしまう。osascriptのquitで
# 通常のウィンドウクローズ要求にして、正常終了として閉じるようにする。
if pgrep -x "Safari" >/dev/null 2>&1; then
    log "Safariを終了します。"
    osascript -e 'quit app "Safari"' >> "$LOG_FILE" 2>&1 || true
fi
if pgrep -x "Google Chrome" >/dev/null 2>&1; then
    log "Google Chromeを終了します。"
    osascript -e 'quit app "Google Chrome"' >> "$LOG_FILE" 2>&1 || true
fi

log ""
log "==== 3/3: プロジェクト一式を削除します ===="
log "「${APP_BASE}」（プロジェクト本体・VSCode専用プロファイル・実行ログ）を削除します。このログファイル自身もここに含まれます。"

log ""
log "クリーンアップが完了しました。Webプロセットアップ.sh とこのスクリプト自身は削除していません。"

echo ""
read -n 1 -s -r -p "何かキーを押すと終了します..."
echo ""
rm -rf "$APP_BASE"
