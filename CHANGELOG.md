### 3.0.30 (2025-07-28)
1. **Simpletools\Http\Api\Input**
    1. Remove object reference in `toObject()` method

### 3.0.29 (2025-07-22)
1. **PHP 8.4 sync'd with Simpletools-PHP7 Branch**
    1. **Simpletools\Cloud\Google\Client**
        1. Added standalone object of the class
    2. **Simpletools\Cloud\Google\Storage\File**
        1. It can accept Client in constructor
    3. **Simpletools\Cloud\Bucket**
        1. Added `listFiles($directory = null, $sortBy='created',$sortDirection ='asc')` method to list files in bucket directory
        2. Added `getIterator($directory = null)` method to return an iterator for files in a bucket directory
    4. **Simpletools\Cloud\Google\Storage\Bucket**
        1. Added `listFiles($directory = null, $sortBy='created',$sortDirection ='asc')` method to list files in bucket directory
        2. Added `getIterator($directory = null)` method to return an iterator for files in a bucket directory
    5. **Simpletools\Mvc\Router**
        1. Fixed notices in `_fixDuplicateContent()`

### 3.0.28 (2025-01-30)
1. **PHP 8.4 Compatibility.**
    1. ***File List from 3.0.23***
        1. Amended `<type>|null` to `mixed` to prevent type casting between string/int when not explicitly set.


### 3.0.27 (2025-01-24)
1. **PHP 8.4 Compatibility.**
    1. ***Simpletools\Store\Credentials***
        1. Amended `_encrypt()`  function to pass `openssl_encrypt()` parameters by name, to clear deprication warning when passing `null` to parameter.


### 3.0.26 (2025-01-21)
1. **PHP 8.4 Compatibility.**
    1. ***Simpletools\Http\Api\Input***
        1. Amended `input()` function to allow array `$key` to be passed.

### 3.0.25 (2025-01-17)
1. **PHP 8.4 Compatibility. (Implicitly marking parameter as nullable is deprecated.)**
    1. ***Simpletools\Db\Mysql\Driver***
        1. Changed return to mysql `#[\ReturnTypeWillChange]`;


### 3.0.24 (2025-01-15)
1. **PHP 8.4 Compatibility. (Implicitly marking parameter as nullable is deprecated.)**
    1. ***Simpletools\Db\Mysql\Client***
        1. Changed Default `$flags` value from null to 0;

### 3.0.23 (2025-01-13)
1. **PHP 8.4 Compatibility. (Implicitly marking parameter as nullable is deprecated.)**
    1. ***Simpletools\Http\Api\Acl***
        1. Removed unused nulled parameter `$invoke=null` from  `_parsePath()` function call
    2. ***Simpletools\Http\Api\Exception***
        1. Amended constructor function to allow `\Exception` to be nullable
    3. ***Simpletools\Http\Api\Input***
        1. Amended `input()` function to allow nullable parameters `$key` & `$value`
    4. ***Simpletools\Http\Api\Response***
        1. Amended `__construct()` function to allow nullable array `$body`
        2. Amended `self()` function to allow nullable array `$body`
        3. Amended `private()` function to allow nullable array/string `$keys`
        4. Amended `body()` function to allow nullable callable `$transformer`
        5. Amended `meta()` function to allow nullable string `$value`
        6. Amended `verboseTrace()` function to allow nullable string `$enable`
    5. ***Simpletools\Cloud\Google\Storage\File***
        1. Amended `setBody()` function to allow nullable string `$flag`
    6. ***Simpletools\Cloud\File***
        1. Amended `setBody()` function to allow nullable string `$flag`
    7. ***Simpletools\Config\Ini***
        1. Amended `__construct()` function to allow nullable array `$settings`
    8. ***Simpletools\Db\Mongo\Client***
        1. Amended `__construct()` function to allow nullable array `$settings`
        2. Amended `getObjectId()` function to allow nullable string `$id`
    8. ***Simpletools\Db\Mysql\Client***
        1. Amended `setTimezone()` function to allow nullable string `$queryServer`
        2. Amended `getServerInfo()` function to allow nullable string `$queryServer`
        3. Amended `isThreadSafe()` function to allow nullable string `$queryServer`
        4. Amended `isTable()` function to allow nullable string `$queryServer` and nullable string `$db`
        5. Amended `setAutoCommit()` function to allow nullable string `$queryServer`
        6. Amended `startTransaction()` function to allow nullable string `$queryServer`
        7. Amended `beginTransaction()` function to allow nullable string `$queryServer`
        8. Amended `rollback()` function to allow nullable string `$queryServer`
        9. Amended `commit()` function to allow nullable string `$queryServer`
        10. Amended `setUniqueChecks()` function to allow nullable string `$queryServer`
        11. Amended `getInfo()` function to allow nullable string `$queryServer`
        12. Amended `getCharset()` function to allow nullable string `$queryServer`
        13. Amended `setCharset()` function to allow nullable string `$queryServer`
        14. Amended `getConnectionStatus()` function to allow nullable string `$queryServer`
        15. Amended `query()` function to allow nullable string `$queryServer`
        16. Amended `getInstanceOfModel()` function to allow nullable array `$initArgs`
        17. Amended `getQuery()` function to allow nullable string `$id`
        18. Amended `getPost()` function to allow nullable string `$id`
        19. Amended `getRequest()` function to allow nullable string `$id`
        20. Amended `connect()` function. Mysqli expects `$port` and `$flags` to be integer types. `$flags` signature is also not nullable.
    9. ***Simpletools\Db\Mysql\Connection***
        1. Amended `logQuery()` function to allow nullable string `$errMsg` and nullable string `$errNo`
    10. ***Simpletools\Db\Mysql\Driver***
        1. Amended `real_connect()` function to allow nullable strings for constructors `$host`, `$user`, `$password`, `$database`, `$port`, `$socket`, `$flags`
    11. ***Simpletools\Db\Mysql\QueryBuilder***
        1. Amended `whereSql()` function to allow nullable array `$vars`
    12. ***Simpletools\Db\Replicator***
        1. Amended `on()` function to allow nullable string `$meta`
        2. Amended `trigger()` function to allow nullable string `$helper`
    13. ***Simpletools\Events\Event***
        1. Amended `unqueue()` function to allow nullable string `$id`
    14. ***Simpletools\Mvc\Common***
        1. Amended `isPost()` function to allow nullable string `$filter`
        2. Amended `isQuery()` function to allow nullable string `$filter`
        3. Amended `isRequest()` function to allow nullable string `$filter`
        4. Amended `getQuery()` function to allow nullable string `$id`
        5. Amended `getPost()` function to allow nullable string `$id`
        6. Amended `getRequest()` function to allow nullable string `$id`
    15. ***Simpletools\Mvc\Controller***
        1. Amended `getInstance()` function to allow nullable bool `$empty`
        2. Amended `render()` function to allow nullable string `$view`
        3. Amended `forward()` function to allow nullable string `$action`
        3. Amended `isAction()` function to allow nullable string `$action`
    16. ***Simpletools\Mvc\Router***
        1. Amended `forward()` function to allow nullable string `$action`
        2. Amended `_render()` function to allow nullable string `$view`
    17. ***Simpletools\Page\Layout***
        1. Amended `getInstance()` function to allow nullable array `$settings`
    18. ***Simpletools\Store\Credentials***
        1. Amended `get()` function to allow nullable string `$name`
    19. ***Simpletools\Store\Flash***
        1. Amended `reflush()` function to allow nullable string/array `$keys`
    20. ***Simpletools\Store\Session***
        1. Amended `start()` function to allow nullable string/array `$sessionId`
        2. Amended `_autoStart()` function to allow nullable string/array `$sessionId`
    21. ***Simpletools\Terminal\Progress***
        1. Amended `step()` function to allow nullable string/int `$step`

