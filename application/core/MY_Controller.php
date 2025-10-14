<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('user_m');
        $this->load->library('session');
 
        // Verificar si el usuario está logueado y activo
        $loggedin_result = $this->user_m->loggedin();
        error_log("[DIAGNOSTIC] MY_Controller constructor: loggedin() result: " . ($loggedin_result ? 'true' : 'false'));
        if (!$loggedin_result) {
            error_log("[DIAGNOSTIC] MY_Controller constructor: redirecting to login because loggedin() false");
            $this->session->set_flashdata('error', 'Su cuenta está inactiva o la sesión ha expirado.');
            redirect('user/login');
        }
        error_log("[DIAGNOSTIC] MY_Controller constructor: user is logged in, proceeding");
    }

}