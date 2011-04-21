<?php
/*
paraglide.php
Copyright (c) 2011 Brandon Goldman
Released under the MIT License.
*/

Paraglide::init();

class Paraglide {
	private static $_data = array();
	private static $_done_loading = false;
	private static $_controller_instance = null;
	private static $_request_types = array(
		'html' => 'text/html',
		'json' => 'text/javascript',
		'rss' => 'application/rss+xml',
		'txt' => 'text/plain',
		'xml' => 'text/xml',
	);
	
	public static $action = null;
	public static $controller = null;
	public static $layout = null;
	public static $nested_dir = '';
	public static $request_type = null;
	public static $wrapper = null;
	
	private static function _execute_hook($source = null, $hook) {
		if ($source == 'file') {
			if (file_exists(APP_PATH . 'lib/hooks.php')) {
				require_once APP_PATH . 'lib/hooks.php';
			}
			
			if (method_exists('Hooks', $hook)) {
				call_user_func(array('Hooks', $hook));
			}
			
			return;
		}

		if ($source == 'controller') {
			if (method_exists(self::$_controller_instance, '_' . $hook)) {
				call_user_func(array(self::$_controller_instance, '_' . $hook));
			}
		}
	}

	private static function _inflect_camelize($word) {
		return str_replace(' ', '', ucwords(str_replace(array('_', ' '), ' ', $word)));
	}
	
	private static function _inflect_underscore($word) {
		$return = '';
		
		for ($i = 0; $i < strlen($word); $i++) {
			$letter = $word{$i};

			if (strtolower($letter) !== $letter) {
				if ($i != 0) $return .= '_';
				$letter = strtolower($letter);
			}

			$return .= $letter;
		}

		return $return;
	}
	
	private static function _render() {
		$master_view = 'layout';
		$modal_view = self::$nested_dir . 'modal.layout';
		$nested_view = self::$nested_dir . 'layout';
		$controller_view = self::$nested_dir . self::$controller . '/layout';
		$controller_wrapper = self::$nested_dir . self::$controller . '/wrapper';
		$local_view = self::$nested_dir . self::$controller . '/' . self::$action . '.layout';
		$local_wrapper = self::$nested_dir . self::$controller . '/' . self::$action . '.wrapper';

		if (!empty(self::$wrapper) && file_exists(APP_PATH . 'views/' . self::$wrapper . '.tpl')) {
			$wrapper = self::$wrapper;
		} elseif (file_exists(APP_PATH . 'views/' . $local_wrapper . '.tpl')) {
			$wrapper = $local_wrapper;
		} else if (file_exists(APP_PATH . 'views/' . $controller_wrapper . '.tpl')) {
			$wrapper = $controller_wrapper;
		}

		if (!empty(self::$layout) && file_exists(APP_PATH . 'views/' . self::$layout . '.tpl')) {
			$view = self::$layout;
		} elseif (!empty($_GET['modal'])) {
			$view = $modal_view;
		} elseif (file_exists(APP_PATH . 'views/' . $local_view . '.tpl')) {
			$view = $local_view;
		} elseif (file_exists(APP_PATH . 'views/' . $controller_view . '.tpl')) {
			$view = $controller_view;
		} elseif (file_exists(APP_PATH . 'views/' . $nested_view . '.tpl')) {
			$view = $nested_view;
		} else {
			$view = $master_view;
		}

		if (!empty($wrapper)) {
			ob_start();
			self::render_view($wrapper);
			self::$_data['PAGE_CONTENT'] = ob_get_clean();
		}

		self::render_view($view);
		self::$_done_loading = true;
	}
	
	private static function _set_cache() {
		if (empty($GLOBALS['config']['cache'])) {
			return;
		}
		
		if (empty($GLOBALS['config']['cache'][ENVIRONMENT])) {
			self::error('Cache config not found for environment \'' . ENVIRONMENT . '\' in <strong>cache.cfg</strong>');
		}
		
		$c = $GLOBALS['config']['cache'][ENVIRONMENT];
		$default_class = 'Memcache';
		$class = $default_class;
		
		if (!empty($c['class']) && $c['class'] != $default_class) {
			$class = $c['class'];
			$filename = APP_PATH . 'lib/classes/' . self::_inflect_underscore($class) . '.php';

			if (!file_exists($filename)) {
				self::error('Cache class \'' . $class . '\' not found at <em>' . $filename . '</em> in <strong>cache.cfg</strong>');
			}

			require_once $filename;
		}

		$GLOBALS['cache'] = new $class();
		$servers = explode(',', $c['servers']);
		
		foreach ($servers as $key => $server) {
			$server_parts = explode(':', $server);
			$host = $server_parts[0];
			$port = !empty($server_parts[1]) ? $server_parts[1] : null;
			$GLOBALS['cache']->addServer($host, $port, false);
		}
	}
	
