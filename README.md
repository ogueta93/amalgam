# Amalgam
Amalgam is websocket server created for supplied all the processes to run a video game inspired in [Triple Triad](https://finalfantasy.fandom.com/wiki/Triple_Triad).
- [Releases](https://github.com/ogueta93/amalgam/releases) 

## Application Frontend
Amalgam supplies the backend side. You can get the front here: 
- [Amalgam Front](https://github.com/ogueta93/amalgam-front)

## Amalgam Dev-Tools
Amalgam Dev Tools is a project that was maked with the idea to facilitied the necesary tools to develop in Amalgam and Amalgam Front.
- [Amalgam Dev Tools](https://github.com/ogueta93/amalgam-dev-tools)

## Base technologies
- [PHP 7.3](https://www.php.net/manual/en/intro-whatis.php)
- [Symfony 4.3](https://symfony.com/)
- [Rachet - PHP Websockets](http://socketo.me/)

## Requirements
To run this proyect correctly you need to prepare your local machine:

- Debian Buster or greather.
- Php version greather or equal to 7.3.
	- **php-xml** extension.
	- **php-curl** extension. 
	- **php-memcached** extension.
	- **php-intl** extension. 
	- **php-mysql** extension.
- Memcached, last version.
- Mariadb, last version.
- Composer, last version.

## Installation instructions

### 1. Composer
**In the root directory run:**
```
composer install
```

### 2. Basic Proyect Configuration
1. You must to set the host and the port for your memcached service in **config/cache.yaml** file. Example:
```
# This file containt the cache configuration data
connection: 'memcached://amalgam_memcached:11211'
compression: true
serializer: 'igbinary'
```
2. You must to set  the database configuration in **config/packages/dev/doctrine.yaml** file. Example:
```
doctrine:
	dbal:
		# Template Example:
		url: mysql://{user}:{password}@{host}:{port}/{database}
		# Live Example 
		url: mysql://amalgam:amalgam@amalgam_mariadb:3306/amalgam
```

**If you need more information you probably would like to check the symfony documentation**:
- [Doctrine Configuration](https://symfony.com/doc/current/reference/configuration/doctrine.html)

## Start Websocket Server
The websocket server runs on 8080 port number. At the moment, the port number is a hardcoded value.
To start the server execute the custom **ws:start** command in the root directory:
```
php bin\console ws:start
```
