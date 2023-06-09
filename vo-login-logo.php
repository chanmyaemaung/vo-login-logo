<?php

/**
 * Plugin Name: VO Login Logo
 * Plugin URI: https://visibleone.com
 * Description: This plugin gives you the ability to modify the logo that appears when you log in to WordPress from a media file, and it also gives you the option to use a remote URL.
 * Author: Conor Visibee <Chen Lay>
 * Version: 1.1.2
 * Requires at least: 5.0
 * Tested up to: 6.2
 * Requires PHP: 5.6.20
 * This plugin requires PHP 5.6.20 or higher to run properly.
 * It is recommended to use a PHP version of at least 7.4 for security reasons.
 */

defined('ABSPATH') or die('No direct script access allowed!');

if (!function_exists('add_action')) {
    echo 'The WordPress environment could not be found.';
    exit;
}

class VO_Login_Logo
{

    public function __construct()
    {
        add_action('login_enqueue_scripts', array($this, 'vo_custom_login_logo'));
        add_filter('login_headerurl', array($this, 'vo_custom_login_logo_url'));
        add_action('admin_menu', array($this, 'vo_login_logo_menu'));
        add_action('admin_init', array($this, 'vo_register_login_logo_settings'));
        add_action('wp_ajax_vo_upload_logo', array($this, 'vo_upload_logo'));
        add_action('wp_ajax_nopriv_vo_upload_logo', array($this, 'vo_upload_logo'));
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_media();
            wp_enqueue_script('jquery');
        });
        // Set default logo
        $this->set_default_logo();
    }

    // Set default logo
    public function set_default_logo()
    {
        $default_logo = array(
            'url' => '',
            'id' => 0
        );

        $logo_id = get_option('vo_login_logo_id');
        $logo_url = esc_url(get_option('vo_login_logo_url'));

        if (!$logo_id && !$logo_url) {
            // Set default logo to use when neither logo ID nor URL is provided
            $default_logo_id = get_option('thumbnail_id');
            $default_logo_src = wp_get_attachment_image_src($default_logo_id, 'full');
            $default_logo['url'] = $default_logo_src[0];
            $default_logo['id'] = $default_logo_id;

            // Save default logo settings
            update_option('vo_login_logo_id', $default_logo['id']);
            update_option('vo_login_logo_url', $default_logo['url']);
        } elseif ($logo_id && !$logo_url) {
            // Set default logo URL to use when only logo ID is provided
            $logo_src = wp_get_attachment_image_src($logo_id, 'full');
            $logo_url = $logo_src[0];
            update_option('vo_login_logo_url', $logo_url);
        } elseif (!$logo_id && $logo_url) {
            // Set default logo ID to use when only logo URL is provided
            $attachment_id = attachment_url_to_postid($logo_url);
            if ($attachment_id) {
                update_option('vo_login_logo_id', $attachment_id);
            }
        }
    }

    // Add custom logo to login page
    public function vo_custom_login_logo()
    {
        $logo_id = get_option('vo_login_logo_id');
        $logo_url = esc_url(get_option('vo_login_logo_url'));

        if ($logo_id && $logo_url) {
            $logo_src = wp_get_attachment_image_src($logo_id, 'full');
            $logo_url = $logo_src[0];
        } elseif (!$logo_id && !$logo_url) {
            return;
        }

        if (!empty($logo_url) && filter_var($logo_url, FILTER_VALIDATE_URL)) {
            echo '<style type="text/css">
            #login h1 a{
                background-image:url(' . $logo_url . ') !important;
                background-size:contain !important;
                width:100% !important;
            }
        </style>';
        }
    }


    // Add custom logo url to login page
    public function vo_custom_login_logo_url($url)
    {
        $logo_link = esc_url(get_option('vo_login_logo_link'));
        if (!empty($logo_link) && filter_var($logo_link, FILTER_VALIDATE_URL)) {
            return $logo_link;
        } else {
            return $url;
        }
    }

    // Add admin menu for plugin settings
    public function vo_login_logo_menu()
    {
        add_options_page('VO Login Logo Settings', 'VO Login Logo', 'manage_options', 'vo-login-logo-settings', array($this, 'vo_login_logo_settings_page'));
    }

    // Add options to plugin settings
    public function vo_register_login_logo_settings()
    {
        register_setting('vo_login_logo_settings_group', 'vo_login_logo_url', 'esc_url_raw');
        register_setting('vo_login_logo_settings_group', 'vo_login_logo_link', 'esc_url_raw');
        register_setting('vo_login_logo_settings_group', 'vo_login_logo_id', 'intval');
    }

    // Create plugin settings page
    public function vo_login_logo_settings_page()
    {
        $current_version = get_plugin_data(__FILE__)['Version'];
        $githubLink = 'https://bit.ly/42oxBI2';
?>
        <div class="voWrap">
            <h1 class="voTitle">VO Login Logo Settings</h1>
            <p class="voSubtitle">This plugin was crafted by <span class="author">Conor</span> at <a href="https://visibleone.com/" target="_blank" class="company">Visible One</a>.</p>
            <p class="version">
                <span>Current version: <?php echo $current_version; ?></span>
                <a href="<?php echo $githubLink; ?>" target="_blank" class="voDownload">Download Plugin ♺</a>
            </p>
            <form method="post" action="options.php">
                <?php settings_fields('vo_login_logo_settings_group'); ?>
                <?php do_settings_sections('vo_login_logo_settings_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Logo Remote URL:</th>
                        <td><input type="text" name="vo_login_logo_url" placeholder="Insert your image url" value="<?php echo esc_attr(get_option('vo_login_logo_url')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Logo Link:</th>
                        <td><input type="text" name="vo_login_logo_link" placeholder="Add your redirect link" value="<?php echo esc_attr(get_option('vo_login_logo_link')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Upload or Select Logo:</th>
                        <td>
                            <?php
                            $logo_id = get_option('vo_login_logo_id');
                            if ($logo_id) {
                                $logo_src = wp_get_attachment_image_src($logo_id, 'full');
                                $logo_url = $logo_src[0];
                            } else {
                                $logo_url = '';
                            }
                            ?>
                            <div class="vo-upload-wrap">
                                <div class="vo-button-wrap">
                                    <div class="vo-upload-button">
                                        <button type="button" class="button" id="vo-upload-logo-btn"><?php _e('Upload/Select Image'); ?></button>
                                    </div>
                                    <?php if (!empty($logo_url) || !empty(get_option('vo_login_logo_url'))) { ?>
                                        <div class="vo-remove-button">
                                            <button type="button" class="vo-remove-logo-btn button"><?php _e('Remove Image'); ?></button>
                                        </div>
                                    <?php } ?>
                                </div>
                                <div class="vo-preview-image">
                                    <?php if (!empty($logo_url)) { ?>
                                        <img src="<?php echo $logo_url; ?>" width="200" />
                                    <?php } else if (!empty(get_option('vo_login_logo_url'))) { ?>
                                        <?php $logo_remote_url = esc_html(get_option('vo_login_logo_url')); ?>
                                        <?php if (filter_var($logo_remote_url, FILTER_VALIDATE_URL)) { ?>
                                            <img src="<?php echo $logo_remote_url; ?>" width="200" />
                                        <?php } else { ?>
                                            <span class="vo-remote-url"><?php echo $logo_remote_url; ?></span>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                            </div>
                            <input type="hidden" name="vo_login_logo_id" id="vo-login-logo-id" value="<?php echo $logo_id; ?>" />
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>

        <script>
            jQuery(document).ready(function($) {
                var vo_upload_frame;
                $('#vo-upload-logo-btn').on('click', function(e) {
                    e.preventDefault();
                    if (vo_upload_frame) {
                        vo_upload_frame.open();
                        return;
                    }
                    vo_upload_frame = wp.media.frames.file_frame = wp.media({
                        title: '<?php _e('Upload or Select Image'); ?>',
                        button: {
                            text: '<?php _e('Use this image'); ?>'
                        },
                        multiple: false
                    });
                    vo_upload_frame.on('select', function() {
                        var attachment = vo_upload_frame.state().get('selection').first().toJSON();
                        $('.vo-preview-image').html('<img src="' + attachment.url + '" width="200" />');
                        $('#vo-login-logo-id').val(attachment.id);
                    });
                    vo_upload_frame.open();
                });

                $('.vo-remove-logo-btn').on('click', function(e) {
                    e.preventDefault();
                    $('.vo-preview-image').html('');
                    $('#vo-login-logo-id').val('');
                });
            });
        </script>

        <style>
            @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;700;900&display=swap');

            .voWrap {
                font-family: 'Poppins', sans-serif;
                border: 1px solid #fff3d9;
                border-radius: 20px;
                background: white;
                max-width: 500px;
                width: 100%;
                padding: 1.5rem;
                -webkit-box-shadow: 1px 2px 5px #00db92;
                box-shadow: 1px 2px 5px #00db92;
                margin: 2rem auto;
            }

            .voWrap h1 {
                font-weight: 700;
            }

            .voWrap p {
                color: #212121;
                font-weight: 400;
            }

            .vo-upload-wrap .vo-button-wrap {
                display: -webkit-box;
                display: -ms-flexbox;
                display: flex;
                -webkit-box-align: center;
                -ms-flex-align: center;
                align-items: center;
            }

            .vo-upload-wrap .vo-button-wrap .vo-remove-button {
                margin-left: 10px;
            }

            .vo-preview-image {
                margin-top: 1rem;
            }

            .voTitle {
                font-size: 1.5rem;
                font-weight: bold;
            }

            .voSubtitle {
                font-size: 1rem;
            }

            .author {
                color: #ffae00;
                font-weight: bold;
                -webkit-transition: all 300ms ease-in;
                -o-transition: all 300ms ease-in;
                transition: all 300ms ease-in;
            }

            .author:hover {
                color: #00db92;
            }

            .company {
                color: #00db92;
                font-weight: bold;
                text-decoration: none;
                -webkit-transition: all 300ms ease-in;
                -o-transition: all 300ms ease-in;
                transition: all 300ms ease-in;
            }

            .company:hover {
                color: #ffae00;
            }

            .form-table th {
                font-size: 1rem;
                font-weight: 500;
            }

            #submit {
                background-color: #00db92;
                padding: 5px 15px;
                border: none;
                border-radius: 5px;
                -webkit-box-shadow: 1px 1px 5px #ffae00;
                box-shadow: 1px 1px 5px #ffae00;
                border: 1px solid #00db92;
                -webkit-transition: all 300ms ease-in;
                -o-transition: all 300ms ease-in;
                transition: all 300ms ease-in;
            }

            #submit:hover {
                background: transparent;
                color: #212121;
                border-color: #ffae00;
            }

            p.version {
                color: #7c7c7c;
                font-size: 0.75rem;
                display: -webkit-box;
                display: -ms-flexbox;
                display: flex;
                -webkit-box-pack: justify;
                -ms-flex-pack: justify;
                justify-content: space-between;
                -webkit-box-align: center;
                -ms-flex-align: center;
                align-items: center;
            }

            p.version a.voDownload {
                text-decoration: none;
                font-weight: 700;
                color: #00db92;
                margin-right: 10px;
            }

            #vo-upload-logo-btn,
            .vo-upload-wrap .vo-button-wrap .vo-remove-button button {
                padding: 5px 10px;
                color: white;
                border: none;
                -webkit-transition: all 300ms ease-in;
                -o-transition: all 300ms ease-in;
                transition: all 300ms ease-in;
            }

            .vo-upload-wrap .vo-button-wrap .vo-remove-button button {
                border: 1px solid #d81f18;
            }

            .vo-button-wrap .vo-remove-button button:hover {
                background-color: transparent !important;
                color: #212121;
            }

            #vo-upload-logo-btn {
                background-color: #ffae00;
                border: 1px solid #ffae00;
                -webkit-transition: all 300ms ease-in;
                -o-transition: all 300ms ease-in;
                transition: all 300ms ease-in;
            }

            #vo-upload-logo-btn:hover {
                background-color: transparent;
                color: #212121;
            }

            .vo-upload-wrap .vo-button-wrap .vo-remove-button button {
                background-color: #d81f18;
            }


            .vo-preview-image {
                margin-top: 2rem;
            }

            .vo-preview-image img {
                width: 100%;
                -webkit-box-shadow: 1px 0 5px #ffae00;
                box-shadow: 1px 0 5px #ffae00;
                -o-object-fit: cover;
                object-fit: cover;
                aspect-ratio: 16/9;
                border-radius: 5px;
            }

            .form-table th {
                font-weight: 500;
            }

            .form-table td input {
                padding: 5px 10px;
                border: 1px solid #ffae00;
                -webkit-box-shadow: 1px 1px 3px #ffae00;
                box-shadow: 1px 1px 3px #ffae00;
                -webkit-transition: all 300ms ease-in;
                -o-transition: all 300ms ease-in;
                transition: all 300ms ease-in;
            }

            .form-table td input:focus {
                border-color: #00db92;
                border: 1px solid #00db92;
                -webkit-box-shadow: 1px 1px 3px #00db92;
                box-shadow: 1px 1px 3px #00db92;
            }

            .form-table td input {
                width: 100%;
            }

            .wp-core-ui .button-primary:focus {
                -webkit-box-shadow: 0 0 0 1px #fff, 0 0 0 3px #ffae00;
                box-shadow: 0 0 0 1px #fff, 0 0 0 3px #ffae00;
            }
        </style>

<?php
    }

    // Upload or select logo and save to database
    public function vo_upload_logo()
    {
        check_ajax_referer('vo_upload_logo_nonce', 'security');

        $attachment_id = intval($_POST['attachment_id']);
        update_option('vo_login_logo_id', $attachment_id);
        wp_send_json_success();
    }
}

// Instantiate the VO_Login_Logo class
$vo_login_logo = new VO_Login_Logo();
