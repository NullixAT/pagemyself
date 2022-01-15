FROM bitnami/php-fpm:8.1-prod

RUN install_packages cron && mkdir -p /etc/cron.pagemyself
COPY cronjobs /etc/cron.pagemyself/cronjobs
COPY php-fpm-entrypoint.sh /
COPY php.ini /opt/bitnami/php/etc/php.ini
RUN chmod -R 0755 /etc/cron.pagemyself
RUN chmod 0755 /php-fpm-entrypoint.sh
RUN crontab /etc/cron.pagemyself/cronjobs && touch /var/log/cron.log
ENTRYPOINT [ "/php-fpm-entrypoint.sh" ]
