<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_fcm_tokens_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    public function getFcmTokensByUserId($user_id) {
        $this->db->select('fcm_token');
        $this->db->from('user_fcm_tokens');
        $this->db->where('user_id', $user_id);
        $query = $this->db->get();
        return array_column($query->result_array(), 'fcm_token');
    }
}
