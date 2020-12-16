<?php
// Check for username
if(!isset($_POST["username"])){
    echo "username not set";
    die();
}

// Get connection
require_once "../php/dbconn.php";

try {
    $conn = getConn();
} catch (dbConnNotCreatedException $e) {
    echo "connection not available";
    die();
}

// Check the username exists or not
require_once "../php/common_functions.php";

$username = steriliseInput($conn, $_POST["username"]);

$result = $conn->query("SELECT * FROM users WHERE username='" . $username . "'");

if($result->num_rows > 0) {
    echo "true";
} else {
    echo "false";
}

$conn->close();
die();