	private static function _set_config_and_environment() {
		// set the main config first, because the environment relies on options in this config
		$GLOBALS['config'] = array();
		$GLOBALS['config']['app'] = self::parse_config('app');
		
		// set the environment
		self::_set_environment();
		
		// set the other configs later, because they rely on the environment
		$GLOBALS['config']['cache'] = self::parse_config('cache', true);
		$GLOBALS['config']['database'] = self::parse_config('database', true);
		$GLOBALS['config']['mail'] = self::parse_config('mail', true);
		
		// DEFAULT_CONTROLLER is the controller your application executes if the one being accessed doesn't exist or one isn't provided (it's usually main)
		define('DEFAULT_CONTROLLER', $GLOBALS['config']['app']['main']['default_controller']);
		
		// make sure the default controller exists
		if (!file_exists(APP_PATH . 'controllers/' . DEFAULT_CONTROLLER . '_controller.php')) {
			self::error('Missing required default controller file <strong>controllers/' . DEFAULT_CONTROLLER . '_controller.php</strong>');
		}
	}
	
	private static function _set_constants() {
		if (empty($_SERVER['REQUEST_URI'])) {
			$_SERVER['REQUEST_URI'] = '/';
		}

		// APP_PATH is the full server path to the directory of this application, with leading and trailing slashes
		// example: for http://www.example.com/shop/index.php/categories/5?size=medium, APP_PATH is something like /home/example.com/
		define('APP_PATH', dirname(__FILE__) . '/');

		// SITE_PATH is the full server path to the directory of this application relative to the domain, with leading and trailing slashes
		// example: for http://www.example.com/shop/index.php/categories/5?size=medium, SITE_PATH is something like /home/example.com/public_html/shop/
		define('SITE_PATH', dirname($_SERVER['SCRIPT_FILENAME']) . '/');
		
		// SITE_ROOT is the path of this application relative to the domain, with leading and trailing slashes
		// example: for http://www.example.com/shop/index.php/categories/5?size=medium, SITE_ROOT is /shop/
		$docroot = realpath($_SERVER['DOCUMENT_ROOT']);
		if (substr($docroot, -1) == '/') $docroot = substr($docroot, 0, -1);
		if ($docroot === false) $docroot = '';
		define('SITE_ROOT', substr(SITE_PATH, strlen($docroot)));
		
		// CURRENT_URL is the this request's URL relative to the domain, with a leading slash and with or without a trailing slash
		// example: for http://www.example.com/shop/categories/5?size=medium, CURRENT_URL is /shop/categories/5?size=medium
		define('CURRENT_URL', $_SERVER['REQUEST_URI']);
		
		// SITE_FILENAME is the path and real filename of this application relative to the domain, with leading and trailing slashes
		// example: for http://www.example.com/shop/index[.php]/categories/5?size=medium, SITE_FILENAME is /shop/index.php/
		define('SITE_FILENAME', substr($_SERVER['SCRIPT_FILENAME'], strlen($_SERVER['DOCUMENT_ROOT'])));
		
		// LOCATION is this request's URL relative to SITE_ROOT, excluding the query string, without leading or trailing slashes
		// example: for http://www.example.com/shop/categories/5?size=medium, LOCATION is categories/5
		$location = $_SERVER['REQUEST_URI'];
		if (strpos($location, '?') != false) $location = substr($location, 0, strpos($location, '?'));
		if (substr($location, 0, strlen(SITE_FILENAME)) == SITE_FILENAME) $location = substr($location, strlen(SITE_FILENAME));
		if ($location === false) $location = '';
		if (substr($location, 0, strlen(SITE_ROOT)) == SITE_ROOT) $location = substr($location, strlen(SITE_ROOT));
		if (substr($location, 0, 1) == '/') $location = substr($location, 1);
		define('LOCATION', $location);
		
		// SITE_URL is path and accessed filename of this application relative to the domain, with leading and trailing slashes
		// example: for http://www.example.com/shop/index[.php]/categories/5?size=medium, SITE_URL is /shop/index[.php]/
		$uri = $_SERVER['REQUEST_URI'];
		$pos = strpos($uri, '?');
		if ($pos != false) $uri = substr($uri, 0, $pos);
		if (LOCATION != '') $uri = substr($uri, 0, -strlen(LOCATION));
		define('SITE_URL', $uri);
	}
	
