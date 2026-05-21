# MediaServer Docker Configuration
# For containerized deployments on Ubuntu

FROM ubuntu:22.04

WORKDIR /var/www/media-server

# Install system dependencies
RUN apt-get update && apt-get install -y \
    curl \
    wget \
    git \
    zip \
    unzip \
    software-properties-common \
    supervisor \
    redis-server \
    nginx \
    ffmpeg \
    vlc \
    libavformat-dev \
    libavcodec-dev \
    libavdevice-dev \
    libswscale-dev \
    pkg-config

# Add PHP repository and install PHP
RUN add-apt-repository -y ppa:ondrej/php && \
    apt-get update && \
    apt-get install -y \
    php8.2-fpm \
    php8.2-cli \
    php8.2-common \
    php8.2-curl \
    php8.2-gd \
    php8.2-mbstring \
    php8.2-mysql \
    php8.2-redis \
    php8.2-xml \
    php8.2-zip \
    php8.2-bcmath \
    php8.2-intl \
    php8.2-iconv \
    php8.2-dev

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy application code
COPY . /var/www/media-server

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Create storage directories
RUN mkdir -p storage/logs storage/streams && \
    chown -R www-data:www-data /var/www/media-server && \
    chmod -R 775 storage bootstrap/cache

# Configure PHP-FPM
COPY php-fpm.conf /etc/php/8.2/fpm/pool.d/www.conf

# Configure Nginx
COPY nginx.conf.example /etc/nginx/sites-available/media-server
RUN ln -sf /etc/nginx/sites-available/media-server /etc/nginx/sites-enabled/media-server && \
    rm -f /etc/nginx/sites-enabled/default

# Configure Supervisor
COPY supervisor.conf.example /etc/supervisor/conf.d/media-server.conf

# Configure Redis
RUN sed -i 's/^# maxmemory/maxmemory/' /etc/redis/redis.conf && \
    sed -i 's/^maxmemory 0/maxmemory 512mb/' /etc/redis/redis.conf

# Expose ports
EXPOSE 80 443 1935 6379

# Start services
CMD ["/bin/bash", "-c", "service redis-server start && service php8.2-fpm start && service supervisor start && nginx -g 'daemon off;'"]
