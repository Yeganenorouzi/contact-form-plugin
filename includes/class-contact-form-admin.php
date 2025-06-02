<?php
class Contact_Form_Admin
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'handle_delete_action'));
        add_action('admin_init', array($this, 'handle_view_action'));
    }

    // Admin styles and scripts
    public function enqueue_admin_scripts($hook)
    {
        // Check if we're on the contact form submissions page
        if ('toplevel_page_contact-form-submissions' !== $hook) {
            return;
        }

        wp_enqueue_style('contact-form-admin-style', CFP_PLUGIN_URL . 'assets/css/admin.css');
        wp_enqueue_script('contact-form-admin-script', CFP_PLUGIN_URL . 'assets/js/admin.js');
    }


    // Add admin menu
    public function add_admin_menu()
    {
        add_menu_page(
            __('Contact Form Submissions', 'contact-form-plugin'),
            __('Contact Capture Lite', 'contact-form-plugin'),
            'manage_options',
            'contact-form-submissions',
            array($this, 'render_admin_page'),
            'dashicons-email',
            30
        );
    }

    // Render admin page
    public function render_admin_page()
    {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('شما مجوز دسترسی به این صفحه را ندارید', 'contact-form-plugin'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'contact_form_submissions';

        // Handle view action
        if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
            $this->render_view_page();
            return;
        }

        // Get submissions
        $submissions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

        // Display messages
        if (isset($_GET['message'])) {
            if ($_GET['message'] === 'deleted') {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('حذف با موفقیت انجام شد', 'contact-form-plugin') . '</p></div>';
            } elseif ($_GET['message'] === 'error') {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('خطایی رخ داده است', 'contact-form-plugin') . '</p></div>';
            }
        }

        ?>
        <div class="wrap">
            <h1><?php _e('فهرست پیام ها', 'contact-form-plugin'); ?></h1>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('شناسه', 'contact-form-plugin'); ?></th>
                        <th><?php _e('نام و نام خانوادگی', 'contact-form-plugin'); ?></th>
                        <th><?php _e('ایمیل', 'contact-form-plugin'); ?></th>
                        <th><?php _e('موضوع', 'contact-form-plugin'); ?></th>
                        <th><?php _e('پیام', 'contact-form-plugin'); ?></th>
                        <th><?php _e('تاریخ ثبت', 'contact-form-plugin'); ?></th>
                        <th><?php _e('عملیات', 'contact-form-plugin'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($submissions): ?>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td><?php echo esc_html($submission->id); ?></td>
                                <td><?php echo esc_html($submission->name); ?></td>
                                <td><?php echo esc_html($submission->email); ?></td>
                                <td><?php echo esc_html($submission->subject); ?></td>
                                <td><?php echo esc_html(wp_trim_words($submission->message, 10, '...')); ?></td>
                                <td><?php echo esc_html($submission->created_at); ?></td>
                                <td>
                                    <?php
                                    $view_url = add_query_arg(
                                        array(
                                            'action' => 'view',
                                            'id' => $submission->id
                                        ),
                                        admin_url('admin.php?page=contact-form-submissions')
                                    );

                                    $delete_url = wp_nonce_url(
                                        add_query_arg(
                                            array(
                                                'action' => 'delete',
                                                'id' => $submission->id
                                            ),
                                            admin_url('admin.php?page=contact-form-submissions')
                                        ),
                                        'delete_submission_' . $submission->id
                                    );
                                    ?>
                                    <a href="<?php echo esc_url($view_url); ?>" class="button button-small button-primary">
                                        <?php _e('نمایش', 'contact-form-plugin'); ?>
                                    </a>
                                    <a href="<?php echo esc_url($delete_url); ?>" class="button button-small delete-submission"
                                        onclick="return confirm('<?php _e('Are you sure you want to delete this submission?', 'contact-form-plugin'); ?>')">
                                        <?php _e('حذف', 'contact-form-plugin'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7"><?php _e('No submissions found.', 'contact-form-plugin'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function handle_delete_action()
    {
        // Check if we're on the contact form submissions page
        if (!isset($_GET['page']) || $_GET['page'] !== 'contact-form-submissions') {
            return;
        }

        // Check if the action is delete
        if (!isset($_GET['action']) || $_GET['action'] !== 'delete') {
            return;
        }

        // Check if the id and nonce are set
        if (!isset($_GET['id']) || !isset($_GET['_wpnonce'])) {
            wp_die(__('درخواست معتبر نیست', 'contact-form-plugin'));
        }

        // Check if the user has manage_options permission
        if (!current_user_can('manage_options')) {
            wp_die(__('شما مجوز دسترسی به این صفحه را ندارید', 'contact-form-plugin'));
        }

        // Check if the nonce is valid
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_submission_' . $_GET['id'])) {
            wp_die(__('بررسی امنیتی با خطر مواجه شد', 'contact-form-plugin'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'contact_form_submissions';
        $id = intval($_GET['id']);

        $result = $wpdb->delete($table_name, array('id' => $id), array('%d'));

        if ($result) {
            wp_redirect(add_query_arg('message', 'deleted', admin_url('admin.php?page=contact-form-submissions')));
            exit;
        } else {
            wp_redirect(add_query_arg('message', 'error', admin_url('admin.php?page=contact-form-submissions')));
            exit;
        }
    }

    public function handle_view_action()
    {
        // This method is called by admin_init but the actual view rendering
        // is handled in render_admin_page() method
    }

    public function render_view_page()
    {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('شما مجوز دسترسی به این صفحه را ندارید', 'contact-form-plugin'));
        }

        // Check if ID is provided
        if (!isset($_GET['id'])) {
            wp_die(__('شناسه پیام معتبر نیست', 'contact-form-plugin'));
        }


        global $wpdb;
        $table_name = $wpdb->prefix . 'contact_form_submissions';
        $id = intval($_GET['id']);

        // Get the submission
        $submission = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));

        if (!$submission) {
            wp_die(__('پیام مورد نظر یافت نشد', 'contact-form-plugin'));
        }

        $back_url = admin_url('admin.php?page=contact-form-submissions');
        ?>
        <div class="wrap">
            <h1><?php _e('نمایش جزئیات پیام', 'contact-form-plugin'); ?></h1>

            <a href="<?php echo esc_url($back_url); ?>" class="button button-secondary" style="margin-bottom: 20px;">
                <?php _e('← بازگشت به فهرست', 'contact-form-plugin'); ?>
            </a>

            <div class="card" style="max-width: 800px;">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('شناسه:', 'contact-form-plugin'); ?></th>
                        <td><?php echo esc_html($submission->id); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('نام و نام خانوادگی:', 'contact-form-plugin'); ?></th>
                        <td><?php echo esc_html($submission->name); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('ایمیل:', 'contact-form-plugin'); ?></th>
                        <td><a
                                href="mailto:<?php echo esc_attr($submission->email); ?>"><?php echo esc_html($submission->email); ?></a>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('موضوع:', 'contact-form-plugin'); ?></th>
                        <td><?php echo esc_html($submission->subject); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('پیام:', 'contact-form-plugin'); ?></th>
                        <td>
                            <div
                                style="background: #f9f9f9; padding: 15px; border-radius: 4px; border-left: 4px solid #0073aa;">
                                <?php echo nl2br(esc_html($submission->message)); ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('تاریخ ثبت:', 'contact-form-plugin'); ?></th>
                        <td><?php echo esc_html($submission->created_at); ?></td>
                    </tr>
                </table>

                <div style="margin-top: 20px; padding: 15px; border-top: 1px solid #ddd;">
                    <?php
                    $delete_url = wp_nonce_url(
                        add_query_arg(
                            array(
                                'action' => 'delete',
                                'id' => $submission->id
                            ),
                            admin_url('admin.php?page=contact-form-submissions')
                        ),
                        'delete_submission_' . $submission->id
                    );
                    ?>
                    <a href="<?php echo esc_url($delete_url); ?>" class="button button-secondary delete-submission"
                        onclick="return confirm('<?php _e('Are you sure you want to delete this submission?', 'contact-form-plugin'); ?>')">
                        <?php _e('حذف  پیام', 'contact-form-plugin'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
}