	private static function _set_database() {
		if (empty($GLOBALS['config']['database'])) {
			return;
		}
		
		$GLOBALS['databases'] = array();
		
		$configs = array();
		
		foreach ($GLOBALS['config']['database'] as $key => $config) {
			if (
				$key != ENVIRONMENT
				&& substr($key, 0, strlen(ENVIRONMENT . '-')) != ENVIRONMENT . '-'
			) {
				continue;
			}
			
			if ($key == ENVIRONMENT) {
				$key = ENVIRONMENT . '-_default_';
			}

			$k = $key;
			$key = substr($key, strlen(ENVIRONMENT . '-'));
			$last_char = substr($key, -1);
			$last_digit = (int) $last_char;
			$last_digit = (string) $last_digit;
			$last_two_chars = substr($key, -2);
			$last_two_digits = (int) $last_two_chars;
			$last_two_digits = (string) $last_two_digits;
			
			if ($last_two_digits === $last_two_chars) {
				$key = substr($key, 0, -2);
			}
			
			if ($last_digit === $last_char) {
				$key = substr($key, 0, -1);
			}

			if (empty($configs[$key])) $configs[$key] = array();
			$configs[$key][] = $config;
		}

		foreach ($configs as $key => $key_configs) {
			shuffle($key_configs);
			$configs[$key] = $key_configs;
		}

		foreach ($configs as $key => $key_configs) {
			$default_class = 'mysqli';
			
			foreach ($key_configs as $config) {
				$class = $default_class;

				if (!empty($config['class']) && $config['class'] != $default_class) {
					$class = $config['class'];
					$filename = APP_PATH . 'lib/classes/' . self::_inflect_underscore($class) . '.php';
			
					if (!file_exists($filename)) {
						self::error('Database class \'' . $class . '\' not found at <em>' . $filename . '</em> in <strong>database.cfg</strong>');
					}
			
					require_once $filename;
				}
	
				$database = @new $class($config['server'], $config['username'], $config['password']);
		
				if (!$database) {
					continue;
				}
		
				if (!$database->select_db($config['name'])) {
					continue;
				}

				$GLOBALS['databases'][$key] = $database;
				break;
			}
		}
		
		if (empty($GLOBALS['databases'])) {
			self::error('Database config not found for environment \'' . ENVIRONMENT . '\' in <strong>database.cfg</strong>');
		}

		$GLOBALS['database'] = reset($GLOBALS['databases']);
	}

	private static function _set_environment() {
		if (empty($_SERVER['REQUEST_URI'])) $_SERVER['REQUEST_URI'] = '';
		if (empty($_SERVER['HTTP_HOST'])) $_SERVER['HTTP_HOST'] = 'localhost';
		$server = strtolower($_SERVER['HTTP_HOST']);
		
		$environment_conf = !empty($GLOBALS['config']['app']['environments']) ? $GLOBALS['config']['app']['environments'] : array();
		$deployment_conf = !empty($GLOBALS['config']['app']['deployments']) ? $GLOBALS['config']['app']['deployments'] : array('main' => $server);
		$confs = array(
			'ENVIRONMENT' => $environment_conf,
			'DEPLOYMENT' => $deployment_conf,
		);
		
		foreach ($confs as $const => $conf) {
			foreach ($conf as $env => $domains) {
				$domains_array = explode(',', $domains);
			
				foreach ($domains_array as $domain) {
					$domain = trim($domain);
					$domain = strtolower($domain);
					$domain = str_replace('.', '\.', $domain);
					$domain = str_replace('*', '.+', $domain);
					if (!preg_match('/' . $domain . '/', $server)) continue;
					define($const, $env);
					break;
				}
		
				if (defined($const)) break;
			}
			
			if (!defined($const)) {
				$const_lower = strtolower($const);
				self::error('No ' . $const_lower . ' found for \'' . $server . '\' in <strong>config/app.cfg</strong>');
			}
		}
		
		error_reporting(ENVIRONMENT == 'live' ? 0 : E_ALL);
	}
	
