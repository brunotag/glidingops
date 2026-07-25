#!/bin/bash
# =============================================================================
# glidingops - Production Server Setup Script
# Ubuntu 24.04 LTS + Apache 2.4 + PHP 8.3 + MySQL 8.0
# =============================================================================
# Usage:
#   1. Provision a fresh Ubuntu 24.04 LTS VPS (Vultr / your provider)
#   2. ssh root@<new-ip>
#   3. Copy this script to the server and run:
#        chmod +x setup.sh
#        ./setup.sh
#   4. Follow the manual steps at the end of this script
# =============================================================================
set -euo pipefail

# ---- Config (placeholders — replace with actual values from docs/_secrets.md) ----
DOMAIN="gops.wwgc.co.nz"
ADMIN_EMAIL="bruno.tagliapietra@gmail.com"

MYSQL_ROOT_PASSWORD="{{MYSQL_ROOT_PASSWORD}}"
GLIDING_DB_USER="admin"
GLIDING_DB_PASS="{{GLIDING_DB_PASS}}"
TRACKS_DB_USER="track"
TRACKS_DB_PASS="{{TRACKS_DB_PASS}}"
PARTICLETRACK_DB_USER="particletrack"
PARTICLETRACK_DB_PASS="{{PARTICLETRACK_DB_PASS}}"

# =============================================================================
# 1. System packages
# =============================================================================
echo ">>> Updating system packages..."
apt update -y && apt upgrade -y

echo ">>> Installing Apache, MySQL, PHP 8.3, and utilities..."
apt install -y apache2 mysql-server git curl zip unzip \
  software-properties-common ca-certificates lsb-release \
  certbot python3-certbot-apache \
  php8.3 php8.3-cli php8.3-curl php8.3-xml php8.3-mysql \
  php8.3-mbstring php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl \
  php8.3-imagick

# =============================================================================
# 2. Apache configuration
# =============================================================================
echo ">>> Configuring Apache..."

a2enmod rewrite
a2enmod headers
systemctl restart apache2

# Allow .htaccess overrides
sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Set ServerName to suppress AH00558 warning
echo "ServerName 127.0.0.1" >> /etc/apache2/apache2.conf

# Uncomment Mutex directive (needed for rewrite/SSL on some systems)
sed -i 's/#Mutex/Mutex/' /etc/apache2/apache2.conf

# HTTP → HTTPS redirect
sed -i '/^<\/VirtualHost>/i \    Redirect permanent / https:\/\/gops.wwgc.co.nz\/' /etc/apache2/sites-enabled/000-default.conf

# UFW
ufw allow "Apache Full"

# =============================================================================
# 3. MySQL secure installation
# =============================================================================
echo ">>> Securing MySQL..."

mysql <<EOF
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${MYSQL_ROOT_PASSWORD}';
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test_%';
FLUSH PRIVILEGES;
EOF

# =============================================================================
# 4. Create databases and application users
# =============================================================================
echo ">>> Creating databases and users..."

mysql -u root -p"${MYSQL_ROOT_PASSWORD}" <<EOF
CREATE DATABASE IF NOT EXISTS gliding CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS tracks CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS particletrack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS '${GLIDING_DB_USER}'@'localhost' IDENTIFIED WITH mysql_native_password BY '${GLIDING_DB_PASS}';
GRANT ALL PRIVILEGES ON gliding.* TO '${GLIDING_DB_USER}'@'localhost';

CREATE USER IF NOT EXISTS '${TRACKS_DB_USER}'@'localhost' IDENTIFIED WITH mysql_native_password BY '${TRACKS_DB_PASS}';
GRANT ALL PRIVILEGES ON tracks.* TO '${TRACKS_DB_USER}'@'localhost';

CREATE USER IF NOT EXISTS '${PARTICLETRACK_DB_USER}'@'localhost' IDENTIFIED WITH mysql_native_password BY '${PARTICLETRACK_DB_PASS}';
GRANT ALL PRIVILEGES ON particletrack.* TO '${PARTICLETRACK_DB_USER}'@'localhost';

FLUSH PRIVILEGES;
EOF

# =============================================================================
# 5. Clone application
# =============================================================================
echo ">>> Cloning glidingops..."

cd /var/www/html
rm -f index.html

git clone https://github.com/brunotag/glidingops.git .

# =============================================================================
# 6. gops-reporting (monthly dashboard)
# =============================================================================
echo ">>> Cloning gops-reporting..."

mkdir -p /var/local/gops-reporting
cd /var/local/gops-reporting
git clone https://github.com/brunotag/gopsreporting.git .
composer install --no-dev -n

