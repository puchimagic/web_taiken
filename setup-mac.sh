#!/bin/bash
set -e

# 体験授業の準備用スクリプト。実行すると、
# 1) デスクトップにリポジトリをclone
# 2) VSCodeにPHP Server拡張機能が無ければインストール
# 3) VSCodeでプロジェクトフォルダを開く
# 4) スライド(pptx)をデスクトップにコピー
# をまとめて行う。

REPO_URL="https://github.com/puchimagic/web_taiken.git"
TARGET_DIR="$HOME/Desktop/web_taiken"
EXTENSION_ID="brapifra.phpserver"
SLIDE_NAME="Webプログラミング体験_資料.pptx"

echo "==== 1/4: リポジトリを取得します ===="
if ! command -v git >/dev/null 2>&1; then
    echo "エラー: git コマンドが見つかりません。Gitをインストールしてください。"
    exit 1
fi

if [ -d "$TARGET_DIR" ]; then
    echo "フォルダ \"$TARGET_DIR\" はすでに存在するため、cloneをスキップします。"
else
    git clone "$REPO_URL" "$TARGET_DIR"
fi

echo ""
echo "==== 2/4: VSCode拡張機能 \"PHP Server\" を確認します ===="
if ! command -v code >/dev/null 2>&1; then
    echo "警告: code コマンドが見つからないため、拡張機能の自動インストールをスキップします。"
    echo "VSCodeを開き、拡張機能タブから \"PHP Server\" ($EXTENSION_ID) を手動でインストールしてください。"
else
    if code --list-extensions | grep -qi "$EXTENSION_ID"; then
        echo "\"PHP Server\" 拡張機能は既にインストールされています。"
    else
        echo "\"PHP Server\" 拡張機能をインストールします..."
        code --install-extension "$EXTENSION_ID"
    fi
fi

echo ""
echo "==== 3/4: スライドをデスクトップにコピーします ===="
if [ -f "$TARGET_DIR/$SLIDE_NAME" ]; then
    cp -f "$TARGET_DIR/$SLIDE_NAME" "$HOME/Desktop/$SLIDE_NAME"
    echo "デスクトップに \"$SLIDE_NAME\" を配置しました。"
else
    echo "警告: \"$SLIDE_NAME\" が見つかりませんでした。スキップします。"
fi

echo ""
echo "==== 4/4: VSCodeでプロジェクトを開きます ===="
if command -v code >/dev/null 2>&1; then
    code "$TARGET_DIR"
else
    echo "code コマンドが無いため自動で開けません。VSCodeで手動フォルダを開いてください:"
    echo "  $TARGET_DIR"
fi

echo ""
echo "準備が完了しました。サイトを起動するときは、開いたフォルダ内の start-mac.sh を実行してください。"
