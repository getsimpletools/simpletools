### 2.11.6 (2021-10-30) (master rebase)
1. **Simpletools\Mvc\Router**
    1. Fixed notices in `_fixDuplicateContent()`

### 2.11.4 (2021-07-08)
1. **Simpletools\Db\Replicator**
    1. Added helper and meta

### 2.11.3 (2021-03-25)
1. **Simpletools\Db\Replicator**
    1. Allow assigning multiple replicators

### 2.11.2 (2021-03-05)
1. **Simpletools\Http\Api\Input**
    1. Fixed a bug when namespaced and non-namespaced params were mixed
    
### 2.11.1 (2021-02-15)
1. **Simpletools\Http\Api\Input**
    1. Added namespace support e.g `namespace/param`

### 2.10.2 (2020-12-22)
1. **Simpletools\Store\Session**
    1. Added ability to set `maxLifeTime` for `GC` and `cookie` needs

### 2.10.1 (2020-10-23)
 1. **Simpletools\Store\Session**
    1. Added ability to disable session id rotate by setting up `regenerateSessionIdEverySec` to `false` or `0`
    
### 2.10.0 (2020-10-01)
 1. **Simpletools\Http\Api\Response**
    1. Added `->disableVerboseTrace()`
    2. Added `->enableVerboseTrace()`
    3. Added `->verboseTrace($enable=true|false|null)`
 1. **Simpletools\Http\Api\Input**
    1. Added `::onPrivate(callable $callback)`
    
### 2.9.19 (2020-09-16)
 1. **Simpletools\Cloud\Google\Client**
    1. Added ability to specify multi client settings with `::activeSettingsByClientEmail()`
    
### 2.9.18 (2020-09-15)
 1. **Simpletools\Http\Api\Response**
    1. Fixed body array bug.

### 2.9.17 (2020-09-08)
  1. **Simpletools\Http\Api\ACL**
    1. Fixed space in methods.

### 2.9.16 (2020-09-08)
  1. **Simpletools\Http\Api\ACL**
    1. Improved ACL to prioritize deepest rule.

### 2.9.15 (2020-08-20)
  1. **Simpletools\Http\Api\Response**
    1. Fixed `->toArray()` - reference leaking triggered by `->toLogJson()`.
    
### 2.9.14 (2020-08-12)
  1. **Simpletools\Terminal\Cli**
    1. Added `->select()` - terminal UI with radio/checkbox like user interface.
    
### 2.9.13 (2020-07-19)
 1. **Simpletools\Http\Api\Response**
    1. Added `->private($keys=array())` allowing to set response properties as masks by default `*******`, format editable with `privateMask()`
    2. Added  `->toLogJson()` allowing to extract privatised response body
 1. **Simpletools\Http\Api\Input**
    1. Added `private => true|false` definition allowing params to be masked for log purposes `*******`, format editable with `privateMask()`
    2. Added  `->toLogJson()` allowing to extract privatised input
    3. Changed - throws exception error on malformed JSON
    
### 2.9.12 (2020-07-14)
  1. **Simpletools\Http\Api\Response**
    1. Changed  `->getTrace()` to `->getTraceAsString` for verbose

### 2.9.11 (2020-07-09)
  1. **Simpletools\Cloud\File**
    1. Added `->getSignedUrl($expire)` method
    
### 2.9.10 (2020-07-02)
  1. **Simpletools\Db\Mysql\Client**
    1. Removed `get_magic_quotes_gpc()` and `get_magic_quotes_runtime()` deprecated methods.

### 2.9.9 (2020-06-17)
  1. **Simpletools\Http\Api\Input**
    1. Updated so input defaults to empty object in case content-type is set as application/json but request body is empty.
    
### 2.9.8 (2020-06-17)
  1. **Simpletools\Store\Session**
    1. Support for older PHP versions
    
### 2.9.7 (2020-06-14)
  1. **Simpletools\Store\Session**
    1. Updated `::destroy()` - unnecessary autostart
  
