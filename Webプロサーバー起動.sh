#!/bin/bash
# 接続が切れた等でPHPサーバーだけ再起動したいときに使うスクリプト。
# Webプロセットアップ.sh の「PHPサーバーを起動する」と同じ処理を、
# このファイル単体から実行できるようにしたもの（Webプロサーバー起動.batのmac版）。
# 実行内容はすべてログファイルにも記録される（トラブルの調査用）。
#
# DOCROOTとLOG_FILEは、このスクリプト自身のあるフォルダ基準にしている。
# 体験授業ではプロジェクトフォルダ（~/Library/Application Support/web_taiken/project）の
# 中でこのスクリプトを実行する想定で、その場合は自動的にそのプロジェクトの
# public を指す。通常の開発でリポジトリ直下から実行した場合も同様に
# そのリポジトリの public を指す。

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOCROOT="$SCRIPT_DIR/public"
LOG_FILE="$SCRIPT_DIR/web_taiken_start_log.txt"

log() {
    echo "$1"
    echo "$1" >> "$LOG_FILE"
}

echo "==== Webプロサーバー起動.sh 開始 $(date) ====" > "$LOG_FILE"

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

if ! find_php; then
    log "エラー: 「php」コマンドが見つかりません。PHPをインストールしてください。"
    echo ""
    echo "ログを \"$LOG_FILE\" に保存しました。"
    read -n 1 -s -r -p "何かキーを押すと終了します..."
    echo ""
    exit 1
fi

log "PHP組み込みサーバーを起動します: http://127.0.0.1:8000/index.php"
log "停止するには Ctrl+C を押してください。"
echo "PHPサーバーを起動しました" >> "$LOG_FILE"

php -d upload_max_filesize=200M -d post_max_size=200M -S 127.0.0.1:8000 -t "$DOCROOT"

echo ""
echo "ログを \"$LOG_FILE\" に保存しました。"
read -n 1 -s -r -p "何かキーを押すと終了します..."
echo ""
