<?php
require 'keys.php';

function push($message, $email){
	global $_pushBulletKey;
	//@ symbol must be escaped as %40 for API call to process login correctly
	// as it turns out...it doesn't

	$data = array
		(
			"email" => "$email", 
			"type" => "note",
			"title" => "Intellibasket.com",
			"body" => "$message"
	);	//should $emails be $email possibly??
	
	//$data_string = "json=" . json_encode($data) . "&";
	$data_string = json_encode($data);
	
	
	$url = "https://api.pushbullet.com/v2/pushes";

	//variable to hold curl object				
	$ch = curl_init();

	//provide url variable to curl object
	curl_setopt($ch, CURLOPT_URL, $url);

	// Set so curl_exec returns the result instead of outputting it.
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	//configure CURL to verify remote host
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	
	//curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    	'Authorization: Bearer ' . $_pushBulletKey,
    	'Content-Type: application/json')
    );
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

	//public key for verification
	curl_setopt($ch, CURLOPT_CAINFO, getcwd() . "/certificates/*.pushbullet.com.PEM");

	// Get the response and close the channel.
	$response = curl_exec($ch);

	//check if curl request completed
	if($response === false){
    	//show error if curl didn't complete
    	echo 'Curl error: ' . curl_error($ch);
	}else{
    	//response from API via curl object if curl job ran correctly
    	$returned_JSON = $response;
	}

	//end curl session
	curl_close($ch);
}
?>