### 2.9.6 (2020-06-14)
  1. **Simpletools\Store\Session**
     1. Added `::settings()` option `sessionCookieParams` allowing to set session cookie options
     2. Added `::settings()` option `onSessionIdRegenerate` - a callback triggered on session id change
     3. Added `::hasStarted()` allowing to see if session started
     4. Added `::startIfSessionCookieSet()` allowing to manually start the session if session cookie set
     5. Added `::start()` allowing to manually start the session
     
### 2.9.5 (2020-05-27)
  1. **Simpletools\Http\Api\Input**
     1. Fixed but related to malformed JSON
     2. Fixed matching not set to `false` on missing required param
     
### 2.9.4 (2020-05-20)
  1. **Simpletools\Store\Credentials**
     1. Added new `->prependKey()` method allowing to prepend (extend) the encryption key
     2. Added new `->appendKey()` method allowing to append (extend) the encryption key
     3. Fixed `->decrypt()` method so it returns reference to the object

### 2.9.3 (2020-05-09)
  1. **Simpletools\Terminal\Cli**
     1. Added new `->input()` option `matching` - a function callback allowing to perform input test
     
### 2.9.2 (2020-05-09)
  1. **Simpletools\Http\Api\Input**
     1. Fixed `response.body` to not be set only on `null`, from 2.9.2 `[]`, `false`, `0`, `""` will now be returned as a part of response's body
     
### 2.9.1 (2020-04-26)
  1. **Simpletools\Http\Api\Input**
     1. Added `:test.default` for the conditional params

### 2.9.0 (2020-04-24)
  1. **Simpletools\Http\Api\Response**
     1. Added `->meta()` method allowing to pass meta for the response
  2. **Simpletools\Terminal\Cli**
     1. Added options to `->input($msg,$options=array())` allowing to specify `credentials = true|false` - password like dots, `required = true|false` - to require anything but empty input
  3. **Simpletools\Mvc\Router**
     1. Added support for `:param:` allowing custom routes to map reminder of the URI
        
### 2.8.18 (2020-02-18)
  1. **Simpletools\Db\Replicator**
  2. **Simpletools\Db\TaskReplicator**

### 2.8.17 (2020-01-23)
  1. **Simpletools\Http\Api\Input**
     1. Fixed `notEmpty` - the integer 0, is now allowed.

### 2.8.15 (2020-01-23)
  1. **Simpletools\Http\Api\Input**
     1. Fixed bug with fields, they are now required by default (unless `conditional` is used). Previously, fields were only required if `notEmpty` had been used.
     2. Fixed `notEmpty` - previously allowed NULL value, when using `conditional`

### 2.8.13 (2020-01-13)
  1. **Simpletools\Http\Api\Acl**
     1. Changed the HTTP code from 401 to 403 on bad ACL
     
### 2.8.12 (2020-01-09)
  1. **Simpletools\Http\Api\Input**
     1. Added POST multipart/form-data support
     
### 2.8.10 (2019-12-03)
  1. **Simpletools\Cloud\Google\Client**
     1. Fixed bug with multiple instances

### 2.8.9 (2019-11-27)
  1. **Simpletools\Cloud\File**
     1. Removed `content-length-(*)-gzip` version
     
### 2.8.8 (2019-11-27)
  1. **Simpletools\Cloud\File**
     1. Added `contentLength(*)Gzip` metadata, deprecated `content-length-(*)-gzip` version
     
### 2.8.7 (2019-11-27)
  1. **Simpletools\Cloud\File**
     1. Fixed unlink.

### 2.8.6 (2019-11-26)
  1. **Simpletools\Cloud\File**
     1. Added `stream()` returning `\Psr\Http\Message\StreamInterface` allowing to stream large files

### 2.8.5 (2019-11-26)
  1. **Simpletools\Cloud\File**
     1. Fixed gzip encoding.

