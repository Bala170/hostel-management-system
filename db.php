<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hostel";

$conn = new mysqli(
    "localhost",
    "root",
    "",
    "hostel",
    3306
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>