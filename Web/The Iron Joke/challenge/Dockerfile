FROM php:8.0-apache

COPY ./src/ /var/www/html
RUN chown -R www-data:www-data /var/www/html
RUN rm /var/log/apache2/*
RUN apache2ctl restart
RUN chown -R root:www-data /var/log/apache2/
COPY files/flag.txt /
RUN chmod +r /flag.txt
RUN rm -rf /var/www/html/files/
RUN rm -rf /var/www/html/files/
COPY files/scr /bin/resetApache
RUN chmod +x /bin/resetApache
RUN apt-get update && apt-get -y install cron supervisor
COPY files/mycron /etc/cron.d/mycron
RUN chmod 0644 /etc/cron.d/mycron
RUN crontab /etc/cron.d/mycron
COPY files/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
CMD /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
