<?php
class Contact_Form_Admin
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'handle_delete_action'));
    }

    public function enqueue_admin_scripts($hook)
    {
        if ('toplevel_page_contact-form-submissions' !== $hook) {
            return;
        }

        wp_enqueue_style('contact-form-admin-style', CFP_PLUGIN_URL . 'assets/css/admin.css', array(), CFP_VERSION);
        wp_enqueue_script('contact-form-admin-script', CFP_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), CFP_VERSION, true);
    }

    public function add_admin_menu()
    {
        add_menu_page(
            __('Contact Form Submissions', 'contact-form-plugin'),
            __('Contact Form', 'contact-form-plugin'),
            'manage_options',
            'contact-form-submissions',
            array($this, 'render_admin_page'),
            'dashicons-email',
            30
        );
    }

    public function handle_delete_action()
    {
        if (!isset($_GET['page']) || $_GET['page'] !== 'contact-form-submissions') {
            return;
        }

        if (!isset($_GET['action']) || $_GET['action'] !== 'delete') {
            return;
        }

        if (!isset($_GET['id']) || !isset($_GET['_wpnonce'])) {
            wp_die(__('Invalid request.', 'contact-form-plugin'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'contact-form-plugin'));
        }

        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_submission_' . $_GET['id'])) {
            wp_die(__('Security check failed.', 'contact-form-plugin'));
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

    public function render_admin_page()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contact_form_submissions';

        // Get submissions
        $submissions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

        // Display messages
        if (isset($_GET['message'])) {
            if ($_GET['message'] === 'deleted') {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Submission deleted successfully.', 'contact-form-plugin') . '</p></div>';
            } elseif ($_GET['message'] === 'error') {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error deleting submission.', 'contact-form-plugin') . '</p></div>';
            }
        }

        ?>
        <div class="wrap">
            <h1><?php _e('Contact Form Submissions', 'contact-form-plugin'); ?></h1>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'contact-form-plugin'); ?></th>
                        <th><?php _e('Name', 'contact-form-plugin'); ?></th>
                        <th><?php _e('Email', 'contact-form-plugin'); ?></th>
                        <th><?php _e('Subject', 'contact-form-plugin'); ?></th>
                        <th><?php _e('Message', 'contact-form-plugin'); ?></th>
                        <th><?php _e('Date', 'contact-form-plugin'); ?></th>
                        <th><?php _e('Actions', 'contact-form-plugin'); ?></th>
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
                                <td><?php echo esc_html($submission->message); ?></td>
                                <td><?php echo esc_html($submission->created_at); ?></td>
                                <td>
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
                                    <a href="<?php echo esc_url($delete_url); ?>" class="button button-small delete-submission"
                                        onclick="return confirm('<?php _e('Are you sure you want to delete this submission?', 'contact-form-plugin'); ?>')">
                                        <?php _e('Delete', 'contact-form-plugin'); ?>
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
}