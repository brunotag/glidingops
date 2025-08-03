#!/bin/sh

# If you would like to do some extra provisioning you may
# add any commands you wish to this file and they will
# be run after the Homestead machine is provisioned.
#
# If you have user-specific configurations you would like
# to apply, you may also create user-customizations.sh,
# which will be run after this script.

# Source the user's bashrc to ensure helper functions like 'php74' are available
. /home/vagrant/.bashrc

php74

cd code/lrv
composer install -n --prefer-dist --no-progress

php artisan migrate
php artisan db:seed

sudo apt-get install -y php7.4-xdebug net-tools xsltproc
sudo a2enmod proxy_fcgi setenvif
sudo a2enconf php7.4-fpm

sudo sed '/^zend_extension=/{h;s/=.*/=xdebug.so/};${x;/^$/{s//zend_extension=xdebug.so/;H};x}' -i /etc/php/7.4/fpm/php.ini

xdebug_ini=/etc/php/7.4/mods-available/xdebug.ini #$(php -i | grep "Loaded Configuration File" | sed 's#.* ##')

sudo sed '/^zend_extension=/{h;s/=.*/=xdebug.so/};${x;/^$/{s//zend_extension=xdebug.so/;H};x}' -i $xdebug_ini
sudo sed '/^xdebug.max_nesting_level=/{h;s/=.*/=512/};${x;/^$/{s//xdebug.max_nesting_level=512/;H};x}' -i  $xdebug_ini

ipaddr=$(route -nee | awk '{ print $2 }' | sed -n 3p)
sudo sed "/^xdebug.client_host=/{h;s/=.*/=$ipaddr/};\${x;/^\$/{s//xdebug.client_host=$ipaddr/;H};x}" -i  $xdebug_ini

sudo sed '/^xdebug.client_port=/{h;s/=.*/=9003/};${x;/^$/{s//xdebug.client_port=9003/;H};x}' -i  $xdebug_ini
sudo sed '/^xdebug.log=/{h;s/=.*/=\/var\/log\/xdebug.log/};${x;/^$/{s//xdebug.log=\/var\/log\/xdebug.log/;H};x}' -i  $xdebug_ini
sudo sed '/^xdebug.mode=/{h;s/=.*/=debug/};${x;/^$/{s//xdebug.mode=debug/;H};x}' -i  $xdebug_ini
sudo sed '/^xdebug.idekey=/{h;s/=.*/=vagrant/};${x;/^$/{s//xdebug.idekey=vagrant/;H};x}' -i  $xdebug_ini
sudo sed '/^xdebug.start_with_request=/{h;s/=.*/=yes/};${x;/^$/{s//xdebug.start_with_request=yes/;H};x}' -i  $xdebug_ini

sudo service php7.4-fpm restart
sudo systemctl restart apache2
