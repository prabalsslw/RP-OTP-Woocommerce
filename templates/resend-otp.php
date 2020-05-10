<?php

// echo "<pre>";
// print_r($_SERVER);
	global $wpdb;
	$table_name = $wpdb->prefix . "real_protection";
	$rp_settings = get_option( 'rp_otp_setting' );
	$user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);

	if($_SERVER['REQUEST_URI'] != "")
	{
		$auth_array = explode("/", $_SERVER['REQUEST_URI']);
		if($auth_array[3] != "")
		{
			$auth_token = $auth_array[3];
			global $login_attempt;
		    $login_attempt = $wpdb->get_row( $wpdb->prepare(
		        "
		            SELECT *
		            FROM $table_name
		            WHERE auth_token = %s
		        ",
		        $auth_token
		    ) );

			if($login_attempt->otp_sent_limit < 5)
			{
				$otp_limit = $login_attempt->otp_sent_limit + 1;
				$otp = mt_rand(100000, 999999);
				$wpdb->update(
	                $table_name,
	                array(
	                	'otp' => $otp,
	                    'login_status' => 0,
	                    'login_time' => current_time( 'mysql' ),
	                    'user_ip' => $user_ip,
	                    'otp_sent_limit' => $otp_limit
	                ),
	                array( 'auth_token' => $auth_token ),
	                array(
	                    '%d','%d','%s','%s'
	                ),
	                array( '%s' )
	            );

	            $all_meta_for_user = get_user_meta($login_attempt->user_id);

	            if($rp_settings['email_otp_disable'] == "")
	            {
	            	$user = unserialize( $login_attempt->user_obj );
	                $message = "{$user->user_nicename}, \r\n\r\n";
	                $message .= "Your One Time Pin is: {$otp}\r\n\r\n";
	                $message .= "This pin is only valid for the next {$rp_settings['timeout']} minutes. \r\n\r\n";
	                $message .= get_bloginfo('name');
	                $headers = 'From: ' . get_bloginfo('name') . ' <' . $rp_settings['from_email'] . '>';

	                function rp_otp_email( $user, $otp, $message, $headers ) {
	                    $mail_sent = wp_mail( $user->user_email, get_bloginfo('name') . ": One Time Pin", apply_filters( "rp_otp_message", $message ), apply_filters( "rp_otp_headers", $headers ) );
	                }
	                if ( ! has_action( "rp_otp_send" ) ) {
	                    add_action( "rp_otp_send", "rp_otp_email", 10, 4 );
	                }

	                do_action( "rp_otp_send", $user, $otp, $message, $headers );
	            }

	            if($all_meta_for_user['rp_phone_number'][0] != "" && isset($rp_settings['otp_enable']))
	            {
	                $rp_phone_no = $all_meta_for_user['rp_phone_number'][0];
	                $api_url = trim($rp_settings['api_url']);
	                $otp_text = trim($rp_settings['otp_text']);

	                $otp_text = str_ireplace("{{OTP}}", $otp, $otp_text);

	                if(isset($rp_settings['get_post']))
	                {
	                    $response = callToPostAPI($api_url, postParameter($rp_settings['api_peram'], $rp_phone_no, $otp_text));
	                }
	                else
	                {
	                    $response = callToGetAPI($api_url, getParameter($rp_settings['api_peram'], $rp_phone_no, $otp_text));
	                }
	            
	                if($response != "")
	                {
	                    $wpdb->update(
	                        $table_name,
	                        array(
	                            'sms_ref_id' => serialize($response)
	                        ),
	                        array( 'auth_token' => $auth_token ),
	                        array(
	                            '%s'
	                        ),
	                        array( '%s' )
	                    );
	                }
	            }

	            if($otp != "" && $auth_token != "")
                {
                	wp_redirect( home_url() . "/verify-login/" . $auth_token . "/");
                	exit;
                }
			}
			else
            {
            	if ( 0 == $login_attempt->login_status ) {
		            $wpdb->update(
		                $table_name,
		                array(
		                    'login_status' => 3
		                ),
		                array( 'auth_token' => $auth_token ),
		                array(
		                    '%d'
		                ),
		                array( '%s' )
		            );
		        }
	            
            	$login_url = wp_login_url();
		        $redirect_to = add_query_arg( array('rp_error' => '601'), $login_url );
		        wp_redirect( $redirect_to );
		        exit;
            }
		}
	}

?>