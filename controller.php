<?php
/*
Paragon
Copyright (c) 2013 Brandon Goldman
Released under the MIT License.
*/

class Controller {
	private $_slug;
	
	public function __construct() {
		$controller = get_class($this);
		$controller_underscore = Paraglide::inflect_underscore($controller);
		
		if (substr($controller_underscore, -strlen('_controller')) == '_controller') {
			$controller_underscore = substr($controller_underscore, 0, -strlen('_controller'));
		}
		
		$this->_slug = Paraglide::$nested_dir;

		if ($controller_underscore != DEFAULT_CONTROLLER) {
			// we don't want to append the main controller's name to the url
			$this->_slug .= $controller_underscore;
		} else {
			// remove the trailing /
			$this->_slug = substr($this->_slug, 0, -1);
		}
	}
	
	public function redirect($action = null, $params = null, $query_string = null, $ssl = false) {
		return Paraglide::redirect($this->_slug, $action, $params, $query_string, $ssl);
	}
	
	public function render($view, $data = array(), $buffer = false) {
		return Paraglide::render($this->_slug . '/' . $view, $data, $buffer);
	}
	
	public function url($action = null, $params = null, $query_string = null, $ssl = false) {
		return Paraglide::url($this->_slug, $action, $params, $query_string, $ssl);
	}
}
