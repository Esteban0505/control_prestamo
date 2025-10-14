<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends CI_Controller {

  public function __construct()
  {
    parent::__construct();
    $this->load->library('session');
    $this->load->library('form_validation');
    $this->load->model('user_m');
  }

  public function login()
  {
    $dashboard = 'admin/dashboard';
    $loggedin_check = $this->user_m->loggedin();
    error_log("[DIAGNOSTIC] User login: loggedin() check result: " . ($loggedin_check ? 'true' : 'false'));
    if ($loggedin_check) {
      error_log("[DIAGNOSTIC] User login: redirecting to dashboard because already logged in");
      redirect($dashboard);
    }

    $rules = $this->user_m->rules;
    $this->form_validation->set_rules($rules);

    if ($this->form_validation->run() == TRUE) {

      if ($this->user_m->login() == TRUE) {
        error_log("[DIAGNOSTIC] User login: login successful, session data: " . json_encode($this->session->all_userdata()));
        error_log("[DIAGNOSTIC] User login: redirecting to dashboard");
        redirect($dashboard);
      }else{
        error_log("[DIAGNOSTIC] User login: login failed");
        $this->session->set_flashdata('msg', 'Escribir correctamente su email o password');
        redirect('user/login', 'refresh');
      }

    }

    $this->load->view('_login_layout');
  }

  public function logout()
  {
    $this->user_m->logout();
    redirect('user/login');
  }

}

/* End of file User.php */
/* Location: ./application/controllers/User.php */