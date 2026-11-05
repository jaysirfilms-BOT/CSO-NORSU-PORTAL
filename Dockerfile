FROM php:8.2-apache

# Extensions this app needs: mysqli (database), mbstring (functions.php),
# gd + fileinfo (image upload validation in includes/functions.php)
RUN apt-get update && apt-get install -y libpng-dev libzip-dev \
    && docker-php-ext-install mysqli mbstring gd \
    && docker-php-ext-enable mysqli mbstring gd

# Apache: serve the app from the repo root, enable .htaccess (used by
# assets/uploads/.htaccess to block execution of uploaded files)
RUN a2enmod rewrite
COPY . /var/www/html/
# Belt and braces: even if .dockerignore is missing, none of these ever stay in the image.
RUN rm -rf /var/www/html/.git /var/www/html/test_mail.php /var/www/html/config/secrets.php /var/www/html/database /var/www/html/*.md \
    && find /var/www/html/private/ids -type f ! -name '.gitkeep' -delete 2>/dev/null || true

# Production PHP + Apache hardening: no version banners, no error output to visitors.
RUN { echo 'expose_php=Off'; echo 'display_errors=Off'; echo 'log_errors=On'; echo 'session.use_strict_mode=1'; echo 'session.cookie_httponly=1'; } > /usr/local/etc/php/conf.d/zz-security.ini \
    && printf 'ServerTokens Prod\nServerSignature Off\n' > /etc/apache2/conf-available/zz-security.conf \
    && a2enconf zz-security && a2enmod headers
RUN sed -ri -e 's!/var/www/html!/var/www/html!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Writable uploads folder
RUN chown -R www-data:www-data /var/www/html/assets/uploads

# Railway/Render inject $PORT; Apache needs to listen on it
RUN sed -ri -e 's/Listen 80/Listen ${PORT:-80}/g' /etc/apache2/ports.conf \
    && sed -ri -e 's/:80>/:${PORT:-80}>/g' /etc/apache2/sites-available/000-default.conf
ENV PORT=80
EXPOSE 80

CMD ["sh", "-c", "apache2-foreground"]