### 2.8.4 (2019-11-26)
  1. **Simpletools\Cloud\File**
     1. Added corrected custom meta data for gzip content-before.
    
### 2.8.3 (2019-11-25)
  1. **Simpletools\Cloud\File**
     1. Added `gzipExemptExtensions('png','jpg','jpeg','gif','pdf', ...)` to allow exemptions of certain file types
     
### 2.8.2 (2019-11-25)
  1. **Simpletools\Cloud\File**
     1. Added `::enableGzip($compressionLevel=9,$chunkSize=100000)` and `->gzip($compressionLevel=9,$chunkSize=100000)` args validation
     
### 2.8.1 (2019-11-25)
  1. **Simpletools\Cloud\File**
     1. Added `::enableGzip($compressionLevel=9,$chunkSize=100000)` allowing to disable global gzip across all objects
     2. Added `::disableGzip()` allowing to disable global gzip across all objects
     2. Added `->gzip($compressionLevel=9,$chunkSize=100000)` to enable gzip compression on per object basis
     3. Added `->gzipOff()` to disable gzip compression on per object basis
     
### 2.7.5 (2019-07-10)
  1. **Simpletools\Store\Credentials**
     1. Fixed `use mysql_xdevapi\Exception;` accidentally added by IDE

### 2.7.4 (2019-07-10)
  1. **Simpletools\Store\Credentials**
     1. Added `->encrypt()` allowing to pass a string on construct and get it encrypted rather than decrypted, resulting in error due to default assumption of string being a cipher

### 2.7.3 (2019-07-10)
  1. **Simpletools\Store\Credentials**
     1. Added `->salt()` allowing to specify your own salt string
     2. Added `->disableMeta()` allowing to disable meta data
     3. Added `->enableMeta()` allowing to enable meta data
     4. Added `->get()` allowing to get entire data set
     
### 2.7.2 (2019-06-30)
 1. **Simpletools\Terminal\Progress**
    1. Fixed auto end restart bug
    2. Added `->onCompleted()` callback event
    3. Added `->onEnded()` callback event
    
### 2.7.1 (2019-06-30)
 1. **Simpletools\Terminal\Progress**
    1. Added relevant color and completion status to `->end()`

### 2.7.0 (2019-06-30)
 1. **Simpletools\Terminal\Progress**
    1. Introduced shell progress bar allowing to measure speed as well as additional useful metric across ended-loop running processes

### 2.6.8 (2019-06-26)
 1. **Simpletools\Cloud\Google\Storage\File**
    1. Added temp dir control propagation for the underlying client

### 2.6.7 (2019-05-17)
 1. **Simpletools\Cloud\File**
    1. Added ::setTempDir($tempDir) - to setup a custom temp dir globally
    2. Added ->tempDir($tempDir) - to setup a custom temp dir for current given object
 2. **Simpletools\Cloud\Google\Storage\File**
    1. Added ->tempDir($tempDir) - to setup a custom temp dir for current given object
    
### 2.6.6 (2019-04-09)
  1. **Simpletools\Http\Api\Input**
     1. Fixed :exempt order
     2. Added toArray()
     3. Added toObject()
     
### 2.6.5 (2019-02-18)
  1. **Simpletools\Http\Api\Input**
     1. Fixed notice

### 2.6.4 (2019-02-17)
  1. **Simpletools\Store\Credentials**
     1. Added meta modifiedAt support

### 2.6.3 (2019-02-17)
  1. **Simpletools\Store\Credentials**
     1. Added JSON serialisation support
     2. Updated meta creator version

### 2.6.2 (2019-02-17)
  1. **Simpletools\Store\Credentials**
     1. Removed unnecessary use of namespace
     2. Added support for custom ciphers, defaults to AES-256-CBC

### 2.6.1 (2019-02-17)
  1. **Simpletools\Store\Credentials**
     1. Introduced new Class allowing to easily encrypt/decrypt credentials data

### 2.5.2 (2019-01-16)
  1. **Simpletools\Http\Api**
     1. Fixed CONTENT_TYPE bug

