---
title:  Installing PageMyself by Hand with a release file
---

If you can't or don't want to install docker, you can install PageMyself the good old way, by hand.

## Requirements

You require a PHP 8.1 installation, a running Mysql/MariaDB database and a running webserver like Nginx with PHP enabled.
 
## Setup

Once you have all requirements:

1. Download [latest release package](https://github.com/NullixAT/pagemyself/releases/latest): release-x.x.x.zip - >
   Which contains `install.php`, `package.zip` and a few other files. Do not unpack `package.zip` by hand, this is
   handled by the install script.
2. Upload to your webserver and unpack
3. Open `https://yourdomain/install.php` in your browser, or whatever path you have choosen

For Apache, enable `.htaccess parsing` and `mod_rewrite`  if not. There is a `.htaccess` file in the root folder of PageMyself, which handles required redirects.

For Nginx, use the example config down bellow.

## Cronjob
For proper automated tasks inside PageMyself, you require to setup a cronjob, a task that executes a script every 5 minutes.

For linux this is something like this:

    */5 * * * * php "/pathtoapproot/modules/Framelix/console.php" cron



## Install/Restore from an app backup

Follow this steps if you have made an app backup in PageMyself and you want to revert to the state of the backup.

> Warning: This step requires to delete all existing data

The downloaded `backup.zip` contains 2 folders: `appdatabase` and `appdatabase`.

1. Drop your existing database, recreate it and import the SQL backup from the folder `appdatabase`
2. Delete all files from your webhosting folder that contains PageMyself and unpack all files  from `appdatabase` into this now empty folder

### Example Nginx Config

    server {
        listen 443 ssl http2;
        listen [::]:443 ssl http2;
        root /path-to-app-root;
        server_name yourdomain.com;
        ssl_certificate     /pathtosslcert.pem;
        ssl_certificate_key /pathtosslkey.pem;    
        client_max_body_size 100M;
        # aggresive caching as we use anti-cache parameter anyway
        location ~* \.(woff|woff2|ttf|otf|svg|js|css|png|jpg|jpeg|gif|ico|webp)$ {
            expires 1y;
            log_not_found off;
        }
    
        # try file, folder and at least route to index.php
        location / {
            try_files $uri $uri/ @nofile;
        }
    
        # route every non existing file to index.php
        location @nofile{
            rewrite (.*) /index.php;
        }
    
        # php handling
        location ~ \.php$ {
            fastcgi_pass phpfpm:9000;
            fastcgi_index index.php;
            include fastcgi.conf;
        }
    
        # rewrite urls starting with @ points to another module
        rewrite ^/@([A-Za-z0-9]+)/(.*) /../../$1/public/$2 last;
    
        index index.php;
    
        client_max_body_size 100M;
    
        # some security options
        add_header X-Content-Type-Options nosniff;
        add_header X-Frame-Options "SAMEORIGIN";
        add_header X-XSS-Protection "1; mode=block";
        add_header X-Download-Options noopen;
        add_header X-Permitted-Cross-Domain-Policies none;
        add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;    
    