# =============================================================================
# 7. Composer install (Laravel dependencies)
# =============================================================================
echo ">>> Installing Composer..."
curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer

cd /var/www/html/lrv
composer install --no-dev -n

php artisan config:clear
php artisan config:cache

# =============================================================================
# 8. Laravel .env
# =============================================================================
echo ">>> Writing Laravel .env..."

cat > /var/www/html/lrv/.env <<EOF
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:m8XNVK0wYvRJoDLfIcBYuK+/vZdmTP2+g8A1dPOOEUc=

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=gliding
DB_USERNAME=${GLIDING_DB_USER}
DB_PASSWORD=${GLIDING_DB_PASS}

DB_TRACKS_DATABASE=tracks
DB_TRACKS_USERNAME=${TRACKS_DB_USER}
DB_TRACKS_PASSWORD=${TRACKS_DB_PASS}
EOF

# =============================================================================
# 9. Application config/database.php
# =============================================================================
echo ">>> Writing config/database.php..."

cat > /var/www/html/config/database.php <<EOF
<?php
return [
    'gliding' => [
        'username' => '${GLIDING_DB_USER}',
        'password' => '${GLIDING_DB_PASS}',
        'hostname' => 'localhost',
        'dbname'   => 'gliding'
    ],
    'tracks' => [
        'username' => '${TRACKS_DB_USER}',
        'password' => '${TRACKS_DB_PASS}',
        'hostname' => '127.0.0.1',
        'dbname'   => 'tracks'
    ],
    '48d5f377' => [
        'username' => '${PARTICLETRACK_DB_USER}',
        'password' => '${PARTICLETRACK_DB_PASS}',
        'hostname' => '127.0.0.1',
        'dbname'   => 'particletrack'
    ]
];
EOF

# =============================================================================
# 10. Permissions
# =============================================================================
echo ">>> Setting file permissions..."

chmod -R 755 /var/www/html/storage 2>/dev/null || true
chown -R www-data:www-data /var/www/html/storage 2>/dev/null || true
chown -R www-data:www-data /var/www/html/log 2>/dev/null || true

# Ensure log directory exists and is writable
mkdir -p /var/www/html/log
chown www-data:www-data /var/www/html/log

# =============================================================================
# 11. SSL certificate (Let's Encrypt)
# =============================================================================
echo ">>> Obtaining SSL certificate..."

certbot --apache -d "${DOMAIN}" --non-interactive --agree-tos -m "${ADMIN_EMAIL}"

# =============================================================================
# 12. Apache environment variables (for Google Maps API key, etc.)
# =============================================================================
echo ">>> Setting Apache environment variables..."

# Insert before the closing </VirtualHost> in the SSL VirtualHost
sed -i '/^<\/VirtualHost>/i \    SetEnv MAP_API_KEY {{MAP_API_KEY}}' /etc/apache2/sites-enabled/000-default-le-ssl.conf
sed -i '/^<\/VirtualHost>/i \    SetEnv SMS_KEY {{SMS_KEY}}' /etc/apache2/sites-enabled/000-default-le-ssl.conf
sed -i '/^<\/VirtualHost>/i \    SetEnv SMS_HOST https://loc.nz/api/sms/v1/send' /etc/apache2/sites-enabled/000-default-le-ssl.conf

# =============================================================================
# 13. Journald — limit logs to 100M
# =============================================================================
sed -i 's/#SystemMaxUse=/SystemMaxUse=100M/' /etc/systemd/journald.conf
systemctl restart systemd-journald

# =============================================================================
# 14. Disk space alert
# =============================================================================
cat > /opt/disk-alert.sh <<'EOF'
#!/bin/bash
CURRENT=$(df / | grep / | awk '{ print $5}' | sed 's/%//g')
THRESHOLD=90
if [ "$CURRENT" -gt "$THRESHOLD" ] ; then
  mail -s "GOPS - Disk Space Alert" -a "From: machinery.gops@wwgc.co.nz" \
    it@wwgc.co.nz, bruno.tagliapietra@gmail.com <<< "GOPS disk space is critically low. Used: $CURRENT%"
fi
EOF
chmod 755 /opt/disk-alert.sh

# =============================================================================
# 15. Crontab
# =============================================================================
mkdir -p /media/mysqldump
cat > /tmp/gops-crontab <<EOF
# SPOT tracking (overnight hours UTC = daytime NZ)
*/2 20-23 * * * sudo php /var/www/html/GetSpotTask.php -o 1
*/2 00-07 * * * sudo php /var/www/html/GetSpotTask.php -o 1

# Flarm/OGN tracking (every minute)
* * * * * cd /var/www/html && sudo php getFlarmTask.php > /var/log/getFlarmTask.log

