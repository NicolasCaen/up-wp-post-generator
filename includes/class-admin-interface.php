<?php
class Admin_Interface {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'localize_script_data'));
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
        register_setting('chatgpt_content_generator', 'chatgpt_context', array(
            'sanitize_callback' => array($this, 'sanitize_context')
        ));
    }

    public function sanitize_context($input) {
        if (!is_array($input)) {
            return '[]';
        }

        $clean_context = array();
        foreach ($input as $message) {
            if (isset($message['role']) && isset($message['content'])) {
                $clean_context[] = array(
                    'role' => sanitize_text_field($message['role']),
                    'content' => sanitize_textarea_field($message['content'])
                );
            }
        }

        return json_encode($clean_context);
    }

    public function render_settings_page() {
        // Récupérer le contexte existant
        $context = get_option('chatgpt_context', '[]');
        $context_array = json_decode($context, true) ?: [];
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
                    <tr>
                        <th scope="row">
                            <label>Contexte de discussion</label>
                        </th>
                        <td>
                            <div id="context-messages">
                                <?php foreach ($context_array as $index => $message): ?>
                                <div class="message-pair" style="margin-bottom: 10px;">
                                    <select name="chatgpt_context[<?php echo $index; ?>][role]">
                                        <option value="system" <?php selected($message['role'], 'system'); ?>>System</option>
                                        <option value="user" <?php selected($message['role'], 'user'); ?>>User</option>
                                        <option value="assistant" <?php selected($message['role'], 'assistant'); ?>>Assistant</option>
                                    </select>
                                    <textarea name="chatgpt_context[<?php echo $index; ?>][content]" 
                                              class="large-text" 
                                              rows="2"><?php echo esc_textarea($message['content']); ?></textarea>
                                    <button type="button" class="button remove-message">Supprimer</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="button" id="add-message">Ajouter un message</button>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#add-message').on('click', function() {
                const index = $('.message-pair').length;
                const newMessage = `
                    <div class="message-pair" style="margin-bottom: 10px;">
                        <select name="chatgpt_context[${index}][role]">
                            <option value="system">System</option>
                            <option value="user">User</option>
                            <option value="assistant">Assistant</option>
                        </select>
                        <textarea name="chatgpt_context[${index}][content]" 
                                  class="large-text" 
                                  rows="2"></textarea>
                        <button type="button" class="button remove-message">Supprimer</button>
                    </div>
                `;
                $('#context-messages').append(newMessage);
            });

            $(document).on('click', '.remove-message', function() {
                $(this).closest('.message-pair').remove();
            });
        });
        </script>
        <?php
    }

    public function localize_script_data($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }

        // S'assurer que le script principal est enregistré avant la localisation
        wp_enqueue_script(
            'chatgpt-content-generator',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/content-generator.js',
            array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-data', 'wp-plugins', 'wp-edit-post', 'react'),
            '1.0.0',
            true
        );

        wp_localize_script('chatgpt-content-generator', 'chatgptSettings', array(
            'instructionOptions' => Processor_Registry::get_instruction_options(),
            'nonce' => wp_create_nonce('wp_rest'),
            'restUrl' => rest_url('chatgpt-content-generator/v1/')
        ));
    }
}