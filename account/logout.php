<?php
require_once "../resource/php/session_management.php";
require_once "../resource/php/dbconn.php";

try {
    $conn = getConn();
} catch (dbConnNotCreatedException $e) {
    $conn = null;
}

// It doesnt matter too much if there is no connection her as the function accounts for a null value
endSession($conn);

if(!is_null($conn)){
    $conn->close();
};

header("Location: http://" . $_SERVER['SERVER_NAME'] . "/account/index.php?success=loggedout");
die();
