FROM composer:2

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist

COPY . .

CMD ["./vendor/bin/phpunit"]
