#!/bin/bash

# this script should be executed from within docker container mubu_apache

sudo chown 1000:1000 . -Rf
sudo rm /app/var/cache/dev /app/var/cache/test /app/var/cache/prod /app/var/sessions/* /app/var/spool.mails/* -Rf
sudo chown 1000:1000  /root/.composer/ -Rf
php /app/bin/console cache:warmup
sudo chmod 777 /app/var/cache/ -Rf
sudo chmod 777 /app/var/log/ -Rf

echo "https://$(ip -4 addr show eth0 | grep -oP '(?<=inet\s)\d+(\.\d+){3}')/app_dev.php/"
echo "https://$(ip -4 addr show eth0 | grep -oP '(?<=inet\s)\d+(\.\d+){3}')/"

