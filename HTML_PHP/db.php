<?php
$servername = "bdumoodleserver.mysql.database.azure.com
$username = "pranta";  // Default XAMPP
$password = "pranta25.";      // Default is blank
$dbname = "medicare";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
