<?php
//This file contains functions included for the main application logic and control flow.

function APIrequest($arguments){
	//@ symbol must be escaped as %40 for API call to process login correctly
	$url = "https://secure.techfortesco.com/tescolabsapi/restservice.aspx?" .$arguments;

	//variable to hold curl object				
	$ch = curl_init();

	//provide url variable to curl object
	curl_setopt($ch, CURLOPT_URL, $url);

	// Set so curl_exec returns the result instead of outputting it.
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	//configure CURL to verify remote host
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

	//public key for verification
	curl_setopt($ch, CURLOPT_CAINFO, getcwd() . "/certificates/secure.techfortesco.com.PEM");

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
	
	//declare errorLog as a global variable in order to write debug output to main function
	global $errorLog;
	$errorLog = $errorLog."<br>API Request Sent/Recieved OK!"; 
	return ($returned_JSON);
}


function login(){
global $_email;
global $_password;
global $_tescoDeveloperKey;
global $_tescoApplicationKey;

	//declare errorLog as a global variable in order to write debug output to main function
	global $errorLog;
	
	//function recieves barcode number from remote scanner hardware and passes it to the tescoAPI function
	$login_JSON = APIrequest("command=LOGIN&email=" . $_email . "&password=" . $_password . "&developerkey=" . $_tescoDeveloperKey . "&applicationkey=" . $_tescoApplicationKey);
	
	$decoded_JSON = json_decode($login_JSON, true);
	if ($decoded_JSON['StatusInfo'] == "Command Processed OK") {
		$loginResult = $decoded_JSON['SessionKey'];
		$errorLog = $errorLog."<br>Login OK!"; 
	}else{
		$errorLog = $errorLog."<br>Login Failure!"; 
		$loginResult = "ERROR:LOGIN_FAILED";
	}
	return $loginResult;
}


function lookup_BCN($BCN, $sessionID){

	//declare errorLog as a global variable in order to write debug output to main function
	global $errorLog;
	global $itemDescription;

	
	//function recieves barcode number from remote scanner hardware and passes it to the tescoAPI function
	//$sessionID = get session id key from file
	$lookup_JSON = APIrequest("command=PRODUCTSEARCH&searchtext=" .$BCN. "&page=1&sessionkey=" .$sessionID);
	$decoded_JSON = json_decode($lookup_JSON, true);
	echo "<pre>";
	print_r($lookup_JSON);
	echo "</pre>";
	if ($decoded_JSON['TotalProductCount'] != "1") {
		if ($decoded_JSON['TotalProductCount'] == 0) {
			//no results
		} else {
			//multiple results
			//try to find what this is using outpan
			//lookup_Description($BCN);
			
		}
		
		//$errorLog = $errorLog."<br>Lookup_BCN No SKU found or multiple SKUs returned!(" . $decoded_JSON['TotalProductCount'] . ")"; 
		
		//send push to user
		push("ERROR : Item not added " . $decoded_JSON['TotalProductCount'] . "items found", $_email);
		
		//return "ERROR: No uniquie SKU found!";
	} else {
		$tescoSKU = $decoded_JSON['Products']['0']['ProductId'];
		$errorLog = $errorLog."<br>Lookup_BCN returned OK!"; 
		
		$itemDescription = $decoded_JSON['Products']['0']['Name'];
		
		return $tescoSKU;
	}
}

