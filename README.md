# CheapTransportConnections
This 1-page website shows the cheapest connections in Italy for the next 7 days

**Requirements**
- you have composer installed (https://getcomposer.org/download/)
- you have PHP version > 7

**Installation**
- clone the repository and go into the directory
- run `composer install`
- serve the website using PHP built-in server `php -S 127.0.0.1:8005 -t public`

**Usage**
- to see the cheapest connections from all types of transports go to `http://127.0.0.1:8005`
- to exclude some types of transports add a query parameter in the url called `exclude`, value is comma separated. E.g `http://127.0.0.1:8005/?exclude=car,bus`. This `exclude` query parameter was created because it seems like car is almost always the cheapest transport option and the price is strangely always 1.11 USD.
