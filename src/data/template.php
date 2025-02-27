<?php
/**
* A class representing templates
*
* The class Template allows the representation of rebuilding and manipulating a previously expanded and parsed template
* It allows to change the template by adding to it or removing parameters
* It is also possible to search for parameters and values in a template
*
* @method string getTitle()
* @method array getTitleArgs()
* @method void setTitle(string $title)
* @method string getParam(string $param)
* @method Generator|mixed getParams()
* @method bool contains(string $param)
* @method bool strContains(string $needle)
* @method bool isString()
* @method void setIsArg(bool $isArg)
* @method bool isArg()
* @method Template addParam(string $param, mixed $value)
* @method Template addParamBefore(string $before, string $newParam, string $value)
* @method Template addParamAfter(string $after, string $newParam, string $value)
* @method Template removeParam(string $param)
* @method Template renameParam(string $oldName, string $newName)
* @method Template setParam(string $param, string $value)
* @method Template setText(string $text)
* @method Template strReplace(string $search, string $replace, int $limit)
* @method string rebuild()
* @method string rebuildParam(string $param)
*/
class Template {
	private mixed $template;
	private string $title;
	private array $titleArgs;
	private bool $isArg;
	
	private const TEMPLATE_UNSUPPORTED_OPERATION = "Parameter operations are not supported if template is a string";
	
	/**
	* constructor for class Template
	*
	* @param mixed $template   the previously expanded and parsed template
	* @param string $title     the title of the template
	* @param array $titleArgs  the titleargs if the title is not a string
	* @access public
	*/
	public function __construct(mixed $template = array(), string $title = "", array $titleArgs = array()) {
		$this->template = $template;
		$this->title = $title;
		$this->titleArgs = $titleArgs;
		$this->isArg = false;
	}
	
	/**
	* getter for the title
	*
	* @return string  the title of the template
	* @access public
	*/
	public function getTitle() : string {
		return $this->title;
	}
	
	/**
	* getter for the titleargs
	*
	* @return array  the titleargs
	* @access public
	*/
	public function getTitleArgs() : array {
		return $this->titleArgs;
	}
	
	/**
	* setter for the name of the template
	*
	* @param string $title     the new name of the template
	* @param array $titleArgs  the titleargs if the title is not a string
	* @return Template         itself to allow the chaining of calls
	* @access public
	*/
	public function setTitle(string $title, array $titleArgs = array()) : Template {
		$this->title = $title;
		$this->titleArgs = $titleArgs;
		return $this;
	}
	
	/**
	* getter for a specific param
	*
	* @param string $param  the parameter name to look for
	* @return mixed         the value of the parameter
	* @access public
	*/
	public function getParam(string $param) : mixed {
		if(!$this->contains($param)) { throw new Exception("Parameter {$param} does not exist"); }
		return $this->template[$param]->getValue();
	}
	
	/**
	* generator for all parameters of a template
	*
	* @return Generator|mixed  yields every parameter of the template
	*                          or the entire template, if the template is just a string
	* @access public
	*/
	public function getParams() : Generator|TemplateParameter {
		if(!is_array($this->template)) { throw new Exception(self::TEMPLATE_UNSUPPORTED_OPERATION); }
		if(is_array($this->template)) {
			foreach($this->template as $template) {
				yield $template;
			}
		}
	}
	
	/**
	* check if a given parameter is set in the template
	*
	* @param string $param  the parameter to look for
	* @return boolean       true if the parameter exists, false otherwise
	* @access public
	*/
	public function contains(string $param) : bool {
		if(!is_array($this->template)) { throw new Exception(self::TEMPLATE_UNSUPPORTED_OPERATION); }
		return isset($this->template[$param]);
	}
	
	/**
	* check if the template contains a string
	*
	* @param string $needle  the needle to search for
	* @return bool           true if the template contains the string, false otherwise
	* @access public
	*/
	public function strContains(string $needle) : bool {
		if(!$this->isString()) { throw new Exception(self::TEMPLATE_UNSUPPORTED_OPERATION); }
		return str_contains($this->template, $needle);
	}
	
	/**
	* check if the template is representing a string or an actual template
	*
	* @return bool  true if the template is a string, false otherwise
	* @access public
	*/
	public function isString() : bool {
		return is_string($this->template);
	}
	