### 2.5.1 (2019-01-15)
  1. **Simpletools\Http\Api**
     1. Initial Release of API helpers

### 2.4.2 (2018-04-30)
  1. **Simpletools\Store\Session**
     1. Added custom session handler

### 2.4.1 (2018-04-23)
 1. **Simpletools\Mvc\Router**
    1. Added http methods overloader
 2. **Simpletools\Mvc\Controller**
    1. Added http methods overloader
 2. **Simpletools\Terminal\Cli**
    1. Added Terminal CLI

### 2.3.23 (2018-03-23)
 1. **Simpletools\Cloud\Google\Storage\File**
    1. Save after Import always force upload to Storage

### 2.3.19 (2017-10-27)
 1. **Simpletools\Cloud\Google\Storage\File**
    1. Return $this on import/export

### 2.3.18 (2017-10-08)
 1. **Simpletools\Cloud\Google\Storage\File**
    1. Fixed - file_put_contents === false

### 2.3.17 (2017-10-08)
 1. **Simpletools\Cloud\Google\Storage\File**
    1. Fixed - wrong exception throw params

### 2.3.16 (2017-10-08)
 1. **Simpletools/Mysql/QueryBuilder**
    1. Added - filter() - can be chained without where() first
 2. **Simpletools/Store/Flash**
    1. Fixed - variables notice in case $_SESSION is not started

### 2.3.15 (2017-09-12)
 1. **Simpletools/Mongo/Client**
    1. Fixed - Array recast in php 7.1

### 2.3.14 (2017-07-17)
 1. **Simpletools/Mysql/QueryBuilder**
    1. Fixed - Array recast in php 7.1
 2. **Simpletools/Mongo/QueryBuilder**
    1. Fixed - Array recast in php 7.1

### 2.3.13 (2017-06-09)
 1. **Simpletools/Mysql/Client**
    1. Fixed - Retry on timeout
 2. **Simpletools/Mysql/Connection**
    1. Added - clean connectors

### 2.3.12 (2017-06-02)
 1. **Simpletools/Store/Session**
    1. Added - Don't auto-start session in CLI

### 2.3.11 (2017-04-25)
 1. **Simpletools/Store/Session**
    1. Fixed default return

### 2.3.10 (2017-04-24)
 1. **Simpletools/Db/Mysql/Result**
    1. Fixed PHP warnings for undefined column names under column map set via columnMap() or ->setColumnMap() methods

### 2.3.9 (2017-04-24)
 1. **Simpletools/Db/Mysql/Client**
    1. Added ->columnMap() and ->setColumnMap() methods
 2. **Simpletools/Db/Mysql/Result**
    1. Added ->columnMap() and ->setColumnMap() methods
    2. Improved columnMap executing and iteration process

### 2.3.8(2017-04-24)
 1. **Simpletools/Mvc/Cloud**
    1. Added \File ->exportFile()
    2. Added \Storage\File ->exportFile()
    3. Changed \Storage\File ->importFile()

### 2.3.7(2017-04-23)
 1. **Simpletools/Db/Mysql/QueryBuilder**
     1. Added columns type casting for SELECT queries
 2. **Simpletools/Db/Mysql/Json**
     1. Introduced JSON helper

### 2.3.6(2017-04-21)
 1. **Simpletools/Db/Mongo/Model**
    1. Added ->self()
 1. **Simpletools/Db/Mysql/Model**
     1. Added ->self()

### 2.3.5(2017-03-31)
 1. **Simpletools/Mvc/Cloud**
    1. Added \File ->importFile()
    2. Added \Storage\File ->importFile()

### 2.3.4(2017-03-27)
 1. **Simpletools/Db/Mongo/QueryBuilder**
    1. Fixed bug of escaping some float values.

### 2.3.2 (2017-03-12)
 1. **Simpletools/Mvc/Cloud**
    1. Added \File ->makePublic()
    2. Added \File ->makePrivate()
    3. Added \File ->getUri()
    4. Added \File ->getUrl()

