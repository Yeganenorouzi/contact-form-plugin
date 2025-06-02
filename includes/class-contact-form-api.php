<?php
class Contact_Form_API
{
    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes()
    {
        register_rest_route('contact-form/v1', '/messages', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_messages'),
            'permission_callback' => array($this, 'check_permission')
        ));

     
    }

    public function check_permission()
    {
        return is_user_logged_in();
    }

    public function get_messages($request)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contact_form_submissions';

        $submissions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

        return rest_ensure_response($submissions);
    }

   
}