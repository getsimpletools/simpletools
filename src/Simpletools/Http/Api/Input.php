<?php

namespace Simpletools\Http\Api;

class Input
{
    protected static $_mappings = [];
    protected static $_init = false;

    protected static $_input;
    protected static $_exemptKeys = [];

    protected static $_definitions = [];

    protected static $_triggered = false;
    protected static $_self;

    protected $_exceptions      = [];
    protected $_parsedMappings;

    protected static $_malformedJsonException = false;

    protected static $_onPrivate;

    public static function onPrivate(callable $callback)
    {
        self::$_onPrivate = $callback;
    }

    protected static function _init()
    {
        if(self::$_init) return; self::$_init = true;

        self::$_input = file_get_contents('php://input');

        if(trim(explode(';',strtolower(@$_SERVER['CONTENT_TYPE']))[0])=='application/json')
        {
            if(self::$_input)
                self::$_input = json_decode(self::$_input);
            else
                self::$_input = (object) array();

            if(self::$_input===null)
            {
                if($msg = json_last_error_msg())
                    self::$_malformedJsonException = new InputException("Malformed JSON request - ".$msg,400);

                self::$_input = (object)array();
            }
        }
        elseif(trim(explode(';',strtolower(@$_SERVER['CONTENT_TYPE']))[0])=='multipart/form-data')
        {
            self::$_input = json_decode(json_encode($_POST));
        }
        else
        {
            self::$_input = isset($_REQUEST) ? (object) $_REQUEST : (object) array();
        }
    }

    public static function input($key=null,$value=null)
    {
        self::_init();

        if($key===null) return self::$_input;

        if(is_array($key))
        {
            foreach($key as $k => $v) {
                @self::$_input->{$k} = $v;
            }

            return self::$_input;
        }

        return isset(self::$_input->{$key}) ? self::$_input->{$key} : null;
    }

    protected static function _parseEndpoints($endpoints)
    {
        if(is_string($endpoints)) return array_map('trim',explode(',',$endpoints));
        elseif(is_array($endpoints) OR is_object($endpoints)) return $endpoints;

        throw new Exception('Bad request, not supported endpoints format passed',400);
    }

    public static function bind($method,$endpoints,$mappings)
    {
        self::_init();

        $endpoints = self::_parseEndpoints($endpoints);

        $acl = [];
        foreach($endpoints as $endpoint) {
            $acl[$endpoint] = $method;
        }

        foreach($endpoints as $uri)
        {
            $acl = (object) [
                $uri => $method
            ];

            try {
                (new Acl())
                    ->acl($acl)
                    ->dryRun()
                    ->evaluate();

                foreach($mappings as $key => $map)
                {
                    self::$_mappings[$method.'@'.$uri][$key] = $map;
                }
            }
            catch(\Exception $e){}
        }
    }

    public static function unbind($method,$endpoints)
    {
        self::_init();

        $endpoints = self::_parseEndpoints($endpoints);
        foreach($endpoints as $uri)
        {
            unset(self::$_mappings[$method.'@'.$uri]);
        }
    }

    public static function exempt($key)
    {
        self::_init();
        self::$_exemptKeys[$key] = 1;
    }

    public static function define($mappings,$groupName='') //todo
    {
        self::$_definitions = array_merge(self::$_definitions,$mappings);
    }

    public static function hasBeenTriggered()
    {
        return self::$_triggered;
    }

    public static function self()
    {
        if(!self::$_self)
            new self();

        return self::$_self;
    }

