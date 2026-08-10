<?php
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli('127.0.0.1', 'root', '', 'test'); // Dummy
} catch (Exception $e) {
    echo "Caught: " . $e->getMessage();
}
