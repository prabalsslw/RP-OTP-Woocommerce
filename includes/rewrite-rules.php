<?php
defined( 'ABSPATH' ) or die(); // Protect from alien invasion

// OTP page rewrite rule
add_action( 'init', 'rp_init_internal' );
function rp_init_internal()
{
    add_rewrite_tag('%rp_auth%', '([^&]+)');
    add_rewrite_rule( '^verify-login/([^/]*)/?', 'index.php?rp_api=1&rp_auth=$matches[1]', 'top' );
}

add_filter( 'query_vars', 'rp_query_vars' );
function rp_query_vars( $query_vars )
{
    $query_vars[] = 'rp_api';
    $query_vars[] = 'rp_error';
    return $query_vars;
}

add_action( 'parse_request', 'rp_parse_request' );
function rp_parse_request( &$wp )
{
    if ( array_key_exists( 'rp_api', $wp->query_vars ) ) {
        require_once( RP_PATH . 'templates/verify-login.php');
        exit();
    }
    return;
}


// Resend OTP page rewrite rule
add_action( 'init', 'rp_re_init_internal' );
function rp_re_init_internal()
{
    add_rewrite_tag('%rp_auth%', '([^&]+)');
    add_rewrite_rule( '^resend-otp/([^/]*)/?', 'index.php?rp_reo_api=1&rp_auth=$matches[1]', 'top' );
}

add_filter( 'query_vars', 'rp_re_query_vars' );
function rp_re_query_vars( $query_vars )
{
    $query_vars[] = 'rp_reo_api';
    $query_vars[] = 'rp_error';
    return $query_vars;
}

add_action( 'parse_request', 'rp_re_parse_request' );
function rp_re_parse_request( &$wp )
{
    if ( array_key_exists( 'rp_reo_api', $wp->query_vars ) ) {
        require_once( RP_PATH . 'templates/resend-otp.php');
        exit();
    }
    return;
}