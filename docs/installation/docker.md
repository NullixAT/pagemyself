---
title: Installing PageMyself with Docker
description: The recommended way to install PageMyself
---

We recommend to install PageMyself as a docker service. With docker you have by default a great security level which is
always good.

Furthermore, we already have everything configured for you, with newest Nginx, Mariadb and PHP version. You don't need
to worry much about technical details, just run the docker container and your a good to go with your new homepage.

## A short setup video

<iframe width="100%" height="500" src="https://www.youtube.com/embed/hyQUmu4EHJA" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>

## Setup

You need `git`, `docker` and `docker-compose` installed. This things exist for almost every OS, even Windows and Mac.
More [on Docker here](https://docs.docker.com/get-docker/).

First, clone and modify environment variables:

    git clone https://github.com/NullixAT/pagemyself-docker.git
    cd pagemyself-docker
    cp config/env-default .env

Modify `.env` to your needs. There are 2 ports available inside the service:

* `80` for http handling. Example: `WEBPORT_MAP=8080:80`
* `443` for https handling. Example: `WEBPORT_MAP=8080:443`

You can swap `8080` to any port you like. It's the port from which your page is available.

SSL is default enabled with self signed certificates. You may get browser warnings when you open the page (which you can
bypass in case of localhost or in incognito mode). 

You can pass your own certificates. If you have no other webservice running on your host, you can modify `NGINX_SSL_CERT` and `NGINX_SSL_KEY`. If you not already have certificates, we recommend to use [Certbot](https://certbot.eff.org/).

However, recommended way is to have a separate webserver running on the host, which acts as a reverse proxy, which
handles certificates and other stuff. See config example for Nginx down bellow. With this way, you can setup multiple docker installations of PageMyself on one host and even have other services on the public port.

> If you change https/http and the app is already installed, you must modify `app/modules/Myself/config-editable.php` as well.

## Run

Star the docker service with:

    docker-compose up -d


Open `https://yourdomainorip:8080` and follow instructions in your browser. The container is configured to restart
always, also after host reboot.

All application source files and uploaded files in the application are in the folder `app`.

All database files are in the folder `db`.

## Install/Restore from an app backup

Follow this steps if you have made an app backup in PageMyself and you want to revert to the state of the backup.

> Warning: This step requires to delete all existing data and to shutdown the docker service.

The downloaded `backup.zip` contains 2 folders: `appdatabase` and `appdatabase`.

1. Shutdown the service with `docker-compose down`
2. Attention: Delete everything in `app` and delete everything in `db`
3. Copy the `backup.zip` into `app/backup.zip`
4. Start the container with `docker-compose up`
5. (Optional) Maybe you've moved from another installation, db or whatever to this docker container, you probably need
   to modify `modules/Myself/config-editable.php` db and other settings to your needs to make it fully functional

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
    }}