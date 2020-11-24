<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/session_management.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/dbconn.php";

// TODO Unalive this file. Its a test file. It aint needed in prod.

try {
    $conn = getConn();
} catch (dbConnNotCreatedException $e) {
    echo "Connection error.";
}
    if(isLoggedIn($conn)){
    echo "Logged in!";
} else {
    echo "Not logged in.";
}

$conn->close();