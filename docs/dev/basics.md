---
title: PageMyself Development - The basics
description: Learn how the general structure works.
---

## Introduction - Why it is, how it is

PageMyself is built on top of [Framelix](https://github.com/NullixAT/framelix-core), a new full-stack PHP 8.1+ framework, also made by the devs of PageMyself.

Basically, you must have at least good basic knowledge of PHP, JS, HTML and CSS to do any development for PageMyself. Just copy paste from anywhere without knowing what the things really do is never a good practice.

We are explicitely using NO fancy single-page libraries, like Vue, etc...,  which are itself good for sure, but it's not how Framelix/PageMyself was designed. Damn, we also don't use jQuery , but a similar library called [cash](https://github.com/fabiospampinato/cash).

As Framelix was built from the ground up only for PHP 8.1+. We are free to use advantages of latest PHP features without backward incompatibility. So, you may find stuff and syntax you are not already familar with.

Everything in PageMyself is built in `modules`. The core is a module, PageMyself is a module and so is every other separate thing is a module.

For reference, it is always good to use existing modules for learning and to see how things are made. Here are a few official PageMyself modules that are available in the built-in module store:

* [Calendar](https://github.com/NullixAT/pagemyself-calendar)
* [Contact Form](https://github.com/NullixAT/pagemyself-contactform)
* [Guestbook](https://github.com/NullixAT/pagemyself-guestbook)
* [Code Docs (That's what this documentation is powered by)](https://github.com/NullixAT/pagemyself-docs)
* [Slideshow](https://github.com/NullixAT/pagemyself-slideshow)
* [Image Gallery](https://github.com/NullixAT/pagemyself-imagegallery)

There are 2 things that are possibly the things you need first:
1. You want to make an own [theme](themes.md)
2. You want to make an own [page-block](pageblocks.md) (A block inside an existing page, like Text for example)