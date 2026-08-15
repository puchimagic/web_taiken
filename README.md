# 旅行共有サイト

PHP + SQLite + HTML/CSS/JS で作った「知る人ぞ知る旅行スポット」共有サイトです。予約機能はなく、個人が写真とタグでスポットを投稿・閲覧できます。

## ページ構成

- `login.php` — ログイン／新規登録ページ
- `index.php` — ホーム（投稿されたスポットの一覧・タグ絞り込み）ページ
- `show.php?id=...` — スポット詳細ページ（写真・位置情報・タグ・コメント一覧・コメント投稿）
- `upload.php` — スポット投稿ページ（画像アップロード・現在地取得・タグ入力）

## 構成

- `public/` — Webサーバーの公開ルート（PHP Server拡張はここを開いて起動する）
  - `login.php`, `login.js` — ログイン・新規登録
  - `auth.php` — ログイン・新規登録・ログアウトAPI
  - `index.php`, `index.js` — スポット一覧・タグ絞り込み
  - `show.php`, `show.js` — スポット詳細・コメント
  - `comments.php` — コメント投稿API
  - `upload.php`, `upload.js` — スポット投稿フォーム（現在地取得を含む）
  - `spots.php` — スポット投稿（画像アップロード）API
  - `geocode.php` — 緯度経度から住所を取得する逆ジオコーディングAPI（OpenStreetMap Nominatimを利用）
  - `partials/` — 共通のトップバー・サイドバー
  - `uploads/` — アップロードされた画像ファイルの保存先
  - `style.css`
- `src/db.php` — SQLite接続・テーブル初期化
- `db/board.sqlite` — SQLiteデータベース本体（初回アクセス時に自動生成）

## 位置情報機能について

投稿フォームの「現在地を取得」ボタンは、ブラウザのGeolocation APIを使って緯度・経度を取得します（PCブラウザでも動作しますが、初回はブラウザの位置情報利用の許可が必要です）。取得した緯度・経度は `geocode.php` 経由で外部の住所検索API（OpenStreetMap Nominatim）に渡され、住所テキストに変換されて自動入力されます。

## 動かし方（VSCode拡張 PHP Server）

「PHP Server: Serve project」はターミナルに入力するコマンドではなく、VSCode内の操作です。

1. `public/login.php` を開く
2. `Cmd+Shift+P`（Windowsは`Ctrl+Shift+P`）でコマンドパレットを開く
3. `PHP Server` と入力し、候補から「PHP Server: Serve project」を選んでクリック
   （もしくはエディタを右クリックして「PHP Server: Serve current file at ...」を選ぶ）
4. ブラウザで表示されたURL（例: `http://127.0.0.1:PORT/login.php`）にアクセス

## 動かし方（ターミナルから起動する場合）

プロジェクトのルートで、OSに応じたスクリプトを実行するだけでPHP組み込みサーバーが起動します。

### Mac

```
./start-mac.sh
```

初回のみ実行権限が必要な場合は `chmod +x start-mac.sh` を先に実行してください。

### Windows

エクスプローラーで `start-win.bat` をダブルクリックするか、コマンドプロンプト／PowerShellで以下を実行します。

```
start-win.bat
```

PHPがインストールされていない、またはPATHが通っていない場合はエラーが出るので、その場合は先にPHPをインストールしてください。

### 共通

どちらも起動後、ブラウザで `http://127.0.0.1:8000/login.php` を開く。停止する場合は `Ctrl+C`。

（中身はどちらも `php -S 127.0.0.1:8000 -t public` を実行しているだけです）

## データベースについて

初回アクセス時に `db/board.sqlite` が自動生成されます。削除すれば初期状態に戻ります。

テスト用アカウント: `test / test`
