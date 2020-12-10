<?php
function returnWithError($errorName){
    // Returns the the Sign up page with the given error code.
    global $conn;
    // Try and close the connection
    try {
        $conn->close();
    } catch (Exception $ignored){}

    // Return to the sign-up page with the error given
    header("Location: http://" . $_SERVER['SERVER_NAME'] . "/account/index.php?error=" . $errorName);
    die();
}

// Get the connection
require_once $_SERVER["DOCUMENT_ROOT"] . '/resource/php/dbconn.php';


try {
    $conn = getConn();
} catch (dbConnNotCreatedException $e) {
    returnWithError("connection");
}

// Make sure all data is available and if it is, check that it isn't blank, return to the page with an error if not.
if(isset($_POST["username"])){
    if($_POST["username"] == "") {
        returnWithError("login-username");
    }
} else {
    returnWithError("login-username");
}
if(isset($_POST["password"])){
    if($_POST["password"] == "") {
        returnWithError("login-password");
    }
} else {
    returnWithError("login-password");
}

// We do not need to clean the passwords for xss/sql injection. They are hashed which mitigates this
$user = $_POST["username"];
$pass = $_POST["password"];

// Try get the hashed password from the server.
$results = $conn->query("SELECT id, password FROM users WHERE username='$user'");

if(!$results) {
    returnWithError("login-queryerr");
}

// Get the hash
$row = $results->fetch_assoc();
$hash = $row["password"];
$userid = $row["id"];

if(!password_verify($pass, $hash)){
    returnWithError("login-incorrect");
}

require_once "../resource/php/session_management.php";
// Create a session and redirect to the check page
if(createSession($conn, $userid)){
    $conn->close();
    try{
        session_start();
    } catch (Exception $e){}
    header("Location: http://" . $_SERVER['SERVER_NAME']);
    die();
} else {
    $conn->close();
    returnWithError("sesserr");
    die();
}
