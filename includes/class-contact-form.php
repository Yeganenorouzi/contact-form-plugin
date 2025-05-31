<?php
class Contact_Form
{
    private $nonce_action = 'contact_form_nonce';
    private $nonce_name = 'contact_form_nonce';

    public function __construct()
    {
        add_shortcode('contact_form', array($this, 'render_contact_form'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_submit_contact_form', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_submit_contact_form', array($this, 'handle_form_submission'));
    }

    public function enqueue_scripts()
    {
        wp_enqueue_style('contact-form-style', CFP_PLUGIN_URL . 'assets/css/style.css', array(), CFP_VERSION);
        wp_enqueue_script('contact-form-script', CFP_PLUGIN_URL . 'assets/js/script.js', array('jquery'), CFP_VERSION, true);

        wp_localize_script('contact-form-script', 'contactFormAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce($this->nonce_action),
            'messages' => array(
                'success' => __('پیام شما با موفقیت ارسال شد.', 'contact-form-plugin'),
                'error' => __('خطا در ارسال پیام. لطفاً دوباره تلاش کنید.', 'contact-form-plugin'),
                'validation' => __('لطفاً تمام فیلدها را پر کنید.', 'contact-form-plugin')
            )
        ));
    }

    public function render_contact_form()
    {
        ob_start();
        ?>
        <div class="contact-form-container">
            <form id="contact-form" class="contact-form">
                <?php wp_nonce_field($this->nonce_action, $this->nonce_name); ?>

                <div class="form-group">
                    <label for="name"><?php _e('Name', 'contact-form-plugin'); ?> *</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="email"><?php _e('Email', 'contact-form-plugin'); ?> *</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="subject"><?php _e('Subject', 'contact-form-plugin'); ?> *</label>
                    <input type="text" id="subject" name="subject" required>
                </div>

                <div class="form-group">
                    <label for="message"><?php _e('Message', 'contact-form-plugin'); ?> *</label>
                    <textarea id="message" name="message" rows="5" required></textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="submit-button"><?php _e('Send Message', 'contact-form-plugin'); ?></button>
                </div>

                <div class="form-message"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_form_submission()
    {
        try {
            $this->validate_nonce();
            $data = $this->validate_and_sanitize_data();
            $this->save_submission($data);
            $this->send_notification_email($data);

            wp_send_json_success(array(
                'message' => __('پیام شما با موفقیت ارسال شد.', 'contact-form-plugin')
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    private function validate_nonce()
    {
        if (!check_ajax_referer($this->nonce_action, 'nonce', false)) {
            throw new Exception(__('خطای امنیتی. لطفاً صفحه را رفرش کنید.', 'contact-form-plugin'));
        }
    }

    private function validate_and_sanitize_data()
    {
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'subject' => sanitize_text_field($_POST['subject']),
            'message' => sanitize_textarea_field($_POST['message'])
        );

        if (empty($data['name']) || empty($data['email']) || empty($data['subject']) || empty($data['message'])) {
            throw new Exception(__('لطفاً تمام فیلدها را پر کنید.', 'contact-form-plugin'));
        }

        if (!is_email($data['email'])) {
            throw new Exception(__('لطفاً یک ایمیل معتبر وارد کنید.', 'contact-form-plugin'));
        }

        return $data;
    }

    private function save_submission($data)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contact_form_submissions';

        $result = $wpdb->insert(
            $table_name,
            array_merge($data, array('created_at' => current_time('mysql'))),
            array('%s', '%s', '%s', '%s', '%s')
        );

        if (!$result) {
            throw new Exception(__('خطا در ذخیره پیام. لطفاً دوباره تلاش کنید.', 'contact-form-plugin'));
        }
    }

    private function send_notification_email($data)
    {
        $admin_email = get_option('admin_email');
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $email_message = sprintf(
            __('New contact form submission from %s (%s)<br><br>Subject: %s<br><br>Message:<br>%s', 'contact-form-plugin'),
            $data['name'],
            $data['email'],
            $data['subject'],
            nl2br($data['message'])
        );

        $sent = wp_mail($admin_email, __('New Contact Form Submission', 'contact-form-plugin'), $email_message, $headers);

        if (!$sent) {
            // Log error but don't throw exception as email sending is not critical
            error_log('Failed to send contact form notification email');
        }
    }
}