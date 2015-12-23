<?php
//include php functions called by this script
require 'keys.php';
require "functions.php";
require "push.php";
$itemDescription;

$errorLog = "---BEGIN ERROR LOG---";
$errorLog = $errorLog . "<br>BCN=" . $_GET['BCN']; 

//save BCN querystring to variable
$BCN_QS = $_GET['BCN'];

//remove everything except numbers from barcode variable
$barcode = preg_replace("/\D/","",$BCN_QS);
//add a MOD10/Luhn alogrithm check here check here

//set login result to $loginResult
$loginResult = login();

if ($loginResult != "ERROR:LOGIN_FAILED") {
	//if login was ok
	$sessionID = $loginResult;
	//call function lookup_BCN with barcode and session ID and save result into tescoSKU variable
	$tescoSKU = lookup_BCN($barcode, $sessionID);
	if ($tescoSKU != "ERROR: No uniquie SKU found!") {
		$add_status = addToBasket($tescoSKU, $sessionID);
		if ($add_status == "BASKET_ADD:OK") {
			//echo $add_status;
			echo "<br>";
			echo "STATUS: Item added OK.";
			push($itemDescription . " added to basket", $_email);
		}else{
			//echo $add_status;
			echo "<br>";
			echo "STATUS: Error while adding.";
		}
	} else {
		//$errorLog = $errorLog."<br>Lookup_BCN failed!"; //error already generated inside lookup_BCN function
		//remove error reporting from SKU lookup function as there is now an alternate lookup route
	}
} else {
	//error catch
	echo "STATUS: Login failure.";
	push("Error at login stage", $_email);
}
	$errorLog = $errorLog."<br>---END ERROR LOG---"; 
	saveDebug();
?>