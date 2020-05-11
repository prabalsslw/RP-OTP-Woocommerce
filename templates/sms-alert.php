<?php 
	#-------------------------
	# Register & Trigger Hook For Woocommerce alert
	#-------------------------


	$rp_settings = get_option( 'rp_otp_setting' );

	if(isset($rp_settings['otp_woo_alert']) && $rp_settings['otp_woo_alert'] != "" && isset($rp_settings['otp_enable']) && $rp_settings['otp_enable'] != "")
	{

		if(isset($rp_settings['woo_pending_alert']) && $rp_settings['woo_pending_alert'] != "")
		{
			add_action( 'woocommerce_order_status_pending', 'rp_alert_pending');
		}
		if(isset($rp_settings['woo_processing_alert']) && $rp_settings['woo_processing_alert'] != "")
		{
			add_action( 'woocommerce_order_status_processing', 'rp_alert_processing');
		}
		if(isset($rp_settings['woo_hold_alert']) && $rp_settings['woo_hold_alert'] != "")
		{
			add_action( 'woocommerce_order_status_on-hold', 'rp_alert_hold');
		}
		if(isset($rp_settings['woo_fail_alert']) && $rp_settings['woo_fail_alert'] != "")
		{
			add_action( 'woocommerce_order_status_failed', 'rp_alert_failed');
		}
		if(isset($rp_settings['woo_cancel_alert']) && $rp_settings['woo_cancel_alert'] != "")
		{
			add_action( 'woocommerce_order_status_cancelled', 'rp_alert_cancelled');
		}
		if(isset($rp_settings['woo_complete_alert']) && $rp_settings['woo_complete_alert'] != "")
		{
			add_action( 'woocommerce_order_status_completed', 'rp_alert_completed');
		}
		if(isset($rp_settings['woo_refund_alert']) && $rp_settings['woo_refund_alert'] != "")
		{
			add_action( 'woocommerce_order_status_refunded', 'rp_alert_refunded');
		}
		if(isset($rp_settings['woo_partially_alert']) && $rp_settings['woo_partially_alert'] != "")
		{
			add_action('woocommerce_order_status_partially-paid','rp_alert_partially');
		}
		if(isset($rp_settings['woo_shipped_alert']) && $rp_settings['woo_shipped_alert'] != "")
		{
			add_action( 'woocommerce_order_status_shipped', 'rp_alert_shipped');
		}
		if(isset($rp_settings['user_reg_alert']) && $rp_settings['user_reg_alert'] != "")
		{
			add_action( 'user_register', 'rp_alert_registration');
		}
	}


	function rp_alert_registration($user_id){ 
		$rp_settings = get_option( 'rp_otp_setting' );
		$all_meta_for_user 	= get_user_meta( $user_id );
		global $wpdb;

		$nickname = $all_meta_for_user['nickname'][0];
		$customer_mobile = $all_meta_for_user['rp_phone_number'][0];

		$smstext = trim($rp_settings['user_reg_templete']);
	    $api_url = trim($rp_settings['api_url']);

	    if( $rp_settings['otp_enable'] != "" && $rp_settings['user_reg_templete'] != "" && $rp_settings['otp_woo_alert'] != "" && !empty($customer_mobile) && !empty($smstext) && $rp_settings['user_reg_alert'] != "")
	    {
	    	$smstext = str_ireplace("{{name}}", $nickname, $smstext);
	        
	        if(isset($rp_settings['get_post']))
            {
                $response = callToPostAPI($api_url, postParameter($rp_settings['api_peram'], $customer_mobile, $smstext));
            }
            else
            {
                $response = callToGetAPI($api_url, getParameter($rp_settings['api_peram'], $customer_mobile, $smstext));
            }
	    }
		
		if($response != "")
		{
			$user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
			$table_woo_name = $wpdb->prefix . "real_protection_woo";
			$wpdb->insert(
	            $table_woo_name,
	            array(
	                'user_id' => $user_id,
	                'user_name' => $nickname,
	                'user_ip' => $user_ip,
	                'sms_type' => 'User Registration',
	                'phone_no' => $customer_mobile,
	                'sms_ref_id' => serialize($response)
	            )
	        );
		}
		
		// error_log(serialize($all_meta_for_user), 0); 
	}

	function rp_alert_pending($order_id) {
		$rp_settings = get_option( 'rp_otp_setting' );
	    $order 			= new WC_Order( $order_id );
	    $order_amount	= $order->get_total();
	    $user 			= $order->get_user();
	    $user_id 		= $order->get_user_id();
	    $currency 		= $order->get_currency();
	    global $wpdb;

	    if($order->get_billing_phone() != "")
	    {
	    	$name    		 = $order->get_billing_last_name().' '.$order->get_billing_first_name();
	    	$customer_mobile = $order->get_billing_phone();
	    }
	    else
	    {
	    	$all_meta_for_user 	= get_user_meta( $user_id );
			$customer_mobile 	= $all_meta_for_user['rp_phone_number'][0];
			$name 				= $all_meta_for_user['first_name'][0].' '.$all_meta_for_user['last_name'][0];
	    }
	    

	    $status  = 'Pending';
	    $smstext = trim($rp_settings['order_sms_templete']);
	    $api_url = trim($rp_settings['api_url']);

	    if( $rp_settings['otp_enable'] != "" && $rp_settings['order_sms_templete'] != "" && $rp_settings['otp_woo_alert'] != "" && !empty($customer_mobile) && !empty($smstext) && $rp_settings['woo_pending_alert'] != "")
	    {
	    	$smstext = str_ireplace("{{name}}", $name, $smstext);
	    	$smstext = str_ireplace("{{status}}", $status, $smstext);
	    	$smstext = str_ireplace("{{amount}}", $order_amount, $smstext);
	    	$smstext = str_ireplace("{{currency}}", $currency, $smstext);
	        $smstext = str_ireplace("{{order_id}}", $order_id, $smstext);
	        
	        if(isset($rp_settings['get_post']))
            {
                $response = callToPostAPI($api_url, postParameter($rp_settings['api_peram'], $customer_mobile, $smstext));
            }
            else
            {
                $response = callToGetAPI($api_url, getParameter($rp_settings['api_peram'], $customer_mobile, $smstext));
            }
	    }

	    if($response != "")
		{
			$user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
			$table_woo_name = $wpdb->prefix . "real_protection_woo";
			$wpdb->insert(
	            $table_woo_name,
	            array(
	                'user_id' => $user_id,
	                'user_name' => $name,
	                'user_ip' => $user_ip,
	                'sms_type' => 'Order Notification Alert',
	                'phone_no' => $customer_mobile,
	                'sms_ref_id' => serialize($response)
	            )
	        );
		}
    }

    function rp_alert_failed($order_id) {
    	$rp_settings = get_option( 'rp_otp_setting' );
	    $order 			= new WC_Order( $order_id );
	    $order_amount	= $order->get_total();
	    $user 			= $order->get_user();
	    $user_id 		= $order->get_user_id();
	    $currency 		= $order->get_currency();
	    global $wpdb;

	    if($order->get_billing_phone() != "")
	    {
	    	$name    		 = $order->get_billing_last_name().' '.$order->get_billing_first_name();
	    	$customer_mobile = $order->get_billing_phone();
	    }
	    else
	    {
	    	$all_meta_for_user 	= get_user_meta( $user_id );
			$customer_mobile 	= $all_meta_for_user['rp_phone_number'][0];
			$name 				= $all_meta_for_user['first_name'][0].' '.$all_meta_for_user['last_name'][0];
	    }
	    

	    $status  = 'Failed';
	    $smstext = trim($rp_settings['order_sms_templete']);
	    $api_url = trim($rp_settings['api_url']);

	    if( $rp_settings['otp_enable'] != "" && $rp_settings['order_sms_templete'] != "" && $rp_settings['otp_woo_alert'] != "" && !empty($customer_mobile) && !empty($smstext) && $rp_settings['woo_fail_alert'] != "")
	    {
	    	$smstext = str_ireplace("{{name}}", $name, $smstext);
	    	$smstext = str_ireplace("{{status}}", $status, $smstext);
	    	$smstext = str_ireplace("{{amount}}", $order_amount, $smstext);
	    	$smstext = str_ireplace("{{currency}}", $currency, $smstext);
	        $smstext = str_ireplace("{{order_id}}", $order_id, $smstext);
	        
	        if(isset($rp_settings['get_post']))
            {
                $response = callToPostAPI($api_url, postParameter($rp_settings['api_peram'], $customer_mobile, $smstext));
            }
            else
            {
                $response = callToGetAPI($api_url, getParameter($rp_settings['api_peram'], $customer_mobile, $smstext));
            }
	    }

	    if($response != "")
		{
			$user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
			$table_woo_name = $wpdb->prefix . "real_protection_woo";
			$wpdb->insert(
	            $table_woo_name,
	            array(
	                'user_id' => $user_id,
	                'user_name' => $name,
	                'user_ip' => $user_ip,
	                'sms_type' => 'Order Notification Alert',
	                'phone_no' => $customer_mobile,
	                'sms_ref_id' => serialize($response)
	            )
	        );
		}
    }

    function rp_alert_hold($order_id) {
    	$rp_settings = get_option( 'rp_otp_setting' );
	    $order 			= new WC_Order( $order_id );
	    $order_amount	= $order->get_total();
	    $user 			= $order->get_user();
	    $user_id 		= $order->get_user_id();
	    $currency 		= $order->get_currency();
	    global $wpdb;

	    if($order->get_billing_phone() != "")
	    {
	    	$name    		 = $order->get_billing_last_name().' '.$order->get_billing_first_name();
	    	$customer_mobile = $order->get_billing_phone();
	    }
	    else
	    {
	    	$all_meta_for_user 	= get_user_meta( $user_id );
			$customer_mobile 	= $all_meta_for_user['rp_phone_number'][0];
			$name 				= $all_meta_for_user['first_name'][0].' '.$all_meta_for_user['last_name'][0];
	    }
	    

	    $status  = 'On-Hold';
	    $smstext = trim($rp_settings['order_sms_templete']);
	    $api_url = trim($rp_settings['api_url']);

	    if( $rp_settings['otp_enable'] != "" && $rp_settings['order_sms_templete'] != "" && $rp_settings['otp_woo_alert'] != "" && !empty($customer_mobile) && !empty($smstext) && $rp_settings['woo_hold_alert'] != "")
	    {
	    	$smstext = str_ireplace("{{name}}", $name, $smstext);
	    	$smstext = str_ireplace("{{status}}", $status, $smstext);
	    	$smstext = str_ireplace("{{amount}}", $order_amount, $smstext);
	    	$smstext = str_ireplace("{{currency}}", $currency, $smstext);
	        $smstext = str_ireplace("{{order_id}}", $order_id, $smstext);
	        
	        if(isset($rp_settings['get_post']))
            {
                $response = callToPostAPI($api_url, postParameter($rp_settings['api_peram'], $customer_mobile, $smstext));
            }
            else
            {
                $response = callToGetAPI($api_url, getParameter($rp_settings['api_peram'], $customer_mobile, $smstext));
            }
	    }

	    if($response != "")
		{
			$user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
			$table_woo_name = $wpdb->prefix . "real_protection_woo";
			$wpdb->insert(
	            $table_woo_name,
	            array(
	                'user_id' => $user_id,
	                'user_name' => $name,
	                'user_ip' => $user_ip,
	                'sms_type' => 'Order Notification Alert',
	                'phone_no' => $customer_mobile,
	                'sms_ref_id' => serialize($response)
	            )
	        );
		}
    }

    function rp_alert_processing($order_id) {
    	$rp_settings = get_option( 'rp_otp_setting' );
	    $order 			= new WC_Order( $order_id );
	    $order_amount	= $order->get_total();
	    $user 			= $order->get_user();
	    $user_id 		= $order->get_user_id();
	    $currency 		= $order->get_currency();
	    global $wpdb;

	    if($order->get_billing_phone() != "")
	    {
	    	$name    		 = $order->get_billing_last_name().' '.$order->get_billing_first_name();
	    	$customer_mobile = $order->get_billing_phone();
	    }
	    else
	    {
	    	$all_meta_for_user 	= get_user_meta( $user_id );
			$customer_mobile 	= $all_meta_for_user['rp_phone_number'][0];
			$name 				= $all_meta_for_user['first_name'][0].' '.$all_meta_for_user['last_name'][0];
	    }
	    

	    $status  = 'Processing';
	    $smstext = trim($rp_settings['order_sms_templete']);
	    $api_url = trim($rp_settings['api_url']);

	    if( $rp_settings['otp_enable'] != "" && $rp_settings['order_sms_templete'] != "" && $rp_settings['otp_woo_alert'] != "" && !empty($customer_mobile) && !empty($smstext) && $rp_settings['woo_processing_alert'] != "")
	    {
	    	$smstext = str_ireplace("{{name}}", $name, $smstext);
	    	$smstext = str_ireplace("{{status}}", $status, $smstext);
	    	$smstext = str_ireplace("{{amount}}", $order_amount, $smstext);
	    	$smstext = str_ireplace("{{currency}}", $currency, $smstext);
	        $smstext = str_ireplace("{{order_id}}", $order_id, $smstext);
	        
	        if(isset($rp_settings['get_post']))
            {
                $response = callToPostAPI($api_url, postParameter($rp_settings['api_peram'], $customer_mobile, $smstext));
            }
            else
            {
                $response = callToGetAPI($api_url, getParameter($rp_settings['api_peram'], $customer_mobile, $smstext));
            }
	    }

	    if($response != "")
		{
			$user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
			$table_woo_name = $wpdb->prefix . "real_protection_woo";
			$wpdb->insert(
	            $table_woo_name,
	            array(
	                'user_id' => $user_id,
	                'user_name' => $name,
	                'user_ip' => $user_ip,
	                'sms_type' => 'Order Notification Alert',
	                'phone_no' => $customer_mobile,
	                'sms_ref_id' => serialize($response)
	            )
	        );
		}
    }

    function rp_alert_completed($order_id) {
    	$rp_settings = get_option( 'rp_otp_setting' );
	    $order 			= new WC_Order( $order_id );
	    $order_amount	= $order->get_total();
	    $user 			= $order->get_user();
	    $user_id 		= $order->get_user_id();
	    $currency 		= $order->get_currency();
	    global $wpdb;

	    if($order->get_billing_phone() != "")
	    {
	    	$name    		 = $order->get_billing_last_name().' '.$order->get_billing_first_name();
	    	$customer_mobile = $order->get_billing_phone();
	    }
	    else
	    {
	    	$all_meta_for_user 	= get_user_meta( $user_id );
			$customer_mobile 	= $all_meta_for_user['rp_phone_number'][0];
			$name 				= $all_meta_for_user['first_name'][0].' '.$all_meta_for_user['last_name'][0];
	    }
	    

	    $status  = 'Completed';
	    $smstext = trim($rp_settings['order_sms_templete']);
	    $api_url = trim($rp_settings['api_url']);

	    if( $rp_settings['otp_enable'] != "" && $rp_settings['order_sms_templete'] != "" && $rp_settings['otp_woo_alert'] != "" && !empty($customer_mobile) && !empty($smstext) && $rp_settings['woo_complete_alert'] != "")
	    {
	    	$smstext = str_ireplace("{{name}}", $name, $smstext);
	    	$smstext = str_ireplace("{{status}}", $status, $smstext);
	    	$smstext = str_ireplace("{{amount}}", $order_amount, $smstext);
	    	$smstext = str_ireplace("{{currency}}", $currency, $smstext);
	        $smstext = str_ireplace("{{order_id}}", $order_id, $smstext);
	        
	        if(isset($rp_settings['get_post']))
            {
                $response = callToPostAPI($api_url, postParameter($rp_settings['api_peram'], $customer_mobile, $smstext));
            }
            else
            {
                $response = callToGetAPI($api_url, getParameter($rp_settings['api_peram'], $customer_mobile, $smstext));
            }
	    }

	    if($response != "")
		{
			$user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
			$table_woo_name = $wpdb->prefix . "real_protection_woo";
			$wpdb->insert(
	            $table_woo_name,
	            array(
	                'user_id' => $user_id,
	                'user_name' => $name,
	                'user_ip' => $user_ip,
	                'sms_type' => 'Order Notification Alert',
	                'phone_no' => $customer_mobile,
	                'sms_ref_id' => serialize($response)
	            )
	        );
		}
    }

    function rp_alert_refunded($order_id) {
    	$rp_settings = get_option( 'rp_otp_setting' );
	    $order 			= new WC_Order( $order_id );
	    $order_amount	= $order->get_total();
	    $user 			= $order->get_user();
	    $user_id 		= $order->get_user_id();
	    $currency 		= $order->get_currency();
	    global $wpdb;

	    if($order->get_billing_phone() != "")
	    {
	    	$name    		 = $order->get_billing_last_name().' '.$order->get_billing_first_name();
	    	$customer_mobile = $order->get_billing_phone();
	    }
	    else
	    {
	    	$all_meta_for_user 	= get_user_meta( $user_id );
			$customer_mobile 	= $all_meta_for_user['rp_phone_number'][0];
			$name 				= $all_meta_for_user['first_name'][0].' '.$all_meta_for_user['last_name'][0];
	    }
	    

	    $status  = 'Refunded';
	    $smstext = trim($rp_settings['order_sms_templete']);
	    $api_url = trim($rp_settings['api_url']);

	    if( $rp_settings['otp_enable'] != "" && $rp_settings['order_sms_templete'] != "" && $rp_settings['otp_woo_alert'] != "" && !empty($customer_mobile) && !empty($smstext) && $rp_settings['woo_refund_alert'] != "")
	    {
	    	$smstext = str_ireplace("{{name}}", $name, $smstext);
	    	$smstext = str_ireplace("{{status}}", $status, $smstext);
	    	$smstext = str_ireplace("{{amount}}", $order_amount, $smstext);
	    	$smstext = str_ireplace("{{currency}}", $currency, $smstext);
	        $smstext = str_ireplace("{{order_id}}", $order_id, $smstext);
	        
	        if(isset($rp_settings['get_post']))
            {
                $response = callToPostAPI($api_url, postParameter($rp_settings['api_peram'], $customer_mobile, $smstext));
            }
            else
            {
                $response = callToGetAPI($api_url, getParameter($rp_settings['api_peram'], $customer_mobile, $smstext));
            }
	    }

	    if($response != "")
		{
			$user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
			$table_woo_name = $wpdb->prefix . "real_protection_woo";
			$wpdb->insert(
	            $table_woo_name,
	            array(
	                'user_id' => $user_id,
	                'user_name' => $name,
	                'user_ip' => $user_ip,
	                'sms_type' => 'Order Notification Alert',
	                'phone_no' => $customer_mobile,
	                'sms_ref_id' => serialize($response)
	            )
	        );
		}
    }

    function rp_alert_cancelled($order_id) {
    	$rp_settings = get_option( 'rp_otp_setting' );
	    $order 			= new WC_Order( $order_id );
	    $order_amount	= $order->get_total();
	    $user 			= $order->get_user();
	    $user_id 		= $order->get_user_id();
	    $currency 		= $order->get_currency();
	    global $wpdb;

	    if($order->get_billing_phone() != "")
	    {
	    	$name    		 = $order->get_billing_last_name().' '.$order->get_billing_first_name();
	    	$customer_mobile = $order->get_billing_phone();
	    }
	    else
	    {
	    	$all_meta_for_user 	= get_user_meta( $user_id );
			$customer_mobile 	= $all_meta_for_user['rp_phone_number'][0];
			$name 				= $all_meta_for_user['first_name'][0].' '.$all_meta_for_user['last_name'][0];
	    }
	    

	    $status  = 'Cancelled';
	    $smstext = trim($rp_settings['order_sms_templete']);
	    $api_url = trim($rp_settings['api_url']);

	    if( $rp_settings['otp_enable'] != "" && $rp_settings['order_sms_templete'] != "" && $rp_settings['otp_woo_alert'] != "" && !empty($customer_mobile) && !empty($smstext) && $rp_settings['woo_cancel_alert'] != "")
	    {
	    	$smstext = str_ireplace("{{name}}", $name, $smstext);
	    	$smstext = str_ireplace("{{status}}", $status, $smstext);
	    	$smstext = str_ireplace("{{amount}}", $order_amount, $smstext);
	    	$smstext = str_ireplace("{{currency}}", $currency, $smstext);
	        $smstext = str_ireplace("{{order_id}}", $order_id, $smstext);
	        
	        if(isset($rp_settings['get_post']))
            {
                $response = callToPostAPI($api_url, postParameter($rp_settings['api_peram'], $customer_mobile, $smstext));
            }
            else
            {
                $response = callToGetAPI($api_url, getParameter($rp_settings['api_peram'], $customer_mobile, $smstext));
            }
	    }

	    if($response != "")
		{
			$user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
			$table_woo_name = $wpdb->prefix . "real_protection_woo";
			$wpdb->insert(
	            $table_woo_name,
	            array(
	                'user_id' => $user_id,
	                'user_name' => $name,
	                'user_ip' => $user_ip,
	                'sms_type' => 'Order Notification Alert',
	                'phone_no' => $customer_mobile,
	                'sms_ref_id' => serialize($response)
	            )
	        );
		}
    }

	function rp_alert_shipped($order_id){
	    $rp_settings = get_option( 'rp_otp_setting' );
	    $order 			= new WC_Order( $order_id );
	    $order_amount	= $order->get_total();
	    $user 			= $order->get_user();
	    $user_id 		= $order->get_user_id();
	    $currency 		= $order->get_currency();
	    global $wpdb;

	    if($order->get_billing_phone() != "")
	    {
	    	$name    		 = $order->get_billing_last_name().' '.$order->get_billing_first_name();
	    	$customer_mobile = $order->get_billing_phone();
	    }
	    else
	    {
	    	$all_meta_for_user 	= get_user_meta( $user_id );
			$customer_mobile 	= $all_meta_for_user['rp_phone_number'][0];
			$name 				= $all_meta_for_user['first_name'][0].' '.$all_meta_for_user['last_name'][0];
	    }
	    

	    $status  = 'Shipped';
	    $smstext = trim($rp_settings['order_sms_templete']);
	    $api_url = trim($rp_settings['api_url']);

	    if( $rp_settings['otp_enable'] != "" && $rp_settings['order_sms_templete'] != "" && $rp_settings['otp_woo_alert'] != "" && !empty($customer_mobile) && !empty($smstext) && $rp_settings['woo_shipped_alert'] != "")
	    {
	    	$smstext = str_ireplace("{{name}}", $name, $smstext);
	    	$smstext = str_ireplace("{{status}}", $status, $smstext);
	    	$smstext = str_ireplace("{{amount}}", $order_amount, $smstext);
	    	$smstext = str_ireplace("{{currency}}", $currency, $smstext);
	        $smstext = str_ireplace("{{order_id}}", $order_id, $smstext);
	        
	        if(isset($rp_settings['get_post']))
            {
                $response = callToPostAPI($api_url, postParameter($rp_settings['api_peram'], $customer_mobile, $smstext));
            }
            else
            {
                $response = callToGetAPI($api_url, getParameter($rp_settings['api_peram'], $customer_mobile, $smstext));
            }
	    }

	    if($response != "")
		{
			$user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
			$table_woo_name = $wpdb->prefix . "real_protection_woo";
			$wpdb->insert(
	            $table_woo_name,
	            array(
	                'user_id' => $user_id,
	                'user_name' => $name,
	                'user_ip' => $user_ip,
	                'sms_type' => 'Order Notification Alert',
	                'phone_no' => $customer_mobile,
	                'sms_ref_id' => serialize($response)
	            )
	        );
		}
	}

	function rp_alert_partially($order_id){
	    $rp_settings = get_option( 'rp_otp_setting' );
	    $order 			= new WC_Order( $order_id );
	    $order_amount	= $order->get_total();
	    $user 			= $order->get_user();
	    $user_id 		= $order->get_user_id();
	    $currency 		= $order->get_currency();
	    global $wpdb;

	    if($order->get_billing_phone() != "")
	    {
	    	$name    		 = $order->get_billing_last_name().' '.$order->get_billing_first_name();
	    	$customer_mobile = $order->get_billing_phone();
	    }
	    else
	    {
	    	$all_meta_for_user 	= get_user_meta( $user_id );
			$customer_mobile 	= $all_meta_for_user['rp_phone_number'][0];
			$name 				= $all_meta_for_user['first_name'][0].' '.$all_meta_for_user['last_name'][0];
	    }
	    

	    $status  = 'Partially Paid';
	    $smstext = trim($rp_settings['order_sms_templete']);
	    $api_url = trim($rp_settings['api_url']);

	    if( $rp_settings['otp_enable'] != "" && $rp_settings['order_sms_templete'] != "" && $rp_settings['otp_woo_alert'] != "" && !empty($customer_mobile) && !empty($smstext) && $rp_settings['woo_partially_alert'] != "")
	    {
	    	$smstext = str_ireplace("{{name}}", $name, $smstext);
	    	$smstext = str_ireplace("{{status}}", $status, $smstext);
	    	$smstext = str_ireplace("{{amount}}", $order_amount, $smstext);
	    	$smstext = str_ireplace("{{currency}}", $currency, $smstext);
	        $smstext = str_ireplace("{{order_id}}", $order_id, $smstext);
	        
	        if(isset($rp_settings['get_post']))
            {
                $response = callToPostAPI($api_url, postParameter($rp_settings['api_peram'], $customer_mobile, $smstext));
            }
            else
            {
                $response = callToGetAPI($api_url, getParameter($rp_settings['api_peram'], $customer_mobile, $smstext));
            }
	    }

	    if($response != "")
		{
			$user_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
			$table_woo_name = $wpdb->prefix . "real_protection_woo";
			$wpdb->insert(
	            $table_woo_name,
	            array(
	                'user_id' => $user_id,
	                'user_name' => $name,
	                'user_ip' => $user_ip,
	                'sms_type' => 'Order Notification Alert',
	                'phone_no' => $customer_mobile,
	                'sms_ref_id' => serialize($response)
	            )
	        );
		}
	}

  ?>