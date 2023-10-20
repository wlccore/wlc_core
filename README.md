**wlc_core** - A set of wlc core functionality for casino web sites.

Install
=======

Add configuration into composer.json "repositories" array:

{ "type": "vcs", "url": "git@vcsxa.egamings.com:wlc/core.git" }


Install-Dev
=======
- git clone git@vcsxa.egamings.com:wlc/core.git wlc_core
- cd wlc_core
- git config --local include.path "../.gitconfig"


Dependencies for Geoip2 module
=======
- Install Geoip2 nginx module [https://github.com/leev/ngx_http_geoip2_module](https://github.com/leev/ngx_http_geoip2_module)<br>

>For use headers "X-GEO-*"<br>
>Example:<br>
>header X-GEO-COUNTRY for transfer to php process country code in [ISO 3166-1 alpha-3](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-3) standart

- Install Geoip2 php module [https://github.com/maxmind/GeoIP2-php](https://github.com/maxmind/GeoIP2-php)<br>

>Get information about continent, countries, cities from GeoIp databases by ip

- Define following variables in siteconfig.php:

```php
cfg['geoipDatabasePath'] = 'path to Geoip2 database'; 
cfg['geoipDatabaseType'] = 'type of model to get'; /* available type of models (country, city) */
```

Database Migrations  
=======

```
./vendor/bin/phinx migrate -e "environment" -c vendor/egamings/wlc_core/phinx.php

PHINX_DBHOST="db host" PHINX_DBNAME="db name" PHINX_DBUSER="db user" PHINX_DBPASS="db pass" PHINX_DBPORT="db port" ./vendor/bin/phinx migrate -c vendor/egamings/wlc_core/phinx.php -e "enviroment"
```

Cron Setup
=======

Cron job must run every 10 min

Example:
```
*/10 * * * * cd /www/$SITEDIR/current && SITE_ENV=qa php -q roots/index.php runcron > /tmp/runcron_$SITEDIR.log
```

How to append the metrics?
=======
Scheme for adding counters/metrics:

wlc_someSite/roots/siteconfig.php:
```
$cfg['counters'] = [
  'google_tag_manager' => [
    'key' => 'some_key_for_the_google_tm' // Required value or will be ignored
  ],
  'google_analytics' => [
    'key' => 'some_key_for_the_google_analytics' // Required value or will be ignored
  ]
];
```
