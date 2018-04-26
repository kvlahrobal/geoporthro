# *Geoport.HRO*

An adoption of [*Mapbender*](https://mapbender.org), the spatial web mapping framework, for *Geoport.HRO*, the web map portal of the municipality of Rostock

## Requirements

see [*Mapbender* documentation](https://mapbender.org/?q=en/documentation)

## Installation

1.  Clone the project:

        git clone https://github.com/rostock/geoporthro /srv/www/htdocs/mapbender

## Configuration

1.  Create a new settings file by copying the template for it:

        cp /srv/www/htdocs/mapbender/application/app/config/parameters_hro_default.yml /srv/www/htdocs/mapbender/application/app/config/parameters.yml

1.  Edit the settings file

## Initialisation

1.  Install the bundle assets into the web folder (by using symlinks instead of copying) by running the [*Symfony*](https://symfony.com) console command from the application directory:

        cd /srv/www/htdocs/mapbender/application
        app/console assets:install --symlink web

1.  Initialise the database by running the *Symfony* console command from the application directory:

        cd /srv/www/htdocs/mapbender/application
        app/console doctrine:database:create
        app/console doctrine:schema:create

1.  Load [EPSG](http://www.epsg-registry.org) codes into the database:

        cd /srv/www/htdocs/mapbender/application
        app/console doctrine:fixtures:load --fixtures=mapbender/src/Mapbender/CoreBundle/DataFixtures/ORM/Epsg/ --append

1.  Create an administrator account:

        cd /srv/www/htdocs/mapbender/application
        app/console fom:user:resetroot

## Deployment

If you want to deploy *Geoport.HRO* with [*Apache HTTP Server*](https://httpd.apache.org), you can follow these steps:

1.  Create a new symbolic link:

        ln -s /srv/www/htdocs/mapbender/application /srv/www/htdocs/geoporthro

1.  Open your *Apache HTTP Server* configuration file and insert something like this:
    
        Alias /geoporthro /srv/www/htdocs/geoporthro/web
        
        <Directory /srv/www/htdocs/geoporthro/web>
            AddDefaultCharset   utf-8
            Options             +FollowSymlinks +Indexes +MultiViews
        </Directory>