# Day summary email to ops manager and service delivery
0 6 * * * cd /var/www/html && sudo php DayTimes.php -m operationsmanager@wwgc.co.nz
0 6 * * * cd /var/www/html && sudo php DayTimes.php -m servicedelivery@wwgc.co.nz

# Monthly gops-reporting (1st of month)
0 0 1 * * cd /var/local/gops-reporting && sudo php main.php \$(date --date="yesterday" "+%m %Y")

# Database backups (daily at noon)
0 12 * * * mysqldump -u root -p${MYSQL_ROOT_PASSWORD} gliding | gzip > /media/mysqldump/gliding-\$(date +\%Y\%m\%d).sql.gz
0 12 * * * mysqldump -u root -p${MYSQL_ROOT_PASSWORD} tracks | gzip > /media/mysqldump/tracks-\$(date +\%Y\%m\%d).sql.gz
0 12 * * * mysqldump -u root -p${MYSQL_ROOT_PASSWORD} particletrack | gzip > /media/mysqldump/particletrack-\$(date +\%Y\%m\%d).sql.gz

# Rotate backups (keep 30 days)
0 12 * * * find /media/mysqldump -type f -mtime +30 -delete

# Disk space alert
0 12 * * * sudo /opt/disk-alert.sh
EOF
crontab /tmp/gops-crontab
rm /tmp/gops-crontab

# =============================================================================
# 16. Restart Apache
# =============================================================================
systemctl restart apache2

echo ""
echo "============================================"
echo "  Automated setup complete!"
echo "============================================"
echo ""
echo "=== MANUAL STEPS REQUIRED ==="
echo ""
echo "1. RESTORE DATABASES"
echo "   Copy backups from old server, then:"
echo "     gunzip < gliding-YYYYMMDD.sql.gz | mysql -u root -p'${MYSQL_ROOT_PASSWORD}' gliding"
echo "     gunzip < tracks-YYYYMMDD.sql.gz | mysql -u root -p'${MYSQL_ROOT_PASSWORD}' tracks"
echo "     gunzip < particletrack-YYYYMMDD.sql.gz | mysql -u root -p'${MYSQL_ROOT_PASSWORD}' particletrack"
echo ""
echo "2. CLEAR SESSIONS (force all users to re-login)"
echo "     rm -f /var/lib/php/sessions/*"
echo ""
echo "3. CONFIG FILES TO CREATE (gitignored — see samples and docs/_secrets.md)"
echo ""
echo "   a) /var/www/html/config/mail.php (SMTP — template: config/mail.php.sample)"
echo "      Dev: localhost:1025, no auth. Production: smtp.gmail.com:465,"
echo "      machinery.gops@wwgc.co.nz with app password."
echo ""
echo "   b) /var/www/html/config/oauth.php (OAuth — template: config/oauth.php.sample)"
echo "      Google + Facebook client IDs and secrets."
echo ""
echo "   c) /var/www/html/config/google-calendar.php"
echo "      Calendar ID + service account key path."
echo "      Also copy google-calendar-key.json to lrv/storage/"
echo ""
echo "   d) /var/www/html/config/site.php"
echo "      Maps API key (MAP_API_KEY), calendar IDs."
echo ""
echo "4. GOOGLE SERVICE ACCOUNT (DB BACKUPS — OPTIONAL)"
echo "   See README.md for rclone setup to sync backups to Google Shared Drive."
echo ""
echo "5. MEMBER PHOTOS"
echo "   No longer synced from Google Drive. Uploaded via /MemberNew form."
echo "   Copy old img/members/ directory if needed."
echo "   Directory is created automatically and must be www-data writable."
echo ""
echo "6. GOPS-REPORTING CONFIG"
echo "   Cloned to /var/local/gops-reporting but still needs:"
echo "     - /var/local/gops-reporting/config.json (see config.live for template)"
echo "     - /var/local/gops-reporting/google_sheet_cred.json (service account key)"
echo ""
echo "7. SFTP WEBCAM UPLOADS"
echo "   See README.md step 8 for setup commands."
echo ""
echo "8. DNS"
echo "   Point ${DOMAIN} to the new server's IP address."
echo ""
echo "9. VERIFY"
echo "   - Visit https://${DOMAIN}/ and log in"
echo "   - Check /Logs for any errors"
echo "   - Run a test flight entry"
echo "   - Verify tracking data on the map"
echo ""
echo "10. OLD SERVER"
echo "   - Keep old server running for at least 48h"
echo "   - Monitor both for issues"
echo "   - Deprovision old server after confirming everything works"