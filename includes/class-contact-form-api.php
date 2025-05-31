<?php
class Contact_Form_API
{
    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes()
    {
        register_rest_route('contact-form/v1', '/submissions', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_submissions'),
            'permission_callback' => array($this, 'check_permission')
        ));

        register_rest_route('contact-form/v1', '/submissions', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_submission'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('contact-form/v1', '/submissions/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_submission'),
            'permission_callback' => array($this, 'check_permission')
        ));

        register_rest_route('contact-form/v1', '/messages', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_messages'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'per_page' => array(
                    'default' => 10,
                    'sanitize_callback' => 'absint',
                ),
                'page' => array(
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                ),
            ),
        ));
    }

    public function check_permission()
    {
        return current_user_can('manage_options');
    }

    public function get_submissions($request)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contact_form_submissions';

        $submissions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

        return rest_ensure_response($submissions);
    }

    public function create_submission($request)
    {
        $params = $request->get_params();

        $name = sanitize_text_field($params['name']);
        $email = sanitize_email($params['email']);
        $subject = sanitize_text_field($params['subject']);
        $message = sanitize_textarea_field($params['message']);

        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            return new WP_Error('missing_fields', __('Please fill in all required fields.', 'contact-form-plugin'), array('status' => 400));
        }

        if (!is_email($email)) {
            return new WP_Error('invalid_email', __('Please enter a valid email address.', 'contact-form-plugin'), array('status' => 400));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'contact_form_submissions';

        $result = $wpdb->insert(
            $table_name,
            array(
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );

        if ($result) {
            // Send email notification
            $admin_email = get_option('admin_email');
            $headers = array('Content-Type: text/html; charset=UTF-8');
            $email_message = sprintf(
                __('New contact form submission from %s (%s)<br><br>Subject: %s<br><br>Message:<br>%s', 'contact-form-plugin'),
                $name,
                $email,
                $subject,
                nl2br($message)
            );

            wp_mail($admin_email, __('New Contact Form Submission', 'contact-form-plugin'), $email_message, $headers);

            return rest_ensure_response(array(
                'message' => __('Thank you for your message. We will get back to you soon.', 'contact-form-plugin'),
                'submission_id' => $wpdb->insert_id
            ));
        }

        return new WP_Error('db_error', __('There was an error sending your message. Please try again later.', 'contact-form-plugin'), array('status' => 500));
    }

    public function delete_submission($request)
    {
        $id = $request['id'];

        global $wpdb;
        $table_name = $wpdb->prefix . 'contact_form_submissions';

        $result = $wpdb->delete($table_name, array('id' => $id), array('%d'));

        if ($result) {
            return rest_ensure_response(array('message' => __('Submission deleted successfully.', 'contact-form-plugin')));
        }

        return new WP_Error('delete_failed', __('Failed to delete submission.', 'contact-form-plugin'), array('status' => 500));
    }

    public function get_messages($request)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contact_form_submissions';

        // Get pagination parameters
        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');
        $offset = ($page - 1) * $per_page;

        // Get total count
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_pages = ceil($total_items / $per_page);

        // Get messages
        $messages = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );

        // Prepare response
        $response = array(
            'messages' => array_map(function ($message) {
                return array(
                    'id' => intval($message->id),
                    'name' => esc_html($message->name),
                    'email' => esc_html($message->email),
                    'subject' => esc_html($message->subject),
                    'message' => esc_html($message->message),
                    'created_at' => $message->created_at
                );
            }, $messages),
            'pagination' => array(
                'total_items' => intval($total_items),
                'total_pages' => intval($total_pages),
                'current_page' => intval($page),
                'per_page' => intval($per_page)
            )
        );

        return rest_ensure_response($response);
    }
}