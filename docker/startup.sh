#!/bin/sh

# Laravelの設定とマイグレーションを実行
echo "Starting Laravel application..."

# 設定キャッシュのクリア
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 本番環境の設定
php artisan config:cache
php artisan route:cache
php artisan view:cache

# APP_KEYが設定されていない場合は生成
if [ -z "$APP_KEY" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# マイグレーションの実行
echo "Running database migrations..."
php artisan migrate --force

# 本番環境用シーダーの実行
echo "Running production seeder..."
php artisan db:seed --class=ProductionSeeder --force

# Supervisordを起動
echo "Starting supervisord..."
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf