<?php
function xssSterilise($input){
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

function sqliSterilise($conn, $input){
    return mysqli_real_escape_string($conn, $input);
}

function steriliseInput($conn, $input){
    $input = xssSterilise($input);
    $input = sqliSterilise($conn, $input);

    return $input;
}

function updateFollow($user1ID, $user2ID, $state, $conn=null){
    if(is_null($conn)){
        require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/dbconn.php";
        // $conn = getConn();
    }

}