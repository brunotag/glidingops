#!/bin/sh

# If you would like to do some extra provisioning you may
# add any commands you wish to this file and they will
# be run after the Homestead machine is provisioned.
#
# If you have user-specific configurations you would like
# to apply, you may also create user-customizations.sh,
# which will be run after this script.
php73

cd code/lrv
composer install -n

php artisan migrate
php artisan db:seed

sudo apt-get install php7.3-xdebug -y

phpini=/etc/php/7.3/fpm/conf.d/20-xdebug.ini #$(php -i | grep "Loaded Configuration File" | sed 's#.* ##')
sudo sed -i -e '$axdebug.remote_enable = true' $phpini
sudo sed -i -e '$axdebug.remote_autostart = true' $phpini

sudo apt-get install net-tools -y
ipaddr=$(route -nee | awk '{ print $2 }' | sed -n 3p)
sudo sed -i -e "\$axdebug.remote_host = $ipaddr" $phpini

sudo sed -i -e '$axdebug.remote_port = 9003' $phpini
sudo sed -i -e '$axdebug.remote_log = /var/log/xdebug.log' $phpini
sudo sed -i -e '$axdebug.max_nesting_level = 1000' $phpini
sudo sed -i -e '$axdebug.mode=debug,develop' $phpini
sudo sed -i -e '$axdebug.idekey=vagrant' $phpini

sudo service php7.3-fpm restart
sudo systemctl restart apache2