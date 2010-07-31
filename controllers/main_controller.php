<?php
class MainController {
	public function index() {
		$data = array(
			'message' => 'Hello world!'
		);
		Paraglide::render_view('main/index', $data);
	}
}