    public function __construct($mappings=array()) {

        if(isset(self::$_self)) {

            $this->_parsedMappings = self::$_self->_parsedMappings;

            return self::$_self;
        }

        self::_init();
        self::$_triggered = true;
        self::$_self = $this;

        if(self::$_malformedJsonException)
            $this->_exceptions[] = self::$_malformedJsonException;

        $bindedMaps = [];
        $localMaps = [];
        $localNsMaps = [];

        foreach($mappings as $key => $val)
        {
            if(is_integer($key)) $key = $val;


            if(strpos($key,'/')!==false)
            {
                $keyChunks = explode('/',$key);

                $key = array_pop($keyChunks);
                $ns = implode($keyChunks);

                $localNsMaps[$key] = $ns;
            }

            $localMaps[$key] = 1;
        }

        if(count(self::$_mappings)) {

            $mappings_ = [];

           foreach(self::$_mappings as $map)
           {
               $mappings_ = array_merge($mappings_, $map);

               foreach($map as $key => $val) {
                   if(is_integer($key)) $key = $val;
                   $bindedMaps[$key]=1;
               }
           }

            $mappings = array_merge($mappings_,$mappings);
        }

        $exempt = self::$_exemptKeys;
        if(isset($mappings[':exempt']))
        {
            $exempt = $mappings[':exempt'];

            if(!is_array($exempt))
            {
                $exempt = explode(',',$exempt);
            }

            $exempt = array_flip($exempt);

            $exempt = array_merge($exempt, self::$_exemptKeys);
						unset($mappings[':exempt']);
        }

        //$this->_exceptions = [];

        $runOnPrivate = false;

        foreach($mappings as $key => $settings)
        {


            if(isset($mappings[$key]['private']) && $mappings[$key]['private'])
                $runOnPrivate = true;

            if(is_integer($key))
            {
                unset($mappings[$key]);

                $key = $settings;
                if(!isset(self::$_definitions[$key]))
                {
                    $this->_exceptions[] = new InputException('Not Implemented, {'.$key.'} definition doesn\'t exist',501);
                }
                else {
                    $settings = @self::$_definitions[$key];

                    if (!isset($mappings[$key])) {
                        $mappings[$key] = $settings;
                    }
                }
            }

            /*
             * Exempt
             */
            if((isset($exempt[$key]) && isset($bindedMaps[$key]) && !isset($localMaps[$key])))
            {
                unset($mappings[$key]);
                continue;
            }

            if(!isset($settings['about']))
            {
                if(!isset(self::$_definitions[$key]))
                {
                    $this->_exceptions[] = new InputException('Not Implemented, {'.$key.'} definition doesn\'t exist',501);
                }
                else
                {
                    if($settings[':test'] && self::$_definitions[$key][':test'])
                    {
                        $settings[':test'] = array_merge(self::$_definitions[$key][':test'],$settings[':test']);
                    }

                    $mappings[$key] = $settings = array_merge(self::$_definitions[$key], $settings);
                }
            }

            $input_ns_key = $key;
            if(strpos($input_ns_key,'/')!==false)
            {
                $input_ns_key = explode('/',$input_ns_key);
                $input_ns_key = array_pop($input_ns_key);
            }

            if(isset(self::$_input->{$input_ns_key}) && isset($settings['decorator']) && is_callable($settings['decorator']))
            {
                @self::$_input->{$input_ns_key} = $settings['decorator'](@self::$_input->{$input_ns_key});
                unset($mappings[$key]['decorator']);
            }

						if (
						    (!isset($settings[':test']['conditional']) OR !$settings[':test']['conditional']) &&
                            (!self::$_input OR !property_exists(self::$_input, $input_ns_key))
                        )
						{
                            $mappings[$key][':test']['matching'] = false;
							$this->_exceptions[] = new InputException('Bad Request, missing value for required key: {' . $input_ns_key . '}', 400);
						}
						else if (isset($settings[':test']))
						{
							$value = @self::$_input->{$input_ns_key};

							$mappings[$key][':test']['matching'] = true;

							/*
							 * Checking if default value has been set for the :test.conditional=true and if specified and otherwise
							 * property has not been set by the user, sets it to default so matching function can be run against
							 */
                            if (
                                isset($settings[':test']['conditional']) && $settings[':test']['conditional'] &&
                                !property_exists(self::$_input, $input_ns_key) && isset($settings[':test']['default'])
                            )
                            {
                                self::$_input->{$input_ns_key} = $value = $settings[':test']['default'];
                            }

							if (
									(isset($settings[':test']['conditional']) && $settings[':test']['conditional'] && property_exists(self::$_input, $input_ns_key)) OR
									(!isset($settings[':test']['conditional']) OR !$settings[':test']['conditional'])
							)
							{
								$_value = (array)$value;
								if (isset($settings[':test']['notEmpty']) && $settings[':test']['notEmpty'] && ($value === null OR (is_string($value) && !strlen($value)) OR (is_array($value) && !count($value)) OR (is_object($value) && empty($_value))))
								{
									$mappings[$key][':test']['matching'] = false;
									$this->_exceptions[] = new InputException('Bad Request, missing value for non-empty key {' . $input_ns_key . '}', 400);
								}
								unset($_value);

								if (isset($settings[':test']['matching']) && is_callable($settings[':test']['matching']))
								{
									$settings[':test']['matching'] = $mappings[$key][':test']['matching'] = $settings[':test']['matching']($value);
									if (!$settings[':test']['matching'])
									{
										$mappings[$key][':test']['matching'] = false;
										$this->_exceptions[] = new InputException('Bad Request, non-empty {' . $input_ns_key . '} param is not passing value matching test', 400);
									}
								}
							}
						}
						else
						{
							$this->_exceptions[] = new InputException('Not Implemented, :test is missing for defined key {' . $input_ns_key . '}', 501);
						}
        }

        //run once, on the first private
        if(isset($runOnPrivate) && self::$_onPrivate && is_callable(self::$_onPrivate))
        {
            call_user_func(self::$_onPrivate);
            self::$_onPrivate = false;
        }

        if(self::$_input && (is_object(self::$_input) OR is_array(self::$_input))) {
            foreach (self::$_input as $key => $value) {

                if(isset($localNsMaps[$key])) {
                    $ns = $localNsMaps[$key];
                    $key = $ns.'/'.$key;
                }

                if (!isset($mappings[$key])) {
                    $this->_exceptions[] = new InputException("Bad Request, submitted key {'.$key.'} is not supported", 400);
                }
            }
        }

        $this->_parsedMappings = $mappings;

        if($this->_exceptions)
        {
            throw $this->_exceptions[0];
        }
    }

    public function mappings()
    {
        return self::$_self->_parsedMappings;
    }

    public function __get($name)
    {
        return isset(self::$_input->{$name}) ? self::$_input->{$name} : null;
    }

    public function __set($name,$value)
    {
        if(isset(self::$_input->{$name}))
            self::$_input->{$name} = $value;
    }

    public function __isset($name)
    {
        return isset(self::$_input->{$name});
    }

    protected $_privateMask = '*******';

    public function privateMask($mask)
    {
        $this->_privateMask = $mask;
        return $this;
    }

    protected function _preparedInput()
    {
        $input      = self::$_input;
        $mapping    = $this->mappings();

        foreach($input as $key => $val)
        {
            if(isset($mapping[$key]) && isset($mapping[$key]['private']) && $mapping[$key]['private'])
                $input->{$key} = $this->_privateMask;
        }

        return $input;
    }

    public function toLogJson()
    {
        return json_encode($this->_preparedInput(self::$_input));
    }

    public function toObject()
    {
        return self::$_input;
    }

    public function toArray()
    {
        return json_decode(json_encode(self::$_input),true);
    }
}
