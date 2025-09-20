# freee API設定手順

## 1. freeeアプリケーション登録

1. [freee開発者ポータル](https://secure.freee.co.jp/developer/applications)にアクセス
2. 新しいアプリケーションを作成
3. 以下の情報を取得：
   - Client ID
   - Client Secret
   - Redirect URI

## 2. 環境変数設定

`.env`ファイルに以下の設定を追加してください：

```env
# freee API設定
FREEE_CLIENT_ID=your_client_id_here
FREEE_CLIENT_SECRET=your_client_secret_here
FREEE_REDIRECT_URI=http://localhost:8000/api/freee/callback
FREEE_BASE_URL=https://api.freee.co.jp
```

## 3. データベース設定

MySQLデータベースの設定も`.env`ファイルで行ってください：

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kaigo_app
DB_USERNAME=root
DB_PASSWORD=your_password_here
```

## 4. マイグレーション実行

```bash
php artisan migrate
php artisan db:seed
```

## 5. freee認証フロー

1. `/api/freee/auth-url`にアクセスして認証URLを取得
2. 取得したURLにアクセスしてfreeeで認証
3. 認証完了後、コールバックURLにリダイレクト
4. トークンがデータベースに保存される

## 6. API使用例

### 勤務開始打刻
```bash
curl -X POST http://localhost:8000/api/freee/clock-in \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN"
```

### 勤務終了打刻
```bash
curl -X POST http://localhost:8000/api/freee/clock-out \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN"
```

### 勤務記録取得
```bash
curl -X GET "http://localhost:8000/api/freee/work-records?month=2024-09" \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN"
```
