<?php
class MainController {
	public function index() {
		Paraglide::render_view('main/index', array(
			'message' => 'Hello world!'
		));
	}
}