	private static function _to_output_array($data) {
		foreach ($data as $key => $value) {
			if (is_array($value)) {
				$data[$key] = self::_to_output_array($value);
			} elseif (is_object($value) && method_exists($value, '__toArray')) {
				$data[$key] = $value->__toArray();
			}
		}
		
		return $data;
	}

	public static function error($message) {
		if (!file_exists(APP_PATH . 'views/framework_error.tpl')) {
			die($message);
		}
		
		require_once APP_PATH . 'views/framework_error.tpl';
		die;
	}
	
	public static function init() {
		self::_set_constants();
		self::_set_config_and_environment();
		self::_set_database();
		self::_set_cache();
		self::_execute_hook('file', 'init');
		$GLOBALS['data'] = array();
	}
	
	public static function load($location) {
		// fix the location
		if (strlen($location) > 0 && substr($location, 0, 1) == '/') $location = substr($location, 1);
	
		// set the query string if passed in
		$location_parts = explode('?', $location, 2);
		$location = $location_parts[0];

		if (!empty($location_parts[1])) {
			$query_string = $location_parts[1];
			$query_string_parts = explode('&', $query_string);
			$_GET = array();
			
			foreach ($query_string_parts as $part) {
				$pair = explode('=', $part);
				$_GET[$pair[0]] = isset($pair[1]) ? $pair[1] : null;
			}
		}
		
		if (empty(self::$request_type)) {
			// set the request type
			$pos = strrpos($location, '.');

			if ($pos != false) {
				$type = substr($location, $pos + 1);
			
				if (!empty(self::$_request_types[$type])) {
					self::$request_type = $type;
					$location = substr($location, 0, $pos);
				}
			}
			
			if (empty(self::$request_type)) {
				self::$request_type = 'html';
			}
		}
	
		// perform routing
		$GLOBALS['arguments'] = array();
		$arguments = explode('/', $location);
		$nested_dirs = $arguments;
		$controller = DEFAULT_CONTROLLER;
		self::$nested_dir = '';
		
		while (true) {
			$try = APP_PATH . 'controllers/';
			$try_controller = str_replace('-', '_', DEFAULT_CONTROLLER);
			$i = count($nested_dirs);
			foreach ($nested_dirs as $dir) $try .= str_replace('-', '_', $dir) . '/';
			
			if (is_dir($try)) {
				$try_controller = str_replace('-', '_', isset($arguments[$i]) ? $arguments[$i] : DEFAULT_CONTROLLER);

				if (!file_exists($try . $try_controller . '_controller.php')) {
					$try_controller = str_replace('-', '_', DEFAULT_CONTROLLER);
				} else {
					$i++;
				}
			}
			
			if (!file_exists($try . $try_controller . '_controller.php')) {
				if (count($nested_dirs) == 0) break;
				$nested_dirs = array_slice($nested_dirs, 0, -1);
				continue;
			}
			
			if (count($nested_dirs) > 0) self::$nested_dir = implode('/', $nested_dirs) . '/';
			$arguments = array_slice($arguments, $i);
			$controller = $try_controller;
			break;
		}

		// set the controller
		self::$controller = $controller;
		
		// run file preprocessing
		self::_execute_hook('file', 'preprocess');
		
		// init the controller
		if (empty(self::$nested_dir)) {
			$controller_file = 'controllers/' . self::$controller . '_controller.php';
		} else {
			$controller_file = 'controllers/' . self::$nested_dir . '/' . self::$controller . '_controller.php';
		}

		require_once $controller_file;
		$controller_class = str_replace(' ', '', self::_inflect_camelize(self::$controller)) . 'Controller';

		if (!class_exists($controller_class)) {
			self::error('Undefined controller class \'' . $controller_class . '\' in <strong>' . $controller_file . '</strong>');
		}

		self::$_controller_instance = new $controller_class();

		// load the classes, helpers, and models
		if (!empty(self::$_controller_instance->classes)) self::load_classes(self::$_controller_instance->classes);
		if (!empty(self::$_controller_instance->helpers)) self::load_helpers(self::$_controller_instance->helpers);
		if (!empty(self::$_controller_instance->models)) self::load_models(self::$_controller_instance->models);
		
		// set the function
		$action = !empty($arguments[0]) ? $arguments[0] : 'index';
		if (substr($action, 0, 1) == '_') $action = 'index';

		$function = str_replace('-', '_', $action);
		$function = method_exists(self::$_controller_instance, $function) ? $function : 'index';
		
		if (!empty($arguments[0]) && str_replace('-', '_', $arguments[0]) == $function) {
			$arguments = array_slice($arguments, 1);
		}
		
		foreach ($arguments as $key => $val) {
			$arguments[$key] = urldecode($val);
		}

		$GLOBALS['arguments'] = $arguments;
		
		self::$action = str_replace('_', '-', $function);

		if (!method_exists(self::$_controller_instance, 'index')) {
			self::error('Missing required function \'index\' in <strong>' . $controller_file . '</strong>');
		}
		
		// run controller preprocessing
		self::_execute_hook('controller', 'preprocess');

		// start buffering the output for display later
		ob_start();

		// run the controller and generate the view
		call_user_func_array(array(self::$_controller_instance, $function), $GLOBALS['arguments']);
		
		// if the request was redirected, stop here
		if (self::$_done_loading) {
			ob_end_flush(); // turn off output buffering so it's not nested
			return;
		}

		// get the content
		self::$_data['PAGE_CONTENT'] = ob_get_clean();
		
		// run any postprocessing
		self::_execute_hook('controller', 'postprocess');
		self::_execute_hook('file', 'postprocess');
		
		// render
		self::_render();
	}
	
