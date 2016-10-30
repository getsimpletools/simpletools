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