<?php
// Check for POST data
if(!isset($_POST["username"])){
    echo "username not provided";
    die();
}

require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/session_management.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/dbconn.php";



// Create a database connection
try {
    $conn = getConn();
} catch (dbConnNotCreatedException $e) {
    echo "connection error";
}

if(is_null($conn)) {
    echo "connection error";
    die();
}

// Start session
if(!isLoggedIn($conn)){
    echo "no log on session";
    die();
}

$curUser = getLoggedInUser($conn);
// Sanitise the input
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/common_functions.php";
$username = steriliseInput($conn, $_POST["username"]);

// Get the id of the other user
$sql = "SELECT id FROM users WHERE username='$username'";

$result = $conn->query($sql);

if($result->num_rows <= 0) {
    echo "no user found";
    die();
}

$otherUserID = $result->fetch_assoc()["id"];

// Run the follow update
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/classes/dbConnNotCreatedException.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/classes/couldNotFollowException.php";


if(isset($_POST["action"])){
    $action = steriliseInput($conn, $_POST["action"]);

    if($action == "unfollow"){
        $state = false;
    } else if($action == "follow") {
        $state = true;
    } else {
        echo "invalid action";
        die();
    }

    try {
        updateFollow((int)$curUser->getId(), (int)$otherUserID, $state);
        echo "success";
    } catch (couldNotFollowException $e) {
        echo "could not follow: " . $e->getMessage();
    } catch (dbConnNotCreatedException $e) {
        echo "connection error";
    } catch (Throwable $e) {
        echo $e->getMessage();
    }
    die();
}


try {
    switchFollow((int)$curUser->getId(), (int)$otherUserID);
    echo "success";
} catch (couldNotFollowException $e) {
    echo "could not follow: " . $e->getMessage();
} catch (dbConnNotCreatedException $e) {
    echo "connection error";
}

die();
