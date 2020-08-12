<?php

namespace Simpletools\Terminal;

class Cli
{
    const INFO                  = "\e[0m"; //default colors
    const ERROR                 = "\e[41m\e[37m\e[1m"; //red background, white font
    const WARNING               = "\e[43m\e[30m"; //yellow background, black font
    const SUCCESS               = "\e[42m\e[37m\e[1m"; //green background, white font
    const NOTE                  = "\e[44m\e[37m"; //blue background, white font

    const TEXT_COLOR_BLACK      = "\e[30m";
    const TEXT_COLOR_CYAN       = "\e[36m";
    const TEXT_COLOR_RED        = "\e[31m";
    const TEXT_COLOR_GREEN      = "\e[32m";
    const TEXT_COLOR_PURPLE     = "\e[35m";
    const TEXT_COLOR_YELLOW     = "\e[33m";
    const TEXT_COLOR_WHITE      = "\e[37m";
    const TEXT_COLOR_GREY       = "\e[0;37m";
    const TEXT_COLOR_BLUE       = "\e[34m";


    const TEXT_BOLD             = "\e[1m";

    const BG_COLOR_BLACK        = "\e[40m";
    const BG_COLOR_RED          = "\e[41m";
    const BG_COLOR_GREEN        = "\e[42m";
    const BG_COLOR_YELLOW       = "\e[43m";
    const BG_COLOR_BLUE         = "\e[44m";
    const BG_COLOR_MAGENTA      = "\e[45m";
    const BG_COLOR_CYAN         = "\e[46m";

    const COLOR_OFF             = "\e[0m";

    protected $_suffix = '';
    protected $_prefix = '';

    protected static $_S_syslog = false;
    protected $_syslog          = false;

    protected $_decorator;

    public function __construct()
    {
        $this->_syslog = self::$_S_syslog;
    }

    public static function enableSyslog()
    {
        self::$_S_syslog = true;
    }

    public static function disableSyslog()
    {
        self::$_S_syslog = false;
    }

    public function syslogOn()
    {
        $this->_syslog = true;
    }

    public function syslogOff()
    {
        $this->_syslog = false;
    }

    public function clear()
    {
        if (preg_match('/^win/i', PHP_OS)) {
            system('cls');
        }
        else{
            system('clear');
        }
    }

    protected function _prepareMessage($msg,$status=self::INFO,$noEol=false)
    {
        $line = array();


        if($this->_prefix)
        {
            $line[] = $this->_prefix;
        }

        $line[] = $status;

        if(is_callable($this->_decorator))
        {
            $msg = call_user_func($this->_decorator,$msg);
        }

        $line[] = $msg;
        $line[] = "\e[0m";

        if($this->_suffix)
        {
            $line[] = $this->_suffix;
        }

        if(!$noEol)
            $line[] = PHP_EOL;

        return implode('',$line);
    }

    public function msg($msg,$status=self::INFO)
    {
        print $this->_prepareMessage($msg,$status);

        return $this;
    }

    public function info($msg)
    {
        $this->syslog(LOG_INFO,$msg);
        return $this->msg($msg);
    }

    public function debug($msg)
    {
        $this->syslog(LOG_DEBUG,$msg);
        return $this->msg($msg,self::NOTE);
    }

    public function error($msg)
    {
        $this->syslog(LOG_ERR,$msg);
        return $this->msg($msg,self::ERROR);
    }

    public function warning($msg)
    {
        $this->syslog(LOG_WARNING,$msg);
        return $this->msg($msg,self::WARNING);
    }

    public function success($msg)
    {
        $this->syslog(LOG_INFO,$msg);
        return $this->msg($msg,self::SUCCESS);
    }

    public function prefix($prefix)
    {
        $args       = func_get_args();
        $colors     = self::COLOR_OFF;

        if(count($args)>1) {
            $colors = array_slice($args, 1);
            $colors = implode('', $colors);
        }
        $this->_prefix = $colors.$prefix.self::COLOR_OFF.' ';

        return $this;
    }

    public function suffix($suffix)
    {
        $args       = func_get_args();
        $colors     = self::COLOR_OFF;

        if(count($args)>1) {
            $colors = array_slice($args, 1);
            $colors = implode('', $colors);
        }

        $this->_suffix = self::COLOR_OFF.' '.$colors.$suffix.self::COLOR_OFF;

        return $this;
    }

    public function decorator(callable $decorator)
    {
        $this->_decorator = $decorator;

        return $this;
    }

    public function line($text="")
    {
        $string     = "-";
        $width      = exec('tput cols');
        // $colors     = self::TEXT_COLOR_YELLOW;
        $colors     = "";

        if($text)
        {
            $length = strlen($text)+2;
            $parts = floor(($width - $length)/2);
            if($parts<0) $parts = 0;

            print $colors.str_repeat($string,$parts).' '.$text.' '.str_repeat($string,$parts).self::COLOR_OFF.PHP_EOL;
        } else
            print $colors.str_repeat($string,$width).self::COLOR_OFF.PHP_EOL;

        return $this;
    }

