<?php
require_once APP_PATH . 'lib/classes/paragon/paragon.php';
require_once APP_PATH . 'lib/classes/paragon/paragon_drivers/mysqli_master_slave_driver.php';
require_once APP_PATH . 'lib/classes/paragon/validator.php';

class Hooks {
	static public function preprocess() {
		$mysqli_driver = new MysqliMasterSlaveDriver(array(
			'master' => $GLOBALS['database'],
			'slave' => $GLOBALS['database'],
		));
		Paragon::set_connection($mysqli_driver);
	}
	
	static public function postprocess() {
	}
}
