FROM php:8.3-apache

# Arguments
ARG DEBIAN_FRONTEND=noninteractive

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    librabbitmq-dev \
    && rm -rf /var/lib/apt/lists/*

# Installation de Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Installation des extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    zip \
    intl \
    mbstring \
    opcache \
    gd \
    bcmath \
    sockets

# Installation de l'extension AMQP pour RabbitMQ
RUN pecl install amqp \
    && docker-php-ext-enable amqp

# Installation de Redis
RUN pecl install redis \
    && docker-php-ext-enable redis

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration Apache
RUN a2enmod rewrite headers

# Configuration PHP
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Configuration PHP personnalisée
RUN echo "memory_limit = 512M" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "upload_max_filesize = 50M" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "post_max_size = 50M" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "max_execution_time = 300" >> "$PHP_INI_DIR/conf.d/custom.ini"

# Création du répertoire de travail
WORKDIR /var/www/html

# Permissions
RUN chown -R www-data:www-data /var/www/html

# Exposition du port
EXPOSE 80

CMD ["apache2-foreground"]