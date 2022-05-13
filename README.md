[![logo](docs/media/logo-github.png)](https://scripts.0x.at/github-webhooks/slack-join/)

## PageMyself - Fast, easy and powerful website generator

> We try to give you the tools to create your private/company website in no time and without coding skills.

### Features

* 👍 Self-hosted and open source
* WYSIWYG 👀 What you see is what you get - Live edit text and other information directly on the website
* Easy installation 💪 Setup just take a few commands
* Multilanguage interface 👄 Currently there is english and german, but plan is to integrate open-source translations as
  well

## Demo

Everything is easier to understand 👀 when you get your hands on our demo page at https://demo.pagemyself.com/

### Requirements

PageMyself runs in a `docker` container, so you have to `docker` and `docker-compose`. All of them are available on
almost every OS. Docker give you an extra layer of security and a simple setup process for PageMyself.

### Installation

#### On windows it recommended to use a WSL ubuntu instance which is also linux in the end

```
mkdir pagemyself
cd pagemyself
wget https://github.com/NullixAT/pagemyself/releases/latest/download/docker-release.tar -O docker-release.tar
tar xf docker-release.tar
rm docker-release.tar  
cp config/env-default .env
docker-compose up -d --build
```

Open `https://yourdomainorip:8686` and follow instructions in your browser. The container is configured to restart
always, also after host reboot. For more help on configuring the docker instance,
goto [our docker implementation repository](https://github.com/NullixAT/framelix-docker).

### Team

This project was created by me, [brainfoolong](https://github.com/brainfoolong). I hope that this evolves and the
open-source team can grow. I do this in my spare time beside my full-time job as a web-dev. Let us discus about ideas
here in Github issues or you can join [my slack chat](https://scripts.0x.at/github-webhooks/slack-join/). I try to be as
active as possible.

### Based on Framelix

This project is based on the [PHP 8.1+ Framework: Framelix](https://github.com/NullixAT/framelix-core) which is
primarely designed for backend applications but also work well for PageMyself as it is highly customizable.

### Development of themes

The system is prepared for theme development. We are currently working on a tutorial on how the create your own theme.