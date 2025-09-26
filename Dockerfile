FROM php:8.3-fpm-alpine

# システムパッケージのインストール
RUN apk add --no-cache \
    nginx \
    supervisor \
    nodejs \
    npm \
    mysql-client \
    zip \
    unzip \
    git \
    curl

# PHP拡張のインストール
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli

# Composerのインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 作業ディレクトリの設定
WORKDIR /var/www/html

# アプリケーションファイルのコピー
COPY . .

# 依存関係のインストール
RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN npm install && npm run build

# 権限の設定
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 設定ファイルのコピー
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/startup.sh /usr/local/bin/startup.sh

# スタートアップスクリプトに実行権限を付与
RUN chmod +x /usr/local/bin/startup.sh

# ポート8080を公開（Railwayのデフォルトポート）
EXPOSE 8080

# スタートアップスクリプトを実行
CMD ["/usr/local/bin/startup.sh"]