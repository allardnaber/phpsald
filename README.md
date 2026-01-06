# phpsald (INCOMPLETE)
PHP Simple Abstraction Layer for Databases

**Warning:** VERY MUCH Work-in-progress

Features:
- Entity based queries and statements
- Auto retrieval of related entities
- Multi host connections for PostgreSQL
- More uniform error reporting across drivers

## Connect to a database
Set up a connection config once
```php
use Sald\Sald;

Sald::setDefaultConfig([
	'dsn' => 'pgsql:host=...;dbname=...;sslmode=...',
	'username' => '<username>',
	'password' => '<password>'
]);
```

When using a single connection, throughout your application getting a connection can
be retrieved using:

```php
$connection = Sald::get();
```

The library will make sure the connection always gets reused during the lifetime of
the application.

## Basic usage with entities
The goal of this abstraction layer is to take away (some of) the boilerplate for
interacting with several types of databases and result sets.

Define database entities using Attributes. Additional table columns will be available
as members, but can be defined using PHPDoc style comments, if your IDE supports them.

```php
#[Table('my_table')]
class MyEntity extends Entity {
    
    #[Id(Id:AUTO_INCREMENT)]
    public int $id;
    
    public string $name;
    public int $age;
}
```

Then retrieve your entities from the database:

```php
// fetch
$entities = MyEntity::select()
    ->where('age', 18)
    ->fetchAll();

// do something
foreach ($entities as $entity) {
    printf("Entity: [%d] %s, age %d\n",
        $entity->id,
        $entity->name,
        $entity->age);
}
```

**Note - For PostgreSQL:** JSON type columns will automatically be decoded into an object. 

Update an entity as follows:
```php
$entity = MyEntity::select()
    ->whereId(3)
    ->fetchSingle();

$entity->name = 'Dummy';
$entity->update();
```

Or delete another entity:
```php
$entity = MyEntity::select()
    ->whereId(4)
    ->fetchSingle();

$entity->delete();
```

Create new entities:
```php
$entity = new MyEntity();
$entity->age = 37;
$entity->name = 'A new dummy';
 // performs insert on default connection
$entity->insert();

// show inserted id:
printf("Entity was created with id %d.\n", $entity->id);
```

Database column names will be converted from snake-case (`entity_id`) to camel case (`entityId`).
A column name can be overwritten using the `Column` attribute, and it can be used to specify a 
column type (currently only JSON, so any _input_ is JSON encoded upon insertion).

```php
#[Table('my_table_2')]
class MyEntity2 extends Entity {
    
    #[Id(Id:AUTO_INCREMENT)]
    public int $id;
    
    #[Column('username')]
    public string $name;
    
    #[Column(type: ColumnType::JSON)]
    public mixed $metadata
}
```


## Advanced usage
Use the following methods to create extensible queries, where `$classname` must
refer to a class extending `Entity`.
```php
$selectQuery = Sald::select($classname);
$insertQuery = Sald::insert($classname);
$updateQuery = Sald::update($classname);
$deleteQuery = Sald::delete($classname);
```

These extensible queries allow you to add more advanced logic to the queries,
like updating or deleting a range of records.

## Multi host configuration
A distributed or failover setup is supported by simply providing multiple hostnames in the DSN.
If the first host is unavailable or unsuitable, the second host will be used, and so on.

Using the `targetServerType` element in the DSN it is possible to indicate what type of server is
required:

| value             | definition                                                                                                                      |
|-------------------|---------------------------------------------------------------------------------------------------------------------------------|
| `any`             | any server is fine (used if `targetServerType` is omitted)                                                                      |
| `primary`         | it is required to connect to a primary server (i.e. not in failover / readonly state).                                          |
| `preferPrimary`   | it is preferred to connect to a primary server, but in case no primary server is available, a secondary server is acceptable.   |
| `secondary`       | it is required to connect to a secondary server (in failover / readonly state)                                                  |
| `preferSecondary` | it is preferred to connect to a secondary server, but in case no secondary server is available, a primary server is acceptable. |

A host name may be followed by a port number, if that host requires a specific port. Otherwise, the value
of the `port` element is used. If this element is also undefined, for now we use the default 5432 port.

```php
$config = [
	'dsn' => 'pgsql:host=dbhost1.example.org,dbhost2.example.org;port=5432;dbname=important_db;sslmode=require;targetServerType=preferPrimary',
	[...]
];
// or with a specific port number for the first host.
$config = [
	'dsn' => 'pgsql:host=dbhost1.example.org:5400,dbhost2.example.org;port=5432;dbname=important_db;sslmode=require;targetServerType=preferPrimary',
	[...]
];
```

## Expert usage
The `Connection` class is an extension of the native [`PDO`](https://www.php.net/manual/en/book.pdo.php) class, so custom queries
can be used. Use the `fetchAll`, `fetchFirst` or `fetchSingle` methods to convert the result
set into entities.
