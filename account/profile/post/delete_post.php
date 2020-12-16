<?php
$curUser = null;
function returnWithError($error) {
    global $curUser;
    // Returns to the home page but this time with an error code
    if(is_null($curUser)){
        $return_addr = "http://" . $_SERVER["SERVER_NAME"] . "/account/profile/?del-err=$error";
    } else {
        $return_addr = "http://" . $_SERVER["SERVER_NAME"] . "/account/profile/?user=" . $curUser->getUsername() . "&del-err=$error";
    }

    header("Location: " . $return_addr);
    die();
}

function returnSuccess(){
    global $curUser;
    // Returns to the profile page but this time with an error code
    if(is_null($curUser)){
        $return_addr = "http://" . $_SERVER["SERVER_NAME"] . "/account/profile/?del-succ=1";
    } else {
        $return_addr = "http://" . $_SERVER["SERVER_NAME"] . "/account/profile/?user=" . $curUser->getUsername() . "&del-succ=1";
    }

    header("Location: " . $return_addr);
    die();
}

// Check input is available
if(!isset($_POST["post-id"])) {
    returnWithError("No id was provided.");
}


require_once "../../..//resource/php/dbconn.php";
require_once "../../..//resource/php/common_functions.php";


// Create connection
try {
    $conn = getConn();
} catch (dbConnNotCreatedException $e) {
    returnWithError("Could not connect to database.");
    die();
}


// Sanitise input
$id = steriliseInput($conn, $_POST["post-id"]);

// Check for a valid logon session
require_once "../../..//resource/php/session_management.php";

$curUser = getLoggedInUser($conn);

if(is_null($curUser)) {
    returnWithError("No logon session found.");
}

// By providing the current user ID we assure that the user cannot delete other peoples posts
$sql = "DELETE FROM posts WHERE postID=$id AND userID=" . $curUser->getId();

if($conn->query($sql)){
    $conn->close();
    returnSuccess();
} else {
    $conn->close();
    returnWithError("Could not delete your post at this time.");
}
