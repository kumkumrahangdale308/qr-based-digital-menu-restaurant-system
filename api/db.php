<?php

$host = 'sql210.infinityfree.com';
$user = 'if0_42711105';
$password = 'KWopsU01izaoI';
$database = 'if0_42711105_digital_menu_project_db';
$port = 3306;

$conn = new mysqli($host, $user, $password, $database, $port);

// Create a connection

if ($conn->connect_error) {
    http_response_code(500);
    die('Database connection failed.');
}

$conn->set_charset('utf8mb4');

?>
