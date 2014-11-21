<?php
/*
 * Simpletools Framework.
 * Copyright (c) 2009, Marcin Rosinski. (http://www.getsimpletools.com)
 * All rights reserved.
 * 
 * LICENCE
 *
 * Redistribution and use in source and binary forms, with or without modification, 
 * are permitted provided that the following conditions are met:
 *
 * - 	Redistributions of source code must retain the above copyright notice, 
 * 		this list of conditions and the following disclaimer.
 * 
 * -	Redistributions in binary form must reproduce the above copyright notice, 
 * 		this list of conditions and the following disclaimer in the documentation and/or other 
 * 		materials provided with the distribution.
 * 
 * -	Neither the name of the Simpletools nor the names of its contributors may be used to 
 * 		endorse or promote products derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR 
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY 
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR 
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL 
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, 
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER 
 * IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF 
 * THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 * @framework		Simpletools
 * @packages    	Layout
 * @description		Simple Layout solution
 * @copyright  		Copyright (c) 2009 Marcin Rosinski. (http://www.getsimpletools.com)
 * @license    		http://www.opensource.org/licenses/bsd-license.php - BSD
 * @version    		Ver: 2.0.3 2014-11-19 18:35
 * 
 */

	namespace Simpletools;

	class Layout
	{
		private $_layout 		= '';
		private $_layoutDir		= '';
		private $_render		= false;
		private $_css			= null;
		private $_js			= null;
		private $_internalJs	= null;
		public $content			= '';
		private $_style			= null;
		private $_head_link		= null;
		private $_meta_tags		= array();
		
		public $description		= null;
		public $title			= null;
		
		private $_settings		= null;

		protected $_layouts 		= array();
		protected $_currentLayout	= 'default';

		private static $_instance;
	
		public function __construct(array $settings)
		{
			$this->_settings = $settings;
			$this->checkSettings();
			
			if(isset($settings['path_to_layout']))
			{	
				$this->setLayout($settings['path_to_layout']);
			}
			elseif(isset($settings['path']))
			{	
				$this->setLayout($settings['path']);
			}
			elseif(isset($settings['layouts']))
			{	
				$this->registerLayouts($settings['layouts']);
			}

			if (empty(self::$_instance)) 
			{
				self::$_instance = &$this;
			}
		}
		
		public static function &settings(array $settings)
		{
			if (empty(self::$_instance)) 
			{
			     new \Simpletools\Layout($settings);
		    }
		    else
		    {
		   		$this->_settings = $settings;
		   		$this->checkSettings();
		    }
		    
		    return self::getInstance();
		}
		
		private function checkSettings()
		{
			if(!isset($this->_settings['charset'])) 
				$this->_settings['charset'] = 'UTF-8';
		}
		
		public static function &getInstance($settings=null)
		{
			if (empty(self::$_instance)) 
			{
				if(!$settings OR !is_array($settings))
				{
					$settings = array();
				}

			    self::$_instance = new \Simpletools\Layout($settings);
		    }
		     
		   	return self::$_instance;	
		}
		
		public function &setLayout($layDir)
		{
			$this->_layoutDir 	= $layDir;
			$this->_render		= true;

			$this->_layouts['default'] = $layDir;
			
			return $this;
		}

		public function &registerLayouts(array $layouts)
		{
			foreach($layouts as $name => $path)
			{
				$this->_layouts[$name] = $path; 
			}

			$this->_render		= true;

			return $this;
		}
		
		public function &start()
		{
			return $this->startBuffer();
		}
		
		public function &startBuffer()
		{
			ob_start();
			
			return $this;
		}
		
		public function clearBuffer()
		{
			ob_end_clean();
		}
		
		public function render($minify=false)
		{
			if($this->_render)
			{
				$this->layout()->content = ob_get_contents();
				ob_end_clean();
				
				ob_start();
				
				if($this->_render === true)
				{
					require($this->_layouts[$this->_currentLayout]);
					$c = ob_get_contents();
				}
				else
					$c = $this->layout()->content;
				
				ob_end_clean();
				
				if($minify)
				{
					echo preg_replace('/\s\s+/', ' ',str_replace(array("\t","\n","\r"),' ',$c));
					//echo preg_replace('/<!--(.*)-->/Uis', '', $html);
				}
				else
					echo $c;
			}
			
			
		}
		
		public function &layout()
		{
			return $this;
		}
		
		public function displayContent()
		{
			echo $this->layout()->content;
		}
		
		//depracted
		public function &disableLayout()
		{
			//ob_end_clean();
			$this->_render		= false;
			
			return $this;
		}
		
		public function &disable($renderView=false)
		{
			if(!$renderView)
				$this->_render = false;
			else
				$this->_render = -1;
				
			return $this;
		}

		public function &set($layout='default')
		{
			if($layout && isset($this->_layouts[$layout]))
			{
				$this->_currentLayout = $layout;
			}

			return $this;
		}
		
		public function &enable($layout='default')
		{
			$this->_render = true;

			if($layout && isset($this->_layouts[$layout]))
			{
				$this->_currentLayout = $layout;
			}

			return $this;
		}
		
		private function htmlentities($str)
		{
			return htmlentities(
				html_entity_decode($str,ENT_COMPAT,$this->_settings['charset'])
				,ENT_COMPAT,$this->_settings['charset']);
		}
		
		public function &setTitle($title)
		{
			$this->layout()->title = $this->htmlentities($title);
			return $this;
		}
		
		public function &setDefaultLayoutTitle($title)
		{
			$this->layout()->title = $title;
			return $this;
		}
		
		public function displayTitle()
		{
			echo $this->layout()->title;
		}
		
		public function &setDescription($description)
		{
			$this->layout()->description = $this->htmlentities($description);
			return $this;
		}
		
		public function displayDescription()
		{
			echo '<meta name="description" content="'.$this->layout()->description.'" />'."\r\n";
		}
		
		public function &addInternalCss($style)
		{
			$this->_style .= ' '.$style;
			return $this;
		}
		
		public function displayInternalCss()
		{
			if($this->_style != null)
			{
				echo '
					<style type="text/css">
					'.trim($this->_style,' ').'
					</style>
				';
			}
		}
		
		public function &clearInternalCss()
		{
			$this->_style = null;
			return $this;
		}
		
		public function &addExternalCss($href)
		{
			$args = func_get_args();
			
			foreach($args as $href)
			{
				$this->_css .= '<link href="'.$href.'" rel="stylesheet" type="text/css" media="screen" />';
			}
			
			return $this;
		}
		
		public function &addExternalCss_($href,$media='screen',$rel='stylesheet')
		{
			$this->_css .= '<link href="'.$href.'" rel="'.$rel.'" type="text/css" media="'.$media.'" />';
			return $this;
		}
		
		public function &clearExternalCss()
		{
			$this->_css = null;
			return $this;
		}
		
		public function displayExternalCss()
		{
			if($this->_css) echo $this->_css;
		}
		
		public function &addMetaTag($name,$content)
		{
			$this->_meta_tags[$name] = $content;
			
			return $this;
		}
		
		public function displayMetaTags()
		{
			if(count($this->_meta_tags)) {
				
				foreach($this->_meta_tags as $tag => $content)
				{
					if($content)
					{
						echo '<meta name="'.$tag.'" content="'.$content.'" />';
					}
				}
			}
		}
		
		public function &addHeadLink(array $options)
		{
			$rel 	= isset($options['rel']) ? 'rel="'.$options['rel'].'"' : null;
			$title 	= isset($options['title']) ? 'title="'.$options['title'].'"' : null;
			$type 	= isset($options['type']) ? 'type="'.$options['type'].'"' : null;
			$href 	= isset($options['href']) ? 'href="'.$options['href'].'"' : null;
			
			$this->_head_link .= '
				<link '.$rel.' '.$title.' '.$type.' '.$href.' />' ;
			
			return $this;
		}
		
		public function displayHeadLinks()
		{
			if($this->_head_link) echo $this->_head_link;
		}
		
		public function &addExternalJs($href)
		{
			$args = func_get_args();
			
			foreach($args as $href)
			{
				$this->_js .= '<script src="'.$href.'" type="text/javascript"></script>';
			}
			
			return $this;
		}
		
		public function &clearExternalJs()
		{
			$this->_js = null;
			return $this;
		}
		
		public function displayExternalJs()
		{
			if($this->_js) echo $this->_js;
		}
		
		public function &addInternalJs($source)
		{
			$this->_internalJs .= ' '.$source;
			return $this;
		}
		
		public function &clearInternalJs()
		{
			$this->_internalJs = null;
			return $this;
		}
		
		public function displayInternalJs()
		{
			if($this->_internalJs != null)
			{
				echo '
				<script type="text/javascript">
				/* <![CDATA[ */
					'.trim($this->_internalJs,' ').'
				/* ]]> */
				</script>';
			}
			
		}
		
	}
	
?>