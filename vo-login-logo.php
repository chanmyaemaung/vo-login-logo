<?php

/**
 * Plugin Name: VO Login Logo
 * Plugin URI: https://visibleone.com
 * Description: This plugin gives you the ability to modify the logo that appears when you log in to WordPress from a media file, and it also gives you the option to use a remote URL.
 * Author: Conor Visibee <Chen Lay>
 * Version: 1.0.0
 * Requires at least: 5.6
 * Tested up to: 6.2
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
    }

    // Add custom logo to login page
    public function vo_custom_login_logo()
    {
        $logo_url = esc_url(get_option('vo_login_logo_url'));
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
    }

    // Create plugin settings page
    public function vo_login_logo_settings_page()
    {
?>
        <div class="wrap">
            <h2>VO Login Logo Settings</h2>
            <form method="post" action="options.php">
                <?php settings_fields('vo_login_logo_settings_group'); ?>
                <?php do_settings_sections('vo_login_logo_settings_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Logo Remote URL:</th>
                        <td><input type="text" name="vo_login_logo_url" value="<?php echo esc_attr(get_option('vo_login_logo_url')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Logo Link:</th>
                        <td><input type="text" name="vo_login_logo_link" value="<?php echo esc_attr(get_option('vo_login_logo_link')); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
<?php
    }
}

// Instantiate the VO_Login_Logo class
$vo_login_logo = new VO_Login_Logo();
