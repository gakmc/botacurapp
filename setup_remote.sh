#!/bin/bash
set -e
sudo apt-get update -qq
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server
sudo systemctl start mysql
sudo systemctl enable mysql
sudo mysql -e "CREATE DATABASE IF NOT EXISTS cbo56863_botacurapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE DATABASE IF NOT EXISTS cbo56863_botacura_iot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'cbo56863'@'localhost' IDENTIFIED BY 'gZbQTjPFVYDzRzTdNmmA';"
sudo mysql -e "ALTER USER 'cbo56863'@'localhost' IDENTIFIED BY 'gZbQTjPFVYDzRzTdNmmA';"
sudo mysql -e "GRANT ALL PRIVILEGES ON cbo56863_botacurapp.* TO 'cbo56863'@'localhost';"
sudo mysql -e "GRANT ALL PRIVILEGES ON cbo56863_botacura_iot.* TO 'cbo56863'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
echo "MySQL OK"