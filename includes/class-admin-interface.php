<?php
class Admin_Interface {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_settings_page() {
        add_options_page(
            'ChatGPT Content Generator',
            'ChatGPT Generator',
            'manage_options',
            'chatgpt-content-generator',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('chatgpt_content_generator', 'chatgpt_api_key');
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>ChatGPT Content Generator Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('chatgpt_content_generator');
                do_settings_sections('chatgpt_content_generator');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="chatgpt_api_key">Clé API ChatGPT</label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="chatgpt_api_key" 
                                   name="chatgpt_api_key" 
                                   value="<?php echo esc_attr(get_option('chatgpt_api_key')); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}