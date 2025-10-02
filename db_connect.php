<?php
$host = "localhost";
$user = "root";       // update if needed
$pass = "";           // update if needed
$db   = "eventzilla";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
