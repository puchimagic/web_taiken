# 動画共有サイト

PHP + SQLite + HTML/CSS/JS で作ったシンプルな動画共有サイトです。

## ページ構成

- `login.php` — ログイン／新規登録ページ
- `index.php` — ホーム（投稿された動画の一覧）ページ
- `show.php?id=...` — 動画詳細ページ（視聴＋コメント一覧・コメント投稿）
- `upload.php` — 動画投稿（アップロード）ページ

## 構成

- `public/` — Webサーバーの公開ルート（PHP Server拡張はここを開いて起動する）
  - `login.php`, `login.js` — ログイン・新規登録
  - `auth.php` — ログイン・新規登録・ログアウトAPI
  - `index.php`, `index.js` — 動画一覧
  - `show.php`, `show.js` — 動画詳細・コメント
  - `comments.php` — コメント投稿API
  - `upload.php`, `upload.js` — 動画投稿フォーム
  - `videos.php` — 動画投稿（アップロード）API
  - `uploads/` — アップロードされた動画ファイルの保存先
  - `style.css`
- `src/db.php` — SQLite接続・テーブル初期化
- `db/board.sqlite` — SQLiteデータベース本体（初回アクセス時に自動生成）

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

テスト用アカウント: `taro / taro123`、`hanako / hanako123`
