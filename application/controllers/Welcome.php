<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

	public function index()
	{
		echo "GOOGLE_CLIENT_ID: " . $_ENV['GOOGLE_CLIENT_ID'] . "<br>"; // Mostrar el Client ID
        echo "GOOGLE_CLIENT_SECRET: " . $_ENV['GOOGLE_CLIENT_SECRET'];
		$this->load->view('welcome_message');
	}
}
