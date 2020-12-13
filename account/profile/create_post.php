<?php
function returnSuccess(){
    // Returns to the previous page but this time with an error code
    $url_parts = parse_url($_SERVER['HTTP_REFERER']);
    $return_addr = $url_parts["scheme"] . "://" . $url_parts["host"] . $url_parts["path"] . "?";
    parse_str($url_parts["query"], $query);
    unset($query["post-comp-error"]);
    $query["post-comp-success"] = 1;

    $return_addr .= http_build_query($query);

    header("Location: " . $return_addr);
    die();
}

function returnWithError($error) {
    // Returns to the previous page but this time with an error code
    $url_parts = parse_url($_SERVER['HTTP_REFERER']);
    $return_addr = $url_parts["scheme"] . "://" . $url_parts["host"] . $url_parts["path"] . "?";
    parse_str($url_parts["query"], $query);
    unset($query["post-comp-success"]);
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

require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/common_functions.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/dbconn.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/classes/dbConnNotCreatedException.php";

// Create connection
try {
    $conn = getConn();
} catch (dbConnNotCreatedException $e) {
    returnWithError("Could not connect to database.");
    die();
}

// Sanitise input
$content = steriliseInput($conn, $_POST["post-content"]);

// Check for a valid logon session
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/session_management.php";

$curUser = getLoggedInUser($conn);

if(is_null($curUser)) {
    returnWithError("No logon session found.");
}

$curDatetime = date('Y-m-d H:i:s');

// Upload post data
$sql = "INSERT INTO posts (userID, content, datetime) VALUES (" . $curUser->getId() . ", '$content', '$curDatetime')";

if($conn->query($sql)){
    $conn->close();
    returnSuccess();
} else {
    $conn->close();
    returnWithError($conn->err);
}