	public static function load_classes($classes) {
		if (empty($classes)) {
			return;
		}
		
		self::load_files('class', 'lib/classes', $classes);
	}

	public static function load_files($type, $dir, $files) {
		if (empty($files) || !is_array($files)) {
			return;
		}

		foreach ($files as $file) {
			$filename = self::_inflect_underscore($file);
			$path = APP_PATH . $dir . "/{$filename}.php";

			if (!file_exists($path)) {
				$controller_file = 'controllers/' . self::$nested_dir . self::$controller . '_controller.php';
				self::error('Missing ' . $type . ' file \'' . $path . '\' referenced from <strong>' . $controller_file . '</strong>');
			}

			require_once $path;
		}
	}

	public static function load_helpers($helpers) {
		if (empty($helpers)) {
			return;
		}
		
		self::load_files('helper', 'lib/helpers', $helpers);
	}

	public static function load_models($models) {
		if (empty($models)) {
			return;
		}
		
		self::load_files('model', 'models', $models);
	}

	public static function long_url($controller = null, $action = null, $params = null, $query_string = null, $ssl = false) {
		$url = self::url($controller, $action, $params, $query_string, $ssl);
		if (substr($url, 0, 7) == 'http://') return $url;
		if (substr($url, 0, 8) == 'https://') return $url;
		$prefix = ($ssl == true) ? 'https' : 'http';
		return $prefix . '://' . $_SERVER['HTTP_HOST'] . $url;
	}
	
	public static function parse_config($file, $ignore_errors = false) {
		$local_filename = APP_PATH . 'config/local/' . $file . '.cfg';
		if (file_exists($local_filename)) $filename = $local_filename;
		
		if (defined('DEPLOYMENT') && empty($filename)) {
			$deployment_filename = APP_PATH . 'config/deployments/' . DEPLOYMENT . '/' . $file . '.cfg';
			if (file_exists($deployment_filename)) $filename = $deployment_filename;
		}
		
		if (empty($filename)) {
			$filename = APP_PATH . 'config/' . $file . '.cfg';
			
			if (!file_exists($filename)) {
				if ($ignore_errors) return null;
				die('Config file \'config/' . $file . '.cfg\' not found');
			}
		}

		return parse_ini_file($filename, true);
	}

	public static function query_string($params) {
		if (empty($params) || !is_array($params)) {
			return '';
		}
		
		$parts = array();
		foreach ($params as $key => $val) $parts[] = urlencode($key) . '=' . urlencode($val);
		$string = '?' . implode('&', $parts);
		return $string;
	}

