<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "collegeconnect";

$conn = new mysqli("localhost","root","","collegeconnect");

if ($conn->connect_error) {
    die("Database connection failed");
}

#session_start();
?>
