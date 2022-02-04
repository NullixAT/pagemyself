---
title: PageMyself Development Setup
---

To start developing your own stuff, you need to setup PageMyself in your development environment.

It is highly recommended to use our [docker installation](../installation/docker.md), but you can do setup [manually](../installation/manual.md) as well.

## Requirements
You have the same requirements as the normal installation requires plus you must install `NodeJS`, this is for the babel JS and scss compilers.

## Setup
Almost everything is the same as an above linked help, but instead of using a `release` file, you must clone this whole repository because it contains all development files as well.

For docker, clone this repository into the `app` folder. For manual installation, just clone to a folder you want. After that you have to run `npm install` in the module `Framelix` to install the compilers.

Here is how:

    git clone https://github.com/NullixAT/pagemyself pagemyself
    cd pagemyself/modules/Framelix
    npm install

After that, just open the root of your folder in your browser, you should be guided through the first time setup as in the regular installation.

After the installation, add a new config flag `"devMode": true` to enable developer mode into `modules/Myself/config-editable.php`

    <script type="application/json">
        {
        "devMode": true,
        "applicationHttps": true,
        ....

That's all. We recommend PhpStorm or VisualStudio Code to develop with PageMyself. But if you are bad-ass, just use `vi`. Whatever you like.
