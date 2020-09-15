<?php

    namespace Simpletools\Http\Api;

    class Response
    {
        protected $_statusCode      = 0;
        protected $_payload;
        protected $_meta;
        protected $_statusMsg       = '';
        protected $_exception       = '';
        protected $_httpHeader      = [];
        protected static $_self;

        protected $_apiEndpointParams = '';
        protected $_verbose=false;

        public static function self(array $body=null)
        {
            if(!self::$_self)
            {
                self::$_self = new static($body);
            }

            return self::$_self;
        }

        protected $_private             = array();
        protected $_privateFlipped      = array();
        protected $_privateAll          = false;

        public function private($keys=null)
        {
            if($this->_privateFlipped) {
                $this->_private = array_flip($this->_private);
                $this->_privateFlipped = false;
            }

            if($keys===null)
            {
                $this->_privateAll=true;
                return $this;
            }

            if(!is_array($keys))
                $keys = explode(',',$keys);

            $keys = array_flip($keys);

            $this->_private = array_merge($this->_private,$keys);
            $this->_privateFlipped = true;

            return $this;
        }

        protected $_privateMask = '*******';

        public function privateMask($mask)
        {
            $this->_privateMask = $mask;
            return $this;
        }

        protected function _logListPrepare($list)
        {
						if(!is_array($list) && !is_object($list))
							return $this->_privateAll ? $this->_privateMask : $list;

            $type = is_object($list) ? 'object' : 'array';

            foreach ($list as $key => $value)
            {
                if ($type == 'object') {
                    if ($this->_privateAll or isset($this->_private[$key]))
                        $list->{$key} = $this->_privateMask;
                } elseif ($type == 'array') {
                    if ($this->_privateAll or isset($this->_private[$key]))
                        $list[$key] = $this->_privateMask;
                }
            }

            return $list;
        }

        public function toLogJson()
        {
            $response = $this->toArray(true);

            unset($response['status']->input);

            if(isset($response['body']) && $response['body']) {
                if (is_array($response['body']) or is_object($response['body'])) {
                    $type = is_object($response['body']) ? 'object' : 'array';

                    $list = false;

                    if($type == 'array' && isset($response['body'][0]))
                    {
                        $list = true;
                    }

                    foreach ($response['body'] as $key => $value)
                    {
                        if(!$list) {
                            if ($type == 'object') {
                                if ($this->_privateAll or isset($this->_private[$key]))
                                    $response['body']->{$key} = $this->_privateMask;
                            } elseif ($type == 'array') {
                                if ($this->_privateAll or isset($this->_private[$key]))
                                    $response['body'][$key] = $this->_privateMask;
                            }
                        }
                        else
                        {
                            $response['body'][$key] = $this->_logListPrepare($value);
                        }
                    }

                } elseif ($this->_privateAll)
                    $response['body'] = $this->_privateMask;
            }

            return json_encode($response);
        }

        public function __construct(array $body=null)
        {
            $this->_payload = $body;
        }

        public function setStatusMessage($message)
        {
            $this->_statusMsg = $message;
        }

        public function setException($e)
        {
            $this->_exception   = $e;
            $this->_statusCode  = 401;
            $this->_statusMsg   = 'Unauthorized';

            if($e instanceof Exception)
            {
                $this->_statusCode  = $e->getCode();
                $this->_statusMsg   = $e->getMessage();
            }
        }

        public function apiEndpointParams($params)
        {
            $this->_apiEndpointParams = $params;
        }

        public function __toString()
        {
            $this->pushHeaders();
            return $this->toJson();
        }

        public function pushHeaders()
        {
            http_response_code($this->_statusCode);

            foreach($this->_httpHeader as $header=>$value)
            {
                header("$header: $value");
            }
        }

        public function withHeader($header,$value)
        {
            $this->_httpHeader[$header]   = $value;

            return $this;
        }

        public function unsetHeader($header)
        {
            unset($this->_httpHeader[$header]);
        }

        public function status($statusCode, $statusMsg)
        {
            $this->_statusCode  = $statusCode;
            $this->_statusMsg   = $statusMsg;

            return $this;
        }

        public function getStatusCode()
        {
            return $this->_statusCode;
        }

        public function body($body, $transformer=null)
        {
            if(is_callable($transformer))
            {
                if(is_array($body) && isset($body[0]))
                {
                    foreach($body as $key => $elem)
                    {
                        $this->_payload[$key] = $transformer($elem);
                    }
                }
                else
                {
                    $this->_payload = $transformer($body);
                }
            }
            else
            {
                $this->_payload = $body;
            }

            return $this;
        }

        public function meta($key,$value=null)
        {
            if (!is_array($this->_meta))
            {
                $this->_meta = array();
            }

            if($value)
            {
                $this->_meta[$key] = $value;
            }
            elseif(is_array($key))
            {
                $this->_meta = array_merge($this->_meta,$key);
            }
            else
            {
                throw new Exception("Wrong type passed as parameter of Response->meta() function",400);
            }

            return $this;
        }

        public function isError()
        {
            if($this->_exception OR substr($this->_statusCode,0,1)!=2)
                return true;
            else
                return false;
        }

        public function verbose($verbose=true)
        {
            $this->_verbose = $verbose;

            return $this;
        }

        public function toArray($verbose=false)
        {
            $status = [
                'code'  => (int) $this->_statusCode
            ];

            if($this->_statusMsg)
            {
                $status['message']    = $this->_statusMsg;
            }

            $status['OK'] = !$this->isError();

            if(($this->_verbose OR $verbose) && $this->_exception)
            {
                $status['exception'] = [
                    'type'  => get_class($this->_exception),
                    'msg'   => $this->_exception->getMessage(),
                    'code'  => $this->_exception->getCode(),
                    'trace' => explode("\n",$this->_exception->getTraceAsString())
                ];
            }

            if($this->_exception && is_a($this->_exception,'Simpletools\Http\Api\InputException'))
            {
                $status['input'] = (new Input())->mappings();
            }

            //$status['date'] = date(DATE_W3C);

            $response = [
                'status'    => (object) $status
            ];

            if(isset($this->_meta) && $this->_meta)
            {
                $response['meta']         = $this->_meta;
            }

            if($this->_payload!==null)
                $response['body']   =   json_decode(json_encode($this->_payload));

            return $response;
        }

        public function toJson()
        {
            if(isset($_GET['pretty_print']) && $_GET['pretty_print']=='yes')
                return json_encode($this->toArray(),JSON_PRETTY_PRINT);
            else
                return json_encode($this->toArray());
        }
    }
