<?php
$servername = "rei.cs.ndsu.nodak.edu";
$username = "jamal_mohamed_371s25";
$password = "WLdT3N2aMN0!";
$database = "jamal_mohamed_db371s25";

// Create a connection
$conn = new mysqli($servername, $username, $password, $database);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}
?>