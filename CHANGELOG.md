### 2.0.19 (2015-02-21)

  * Simpletools/Mvc/Common
  ** Added default STRING sanitation across getQuery, getRequest, getPost
  ** Added ability to set filters on isQuery, isRequest, isPost

  * Simpletools/Mvc/Router
  ** Improved params sanitation

  * Simpletools/Store/Session
  ** Improved security by enabling default session id regeneration - every 600sec, subject to settings