function lookup_Description($BCN, $sessionID){
	//this function is not called by any other code currently
	//function takes description and compares outpan description and calcuates probability of match

	//declare errorLog as a global variable in order to write debug output to main function
	global $errorLog;
	global $itemDescription;
	global $_email;
	
	$outpanDescription = outpanQuery($BCN);
	$outpanDescriptionExploded = explode(' ', $outpanDescription);

	$outpanDescriptionExploded = array_map('strtolower', $outpanDescription);

		echo "<pre>";
		print_r($outpanDescriptionExploded);
		echo "</pre>";
		
		
	//$sessionID = get session id key from file
	$lookup_JSON = APIrequest("command=PRODUCTSEARCH&searchtext=" . urlencode($outpanDescription). "&page=1&sessionkey=" .$sessionID);
	$decoded_JSON = json_decode($lookup_JSON, true);
	echo "<br>BCN lookup data<br><pre>";
	print_r($lookup_JSON);
	echo "</pre>";
	if ($decoded_JSON['TotalProductCount'] > "0") {
		$resultCount = $decoded_JSON['TotalProductCount'];
		$resultsArray = array_fill(0, $resultCount, NULL);
		for ($i = 0; $i < $resultCount && $i < 10; $i++) {
			$resultsArray[$i] = array($decoded_JSON['Products'][$i]['Name'], $decoded_JSON['Products'][$i]['ProductId'], 0);
			$resultsArray[$i][0] = explode(' ', $resultsArray[$i][0]);
			$resultsArray[$i][0] = array_map('strtolower', $resultsArray[$i][0]);
			
			$comparison = array_intersect($outpanDescriptionExploded, $resultsArray[$i][0]);
			$resultsArray[$i][2] = count($comparison);
			
		}
		$currentHighest = NULL;//variable to store highest value
		$currentHighestSKU = NULL;
		for ($i = 0; $i < count($resultsArray); $i++) {
			if ($resultsArray[$i][2] > $currentHighest) {
				$currentHighest = $resultsArray[$i][2];
				$currentHighestSKU = $resultsArray[$i][1];
			}
		}
		
		echo "Best Match = " . $currentHighestSKU;
		push("Was that ". $currentHighestSKU . "?", $_email);

		
		echo "<pre>";
		print_r($resultsArray);
		echo "</pre>";
		
		//return $tescoSKU;
		
	} else {
		$errorLog = $errorLog."<br>Lookup_Description: No results returned!"; 
		
		//send push to user
		push("ERROR : Item not added no items found", $_email);
		//return "ERROR_NO_results";
	}
}



function addToBasket($tescoSKU, $sessionID){
	//function takes tescoSKU attempts to add to basket
	//this function currently lacks error handling so may return that is has completed
	//sucessfully when the API has returned an error

	//declare errorLog as a global variable in order to write debug output to main function
	//change to use GLOBAL[error] array so that this line is nor required
	global $errorLog;
	
	$addToBasket_JSON = APIrequest("command=CHANGEBASKET&changequantity=1&SESSIONKEY=" .$sessionID. "&PRODUCTID=" .$tescoSKU);
	$decoded_JSON = json_decode($addToBasket_JSON, true);
	echo "<br>Basket add JSON data<br><pre>";
	print_r($addToBasket_JSON);
	echo "</pre>";
	//New error handling code
	if ($decoded_JSON['StatusInfo']=="Basket change completed successfully"){
		$errorLog = $errorLog."<br>Item added to basket OK!"; 
		$addStatus = "BASKET_ADD:OK";
		return $addStatus;
	} else {
		$errorLog = $errorLog."<br>Error adding item to basket!"; 
		$addStatus = "BASKET_ADD:ERROR";
		return $addStatus;
	}


	//add code to output error if there is one
	print_r($decoded_JSON);//debbug only
	}
	
	
function saveDebug(){
	global $errorLog;
	global $_mysqlUsername;
	global $_mysqlPassword;
	
	$servername = "localhost";
	$dbname = "smart_bin";	//production
	

	// Create connection
	$conn = new mysqli($servername, $_mysqlUsername, $_mysqlPassword, $dbname);
	// Check connection
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	} 

	$sql = "INSERT INTO `tblDebugLog` (`ID`, `dateTime`, `debugData`) VALUES (NULL, CURRENT_TIMESTAMP, '$errorLog');";
	if ($conn->query($sql) === TRUE) {
    	echo "New debug record created successfully";
	} else {
    	echo "Error: " . $sql . "<br>" . $conn->error;
	}

	$conn->close();
}



function outpanQuery($BCN){
	global $_outpanKey;
	//@ symbol must be escaped as %40 for API call to process login correctly
	$url = urlencode("https://api.outpan.com/v2/products/" .$BCN. "?apikey=" .$_outpanKey);

	//variable to hold curl object				
	$ch = curl_init();

	//provide url variable to curl object
	curl_setopt($ch, CURLOPT_URL, $url);

	// Set so curl_exec returns the result instead of outputting it.
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	//configure CURL to verify remote host
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

	//public key for verification
	curl_setopt($ch, CURLOPT_CAINFO, getcwd() . "/certificates/api.outpan.com.PEM");

	// Get the response and close the channel.
	$response = curl_exec($ch);
	
	//check if curl request completed
	if($response === false){
    	//show error if curl didn't complete
    	echo 'Curl error: ' . curl_error($ch);
	} else {
    	//response from API via curl object if curl job ran correctly
    	$JSONresponse = json_decode($response, true);
		if ($JSONresponse['name'] == "") {
			$answer = "ERROR:NO_RESULT";
		} else {
			$answer = $JSONresponse['name'];
		}
	}
	//end curl session
	curl_close($ch);
	print("<pre>");
	print_r($response);
	print("</pre>");
	return ($answer);
}
?>