    public function timeLine($text='')
    {
        if($text) $text.= ' ';
        return $this->line($text.date(DATE_ISO8601));
    }

    public function newline()
    {
        print PHP_EOL;
        return $this;
    }

    public function syslog($level,$msg)
    {
        if($this->_syslog)
            syslog($level,$msg);

        return $this;
    }

    public function input($msg,$options=array())
    {
        $options['confidential']    = isset($options['confidential']) ? $options['confidential'] : false;
        $options['required']        = isset($options['required']) ? $options['required'] : false;

        if(isset($options['matching']))
        {
            if(!is_callable($options['matching']))
                throw new \Exception('Matching needs to be a callable',400);
        }
        else
        {
            $options['matching'] = false;
        }

        $prompt = $this->_prepareMessage($msg, self::INFO, true);

        while(true) {
            if ($options['confidential']) {
                if (preg_match('/^win/i', PHP_OS)) {
                    $vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
                    file_put_contents(
                        $vbscript, 'wscript.echo(InputBox("'
                        . addslashes($prompt)
                        . '", "", "password here"))');
                    $command = "cscript //nologo " . escapeshellarg($vbscript);
                    $input = rtrim(shell_exec($command));
                    unlink($vbscript);
                } else {
                    $command = "/usr/bin/env bash -c 'echo OK'";
                    if (rtrim(shell_exec($command)) !== 'OK') {
                        trigger_error("Can't invoke bash");
                        return;
                    }
                    $command = "/usr/bin/env bash -c 'read -s -p \""
                        . addslashes($prompt)
                        . "\" mypassword && echo \$mypassword'";
                    $input = rtrim(shell_exec($command));
                    echo "\n"; //needs to be added to move to the next line
                }
            } else {
                $input = readline($prompt);
            }

            if($options['matching'] && !$options['matching']($input))
            {
                $this->error('Provided input doesn\'t match with permitted values, please try again');
                $this->line();
                continue;
            }

            if($options['required'] && !$input) continue;

            return $input;
        }
    }

