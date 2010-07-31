<?php
class MainController {
	public function index() {
		$data = array(
			'message' => 'Hello world!'
		);
		Web::render_view('main/index', $data);
	}
}
