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

This is what we use to run the pagemyself docker service through a nginx proxy.

    server {
        listen 443 ssl http2;
        listen [::]:443 ssl http2;
        root /path-to-app-docker-root/app;
        server_name yourdomain.com;
        ssl_certificate     /pathtosslcert.pem;
        ssl_certificate_key /pathtosslkey.pem;    
        client_max_body_size 100M;
        location / {
            proxy_pass http://127.0.0.1:7001;
            proxy_set_header Host $host;
            proxy_set_header X-Forwarded-For $remote_addr;
            proxy_set_header X-Forwarded-Proto https;
            proxy_ssl_server_name on;
        }
    }