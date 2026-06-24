FROM php:8.4-fpm

ARG USER_ID=1000
ARG GROUP_ID=1000

ENV DEBIAN_FRONTEND=noninteractive

# --------------------------------------------------
# System packages
# --------------------------------------------------
RUN apt-get update && apt-get install -y \
    git \
    curl \
    nano \
    sudo \
    cron \
    supervisor \
    unzip \
    zip \
    mariadb-client \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libonig-dev \
    libxml2-dev \
    libssl-dev \
    libmagickwand-dev \
    imagemagick \
    libmemcached-dev \
    zlib1g-dev \
    python3-pip \
    jpegoptim optipng pngquant gifsicle \
    && rm -rf /var/lib/apt/lists/*

# --------------------------------------------------
# PHP extensions
# --------------------------------------------------
RUN docker-php-ext-install \
    pdo_mysql \
    zip \
    opcache

RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    && docker-php-ext-install gd

# --------------------------------------------------
# PECL extensions (stable install)
# --------------------------------------------------

# Redis
RUN pecl install redis \
    && docker-php-ext-enable redis

# Memcached
RUN pecl install memcached \
    && docker-php-ext-enable memcached

# Imagick
RUN pecl install imagick-3.7.0 || true \
    && docker-php-ext-enable imagick || true

# --------------------------------------------------
# Composer
# --------------------------------------------------
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

# --------------------------------------------------
# Node.js 20
# --------------------------------------------------
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest svgo

# --------------------------------------------------
# User (laradock)
# --------------------------------------------------
RUN if [ ${USER_ID} -ne 0 ] && [ ${GROUP_ID} -ne 0 ]; then \
    groupadd -g ${GROUP_ID} laradock && \
    useradd -l -u ${USER_ID} -g laradock -m laradock && \
    adduser laradock sudo && \
    echo '%sudo ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers \
;fi

# --------------------------------------------------
# Cron (Laravel scheduler)
# --------------------------------------------------
USER laradock
RUN (crontab -l 2>/dev/null; echo "* * * * * php /var/www/artisan schedule:run >> /dev/null 2>&1") | crontab
USER root

# --------------------------------------------------
# PHP configs (dev / prod)
# --------------------------------------------------
COPY ./opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# --------------------------------------------------
# PHP-FPM config
# --------------------------------------------------
COPY ./xlaravel.pool.conf /usr/local/etc/php-fpm.d/

# --------------------------------------------------
# Workdir
# --------------------------------------------------
WORKDIR /var/www

# --------------------------------------------------
# Cleanup
# --------------------------------------------------
RUN apt-get clean && rm -rf /tmp/* /var/tmp/*

# --------------------------------------------------
# Start services
# --------------------------------------------------
ENTRYPOINT ["sudo", "/usr/bin/supervisord", "-n", "-c", "/etc/supervisord.conf"]

EXPOSE 9000
