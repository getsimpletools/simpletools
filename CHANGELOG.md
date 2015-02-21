### 2.0.19 (2015-02-21)
  1. **Simpletools/Mvc/Common**
    1. Added default STRING sanitation across getQuery, getRequest, getPost
    2. Added ability to set filters on isQuery, isRequest, isPost
  2. **Simpletools/Mvc/Router**
    1. Improved params sanitation
  3. **Simpletools/Store/Session**
    1. Improved security by enabling default session id regeneration - every 600sec, subject to settings