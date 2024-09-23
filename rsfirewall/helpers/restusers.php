<?php

class RSFirewall_Helper_Restusers extends WP_REST_Users_Controller
{

    public static function get_instance(){
        static $inst;
        if (is_null($inst)) {
            $inst = new RSFirewall_Helper_Restusers();
        }

        return $inst;
    }

    public function get_url_base() {
        return rtrim($this->namespace . '/' . $this->rest_base, '/');
    }
}