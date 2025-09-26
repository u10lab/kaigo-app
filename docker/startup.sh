#!/bin/sh

# Laravelの設定とマイグレーションを実行
echo "Starting Laravel application..."

# 設定キャッシュのクリア
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# マイグレーションの実行
echo "Running database migrations..."
php artisan migrate --force

# 本番環境用シーダーの実行（初回のみ）
if [ "$RUN_SEEDER" = "true" ]; then
    echo "Running production seeder..."
    php artisan db:seed --class=ProductionSeeder --force
fi

# Supervisordを起動
echo "Starting supervisord..."
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf