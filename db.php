
<?php

// Database connection settings for MAMP.
$host = "localhost";
$dbname = "Flick_Pick";
$username = "root";
$password = "root";
$port = 8889;

// Connect to MySQL.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(
        $host,
        $username,
        $password,
        $dbname,
        $port
    );

    // Use UTF-8 for database communication.
    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    // Log the technical error without exposing credentials.
    error_log($e->getMessage());
    http_response_code(500);
    exit("Database connection failed. Check your MAMP settings.");
}



