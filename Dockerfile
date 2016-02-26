FROM php:7.0.1-apache

# install needed things
RUN apt-get update && apt-get install -y mysql-client libmysqlclient-dev \
      && docker-php-ext-install mysqli
    # Initialize html and php.ini

# Create a volume !
VOLUME /var/www

# Copy the project ! Doesn't work :(
COPY . /var/www

# Copy php.ini files
COPY php.ini-development /usr/local/etc/php/php.ini
COPY php.ini-development /usr/local/etc/php/
COPY php.ini-production /usr/local/etc/php/

# Update the default apache site with the config we created.
COPY vhosts.conf /etc/apache2/sites-enabled/000-default.conf

#Creates apache needed folders
RUN mkdir /etc/apache2/logs

# Enable apache mods.
#RUN a2enmod php5
RUN a2enmod deflate && a2enmod env && a2enmod expires && a2enmod filter && a2enmod headers && a2enmod rewrite

ENV TERM xterm
