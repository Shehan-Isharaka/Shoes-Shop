<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $connection = new mysqli("localhost", "root", "", "shoe_shop");
    $connection->set_charset("utf8mb4");
} catch (Exception $e) {
    exit("Database connection failed");
}
