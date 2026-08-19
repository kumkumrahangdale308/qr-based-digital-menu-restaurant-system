<?php

$host = '127.0.0.1';
$user = 'root';
$password = '';
$database = 'restaurant_system';
$port = 3307;

$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    http_response_code(500);
    die('Database connection failed.');
}

$conn->set_charset('utf8mb4');

?>
