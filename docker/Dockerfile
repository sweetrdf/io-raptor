FROM php:8.2-cli

# install and setup required system library
RUN apt-get update && apt-get install -y --no-install-recommends \
        curl git gnupg libicu-dev libpng-dev libzip-dev nano net-tools raptor2-utils sudo unzip wget zlib1g-dev
RUN docker-php-ext-install -j$(nproc) intl zip

# install Composer globally (required to install PHP vendors)
RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

# add a non-root user to limit user rights
RUN useradd -r --home /home/raptor -u 1000 raptor
RUN usermod -a -G www-data raptor
RUN mkdir /home/raptor
RUN chown raptor:www-data /home/raptor
RUN adduser raptor sudo

# create and mark working dir
RUN mkdir /raptor
WORKDIR /raptor

# run this after container started to keep it alive
CMD ["tail -f /dev/null"]
