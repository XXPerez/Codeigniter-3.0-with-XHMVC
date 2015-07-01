# Cross Modules for Codeigniter 3.0

## What is Cross Modules

Based on Wiredesign HMVC, (Hiererchical Model/View/Contoller), XHMVC allow to have common modules shared betweeen all applications. Not only to share common modules between apps located under /apps directory, also you can re-use any module between different projects only making copy/paste.

Take alook to this explain:  http://xhmvc.4amics.com/applications/demo/www/user_guide/

Demo : http://xhmvc.4amics.com/applications/demo/www/

## Composer ready

Edit the composer.json to add any package that you want. You can use it in any controller, model or view.

```composer
{
    "require-dev": {
        "phpunit/phpunit": "4.7.*"
    }
}
```

## Installation

XHMVC is ready-to-go, you only need to extract to any directory under a document root of your choice.

Needed: Apache (mod_rewrite enabled for a ready-to-go functionality) + PHP (5.3).

  * Extract all files under your web workspace (as configured in your Xampp/Wampp), extract, for example, as 'xhmvc'
  * Browse demo as: http://localhost/xhmvc/applications/demo/www

## Other functions installed

  * XHMVC have the Xtends, a module extender that allow to extend your controller from a common controller, and add functionallity to base methods.
  * XCache and XDBCache, A cache system ussing files, memcache, apc, mongodb, easy extensible to other drivers.
  * Profiler - A usefull profiler to see what is happen



