<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Cargar la librería de Google API Client
require_once FCPATH . 'vendor/autoload.php';

class Gmail extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session'); // Cargar la librería de sesión
        $this->load->helper('url'); // Cargar el helper de URL
    }

    public function index() {
        // Crear una instancia del cliente de Google
        $client = new Google_Client();

        // Configurar las credenciales usando las variables de entorno
        $client->setClientId($_ENV['GOOGLE_CLIENT_ID']); // Client ID desde .env
        $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']); // Client Secret desde .env
        $client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']); // Redirect URI desde .env

        // Añadir los scopes necesarios
        $client->addScope(Google_Service_Gmail::GMAIL_READONLY);
        $client->addScope(Google_Service_Gmail::GMAIL_COMPOSE);

        // Configurar el tipo de acceso y el prompt
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        // Verificar si hay un código de autorización en la URL
        if (isset($_GET['code'])) {
            // Autenticar al cliente con el código de autorización
            $client->authenticate($_GET['code']);

            // Obtener el token de acceso
            $accessToken = $client->getAccessToken();

            // Guardar el token de acceso en la sesión
            $this->session->set_userdata('access_token', $accessToken);

            // Redirigir al usuario a la vista de la bandeja de entrada
            redirect('gmail/inbox');
        } else {
            // Si no hay código de autorización, redirigir al usuario a la página de autenticación de Google
            $authUrl = $client->createAuthUrl();
            redirect($authUrl);
        }
    }

    public function inbox() {
        // Crear una instancia del cliente de Google
        $client = new Google_Client();

        // Configurar las credenciales usando las variables de entorno
        $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);

        // Obtener el token de acceso desde la sesión
        $client->setAccessToken($this->session->userdata('access_token'));

        // Verificar si el token de acceso ha expirado
        if ($client->isAccessTokenExpired()) {
            // Obtener el token de actualización y generar un nuevo token de acceso
            $refreshToken = $client->getRefreshToken();
            $client->fetchAccessTokenWithRefreshToken($refreshToken);

            // Guardar el nuevo token de acceso en la sesión
            $this->session->set_userdata('access_token', $client->getAccessToken());
        }

        // Crear una instancia del servicio Gmail
        $service = new Google_Service_Gmail($client);

        // Obtener los últimos 10 mensajes de la bandeja de entrada
        $messages = $service->users_messages->listUsersMessages('me', ['maxResults' => 10]);

        // Preparar los datos para la vista
        $data['messages'] = $messages->getMessages();

        // Cargar la vista con los mensajes
        $this->load->view('gmail_inbox', $data);
    }
}