	/**
	* setter for if template is argument
	*
	* @param bool $isArg  true if the template is an argument, false otherwise
	* @access public
	*/
	public function setIsArg(bool $isArg) : void {
		$this->isArg = $isArg;
	}
	
	/**
	* check if template is argument
	*
	* @return bool  true if the template is an argument, false otherwise
	* @access public
	*/
	public function isArg() : bool {
		return $this->isArg;
	}
	
	/**
	* add a parameter and a value to the template. Will not change the template if the parameter already exists
	*
	* @param string $param  the name of the parameter to add
	* @param string $value  the content of the value to add
	* @param bool $isIndex  whether the parameter is an index
	* @return Template      itself to allow the chaining of calls
	* @access public
	*/
	public function addParam(string $param, mixed $value, bool $isIndex = false) : Template {
		if(!is_array($this->template)) { throw new Exception(self::TEMPLATE_UNSUPPORTED_OPERATION); }
		if($this->contains($param)) { return $this; }
		$this->template[trim($param)] = new TemplateParameter($param, $value, $isIndex);
		return $this;
	}
	
	/**
	* add a parameter and a value before another parameter
	* if $before is not set in the template it will attempt to add the parameter at the end of the template
	* if the new parameter is already set the template will not be changed
	*
	* @param string $before  the name of the param before which the new param should be added
	* @param string $param   the name of the parameter to add
	* @param string $value   the content of the value to add
	* @param bool $isIndex   whether the parameter is an index
	* @return Template       itself to allow the chaining of calls
	* @access public
	*/
	public function addParamBefore(string $before, string $newParam, string $value, bool $isIndex = false) : Template {
		if(!is_array($this->template)) { throw new Exception(self::TEMPLATE_UNSUPPORTED_OPERATION); }
		if(!$this->contains($before)) { return $this->addParam($param, $value); }
		
		$newTemplate = array();
		
		foreach($this->getParams() as $param) {
			if(trim($param->getName()) === trim($before)) {
				$newTemplate[trim($newParam)] = new TemplateParameter($newParam, $value, $isIndex);
				$newTemplateParam = new TemplateParameter($param->getName(), $param->getValue(), $param->isIndex());
				$newTemplate[trim($param->getName())] = $newTemplateParam;
			} else {
				$newTemplateParam = new TemplateParameter($param->getName(), $param->getValue(), $param->isIndex());
				$newTemplate[trim($param->getName())] = $newTemplateParam;
			}
		}
		
		$this->template = $newTemplate;
		
		return $this;
	}
	
	/**
	* add a parameter and a value after another parameter
	* if $after is not set in the template it will attempt to add the parameter at the end of the template
	* if the new parameter is already set the template will not be changed
	*
	* @param string $after  the name of the param after which the new param should be added
	* @param string $param  the name of the parameter to add
	* @param string $value  the content of the value to add
	* @param bool $isIndex  whether the parameter is an index
	* @return Template      itself to allow the chaining of calls
	* @access public
	*/
	public function addParamAfter(string $after, string $newParam, string $value, bool $isIndex = false) : Template {
		if(!is_array($this->template)) { throw new Exception(self::TEMPLATE_UNSUPPORTED_OPERATION); }
		if(!$this->contains($after)) {
			return $this->addParam($newParam, $value);
		}
		
		$newTemplate = array();
		
		foreach($this->getParams() as $param) {
			if(trim($param->getName()) === trim($after)) {
				$newTemplate[trim($param)] = new TemplateParameter($param->getName(), $param->getValue(), $param->isIndex());
				$newTemplate[trim($newParam)] = new TemplateParameter($newParam, $value, $isIndex);
			} else {
				$newTemplate[trim($param)] = new TemplateParameter($param->getName(), $param->getValue(), $param->isIndex());
			}
		}
		
		$this->template = $newTemplate;
		
		return $this;
	}
	
	/**
	* remove an existing parameter from the template
	*
	* @param string $param  the name of the parameter to remove
	* @return Template      itself to allow the chaining of calls
	* @access public
	*/
	public function removeParam(string $param) : Template {
		if(!is_array($this->template)) { throw new Exception(self::TEMPLATE_UNSUPPORTED_OPERATION); }
		unset($this->template[$param]);
		return $this;
	}
	
