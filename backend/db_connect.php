<?php
// Database connection file
/*$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'aries_college_db';
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);
?>*/

// Database connection file
$DB_HOST = 'localhost';
$DB_USER = 'achtecho_user';
$DB_PASS = '2fvW!GSO30,Y8{R&';
$DB_NAME = 'achtecho_db';
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);
?> 