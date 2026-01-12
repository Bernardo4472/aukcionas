# Atnaujinimo instrukcijos / Update Instructions

## 1. Atnaujinti koda (Update Code Only)

```bash
cd /home/stud/aukcionas
git pull
sudo rm -rf /var/www/html/aukcionas
sudo cp -r /home/stud/aukcionas /var/www/html/
sudo chown -R www-data:www-data /var/www/html/aukcionas
sudo chmod -R 755 /var/www/html/aukcionas
sudo systemctl restart apache2
```

## 2. Atnaujinti duomenu baze (Update Database - DELETES ALL DATA)

```bash
sudo mysql -u root -e "DROP DATABASE IF EXISTS aukcionas; CREATE DATABASE aukcionas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -u root aukcionas < /home/stud/aukcionas/database.sql
```

## 3. Pilnas atnaujinimas (Full Update - Code + Database)

```bash
cd /home/stud/aukcionas
git pull
sudo mysql -u root -e "DROP DATABASE IF EXISTS aukcionas; CREATE DATABASE aukcionas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -u root aukcionas < /home/stud/aukcionas/database.sql
sudo rm -rf /var/www/html/aukcionas
sudo cp -r /home/stud/aukcionas /var/www/html/
sudo chown -R www-data:www-data /var/www/html/aukcionas
sudo chmod -R 755 /var/www/html/aukcionas
sudo systemctl restart apache2
```

## Prieiga / Access

```
http://localhost/aukcionas
```
