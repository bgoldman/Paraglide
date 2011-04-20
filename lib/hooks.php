<?php
require_once APP_PATH . 'lib/classes/paragon/paragon.php';
require_once APP_PATH . 'lib/classes/paragon/paragon_drivers/mysqli_master_slave_driver.php';
require_once APP_PATH . 'lib/classes/paragon/validator.php';

class Hooks {
	// this is called very early in the page request, before the controller are loaded
	static public function init() {
		$mysqli_driver = new MysqliMasterSlaveDriver(array(
			'master' => $GLOBALS['database'],
			'slave' => $GLOBALS['database'],
		));
		Paragon::set_connection($mysqli_driver);
	}
	
	// this is called before the controller is processed
	static public function preprocess() {
	}
	
	// this is called after the controller is processed
	static public function postprocess() {
	}
}
