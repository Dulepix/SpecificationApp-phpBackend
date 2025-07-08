# Koristi zvaničnu PHP + Apache sliku
FROM php:8.2-apache

# Kopira ceo repozitorijum u Apache-ov web root
COPY . /var/www/html/

# Otvori port 80 za Render
EXPOSE 80
