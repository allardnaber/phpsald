# phpsald (INCOMPLETE)
PHP Simple Abstraction Layer for Databases

**Warning:** VERY MUCH Work-in-progress

## Connect to a database
Set up a connection once
```php
use Sald\Sald;
use Sald\Connection\Configuration;

$config = new Configuration(
	'pgsql:host=...;dbname=...;sslmode=...;',
	'<username>', '<password>');
$connection = Sald::get($config);
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

## Expert usage
The `Connection` class is an extension of the native [`PDO`](https://www.php.net/manual/en/book.pdo.php) class, so custom queries
can be used. Use the `fetchAll`, `fetchFirst` or `fetchSingle` methods to convert the result
set into entities.
