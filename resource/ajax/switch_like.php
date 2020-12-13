<?php
if(!isset($_POST["postID"])) {
    echo "Post id not set.";
    die();
}

// Create a db connection
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/dbconn.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/classes/dbConnNotCreatedException.php";

try {
    $conn = getConn();
} catch (dbConnNotCreatedException $e) {
    echo "Could not connect to database.";
    die();
}

if(is_null($conn)) {
    echo "Could not connect to database.";
    die();
}

// Sanitise the user input
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/common_functions.php";
$postID = steriliseInput($conn, $_POST["postID"]);

// Check for a login session
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/session_management.php";

$curUser = getLoggedInUser($conn);

if(is_null($curUser)) {
    echo "No log in session found.";
    die();
}

// Try switch the like.
try{
    switchLike((int)$curUser->getId(), (int)$postID);
    echo "success";
} catch (dbConnNotCreatedException $e) {
    echo "Could not connect to database.";
    die();
} catch (InvalidArgumentException $e) {
    echo $e->getMessage();
}

die();
