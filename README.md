# 訪問介護ステーション業務改善アプリ

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-green.svg)](https://vuejs.org)
[![PHP](https://img.shields.io/badge/PHP-8.3+-blue.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-blue.svg)](https://mysql.com)
[![freee API](https://img.shields.io/badge/freee%20API-連携-orange.svg)](https://developer.freee.co.jp)

##  概要

訪問介護ステーションの業務効率化を目的としたWebアプリケーションです。freee APIとの連携により、勤怠管理、給与計算、有給休暇管理、サービス記録管理を一元化しています。

#### ログイン用メールアドレス・パスワード

管理者ログイン用　admin@example.com　admin123!

ヘルパーログイン用

名前	　　　メールアドレス	　　　  パスワード
田中太郎	3396728@imported.local	 password
佐藤花子	3396729@imported.local	 password
鈴木次郎	3396733@imported.local	 password

##  主な機能

###  認証・ユーザー管理
- **ロールベース認証**: 管理者・ヘルパーの役割分離
- **freee API連携**: OAuth2認証による安全な連携
- **従業員データ同期**: freeeからの従業員情報インポート・同期

###  勤怠管理
- **打刻機能**: 出勤・退勤の記録
- **勤務状態管理**: リアルタイムでの勤務状況表示
- **freee連携**: 勤怠データの自動同期

###  給与計算
- **freee給与明細API連携**: 基本給の取得（給与明細API）
- **サービス調整**: 介護サービスに基づく給与調整
- **月次計算**: 2025年9月・10月対応

###  有給休暇管理
- **申請・承認システム**: ヘルパーからの申請と管理者の承認
- **freee連携**: 承認後の勤怠データ自動登録
- **残日数管理**: 年次有給休暇の残日数追跡

###  サービス記録管理
- **サービス実施記録**: 開始・終了時刻の記録
- **クライアント管理**: 利用者情報の管理
- **サービス種別**: 訪問介護サービスの分類管理

###  CSV出力
- **介護保険請求用CSV**: 月次でのサービス記録出力
- **利用者別出力**: 個別利用者の記録出力
- **出力履歴管理**: 過去の出力履歴と再ダウンロード

##  技術スタック

### バックエンド
- **Laravel 12**: PHP Webフレームワーク
- **Laravel Sanctum**: API認証
- **MySQL 8.0**: データベース
- **Guzzle HTTP**: freee API通信

### フロントエンド
- **Vue.js 3**: JavaScriptフレームワーク
- **Pinia**: 状態管理
- **Vue Router**: ルーティング
- **Tailwind CSS**: CSSフレームワーク
- **Axios**: HTTP通信

### 外部API
- **freee API**: 勤怠・給与・人事管理システム連携
  - 従業員情報取得
  - 勤怠データ登録・取得
  - 給与明細取得

##  セットアップ

### 前提条件
- PHP 8.3以上
- Composer
- Node.js 18以上
- MySQL 8.0以上

### 1. リポジトリのクローン
```bash
git clone https://github.com/u10lab/kaigo-app.git
cd kaigo-app
```

### 2. 依存関係のインストール
```bash
# PHP依存関係
composer install

# Node.js依存関係
npm install
```

### 3. 環境設定
```bash
# 環境ファイルのコピー
cp .env.example .env

# アプリケーションキーの生成
php artisan key:generate
```

### 4. 環境変数の設定
`.env`ファイルを編集して以下を設定：

```env
# データベース設定
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kaigo_app
DB_USERNAME=your_username
DB_PASSWORD=your_password

# freee API設定
FREEE_CLIENT_ID=your_freee_client_id
FREEE_CLIENT_SECRET=your_freee_client_secret
FREEE_REDIRECT_URI=http://localhost:8000/api/freee/callback
```

### 5. データベースのセットアップ
```bash
# マイグレーション実行
php artisan migrate

# シーダー実行（初期データ投入）
php artisan db:seed
```

### 6. フロントエンドのビルド
```bash
# 開発用ビルド
npm run dev

# 本番用ビルド
npm run build
```

### 7. アプリケーションの起動
```bash
# Laravel開発サーバー起動
php artisan serve
```

アプリケーションは `http://localhost:8000` でアクセス可能です。

##  freee API連携設定

### 1. freeeアプリケーションの作成
1. [freee Developer Portal](https://developer.freee.co.jp/) にアクセス
2. 新しいアプリケーションを作成
3. 必要なスコープを設定

### 2. コールバックURLの設定
```
http://localhost:8000/api/freee/callback
```

### 3. 環境変数の設定
取得したクライアントIDとシークレットを`.env`ファイルに設定

##  使用方法

### 管理者画面
1. `http://localhost:8000` にアクセス
2. 管理者アカウントでログイン（初期: `admin@example.com` / `admin123!`）
3. freee API認証を実行
4. 従業員データのインポート・同期
5. 各機能の管理・設定

### ヘルパー画面
1. ヘルパーアカウントでログイン
2. 勤怠打刻・サービス記録の入力
3. 有給休暇申請の提出

##  プロジェクト構成

```
kaigo-app/
├── app/
│   ├── Http/Controllers/Api/     # APIコントローラー
│   ├── Models/                   # Eloquentモデル
│   ├── Services/                 # ビジネスロジック
│   └── ...
├── database/
│   ├── migrations/               # データベースマイグレーション
│   ├── seeders/                  # 初期データシーダー
│   └── ...
├── resources/
│   ├── js/
│   │   ├── components/           # Vueコンポーネント
│   │   ├── stores/               # Piniaストア
│   │   ├── views/                # Vueページ
│   │   └── ...
│   └── ...
└── routes/
    ├── api.php                   # APIルート
    └── web.php                   # Webルート
```

##  デプロイ

### Render.com へのデプロイ

#### 1. 環境変数の設定
Render管理画面で以下を設定：

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app-name.onrender.com

# データベース（Render MySQL）
DATABASE_URL=mysql://username:password@host:port/database

# freee API
FREEE_CLIENT_ID=your_freee_client_id
FREEE_CLIENT_SECRET=your_freee_client_secret
FREEE_REDIRECT_URI=https://your-app-name.onrender.com/api/freee/callback
```

#### 2. デプロイ後のコマンド
```bash
# アプリケーションキー生成
php artisan key:generate

# マイグレーション実行
php artisan migrate --force

# シーダー実行
php artisan db:seed --force
```

#### 3. freee API認証
1. デプロイ後のアプリケーションにアクセス
2. 管理者画面でfreee認証を実行
3. 従業員データのインポートを実行

##  テスト

```bash
# ユニットテスト実行
php artisan test

# フロントエンドテスト実行
npm run test
```

##  主要なAPI エンドポイント

### 認証
- `POST /api/login` - ログイン
- `POST /api/logout` - ログアウト
- `GET /api/user` - ユーザー情報取得

### freee連携
- `GET /api/freee/auth-url` - 認証URL取得
- `GET /api/freee/callback` - 認証コールバック
- `POST /api/freee/import-employees` - 従業員インポート
- `POST /api/freee/update-employee-ids` - 従業員ID更新

### 勤怠管理
- `POST /api/freee/clock-in` - 出勤打刻
- `POST /api/freee/clock-out` - 退勤打刻
- `GET /api/service/work-status` - 勤務状況取得

### 給与計算
- `GET /api/payroll/calculate` - 給与計算
- `POST /api/payroll/recalculate` - 再計算

### CSV出力
- `GET /api/export/care-insurance/{month}` - 介護保険請求用CSV出力
- `GET /api/export/history` - 出力履歴取得

##  セキュリティ

- **Laravel Sanctum**: API認証
- **CSRF保護**: フォーム送信の保護
- **SQLインジェクション対策**: Eloquent ORM使用
- **XSS対策**: Vue.jsの自動エスケープ
- **freee API**: OAuth2認証による安全な連携


**訪問介護ステーション業務改善アプリ** - 効率的な介護業務管理を実現