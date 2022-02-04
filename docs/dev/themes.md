---
title: Theme Development
---

If you are not satisfied with the built in theme or the official ones from the store, feel free to create your own theme.

To start, you have to [create your module](modules.md), if not done yet.

Then run following command line to create the theme boilerplate for you

    php modules/Framelix/console.php createTheme --module {YourModuleName} --theme {YourThemeName}

You should now have at least following files/folders:

    modules/YourModuleName/js/themes/YourThemeName/script.js
    modules/YourModuleName/public/themes/YourThemeName
    modules/YourModuleName/src/Themes/YourThemeName.php
    modules/YourModuleName/scss/themes/YourThemeName/style.scss

From this point, you can start developing your theme, but let's check each file what you should modify

### Reference

To learn from live examples, just check out the built-in `Hello` themes source files, it is a way easier to see how already existing things are made. Filepaths are the same as your new module, expect everything is in `Myself`.

### Compiled files for JS/SCSS

Compiled files comes automatically into `modules/YourModuleName/public/dist` and are updated each time you open any page where you theme is activated in the frontend.

### Javascript

`modules/YourModuleName/js/themes/YourThemeName/script.js` - That's where all your frontend javascript comes in. Use this to create dynamic stuff, navigation, etc... Pack all your code into the existing `initLate` function. You can use newest JS features, everything will be compiled back to cross browser code for you.

### SCSS

`modules/YourModuleName/scss/themes/YourThemeName/style.scss` - That's where all your CSS/SCSS comes in. You can write in bare CSS or you can use SCSS syntax as well. It will be automatically compiled to cross browser code for you.

### PHP

`modules/YourModuleName/src/Themes/YourThemeName.php` - Contains your complete theme HTML and logic. By default, this does not create any special HTML markup. Modify the boilerplate to your needs. The most easy way to learn how to build your custom markup, is by checking the built-in `Hello` themes code, which is located at `modules/Myself/Themes/Hello.php`. If you have any more questions to this, please ask us in our Slack Chat or open an issue with your questions.

### Public/Static files

You probably need static files to be included, like images, etc... This all comes into `modules/YourModuleName/public/themes/YourThemeName`. To create a URL to a static file, use `Url::getUrlToFile($filePath)` - This will create a valid URL to a file on disk.