    public function select($msg,array $options,array $settings=array())
    {
        $settings['multi']              = $multi    = (boolean) (isset($settings['multi']) ? $settings['multi'] : false);
        $settings['defaultOptionIndex'] = (isset($settings['defaultOptionIndex']) ? $settings['defaultOptionIndex'] : -1);
        $settings['vertical']           = $vertical = (boolean) (isset($settings['vertical']) ? $settings['vertical'] : false);

        $orgKeys    = array_keys($options);
        $orgKeysFlip= array_flip($orgKeys);
        $options    = array_values($options);

        $settings['defaultOptionIndex'] = isset($orgKeysFlip[$settings['defaultOptionIndex']]) ? $orgKeysFlip[$settings['defaultOptionIndex']] : -1;

        $sign = $settings['multi']?' ':"X";
        $currentOption = $settings['defaultOptionIndex']+1;
        $cliOptions = [];
        $optionsArray = [];

        $cliOption ='';
        foreach ($options as $i => $o)
        {
            $cliOption.=self::NOTE."[ ]".self::INFO." ".$o. ($settings['vertical'] ? "\n" : " ");
        }
        $cliOptions[] = $cliOption;


        foreach ($options as $index => $option)
        {
            $cliOption ='';
            foreach ($options as $i => $o)
            {
                $cliOption.=($index === $i && $settings['multi'] ? self::SUCCESS : self::NOTE).($index === $i ? "[$sign]":"[ ]").self::INFO." ".$o.($vertical ? "\n" : " ");
            }
            $cliOptions[] = $cliOption;
        }

        echo self::TEXT_COLOR_WHITE.$msg.self::INFO.PHP_EOL.PHP_EOL;

        echo $cliOptions[$currentOption];

        system('stty cbreak -echo');
        $stdin = fopen('php://stdin', 'r');


        if($vertical)
        {
            if($multi)
            {
                while (1)
                {
                    $c = ord(fgetc($stdin));

                    if ($c ==10 && $optionsArray) break; //enter
                    elseif ($c == 32) //space
                    {
                        if(in_array($currentOption-1,$optionsArray))
                            $optionsArray = array_diff($optionsArray, [$currentOption-1]);
                        elseif(isset($options[$currentOption-1]))
                            $optionsArray[] = $currentOption-1;
                        else
                            continue;

                        $cliOptions=[];
                        foreach ($options as $i => $o)
                        {
                            $cliOption.=self::NOTE."[ ]".self::INFO." ".$o."\n";
                        }
                        $cliOptions[] = $cliOption;


                        foreach ($options as $index => $option)
                        {
                            $cliOption ='';
                            foreach ($options as $i => $o)
                            {
                                $cliOption.=($index === $i && $multi ? self::SUCCESS : self::NOTE)."[".(in_array($i, $optionsArray) ? 'X':' ')."]".self::INFO." ".$o."\n";
                            }
                            $cliOptions[] = $cliOption;
                        }

                        foreach ($options as $o)
                            echo "\e[1A\r\033[K";

                        echo $cliOptions[$currentOption];
                    }
                    elseif ($c == 65) //arrow left
                    {
                        if(isset($cliOptions[$currentOption-1]) && $currentOption-1)
                        {
                            foreach ($options as $o)
                                echo "\e[1A\r\033[K";
                            echo $cliOptions[--$currentOption];
                        }
                    }
                    elseif ($c == 66 || $c == 9) //arrow right | tab
                    {
                        if(isset($cliOptions[$currentOption+1]))
                        {
                            foreach ($options as $o)
                                echo "\e[1A\r\033[K";
                            echo $cliOptions[++$currentOption];
                        }
                    }
                }
            }
            else //single
            {
                while (1)
                {
                    $c = ord(fgetc($stdin));
                    //echo "Char read: $c\n";

                    if ($c ==10 && $currentOption > 0) break; //enter
                    elseif ($c == 65) //arrow left
                    {
                        if(isset($cliOptions[$currentOption-1]) && $currentOption-1)
                        {
                            foreach ($options as $o)
                                echo "\e[1A\r\033[K";
                            echo $cliOptions[--$currentOption];
                        }
                    }
                    elseif ($c == 66 || $c == 9) //arrow right | tab
                    {
                        if(isset($cliOptions[$currentOption+1]))
                        {
                            foreach ($options as $o)
                                echo "\e[1A\r\033[K";
                            echo $cliOptions[++$currentOption];
                        }
                    }
                }
            }

        }
        else
        {

            if($multi)
            {
                while (1)
                {
                    $c = ord(fgetc($stdin));

                    if ($c ==10 && $optionsArray) break; //enter
                    elseif ($c == 32) //space
                    {
                        if(in_array($currentOption-1,$optionsArray))
                            $optionsArray = array_diff($optionsArray, [$currentOption-1]);
                        elseif(isset($options[$currentOption-1]))
                            $optionsArray[] = $currentOption-1;
                        else
                            continue;

                        $cliOptions=[];
                        foreach ($options as $i => $o)
                        {
                            $cliOption.=self::NOTE."[ ]".self::INFO." ".$o." ";
                        }
                        $cliOptions[] = $cliOption;


                        foreach ($options as $index => $option)
                        {
                            $cliOption ='';
                            foreach ($options as $i => $o)
                            {
                                $cliOption.=($index === $i && $multi ? self::SUCCESS : self::NOTE)."[".(in_array($i, $optionsArray) ? 'X':' ')."]".self::INFO." ".$o." ";
                            }
                            $cliOptions[] = $cliOption;
                        }

                        echo "\033[".strlen($cliOptions[0])."D";
                        echo $cliOptions[$currentOption];
                    }
                    elseif ($c == 68) //arrow left
                    {
                        if(isset($cliOptions[$currentOption-1]) && $currentOption-1)
                        {
                            echo "\033[".strlen($cliOptions[0])."D";
                            echo $cliOptions[--$currentOption];
                        }
                    }
                    elseif ($c == 67 || $c == 9) //arrow right | tab
                    {
                        if(isset($cliOptions[$currentOption+1]))
                        {
                            echo "\033[".strlen($cliOptions[0])."D";
                            echo $cliOptions[++$currentOption];
                        }
                    }
                }
            }
            else //single
            {
                while (1)
                {
                    $c = ord(fgetc($stdin));
                    //echo "Char read: $c\n";

                    if ($c ==10 && $currentOption > 0) break; //enter
                    elseif ($c == 68) //arrow left
                    {
                        if(isset($cliOptions[$currentOption-1]) && $currentOption-1)
                        {
                            echo "\033[".strlen($cliOptions[0])."D";
                            echo $cliOptions[--$currentOption];
                        }
                    }
                    elseif ($c == 67 || $c == 9) //arrow right | tab
                    {
                        if(isset($cliOptions[$currentOption+1]))
                        {
                            echo "\033[".strlen($cliOptions[0])."D";
                            echo $cliOptions[++$currentOption];
                        }
                    }
                }
            }
        }




        system('stty echo');

        echo PHP_EOL;

        if($multi) {
            $_optionsArray = [];
            foreach ($optionsArray as $option)
            {
                $_optionsArray[] = $orgKeys[$option];
            }

            return $_optionsArray;
        }
        else
            return $orgKeys[$currentOption-1];
    }
}