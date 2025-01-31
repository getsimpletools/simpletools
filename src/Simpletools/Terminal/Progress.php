<?php

namespace Simpletools\Terminal;

class Progress
{
    protected $_width   = 100;
    protected $_steps   = 0;
    protected $_pace    = 0.01;
    protected $_startTime;
    protected $_firstRun = true;
    protected $_label = '';
    protected $_step = 0;

    protected $_callback;
    protected $_completedCallback;
    protected $_endedCallback;

    protected $_hidden = false;

    protected $_memStart;
    protected $_lastPercent;
    protected $_done = false;
    protected $_pid;

    protected $_progress = 0;
    protected $_ram;
    protected $_cpu;
    protected $_opsSec = 0;
    protected $_elapsed = 0;

    public function __construct($label='')
    {
        if(!isset($_SERVER['argv']))
        {
            $this->hide();
        }

        $this->_pid = getmypid();
        $this->label($label);
    }

    public function hide()
    {
        $this->_hidden = true;

        return $this;
    }

    public function end()
    {
        if(!$this->_done)
        {
            echo PHP_EOL."\033[43m\033[30m ENDED \033[0m".PHP_EOL.PHP_EOL;

            if($this->_endedCallback)
            {
                $this->_triggerCallback($this->_endedCallback, [
                    'step' => $this->_step,
                    'steps' => $this->_steps,
                    'label' => $this->_label,
                    'pace' => $this->_pace,
                    'progress' => $this->_progress / 100,
                    'ram' => $this->_ram,
                    'cpu' => $this->_cpu / 100,
                    'opsSec' => $this->_opsSec,
                    'elapsed' => $this->_elapsed
                ]);
            }
        }

        $this->_steps       = 0;
        $this->_firstRun    = true;
        $this->_lastPercent = null;
        $this->_startTime   = null;
        $this->_done        = false;
        $this->_memStart    = null;
        $this->_step        = 0;
    }

    public function show()
    {
        $this->_hidden = false;

        return $this;
    }

    public function label($label)
    {
        $this->_label = $label;

        return $this;
    }

    public function onMovement($callback)
    {
        if(!is_callable($callback))
        {
            throw new \Exception('Your callback is not callable',400);
        }

        $this->_callback = $callback;

        return $this;
    }

    public function onCompleted($callback)
    {
        if(!is_callable($callback))
        {
            throw new \Exception('Your callback is not callable',400);
        }

        $this->_completedCallback = $callback;

        return $this;
    }

    public function onEnded($callback)
    {
        if(!is_callable($callback))
        {
            throw new \Exception('Your callback is not callable',400);
        }

        $this->_endedCallback = $callback;

        return $this;
    }

    public function pace($rate)
    {
        if($rate<0.01 OR $rate>1)
        {
            throw new \Exception("Pace can't be smaller than 0.01 and bigger than 1",400);
        }

        $this->_pace = $rate;

        return $this;
    }

    public function width($width)
    {
        $this->_width = $width;

        return $this;
    }

    public function steps($steps)
    {
        $this->_steps = $steps;

        return $this;
    }

    public function start($step=0)
    {
        $this->_startTime   = microtime(true);
        $this->_memStart    = memory_get_peak_usage(true);

        if(!$this->_steps)
        {
            throw new \Exception('Please specify steps',400);
        }

        return $this->step($step);
    }

