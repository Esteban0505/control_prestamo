<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customer_model extends CI_Model {

    public function get_all() {
        return $this->db->get('customers')->result();
    }

    public function get($id) {
        return $this->db->get_where('customers', array('id' => $id))->row();
    }

    public function insert($data) {
        $this->db->insert('customers', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('customers', $data);
    }

    public function delete($id) {
        $this->db->where('id', $id);
        return $this->db->delete('customers');
    }
}
