FROM php:8.2-fpm-alpine

# Install system dependencies & libraries required for IMAP, SQLite, MySQL, Zip, ICU
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite-dev \
    libpq-dev \
    imap-dev \
    krb5-dev \
    openssl-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev

# Configure and install PHP extensions
RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_sqlite \
        pdo_mysql \
        imap \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl

# Install Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
