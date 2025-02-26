<?php
defined('BASEPATH') or exit('No direct script access allowed');
use Google\Client;
use Google\Service\Gmail;
require_once FCPATH . 'vendor/autoload.php';

class Gmaill extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');

        if (!isset($_ENV['GOOGLE_CLIENT_ID']) || !isset($_ENV['GOOGLE_CLIENT_SECRET'])) {
            die("Error: GOOGLE_CLIENT_ID o GOOGLE_CLIENT_SECRET no están definidos en .env");
        }
    }

    private function getClient()
    {
        $client = new Google_Client();
        $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
        $client->setRedirectUri('http://localhost/apigmail/gmail');
        $client->addScope(Google_Service_Gmail::GMAIL_MODIFY);
        $client->setAccessType('offline'); // 🔴 Para obtener refresh_token
        $client->setPrompt('consent'); // 🔴 Forzar para recibir siempre refresh_token

        return $client;
    }

    private function getAccessToken($code)
    {
        $client_id = $_ENV['GOOGLE_CLIENT_ID'];
        $client_secret = $_ENV['GOOGLE_CLIENT_SECRET'];
        $redirect_uri = 'http://localhost/apigmail/gmail';
        $token_url = 'https://oauth2.googleapis.com/token';

        $data = [
            'code' => $code,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri,
            'grant_type' => 'authorization_code'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $token_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $result = curl_exec($ch);
        curl_close($ch);

        if ($result === false) {
            die("Error obteniendo el token");
        }

        return json_decode($result, true);
    }


    public function index()
    {
        $client = $this->getClient();

        if (!isset($_GET['code'])) {
            $authUrl = $client->createAuthUrl();
            header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
            exit;
        }

        if (isset($_GET['code'])) {
            try {
                die('hola');
                // julian
                // $accessToken = $this->getAccessToken($_GET['code']);
                // finJulian

                // andres
                $accessToken = $this->exchangeToken($_GET['code']);
                var_dump($accessToken);
                die();
                // fin andres


                if (!isset($accessToken['access_token'])) {
                    die("Error: No se pudo obtener el token de acceso :)");
                }

                // Guardar tokens en sesión
                $this->session->set_userdata('access_token', $accessToken['access_token']);

                if (isset($accessToken['refresh_token'])) {
                    $this->session->set_userdata('refresh_token', $accessToken['refresh_token']);
                }

                if (!$accessToken) {
                    die("Error: No hay token de acceso. Autentícate nuevamente.");
                }
                // julian
                // $client->setAccessToken(json_encode($accessToken));
                // finJulian

                // andres
                $this->getEmail($accessToken);
                // finAndres

                // julian    
                // $service = new Google_Service_Gmail($client);

                // $messagesResponse = $service->users_messages->listUsersMessages('me', ['maxResults' => 10]);


                // if (!$messagesResponse || !isset($messagesResponse->messages)) {
                //     die("Error: No se pudieron obtener los correos.");
                // }

                // $data['messages'] = $messagesResponse->getMessages();
                // $this->load->view('gmail_inbox', $data);
                // FinJulian
                //  redirect('gmail/inbox');

            } catch (Exception $e) {
                die("Error al autenticar: " . $e->getMessage());
            }
        } else {
            $authUrl = $client->createAuthUrl();
            redirect($authUrl);
        }
    }

    // andres
    public function exchangeToken($code)
    {
        if (!$code) {
            COMMON::sendResponse('', 1, 'Debes enviar un código de seguridad', TRUE, REST_Controller::HTTP_OK);
        }
        $client = $this->getClient();

        // Añade parámetro para obtener refresh_token

        $token = $client->fetchAccessTokenWithAuthCode($code);
        return $token;
        // var_dump($token);
        // die();

        // COMMON::set_global_config("gmailAutToken", json_encode($token));

        // // Verifica si incluye refresh_token
        // if (!isset($token['refresh_token'])) {
        //     COMMON::sendResponse('', 1, 'Falta refresh_token', TRUE, 400);
        // }

        // // Guarda $token (incluye refresh_token) en tu base de datos/sesión
        // COMMON::sendResponse(TRUE);
    }

    public function getEmail($token)
    {


        if (!isset($token)) {
            COMMON::sendResponse('error', 1, 'Access Token is required', TRUE, 400);
        }


        try {
            var_dump($token);
            die();
            $client = $this->getClient();
            $client->setAccessToken($token);

            // if ($client->isAccessTokenExpired()) {
            //     $newToken = GMAILMANAGER::refreshToken($token['refresh_token']);
            //     // Actualiza $token en tu base de datos
            //     $client->setAccessToken($newToken);
            // }

            $service = new Gmail($client);
            $user = "jgamboasana@gmail.com";

            // Obtener la lista de correos
            $messages = $service->users_messages->listUsersMessages($user, ['q' => 'in:inbox is:unread']);
            $emails = [];
            foreach ($messages->getMessages() as $message) {
                $msg = $service->users_messages->get($user, $message->getId());
                $headers = $msg->getPayload()->getHeaders();

                $subject = "";
                foreach ($headers as $header) {
                    if ($header->getName() == "Subject") {
                        $subject = $header->getValue();
                        break;
                    }
                }

                $emails[] = [
                    'id' => $message->getId(),
                    'subject' => $subject
                ];
            }
            var_dump($emails);


        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    // fin andres

    // ia no implementado
    public function getUnreadEmails()
    {
        // Recupera el token de acceso desde la sesión o donde lo hayas guardado.
        $accessToken = $this->session->userdata('access_token');
        if (!$accessToken) {
            die("Error: No se encontró el token de acceso.");
        }

        // Configura el cliente con el token de acceso.
        $client = $this->getClient();
        $client->setAccessToken($accessToken);

        // Crea la instancia del servicio de Gmail.
        $gmailService = new Google_Service_Gmail($client);

        // Define los parámetros de consulta para obtener solo los correos no leídos.
        $optParams = [
            'q' => 'is:unread'
        ];

        try {
            // Obtén la lista de mensajes (puede ser paginada).
            $messagesResponse = $gmailService->users_messages->listUsersMessages('me', $optParams);

            // Verifica si se encontraron mensajes.
            if (empty($messagesResponse->getMessages())) {
                echo "No hay correos no leídos.";
                return;
            }

            // Recorre cada mensaje para procesarlo o mostrar información.
            foreach ($messagesResponse->getMessages() as $message) {
                // Obtén el mensaje completo a partir de su ID.
                $msg = $gmailService->users_messages->get('me', $message->getId());

                // Ejemplo: extraer el asunto del correo.
                $subject = $this->getHeader($msg, 'Subject');

                echo "ID: " . $message->getId() . " - Asunto: " . $subject . "<br />";
            }
        } catch (Exception $e) {
            echo "Error al obtener correos: " . $e->getMessage();
        }
    }

    /**
     * Función auxiliar para extraer un header específico del mensaje.
     */
    private function getHeader($message, $headerName)
    {
        $headers = $message->getPayload()->getHeaders();
        foreach ($headers as $header) {
            if ($header->getName() == $headerName) {
                return $header->getValue();
            }
        }
        return "Sin asunto";
    }
// fin ia no implementado

// ia re escrito inbox e idex en uno solo linea 101
    public function inbox()
    {
        $client = $this->getClient();
        $accessToken = $this->session->userdata('access_token');
        $refreshToken = $this->session->userdata('refresh_token');

        if (!$accessToken) {
            die("Error: No hay token de acceso. Autentícate nuevamente.");
        }
        echo $accessToken;
        echo "<br>";
        echo $refreshToken;
        $client->setAccessToken($accessToken);
        if ($client->isAccessTokenExpired()) {
            echo "a";
            if (!$refreshToken) {
                die("Error: No hay refresh token disponible. Debes autenticarse de nuevo.");
            }

            $newAccessToken = $this->refreshAccessToken($refreshToken);
            $this->session->set_userdata('access_token', $newAccessToken['access_token']);
        }
        echo "b";

        $service = new Google_Service_Gmail($client);
        $messagesResponse = $service->users_messages->listUsersMessages('me', ['maxResults' => 10]);

        if (!$messagesResponse || !isset($messagesResponse->messages)) {
            die("Error: No se pudieron obtener los correos.");
        }

        $data['messages'] = $messagesResponse->getMessages();
        $this->load->view('gmail_inbox', $data);
    }
// fin ia re escrito

// no sirve
    private function refreshAccessToken($refreshToken)
    {
        $client_id = $_ENV['GOOGLE_CLIENT_ID'];
        $client_secret = $_ENV['GOOGLE_CLIENT_SECRET'];
        $token_url = 'https://oauth2.googleapis.com/token';

        $data = [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token'
        ];

        $options = [
            'http' => [
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ],
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($token_url, false, $context);

        if ($result === FALSE) {
            die("Error renovando el token");
        }

        return json_decode($result, true);
    }
    // fin no sirve
}
