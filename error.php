<?php
//this page shows the log sved by the debugging code in functions.php and endpoint.php
require 'keys.php';
$servername = "localhost";

$dbname = "smart_bin";

// Create connection
$conn = new mysqli($servername, $_mysqlUsername, $_mysqlPpassword, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

$sql = "SELECT ID, dateTime, debugData FROM tblDebugLog";
$result = $conn->query($sql);
	echo "<table border=\"1\">";
	if ($result->num_rows > 0) {
    	// output data of each row
    	while($row = $result->fetch_assoc()) {
        	echo "<tr><td>ID: " . $row["ID"]. "</td><td> - Time Stamp: " . $row["dateTime"]. "</td><td>" . $row["debugData"]. "</td></tr>";
    }
    echo "</table>";
	} else {
    	echo "0 results";
}
$conn->close();
?>