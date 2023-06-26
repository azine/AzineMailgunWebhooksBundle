#!/usr/bin/env bash

mkdir -p touch /app/var/log
touch /app/var/log/dev.log
echo "" >>  /app/var/log/dev.log
echo "" >>  /app/var/log/dev.log
echo "" >>  /app/var/log/dev.log
echo "#######################################################" >>  /app/var/log/dev.log
echo "" >>  /app/var/log/dev.log
echo "Started Docker Container" >>  /app/var/log/dev.log
echo ""
echo ""
echo ""
echo ""
echo "Symfony Router Configuration:"
php bin/console debug:router
echo "IP configuration:"
ip a | grep 172
echo ""
echo ""
echo "tail-ing dev.log..."
sleep 5
/usr/bin/tail -f /app/var/log/dev.log