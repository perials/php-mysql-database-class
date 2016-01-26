# PHP MYSQL database standalone class using PDO
This class is heavily inspired from the [Laravel framework](https://laravel.com/docs/5.1/eloquent). However unlike the Illuminate eloquent this one has a very small footprint, has no other dependencies and works only for MYSQL.

## Installation
Since this is a standalone php file all you need to do is to require this file in your project
```php
require 'db.php';
```

## Create instance of the db class
```php
$db = new Db('hostname', 'database', 'user', 'password');
```

## Select where
```php
$posts = $db->table('posts')->where('post_author', '=', 1)->get();
```
If no results then get returns empty array. So make sure you check if the result is empty before using it.
```php
if( !empty($posts) ) {
    foreach( $posts as $post ) {
        //table columns are available as property of the instance
        echo $post->id;
        echo $post->title;
    }    
}
```

## Select with multiple where
```php
$posts = $db->table('posts')->where('author', '=', 1)->where('post_date', '>', '2015-02-01')->get();
```

## Select where with limit
```php
$posts = $db->table('posts')->where('author', '=', 1)->get(5);
```

## Select where with limit and offset
```php
$posts = $db->table('posts')->where('author', '=', 1)->get(5,5);
```

## Select all
```php
$posts = $db->table('posts')->get();
```

## Select first
```php
$posts = $db->table('posts')->first();
$posts = $db->table('posts')->where('author', '=', 1)->first();
//if no results found first will return empty array
```

## Select count
```php
$total_posts = $db->table('posts')->count();
$total_posts = $db->table('posts')->where('author', '=', 1)->count();
```

## Select where in
```php
$posts = $db->table('posts')->whereIn('author', [1,5])->get(5);
```

## Select where like
```php
$posts = $db->table('posts')->where('title', 'LIKE', '%hello%')->get(5);
```

## Select where between
```php
$posts = $db->table('posts')->whereBetween('post_date', ['2015-01-01 00:00:00','2016-12-31 00:00:00'])->get();
```

## Insert
```php
$insert_id = $db->table('posts')->insert([
                                'title'=>'Sample Title',
                                'content'=>'Lorem Ipsum Dolor sit amen'
                                ]);
```

## Update
```php
//update query will always return no of affected rows
$no_of_updated_rows = $db->table('posts')->where('title', '=', 'Sample Title')->update([
                                                                                'content' => 'Updated text'
                                                                                ]);

```
## Delete
```php
$no_of_deleted_rows = $db->table('posts')->where('title', '=', 'Sample Title')->delete();

//this will delete all rows from posts table
$no_of_deleted_rows = $db->table('posts')->delete();
```

## Raw select query
```php
$results = $db->sel("SELECT * FROM A LEFT JOIN B ON A.batch_id = B.id");

//raw query with bind parameters
$results = $db->sel("SELECT * FROM A LEFT JOIN B ON A.batch_id = B.id WHERE A.name = ? AND B.batch_name = ?",[$name, $batch_name]);
```

## Select specific columns
```php
$posts = $db->table('posts')->select('title, content, url')->get();
```

# Support
If you have any questions or suggestions feel free to open a new issue or [contact us](http://perials.com/contact).