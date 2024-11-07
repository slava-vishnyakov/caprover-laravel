FROM ubuntu:24.04

ENV TZ=Europe/London
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
ENV LC_ALL=en_US.UTF-8
ENV LANG=en_US.UTF-8

# Install PHP and extensions

RUN apt-get update && \
    apt-get install -y software-properties-common && \
    add-apt-repository ppa:ondrej/php

RUN apt-get update && \
    apt-get install -y --no-install-recommends --no-install-suggests nginx php8.2 php8.2-fpm ca-certificates gettext \
                    php8.2-mbstring php8.2-dom php8.2-zip git php8.2-pdo-pgsql php8.2-redis php8.2-gd php8.2-curl \
                    php8.2-gmp php8.2-intl php8.2-xml
#    &&  rm -rf /var/lib/apt/lists/*

# forward request and error logs to docker log collector

RUN ln -sf /dev/stdout /var/log/nginx/access.log \
	&& ln -sf /dev/stderr /var/log/nginx/error.log \
	&& ln -sf /dev/stderr /var/log/php8.2-fpm.log

RUN rm -f /etc/nginx/sites-enabled/*

RUN mkdir -p /run/php && touch /run/php/php8.2-fpm.sock && touch /run/php/php8.2-fpm.pid

# Install Supervisor

RUN apt-get update && apt-get install -y --no-install-recommends --no-install-suggests supervisor unzip

# Add user and directory
RUN groupadd --gid 32767 appuser
RUN useradd --create-home --uid 32767 --gid 32767 --shell /bin/bash appuser
RUN mkdir /app/
RUN chown -R appuser:appuser /app/

# Install Composer and run it

USER root
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    mv composer.phar /usr/local/bin/composer


# Install Node and build production scripts
USER root
WORKDIR /
RUN apt-get install -y curl
RUN curl -fsSL https://deb.nodesource.com/setup_lts.x | bash -
RUN apt-get update && apt-get install -y nodejs

# Run composer
USER appuser
COPY composer.json /app/
COPY composer.lock /app/
WORKDIR /app/
RUN mkdir -p /app/storage/logs/

RUN composer install --no-dev --no-scripts --prefer-dist --no-autoloader

# Run npm
USER appuser
WORKDIR /app/
COPY --chown=appuser:appuser package.json package-lock.json* /app/
RUN npm install

## Copy the rest of the application
USER appuser
COPY --chown=appuser:appuser . /app/
RUN composer install --no-dev --prefer-dist

# Build production
RUN ./node_modules/vite/bin/vite.js build

# Copy support files
USER root
COPY resources/docker/entrypoint.sh /entrypoint.sh
RUN chmod 755 /entrypoint.sh

COPY resources/docker/supervisor.conf /etc/supervisor/conf.d/supervisor.conf
COPY resources/docker/nginx.conf.tpl /tmp/nginx.conf.tpl
COPY resources/docker/php-fpm.conf.tpl /tmp/php-fpm.conf.tpl
COPY resources/docker/php.ini /etc/php/8.2/cli/php.ini
COPY resources/docker/php.ini /etc/php/8.2/fpm/php.ini

#EXPOSE 8082

CMD ["/entrypoint.sh"]

