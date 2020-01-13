<?php

namespace Simpletools\Http\Api;

class Acl
{
    protected $_acl;
    protected $_aclCompiled;

    protected $_uri;
    protected $_method;

    protected $_dryRun = false;

    public function __construct()
    {
        $this->_uri     = isset($_SERVER['REQUEST_URI']) ? parse_url('http://simpletools.php'.$_SERVER['REQUEST_URI'],PHP_URL_PATH) : null;
        $this->_method  = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'POST';
    }

    public function uri($uri)
    {
        $this->_uri = $uri;
        return $this;
    }

    public function method($method)
    {
        $this->_method = $method;
        return $this;
    }

    public function dryRun($dryRun=true)
    {
        $this->_dryRun = $dryRun;
        return $this;
    }

    public function getAclCompiled()
    {
        return $this->_aclCompiled;
    }

    public function evaluate()
    {
        if($this->_aclCompiled)
        {
            $SERVER_REQUEST_PATH        = $this->_uri;

            //ignore format ie: .json or .xml
            if(strpos($SERVER_REQUEST_PATH,'.')!==false) {
                $SERVER_REQUEST_PATH = explode('.', $SERVER_REQUEST_PATH);
                $SERVER_REQUEST_PATH = implode('.', array_slice($SERVER_REQUEST_PATH, 0, -1));
            }

            $methods                    = [];
            $methods['NONE']            = $this->_aclCompiled['NONE'] ?? [];
            $methods[$this->_method]    = $this->_aclCompiled[$this->_method] ?? [];
            $methods['ANY']             = $this->_aclCompiled['ANY'] ?? [];

            foreach($methods as $method => $routes) {
                foreach ($routes as $route) {

                    if (preg_match($route['pattern'], $SERVER_REQUEST_PATH, $matches)) {

                        if($method=='NONE') {

                            if($this->_dryRun)
                                throw new \Exception('Forbidden, ACL NONE', 403);
                            else
                                throw new Exception('Forbidden, ACL NONE', 403);
                        }

                        return true;
                    }
                }
            }

            if($this->_dryRun)
                throw new \Exception('Forbidden, ACL no matches',403);
            else
                throw new Exception('Forbidden, ACL no matches',403);
        }

        if($this->_dryRun)
            throw new \Exception('ACL not provided',400);
        else
            throw new Exception('ACL not provided',400);
    }

    public function acl($acl)
    {
        $compiled = [];

        foreach($acl as $path => $methods)
        {
            $methods = explode(',',$methods);

            foreach($methods as $method) {
                if (!isset($compiled[$method][$path]))
                    $compiled[$method][$path] = $this->_parsePath($path);
            }
        }

        $this->_aclCompiled = $compiled;
        return $this;
    }

    protected function _parsePath($path,$invoke=null)
    {
        preg_match_all('/\{(.*?)\}/', $path, $matches);

        if(isset($matches[0]))
        {
            $path = str_replace(array('\*','\^','\?'),array('.*','^','?'),preg_quote($path,'/'));
            $map = array();
            foreach($matches[0] as $index => $match)
            {
                $path = str_replace(preg_quote($match),'([A-Za-z0-9\-_]*)',$path);
                $map[] = $matches[1][$index];
            }
        }

        return array(
            'pattern'	=> '/'.$path.'$/',
            'map'		=> $map
        );
    }
}