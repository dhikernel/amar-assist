FROM php:8.1-fpm

# Laravel 9 suporta oficialmente PHP 8.0 -> 8.2.
# A imagem fixa 8.1 para garantir reprodutibilidade independente do PHP do host.

ARG UID=1000
ARG GID=1000

ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=0 \
    COMPOSER_MEMORY_LIMIT=-1

# Dependências de sistema necessárias para as extensões PHP e para o Composer
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        curl \
        ca-certificates \
        unzip \
        zip \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
        libxml2-dev \
        libicu-dev \
        default-mysql-client \
        procps \
    && rm -rf /var/lib/apt/lists/*

# Extensões PHP:
#   pdo_mysql -> MySQL
#   pcntl     -> exigido pelo Horizon para controle dos workers de fila
#   bcmath    -> cálculo monetário dos juros/multa sem erro de ponto flutuante
#   intl      -> formatação pt-BR
#   opcache   -> performance
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Extensão Redis (cache + filas)
RUN pecl install redis-6.0.2 \
    && docker-php-ext-enable redis

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer
COPY php.ini /usr/local/etc/php/conf.d/99-app.ini

# Remapeia www-data para o UID/GID do host: os arquivos gerados dentro do
# container (migrations, cache, storage) ficam editáveis fora dele.
RUN groupmod -o -g ${GID} www-data \
    && usermod -o -u ${UID} -g www-data -d /home/www-data -s /bin/bash www-data \
    && mkdir -p /home/www-data/.composer /var/www/html \
    && chown -R www-data:www-data /home/www-data /var/www/html

WORKDIR /var/www/html

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
