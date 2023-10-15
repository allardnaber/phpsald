# phpsald (INCOMPLETE)
PHP Simple Abstraction Layer for Databases

**Warning:** VERY MUCH Work-in-progress

## Usage
The goal of this abstraction layer is to take away (some of) the boilerplate for
interacting with several types of databases and result sets.

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
or get a basic database query immediately, which can be used to filter records, etc:
```php
// The following will translate into:
// SELECT * FROM <table-matching-classname>
$query = Sald::select($classname);
```

The library will make sure the connection always gets reused during the lifetime of
the application.

Define database entities using Attributes. Additional table columns will be available
as members, but can be defined using PHPDoc style comments, if your IDE supports them.

```php
#[Table('my_table')]
/**
 * @property int $id;
 * @property string $name;
 * @property int $age; 
 */
class My_Entity extends Entity {
    
    #[IdColumn]
    protected int $id;
    
    protected string $name;
    protected int $age;
}
```

Then retrieve your entities from the database:
```php
// fetch
$entities = Sald::select(My_Entity::class)
    ->where('age > 13')
    ->fetchAll();

// do something
foreach ($entities as $entity) {
    printf("Entity: [%d] %s, age %d\n",
        $entity->id,
        $entity->name,
        $entity->age);
}
```

Create new entities:
```php
$entity = new My_Entity();
$entity->age = 37;
$entity->name = 'Dummy';
 // performs insert on default connection
$entity->insert();

// show inserted id:
printf("Entity was created with id %d.\n", $entity->id);
```
