<?php
/**
 * Plugin Name: Real Protection OTP
 * Plugin URI: https://prabalsslw.github.io/RP-OTP-Woocommerce/
 * Description: Real Protection, 2 step Verification for WordPress login and woocommerce transaction alert.
 * Version: 1.0.1
 * Author: Pleabal Mallick
 * Author URI: https://prabalsslw.wixsite.com/prabal
 * WC tested up to: 4.1.0
 * License: GPL2
**/

defined( 'ABSPATH' ) or die(); // Protect from alien invasion

define( 'RPWP_PATH', plugin_dir_path( __FILE__ ) );
define( 'RPWP_URL', plugin_dir_url( __FILE__ ) );


# Include required core files

require_once( RPWP_PATH . 'includes/rewrite-rules.php' );
require_once( RPWP_PATH . 'includes/init.php' );
require_once( RPWP_PATH . 'includes/authenticate.php' );
require_once( RPWP_PATH . 'admin/sms-api.php' );
require_once( RPWP_PATH . 'admin/page-settings.php' );
require_once( RPWP_PATH . 'admin/rp-registration.php' );
require_once( RPWP_PATH . 'templates/sms-alert.php' );



# Create database table
register_activation_hook( __FILE__, 'rp_install' );


# Validate login with OTP
add_filter( 'authenticate', 'rp_auth_login', 30, 3 );

function rp_auth_login ( $user, $username, $password ) 
{
    if ( is_wp_error( $user ) ) {
        return $user;
    } else {
        global $wpdb;
        $table_name = $wpdb->prefix . "real_protection";
        $rp_settings = get_option( 'rp_otp_setting' );

        if ( !isset( $rp_settings['timeout'] ) || '' == $rp_settings['timeout'] ) {
            $rp_settings['timeout'] = 3;
        }

        $user_id = sanitize_key( $user->ID );

        $login_attempt = $wpdb->get_row( $wpdb->prepare(
            "
                SELECT *
                FROM $table_name
                WHERE user_id = %d AND login_status = 0
            ",
            $user_id
        ) );

        if ( NULL === $login_attempt ) {
            $user_hash = md5( $user->ID . time() );
            $user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
            $wpdb->insert(
                $table_name,
                array(
                    'user_id' => $user_id,
                    'user_obj' => serialize($user),
                    'auth_token' => $user_hash,
                    'login_time' => current_time( 'mysql' ),
                    'user_ip' => $user_ip,
                )
            );

            wp_redirect( home_url() . "/verify-login/" . $user_hash . "/");
        } elseif ( ( current_time( 'timestamp' ) - strtotime( $login_attempt->login_time ) ) > $rp_settings['timeout'] * MINUTE_IN_SECONDS ) {
            $wpdb->update(
                $table_name,
                array(
                    'login_status' => 3
                ),
                array( 'auth_token' => $login_attempt->auth_token ),
                array(
                    '%d'
                ),
                array( '%s' )
            );

            $user_hash = md5( $user->ID . time() );
            $user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
            $wpdb->insert(
                $table_name,
                array(
                    'user_id' => $user_id,
                    'user_obj' => serialize($user),
                    'auth_token' => $user_hash,
                    'login_time' => current_time( 'mysql' ),
                    'user_ip' => $user_ip,
                )
            );

            wp_redirect( home_url() . "/verify-login/" . $user_hash . "/");
        } else {
            wp_redirect( home_url() . "/verify-login/" . $login_attempt->auth_token . "/");
        }

        exit;
    }
}


# Display error message on login page
function wprp_modify_html() {
    // $rp_error = isset($_GET['rp_error']) ? esc_html__($_GET['rp_error']) : '';
    if (rp_error_code_sanitize($_GET['rp_error'])) {
        $rp_error = esc_html__($_GET['rp_error']);
    }
    else
    {
        $rp_error = '';
    }

    if ( $rp_error != '' ) {
        $login_error = get_query_var( 'rp_error' );
        switch ( $rp_error ) {
            case 401:
                $message = '<strong>ERROR</strong>: Session timed out!';
                break;
            case 402:
                $message = '<strong>ERROR</strong>: IP does not match!';
                break;
            case 601:
            	$message = '<strong>ERROR</strong>: You have exceeded OTP limit!';
                break;
            default:
                $message = '<strong>ERROR</strong>: Session timed out!';
        }
        add_filter( 'login_message', create_function( '', "return '<div id=\"login_error\">$message</div>';" ) );
    }
}
add_action( 'login_head', 'wprp_modify_html');


# Load Plugin Admin CSS
function rp_load_custom_wp_admin_style() {
        wp_register_style( 'real-protection', RPWP_URL . 'admin/css/style.css', false, '1.0.0' );
        wp_enqueue_style( 'real-protection' );
}
add_action( 'admin_enqueue_scripts', 'rp_load_custom_wp_admin_style' );

function realProPluginLinks($links)
{
    $pluginLinks = array(
                    'settings' => '<a href="'. esc_url(admin_url('admin.php?page=real-protection-otp-settings')) .'">Settings</a>',
                    'docs'     => '<a href="https://prabalsslw.github.io/RP-OTP-Woocommerce/">Docs</a>',
                    'support'  => '<a href="mailto:prabalsslw@gmail.com">Support</a>'
                );

    $links = array_merge($links, $pluginLinks);

    return $links;
}

$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'realProPluginLinks' );


function rp_error_code_sanitize($code)
{
    if (is_numeric($code)) {
        return true;
    }
    else{
        return false;
    }
}