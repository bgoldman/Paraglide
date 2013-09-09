<?php
// parse location
$dir = realpath(dirname(__FILE__) . '/../../') . '/';
$_SERVER['DOCUMENT_ROOT'] = $dir . 'public/';
$_SERVER['SCRIPT_FILENAME'] = $dir . 'public/index.php';
$_SERVER['SERVER_PORT'] = 80;
$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_FILENAME'];

// parse get
$get_string = !empty($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '';
parse_str($get_string, $_GET);

// include paraglide
require_once $dir . 'lib/paraglide/paraglide.php';
