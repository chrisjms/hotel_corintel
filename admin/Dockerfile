FROM php:8.2-apache
COPY . /var/www/html/
RUN a2enmod rewrite
RUN echo "DirectoryIndex index.html index.php" >> /etc/apache2/apache2.conf
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf
EXPOSE 80