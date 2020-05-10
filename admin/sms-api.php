<?php 
	function postParameter($string, $phone_number, $sms_text)
	{
	    $post_param = explode('&', $string);

	    $unique_id = uniqid();

	    $post_param=str_ireplace("{{phone_number}}", $phone_number, $post_param);
	    $post_param=str_ireplace("{{sms_text}}", $sms_text, $post_param);
	    $post_param=str_ireplace("{{unique_id}}", $unique_id, $post_param);

	    $param = explode("&", implode("&",$post_param));
	    $count_param = count($param);

	    $splited_data = array();
	    $post_data = array();

	    for ($i=0; $i < $count_param; $i++) { 
	         $splited_data[] = explode("=",$param[$i]);
	    }

	    $count_spl = count($splited_data);

	    for ($i=0; $i < $count_spl; $i++) { 
	        $post_data[$splited_data[$i][0]] = $splited_data[$i][1];
	    }

	    return $post_data;
	}

	function getParameter($string, $phone_number, $sms_text)
	{
	    $post_param = explode('&', $string);

	    $unique_id = uniqid();

	    $post_param=str_ireplace("{{phone_number}}", $phone_number, $post_param);
	    $post_param=str_ireplace("{{sms_text}}", urlencode($sms_text), $post_param);
	    $post_param=str_ireplace("{{unique_id}}", $unique_id, $post_param);

	    $param = implode("&",$post_param);
	    
	    return $param;
	}

	function callToGetAPI($apiurl, $peram)
	{
		$curl   = curl_init();

		curl_setopt_array($curl, array( CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => "$apiurl?$peram", CURLOPT_USERAGENT => 'Sample cURL Request' ));

		$response = curl_exec($curl);
		curl_close($curl);

		return $response;
	}

	function callToPostAPI($apiurl, $post_data = array())
	{
		$crl = curl_init();

		curl_setopt($crl,CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($crl,CURLOPT_SSL_VERIFYHOST,2);
		curl_setopt($crl,CURLOPT_URL,$apiurl);
		curl_setopt($crl,CURLOPT_HEADER,0);
		curl_setopt($crl,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($crl,CURLOPT_POST,1);
		curl_setopt($crl,CURLOPT_POSTFIELDS,$post_data);

		$response = curl_exec($crl);
		curl_close($crl);

		return $response;
	}
?>