<?php
// Check for and sanitise reply field
if(isset($_POST["navToPost"])){
    $navToPost = true;
} else {
    $navToPost = false;
}

function returnSuccess($retPostID=null){
    global $navToPost;
    // Returns to the previous page but this time with an error code
    $url_parts = parse_url($_SERVER['HTTP_REFERER']);
    $return_addr = $url_parts["scheme"] . "://" . $url_parts["host"] . $url_parts["path"] . "?";
    parse_str($url_parts["query"], $query);
    unset($query["post-comp-error"]);
    unset($query["del-succ"]);
    unset($query["del-err"]);
    $query["post-comp-success"] = 1;

    $return_addr .= http_build_query($query);

    if($navToPost and !is_null($retPostID)) {
        header("Location: http://" . $_SERVER["SERVER_NAME"] . "/account/profile/post/?pid=$retPostID");
    } else {
        header("Location: " . $return_addr);
    }
    die();
}

function returnWithError($error) {
    // Returns to the previous page but this time with an error code
    $url_parts = parse_url($_SERVER['HTTP_REFERER']);
    $return_addr = $url_parts["scheme"] . "://" . $url_parts["host"] . $url_parts["path"] . "?";
    parse_str($url_parts["query"], $query);
    unset($query["post-comp-success"]);
    unset($query["del-succ"]);
    unset($query["del-err"]);
    $query["post-comp-error"] = $error;

    $return_addr .= http_build_query($query);

    header("Location: " . $return_addr);
    die();
}

// Check input is available
if(!isset($_POST["post-content"])) {
    returnWithError("No content was provided for upload.");
}

// Check for blank input/just whitespace
$whiteStripped = "";
$whiteStripped = preg_replace('/\s+/', '', $_POST["post-content"]);

if($whiteStripped == ""){
    returnWithError("No  content was provided for upload.");
}

require_once "../../../resource/php/common_functions.php";
require_once "../../../resource/php/dbconn.php";
require_once "../../../resource/php/classes/dbConnNotCreatedException.php";

// Create connection
try {
    $conn = getConn();
} catch (dbConnNotCreatedException $e) {
    returnWithError("Could not connect to database.");
    die();
}

// Sanitise input
$content = steriliseInput($conn, $_POST["post-content"]);

// Check for and sanitise reply field
if(isset($_POST["repliesTo"])){
    $replyTo = steriliseInput($conn, $_POST["repliesTo"]);
} else {
    $replyTo = -1;
}

// Check for a valid logon session
require_once "../../../resource/php/session_management.php";

$curUser = getLoggedInUser($conn);

if(is_null($curUser)) {
    returnWithError("No logon session found.");
}

$curDatetime = date('Y-m-d H:i:s');

// Upload post data
if($replyTo!= -1) {
    $sql = "INSERT INTO posts (userID, replyToID, content, datetime) VALUES (" . $curUser->getId() . ", $replyTo, '$content', '$curDatetime')";
} else {
    $sql = "INSERT INTO posts (userID, content, datetime) VALUES (" . $curUser->getId() . ", '$content', '$curDatetime')";
}

// Get the inputted result
$sql .= "; SELECT LAST_INSERT_ID() as postID";

$conn->multi_query($sql);

// Get the postID
$retPostID = null;
do {
    if ($result = $conn->store_result()) {
        while ($row = $result->fetch_row()) {
            $retPostID = $row[0];
        }
        $result->free();
    }
} while ($conn->next_result());


if($result){
    returnSuccess($retPostID);
} else {
    returnWithError($conn->err);
}

$conn->close();