	/**
	* rename a parameter in a template without changing it's value
	* if the parameter does not exists, the template will not be changed
	*
	* @param string $oldName  the old name of the parameter
	* @param string $newName  the new name of the parameter
	* @return Template        itself to allow the chaining of calls
	* @access public
	*/
	public function renameParam(string $oldName, string $newName) : Template {
		if(!is_array($this->template)) { throw new Exception(self::TEMPLATE_UNSUPPORTED_OPERATION); }
		if(!$this->contains($oldName)) {
			return $this;
		}
		
		$newTemplate = array();
		
		foreach($this->getParams() as $param) {
			if(trim($param->getName()) === trim($oldName)) {
				$newTemplate[trim($newName)] = new TemplateParameter($newName, $param->getValue());
			} else {
				$newTemplate[trim($param)] = new TemplateParameter($param->getName(), $param->getValue());
			}
		}
		
		$this->template = $newTemplate;
		
		return $this;
	}
	
	/**
	* add or overwrite an existing parameter and a value to a template
	*
	* @param string $param  the name of the parameter to add
	* @param string $value  the content of the value to add
	* @return Template      itself to allow the chaining of calls
	* @access public
	*/
	public function setParam(string $param, string $value, bool $isIndex) : Template {
		if(!is_array($this->template)) { throw new Exception(self::TEMPLATE_UNSUPPORTED_OPERATION); }
		$this->template[trim($param)] = new TemplateParameter($param, $value, $isIndex);
		return $this;
	}
	
	/**
	* set or overwrite current template with a string
	*
	* @param string $text  the text to set
	* @return Template     itself to allow the chaining of calls
	* @access public
	*/
	public function setText(string $text) : Template {
		$this->template = $text;
		return $this;
	}
	
	/**
	* do a string replace on a text template
	*
	* @param string $search   the search value
	* @param string $replace  the replace value
	* @param int $limit       maximum number of replacements
	* @retunr Template        itself to allow the chaining of calls
	* @access public
	*/
	public function strReplace(string $search, string $replace, int $limit = 0) : Template {
		if(!$this->isString()) { throw new Exception(self::TEMPLATE_UNSUPPORTED_OPERATION); }
		$this->template = str_replace($search, $replace, $this->template, $limit);
		return $this;
	}
	
	/**
	* rebuilds the template from the parameters
	*
	* @return string  the string representation of the template
	* @access public
	*/
	public function rebuild() : string {
		if(is_array($this->template)) {
			$s = "{{" . ($this->isArg() ? "{" : "") . $this->getTitle();
			foreach($this->template as $param) {
				if($param->isIndex()) {
					if(is_array($param->getValue())) {
						$s .= "|";
						foreach($param->getValue() as $subTemplate) {
							$s .= $subTemplate->rebuild();
						}
					} else {
						$s .= "|" . $param->getValue();
					}
				} else {
					if(is_array($param->getValue())) {
						$s .= "|" . $param->getName() . "=";
						foreach($param->getValue() as $subTemplate) {
							$s .= $subTemplate->rebuild();
						}
					} else {
						$s .= "|" . $param->getName() . "=" . $param->getValue();
					}
				}
			}
			return $s . ($this->isArg() ? "}" : "") . "}}";
		} else {
			return $this->template;
		}
	}
	
	/**
	* rebuilder for a specific param
	*
	* @param string $param  the parameter name to look for
	* @return string        the string value of the parameter
	* @access public
	*/
	public function rebuildParam(string $param) : string {
		if(!$this->contains($param)) { throw new Exception("Parameter {$param} does not exist"); }
		
		if(is_array($this->template[$param]->getValue())) {
			$s = "";
			foreach($this->template[$param]->getValue() as $subTemplate) {
				$s .= $subTemplate->rebuild();
			}
			return $s;
		} else {
			return $this->template[$param]->getValue();
		}
	}
	
	/**
	* debug function
	*
	* @return array  debug info about this object
	* @access public
	*/
	public function __debugInfo() : array {
		$info = array();
		if(isset($this->title)) { $info["title"] = $this->title; }
		if(!empty($this->titleArgs)) { $info["titleArgs"] = $this->titleArgs; }
		if(isset($this->template)) { $info["template"] = $this->template; }
		if(isset($this->isArg)) { $info["isArg"] = $this->isArg; }
		return $info;
	}
}