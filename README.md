# mittnett/dbal

A very simple database abstraction layer. Uses PDO under-the-hood and supports mysql/mariadb and postgres.

## Usage

Setup a connection:

```php
<?php

// MySQL:
$db = new \HbLib\DBAL\DatabaseConnection(new PDO('mysql:host=localhost;dbname=app', 'app', 'secret'), new \HbLib\DBAL\Driver\MySQLDriver());

// Postgres:
$db = new \HbLib\DBAL\DatabaseConnection(new PDO('pgsql:host=localhost;dbname=app', 'app', 'secret'), new \HbLib\DBAL\Driver\PostgresDriver());

```

One can use `\HbLib\DBAL\LazyDatabaseConnection` which only creates the PDO connection when needed.
