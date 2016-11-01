FROM php:7.0.12-fpm
MAINTAINER "Magento"

ENV PHP_EXTRA_CONFIGURE_ARGS="--enable-fpm --with-fpm-user=magento2 --with-fpm-group=magento2"

RUN apt-get update && apt-get install -y \
    apt-utils \
    sudo \
    wget \
    cron \
    curl \
    libmcrypt-dev \
    libicu-dev \
    libxml2-dev libxslt1-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng12-dev \
    git \
    vim \
    openssh-server \
    supervisor \
    mysql-client \
    ocaml \
    && curl -L https://github.com/bcpierce00/unison/archive/2.48.4.tar.gz | tar zxv -C /tmp && \
             cd /tmp/unison-2.48.4 && \
             sed -i -e 's/GLIBC_SUPPORT_INOTIFY 0/GLIBC_SUPPORT_INOTIFY 1/' src/fsmonitor/linux/inotify_stubs.c && \
             make && \
             cp src/unison src/unison-fsmonitor /usr/local/bin && \
             cd /root && rm -rf /tmp/unison-2.48.4 \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-configure hash --with-mhash \
    && docker-php-ext-install -j$(nproc) mcrypt intl xsl gd zip pdo_mysql opcache soap bcmath json iconv \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && pecl install xdebug && docker-php-ext-enable xdebug \
    && echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_port=9000" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_connect_back=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.max_nesting_level=1000" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && mkdir /var/run/sshd \
    && apt-get clean && apt-get update && apt-get install -y nodejs \
    && ln -s /usr/bin/nodejs /usr/bin/node \
    && apt-get install -y npm \
    && npm update -g npm && npm install -g grunt-cli && npm install -g gulp \
    && echo "StrictHostKeyChecking no" >> /etc/ssh/ssh_config \
    && apt-get install -y apache2 \
    && a2enmod rewrite \
    && a2enmod proxy \
    && a2enmod proxy_fcgi \
    && rm -f /etc/apache2/sites-enabled/000-default.conf \
    && useradd -m -d /home/magento2 -s /bin/bash magento2 && adduser magento2 sudo \
    && mkdir /home/magento2/magento2 && mkdir /var/www/magento2 \
    && curl -sS https://accounts.magento.cloud/cli/installer -o /home/magento2/installer \
    && rm -r /usr/local/etc/php-fpm.d/* \
    && sed -i 's/www-data/magento2/g' /etc/apache2/envvars

# PHP config
ADD conf/php.ini /usr/local/etc/php

# SSH config
COPY conf/sshd_config /etc/ssh/sshd_config
RUN chown magento2:magento2 /etc/ssh/ssh_config

# supervisord config
ADD conf/supervisord.conf /etc/supervisord.conf

# php-fpm config
ADD conf/php-fpm-magento2.conf /usr/local/etc/php-fpm.d/php-fpm-magento2.conf

# apache config
ADD conf/apache-default.conf /etc/apache2/sites-enabled/apache-default.conf

# unison script
ADD conf/.unison/magento2.prf /home/magento2/.unison/magento2.prf
RUN chown -R magento2:magento2 /home/magento2 && \
    chown -R magento2:magento2 /var/www/magento2

ADD conf/unison.sh /usr/local/bin/unison.sh
ADD conf/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/unison.sh && chmod +x /usr/local/bin/entrypoint.sh

ENV PATH $PATH:/home/magento2/scripts/:/home/magento2/.magento-cloud/bin
ENV PATH $PATH:/var/www/magento2/bin

ENV USE_SHARED_WEBROOT = 1
ENV SHARED_CODE_PATH="/var/www/magento2"
ENV WEBROOT_PATH="/var/www/magento2"

#ENV USE_RABBITMQ 0
#ENV USE_REDIS_FULL_PAGE_CACHE 0
#ENV USE_REDIS_CACHE 0
#ENV USE_REDIS_SESSIONS 0
#ENV USE_VARNISH 0
#ENV USE_ELASTICSEARCH 0

#ENV MAGENTO_PUBLIC_KEY=""
#ENV MAGENTO_PRIVATE_KEY=""

#ENV MAGENTO_USE_SOURCES_IN_HOST 0
#ENV CREATE_SYMLINK_EE 0
#ENV HOST_CE_PATH=""
#ENV EE_DIRNAME=""

#ENV MAGENTO_DOWNLOAD_SOURCES_COMPOSER 1
#ENV MAGENTO_EDITION="CE"
#ENV MAGENTO_VERSION="2.1.2"
#ENV MAGENTO_SAMPLE_DATA_INSTALL 0

#ENV MAGENTO_DOWNLOAD_SOURCES_CLOUD 0
#ENV MCLOUD_USERNAME=""
#ENV MCLOUD_PASSWORD=""
#ENV MCLOUD_GENERATE_NEW_TOKEN 0
#ENV MCLOUD_PROJECT=""
#ENV MCLOUD_BRANCH=""

#ENV MAGENTO_CRON_RUN 1
#ENV MAGENTO_DI_COMPILE 0
#ENV MAGENTO_GRUNT_COMPILE 0
#ENV MAGENTO_STATIC_CONTENTS_DEPLOY 0

#ENV MAGENTO_BACKEND_PATH="admin"
#ENV MAGENTO_ADMIN_USER="admin"
#ENV MAGENTO_ADMIN_PASSWORD="admin123"

# Initial scripts
COPY scripts/ /home/magento2/scripts/
RUN cd /home/magento2/scripts && composer install && chmod +x /home/magento2/scripts/m2init

# Delete user password to connect with ssh with empty password
RUN passwd magento2 -d

EXPOSE 80 22 44100
WORKDIR /home/magento2

USER root

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
