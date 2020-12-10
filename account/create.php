<?php
function returnWithError($errorName){
    // Returns the the Sign up page with the given error code.
    global $conn;
    // Try and close the connection
    try {
        $conn->close();
    } catch (Exception $ignored){}

    // Return to the sign-up page with the error given
    header("Location: http://" . $_SERVER['SERVER_NAME'] . "/account/index.php?mode=sign-up&error=" . $errorName);
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
if(isset($_POST["name"])){
    if($_POST["name"] == "") {
        returnWithError("name");
    }
} else {
    returnWithError("name");
}
if(isset($_POST["username"])){
    if($_POST["username"] == "") {
        returnWithError("username");
    }
} else {
    returnWithError("username");
}
if(isset($_POST["password"])){
    if($_POST["password"] == "") {
        returnWithError("password");
    }
} else {
    returnWithError("password");
}
if(isset($_POST["passwordconf"])){
    if($_POST["passwordconf"] == "") {
        returnWithError("passconf");
    }
} else {
    returnWithError("passconf");
}

// Clean up the user input to prevent any SQL injection attempts or XSS
require_once $_SERVER["DOCUMENT_ROOT"] . '/resource/php/common_functions.php';

$name = steriliseInput($conn, $_POST["name"]);
$user = steriliseInput($conn, $_POST["username"]);

// Passwords do not pose a threat, therefore do not need to be cleaned
$pass = $_POST["password"];
$passconf = $_POST["passwordconf"];

// Check the passwords match
if($pass != $passconf){
    $conn->close();
    returnWithError("passmatch");
}

// Check if the username is already taken
$results = $conn->query("SELECT username FROM users WHERE username='$user'");

// Return with an error if any rows are found
if(!$results){
    returnWithError("queryerr"); // Re use this as it shows a
} else if($results->num_rows > 0){
    returnWithError("usertaken");
}
// Clear the results
$results->free_result();

// Now that everything is sort we can go ahead and insert the data into the database.

// First we hash and salt the password
$passhash = password_hash($pass, PASSWORD_BCRYPT);

if(!$passhash){
    returnWithError("passerr");
}

// Now send the
$results = $conn->query("INSERT INTO users (name, username, password, isAdmin)
                         VALUES ('$name', '$user', '$passhash', 0)");

if(!$results){
    returnWithError("query");
}

$conn->close();
header("Location: http://127.0.0.1/account/index.php?success=created");
die();