    public function step(mixed $step=null)
    {
        if($this->_done) return $this;

        if(!$this->_pace)
        {
            throw new \Exception('Please specify pace',400);
        }

        if(!$this->_steps)
        {
            return $this;
        }

        $steps          = $this->_steps;

        if($step)
            $this->_step = (int) $step;
        else
            $step = ++$this->_step;

        $percent        = (int) ($step/$steps*100);

        if(!$this->_startTime)
        {
            $this->start();
        }

        //not to compute more freq than needed
        if($this->_lastPercent!==null && ($this->_lastPercent == $percent OR ($percent% ((int) ($this->_pace*100)) !=0)))
        {
            $this->_lastPercent = $percent;
            return $this;
        }

        $this->_lastPercent = $percent;

        $ram            = ((memory_get_peak_usage(true)-$this->_memStart));
        $cpu            = exec("ps hup ".$this->_pid."|awk '{print $3}'");
        $start_time     = $this->_startTime;
        $now            = microtime(true);
        $elapsed        = $now - $start_time;
        $opsSec         = $elapsed ? round($step/$elapsed) : '-';

        $this->_progress = $percent;
        $this->_ram = $ram;
        $this->_cpu = $cpu;
        $this->_opsSec = $opsSec;
        $this->_elapsed = $elapsed;

        if($this->_callback)
        {
            $this->_triggerCallback($this->_callback,[
                'step'          => $step,
                'steps'         => $this->_steps,
                'label'         => $this->_label,
                'pace'          => $this->_pace,
                'progress'      => $percent/100,
                'ram'           => $ram,
                'cpu'           => $cpu/100,
                'opsSec'        => $opsSec,
                'elapsed'       => $elapsed
            ]);
        }

        if($this->_hidden) return $this;

        $this->_lastPercent = $percent;

        if($this->_firstRun)
        {
            $this->_firstRun = false;
            echo PHP_EOL;

            if($this->_label)
                print $this->_label.PHP_EOL;
        }



        $width          = $this->_width;

        //in case step is greater than steps, will finish stop straight after
        if ($step > $steps)
            $step = $steps;


        $current    = (double) ($step / $steps);
        $bar        = floor($current * $width);

        $progress_bar_sign      ="\u{2588}";


        $status_bar             = "\r";
        $status_bar             .= str_repeat($progress_bar_sign, $bar);

        if ($bar < $width) {
            $status_bar .= $progress_bar_sign . str_repeat("\u{2591}", $width - $bar);
        } else {
            $status_bar .= $progress_bar_sign;
        }

        $elements   = array();

        $elements[] = "";
        $elements[] = "$percent%";
        $elements[] = "$step/$steps";

        if($step)
            $changeRate = $elapsed / $step;
        else
            $changeRate = $step;

        $stepsLeft      = $steps - $step;
        $eta            = round($changeRate * $stepsLeft, 2);

        $elements[] = $opsSec." ops/s";
        $elements[] = "ram: ".round($ram/1048576,2)." MB";
        $elements[] = "cpu: ".round($cpu)."%";
        $elements[] = "eta: ".round($eta)."s";
        $elements[] = "elapsed: ".round($elapsed,2)."s";

        $glue = "  ";
        $status_bar .= implode($glue,$elements);

        print $status_bar;

        if ($step >= $steps)
        {
            if(!$this->_done)
            {
                echo PHP_EOL."\033[42m\033[30m COMPLETED \033[0m".PHP_EOL.PHP_EOL;

                if($this->_completedCallback)
                {
                    $this->_triggerCallback($this->_completedCallback, [
                        'step' => $step,
                        'steps' => $this->_steps,
                        'label' => $this->_label,
                        'pace' => $this->_pace,
                        'progress' => $percent / 100,
                        'ram' => $ram,
                        'cpu' => $cpu / 100,
                        'opsSec' => $opsSec,
                        'elapsed' => $elapsed
                    ]);
                }
            }

            $this->_done = true;

            $this->end();
        }

        return $this;
    }

    protected function _triggerCallback($callback,$args)
    {
        if(is_array($callback))
        {
            $reflection 	= new \ReflectionMethod($callback[0], $callback[1]);
        }
        elseif(is_string($callback))
        {
            $reflection 	= new \ReflectionFunction($callback);
        }
        elseif(is_a($callback, 'Closure') || is_callable($callback, '__invoke'))
        {
            $objReflector 	= new \ReflectionObject($callback);
            $reflection    	= $objReflector->getMethod('__invoke');
        }

        $pass = array();
        foreach($reflection->getParameters() as $param)
        {
            $name = $param->getName();
            if(isset($args[$name]))
            {
                $pass[] = $args[$name];
            }
            else
            {
                try
                {
                    $pass[] = $param->getDefaultValue();
                }
                catch(\Exception $e)
                {
                    $pass[] = null;
                }
            }
        }

        return $reflection->invokeArgs($callback, $pass);
    }
}