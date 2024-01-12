Simple JSON product import 
==========================

Base requirements
-----------------
```
php 8.3
MySQL 8
```

Setup project
--------------

Run in terminal:

```shell script
  composer install
```

Configure your database connection with appropriate credentials and database name in file `.env`
or `env.local` and create database using command:

```shell
  php bin/console doctrine:database:create
```

Run migrations:
```shell
  php bin/console d:m:m --all-or-nothing --no-interaction
```

Application Demo
----------------

Example JSON file is already in project in folder [example][1]. The file contains enough data to demonstrate how
the import works. 

For launch product import run in terminal:
```shell script
  php bin/console app:product-import ./example/example.json
```

To run a command on a schedule, use time-based job scheduler Cron.
For example at 6 and 18 o'clock daily:

```cronexp
  0 6,18 * * * php /var/www/product_import/bin/console app:product-import ./path/to/actual.json
```

[1]: ./example