### 3.0.22 (2025-01-11)
1. **Simpletools\Mvc\Common**
    1. Changed default filter sanitiser for `->getPost()`, `->getQuery()`, `->getRequest()` from `FILTER_SANITIZE_STRING` to `FILTER_SANITIZE_SPECIAL_CHARS`

### 3.0.21 (2025-01-08)
1. **Simpletools\Db\Mysql**
    1. Change return type to `true`;  As of 8.0.0 This function now always returns true. Previously it returned false on failure (https://www.php.net/manual/en/mysqli.close.php).

### 3.0.19 (2025-01-08)
1. **Simpletools\Db\Mysql**
    1. PHP 8.4 Compatibility. Implicitly marking parameter as nullable is deprecated.
2. **Simpletools\Mvc\Router**
    1. PHP 8.4 Compatibility. Implicitly marking parameter as nullable is deprecated.

### 3.0.17 (2025-01-07)
1. **Simpletools\Db\Mysql**
    1. Added return type to Driver `close()` to match mysqli::close().

### 3.0.16 (2024-04-19)
1. **Simpletools\Mvc\Common**
    1. Added `AllowDynamicProperties` class directive
2. **Simpletools\Mvc\Model**
    1. Added `AllowDynamicProperties` class directive
3. **Simpletools\Mvc\Layout**
    1. Added `AllowDynamicProperties` class directive
   
### 3.0.15 (2024-04-13)
1. **Simpletools\Mvc\Router**
    1. Initiated `_shifts_params` as array()
2. **Simpletools\Mvc\Common**
    1. Added `is_array($this->_shifts_params)` check
3. **composer.json**
    1. set to minimum PHP 7.0

### 3.0.13 (2024-02-09)
1. **Simpletools\Db\Mysql\Client**
   1. Set mysqli_report to MYSQLI_REPORT_OFF

### 3.0.5 (2023-07-04)
1. **Simpletools\Cloud\Bucket**
    1. Added `listFiles($directory = '', $sortBy='',$sortDirection ='asc')` method to list files in bucket directory
    2. Added `getIterator($directory = null)` method to return an iterator for files in a bucket directory
1. **Simpletools\Cloud\Google\Storage\Bucket**
    1. Added `listFiles($directory = '', $sortBy='',$sortDirection ='asc')` method to list files in bucket directory
    2. Added `getIterator($directory = null)` method to return an iterator for files in a bucket directory

### 3.0.0 (2022-12-09)
1. **Simpletools\Db\Mysql\QueryBuilder**
    1. Additional Function typecast, fixed `void` type returning a value error.
    2. Updated `Querybuilder.php` with type casting on iterator functions, where PHP highlights deprication warnings in PHP 8.1.
2. **Simpletools\Db\Mysql\Client**
    1. Initialise `$___connected` and `$___credentials` with empty array, as automatic conversion of false to array is deprecated.
3. **Simpletools\Db\Mysql\Driver**
    2. The object-oriented style `mysqli::init()` method has been deprecated. Replaced calls to `parent::init()` with `parent::__construct()`
    3. Typecasting to return type functions.
4. **Simpletools\Db\Mysql\Result**
    1. Updated `Result.php` with type casting on functions, where PHP highlights deprication warnings in PHP 8.1
5. **Simpletools\Cloud\Google\Storage\File**
    1. Explicit check that `$_FileHandler` is a resource before attempting `fclose()`
7. **Simpletools\Mvc\Model**
   1. Updated `Model.php` `__callStatic` Method with function type cast, as Magic Methods will now have their arguments and return types checked if they have them declared. 

### 2.11.6 (2021-10-30)