	public static function redirect($controller = null, $action = null, $params = null, $query_string = null, $ssl = false) {
		if (self::$request_type != 'html') {
			$url = $controller;
			
			if (strlen($action) > 0) {
				if (strlen($controller) > 0) $url .= '/';
				$url .= $action;
			}
			
			if (is_array($params)) $params = implode('/', $params);
			if ($params != '') $url .= '/' . $params;

			if (is_array($query_string)) $query_string = self::query_string($query_string);
			if ($query_string{0} != '?') $query_string = '?' . $query_string;
			if (strlen($query_string) == 1) $query_string = '';
			$url .= $query_string;
			
			if (!empty($_GET['jsonp'])) {
				$jsonp = $_GET['jsonp'];
			}
			
			$_GET = null;
			$_POST = null;
			
			if (!empty($jsonp)) {
				$_GET['jsonp'] = $jsonp;
			}
			
			self::load($url);
			die;
		}
	
		$url = self::url($controller, $action, $params, $query_string, $ssl);
		self::redirect_to($url);
	}

	public static function redirect_to($url) {
		header('Location: ' . $url);
		die;
	}

	public static function render_view($_view, $_data = null, $_buffer = false) {
		if (!$_buffer && self::$request_type == 'json') {
			$_data = self::_to_output_array($_data);
			$js = json_encode($_data);
			if (!empty($_GET['jsonp'])) $js = $_GET['jsonp'] . '(' . $js . ')';
			die($js);
		}
	
		if (!file_exists(APP_PATH . "views/{$_view}.tpl")) {
			self::error('View \'' . $_view . '\' not found at <strong>views/' . $_view . '.tpl</strong>');
		}

		foreach ($GLOBALS['data'] as $key => $val) {
			$$key = $val;
		}

		if (!empty($_data) && is_array($_data)) {
			self::$_data = array_merge(self::$_data, $_data);
		}
		
		foreach (self::$_data as $key => $val) {
			$$key = $val;
		}
		
		if ($_buffer) {
			ob_start();
		}

		require APP_PATH . 'views/' . $_view . '.tpl';
		
		if ($_buffer) {
			return ob_get_clean();
		}
	}

	public static function require_not_ssl() {
		if (empty($_SERVER['HTTPS'])) {
			return;
		}
		 
		$host = $_SERVER['HTTP_HOST'];
		$url = $_SERVER['REQUEST_URI'];
		self::redirect_to('http://' . $host . $url);
    }
    
	public static function require_json() {
		if (self::$request_type == 'json') {
			return;
		}
		
		$url = LOCATION . '.json';
		if (!empty($_GET)) $url .= self::_query_string($_GET);
		self::redirect_to($url);
	}

	public static function require_ssl() {
		if (!empty($_SERVER['HTTPS'])) {
			return;
		}
		
		$host = $_SERVER['HTTP_HOST'];
		$url = $_SERVER['REQUEST_URI'];
		self::redirect_to('https://' . $host . $url);
	}

	public static function url($controller = null, $action = null, $params = null, $query_string = null, $ssl = false) {
		if ($_SERVER['SERVER_PORT'] == 443 && $ssl == false) {
			$prefix = 'http://' . $_SERVER['HTTP_HOST'];
		} elseif ($_SERVER['SERVER_PORT'] != 443 && $ssl == true) {
			$prefix = 'https://' . $_SERVER['HTTP_HOST'];
		} else {
			$prefix = '';
		}

		$prefix .= SITE_URL;
		if (substr($prefix, -1) != '/') $prefix .= '/';
		$url = $prefix . $controller;
		
		if (strlen($action) > 0) {
			if (strlen($controller) > 0) $url .= '/';
			$url .= $action;
		}
		
		if (!empty($params)) {
			$new_params = array();
			if (!is_array($params)) $params = array($params);
		
			foreach ($params as $key => $value) {
				if (strlen($value) > 0) {
					$new_params[] = $value;
				}
			}
		
			$params = $new_params;
		}
		
		if (is_array($params)) $params = implode('/', $params);
		if ($params != '') $url .= '/' . $params;

		if (!empty($query_string)) {
			if (is_array($query_string)) $query_string = self::query_string($query_string);
			if ($query_string{0} != '?') $query_string = '?' . $query_string;
			if (strlen($query_string) == 1) $query_string = '';
			$url .= $query_string;
		}
		
		return $url;
	}
}
