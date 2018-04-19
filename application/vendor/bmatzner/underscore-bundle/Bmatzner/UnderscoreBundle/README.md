# Underscore.js Bundle for Symfony2

## Current Version

Underscore.js v1.6.0

## Installation

### Add bundle to your composer.json file

``` js
// composer.json

{
    "require": {
		// ...
        "bmatzner/underscore-bundle": "~1.6"
    }
}
```

### Add bundle to your application kernel

``` php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Bmatzner\UnderscoreBundle\BmatznerUnderscoreBundle(),
        // ...
    );
}
```

### Download the bundle using Composer

``` bash
$ php composer.phar update bmatzner/underscore-bundle
```

### Install assets

Given your server's public directory is named "web", install the public vendor resources

``` bash
$ php app/console assets:install web
```

Optionally, use the --symlink attribute to create links rather than copies of the resources 

``` bash
$ php app/console assets:install --symlink web
```

## Usage

Refer to the desired files in your HTML template, e.g.

``` html
<script type="text/javascript" src="{{ asset('bundles/bmatznerunderscore/js/underscore.min.js') }}"></script>
```

## Licenses

Refer to the source code of the included files for license information

## References

1. http://documentcloud.github.com/underscore/
2. http://symfony.com/