### 2.3.1 (2017-03-12)
 1. **Simpletools/Mvc/Cloud**
    1. Added Google Storage Cloud/File support
    2. Added Google Storage Cloud/Bucket support

### 2.2.3 (2017-02-27)
 1. **Simpletools/Mvc/Router**
    1. Added catch for errors (php7)

### 2.2.2 (2017-02-27)
 1. **Simpletools/Mvc/Router**
    1. Added views failover
    2. Added forced view
    3. Added content type per extension
    4. Added default content type

### 2.2.1 (2017-02-19)
 1. **Simpletools/Db/Mysql/Client**
    1. Retain DB name setting between slave and master in case of manual change at e.g. Model level

### 2.2.0 (2017-02-08)
 1. **Simpletools/Db/Mysql/Client**
    1. Support of master-slave - read-only slave introduced

### 2.1.8 (2016-12-19)
 1. **Simpletools/Db/Mongo/QueryBuilder**
    1. Fixed determination of which fields(columns) to include in the returned documents.

### 2.1.7 (2016-10-30)
 1. **Simpletools/Db/Mongo/Client**
    1. Connection uri builder - new settings options - host, port, user, pass, authDb

### 2.1.6 (2016-09-25)
 1. **Simpletools/Db/Mysql/QueryBuilder**
    1. Fixed NULL casted as empty string on duplicate key update

### 2.1.5 (2016-09-24)
 1. **Simpletools/Db/Mysql/Client**
    1. Fixed flushed error and errNo after log

### 2.1.4 (2016-09-24)
 1. **Simpletools/Db/Mysql/Connection**
    1. Introduced error query logging storage
 1. **Simpletools/Db/Mysql/Client**
    1. Introduced error query logging
 1. **Simpletools/Db/Mysql/QueryBuilder**
     1. Fixed numeric validation
     2. Fixed null validation

### 2.1.3 (2016-08-21)
 1. **Simpletools/Db/Mysql/Connection**
    1. Introduced query logging storage
 1. **Simpletools/Db/Mysql/Client**
    1. Introduced query logging capabilities, to enable specify `queryLog` at config, accepts the following optional values: `'queryLog'		=> [
                                                                                                            		'minTimeSec'	=> (float) $sec,
                                                                                                            		'emitEvent'		=> (string) $eventName,
                                                                                                            		'emitEventOnly'	=> (true|false),
                                                                                                            		'ignore'		=> ['INSERT','SET','...']
                                                                                                            	]`
    2. Introduced `getQueryLog()`

### 2.1.2 (2016-08-11)
 1. **Simpletools/Db/Mysql/QueryBuilder**
    1. Fixed offset() order

### 2.1.1 (2016-03-12)
  1. **Simpletools/Db/Mysql/Model**
    1. Introduced `table()` method in case of keys conflicts
    2. Introduced `db()` method to setup per query db
    3. Introduced `injectDependency()` to improve in Mvc models loading
  1. **Simpletools/Db/Mysql/QueryBuilder**
    1. Introduced truncate() method
  1. **Simpletools/Mvc/Model**
    1. Improved Simpletools classes dependency injections
  1. **Simpletools/Mvc/Router**
    1. Refactored internal Events trigger-er from `fire()` to `trigger()`
  1. **Simpletools/Db/Mongo**
    1. Introduced Mongo Client library with QueryBuilder
  1. **Simpletools/Events/Event**
    1. Introduced queue
    2. Improved events stacking

### 2.0.32 (2016-02-01)
  1. **Simpletools/Db/Mysql/Driver**
    1. Introduced destructor
  1. **Simpletools/Db/Mysql/Client**
    1. Moved destructor under Driver

### 2.0.31 (2016-01-29)
  1. **Simpletools/Db/Mysql/QueryBuilder**
    1. Fixed empty spaces on fullqualification

