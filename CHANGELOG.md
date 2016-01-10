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