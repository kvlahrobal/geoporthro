# Mapbender3 icon library

Icon set is created and generated with [IcoMoon](https://icomoon.io/) web app.<br/>
Used at least by [mapbender](http://mapbender3.org/) GIS project.

## Preview
It's posible to see live example of the icons [here](http://rawgit.com/mapbender/icons/master/demo.html)

## Integration 

### Composer

Add requirement to [composer](https://getcomposer.org/).<br/>
Configure "component-dir"  the path where font files should be copied.
```javascript
{
  "require": {
    "mapbender/icons",: "*"
  },
  "config": {
       "component-dir": "web/components"
  }
}
```

Update and install requirements.

```sh
composer update 
```


## Change font

To get change the icons use [IcoMoon](https://icomoon.io/).

* Click on import files
* Select and load [selection.json](selection.json) project file.
* Edit icons
* Click "Generate-Font" on bottom panel
* Download "zip" and extract it into this GIT project folder
* Commit, Push/Pull-request