### 2.0.30 (2016-01-29)
  1. **Simpletools/Db/Mysql/QueryBuilder**
    1. Fixed fullqualification of some select queries

### 2.0.29 (2016-01-29)
  1. **Simpletools/Db/Mysql/QueryBuilder**
    1. Fixed fullqualification of Where statements with AND, OR
    2. Fixed fullqualification of select for *

### 2.0.28 (2016-01-16)
  1. **Simpletools/Db/Mysql/Connection**
    1. Added Connection manager to enable shared connections between models or instances
  1. **Simpletools/Db/Mysql/FullyQualifiedQuery**
    1. Added FullyQualifiedQuery to prevent unnecessary databases switches
  1. **Simpletools/Db/Mysql/Driver**
    1. Added Driver extending mysqli extensions to enable current database tracking
  1. **Simpletools/Db/Mysql/Client**
    1. Replaced mysqli with Simpletools/Db/Mysql/Driver
    2. Enabled connection manager - Simpletools/Db/Mysql/Connection - to handle connection pools
    3. Added Simpletools/Db/Mysql/FullyQualifiedQuery on setTimezone
    4. Added config option - compression - enabling to enable connection compression
    5. Added config option - ssl - enabling ssl connection
  1. **Simpletools/Db/Mysql/QueryBuilder**
    1. Updated ->getQuery to return Simpletools/Db/Mysql/FullyQualifiedQuery which can be cast as string instead of just string
    2. Added full DB qualification for every query to prevent unnecessary DB switches
  1. **Simpletools/Db/Mvc/Router**
    1. Disabled is_dir check for the applicationDir - not needed - generates extra IO call

### 2.0.27 (2016-01-10)
  1. **Simpletools/Mvc/RoutingHook**
    1. Removed replaced with Simpletools/Events/Event
  2. **Simpletools/Mvc/Router**
    1. Replaced RoutingHook with Simpletools/Events/Event
    2. Improved routing name performance
  3. **Simpletools/Mysql/QueryBuilder**
    1. Added join handler and on table name, db, column SQL injection prevention
  4. **Simpletools/Mysql/Client**
    1. Added multiple connection handler
  5. **Simpletools/Mysql/Model**
    1. Added multiple connection handler
  6. **Simpletools/Mvc/Model**
    1. Added multiple connection handler

### 2.0.26 (2015-05-29)

  1. **Simpletools/Mvc/Controller**
    1. Fixed ->_render type method

### 2.0.25 (2015-03-22)

  1. **Simpletools/Mvc/RoutingHook**
    1. Added RoutingHook class allowing to attach callables under routing events such us dispatchStart, dispatchEnd, beforeControllerInit etc.
  2. **Simpletools/Store/Session**
    1. Fixed warnings being sent in case of double session init

### 2.0.24 (2015-03-21)

  1. **Simpletools/Mysql/QueryBuilder**
    1. Fixed ->insertDelayed method, missing executor

### 2.0.23 (2015-03-21)

  1. **Simpletools/Mysql/QueryBuilder**
    1. Added ->insertDelayed method

### 2.0.22 (2015-03-21)

  1. **Simpletools/Mysql/QueryBuilder**
    1. Fixed sort and limit imploder function

### 2.0.21 (2015-02-26)

  1. **Simpletools/Mvc/Common**
    1. Fixed sanitasation for array GET, POST and REQUEST

### 2.0.20 (2015-02-22)

  1. **Simpletools/Mvc/Common**
    1. Fixed double url decoding

### 2.0.19 (2015-02-21)

  1. **Simpletools/Mvc/Common**
    1. Added default `string` sanitation across `getQuery`, `getRequest`, `getPost`
    2. Added ability to set filters on `isQuery`, `isRequest`, `isPost`

  2. **Simpletools/Mvc/Router**
    1. Improved params sanitation

  3. **Simpletools/Store/Session**
    1. Improved security by enabling default session id regeneration - every 600sec, subject to settings
