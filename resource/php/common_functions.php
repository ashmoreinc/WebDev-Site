<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/classes/dbConnNotCreatedException.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/classes/couldNotFollowException.php";
/**
 * @param $input
 * @return string
 */
function xssSterilise($input){
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * @param $conn
 * @param $input
 * @return string
 */
function sqliSterilise($conn, $input){
    return mysqli_real_escape_string($conn, $input);
}

/**
 * @param $conn
 * @param $input
 * @return string
 */
function steriliseInput($conn, $input){
    $input = xssSterilise($input);
    $input = sqliSterilise($conn, $input);

    return $input;
}

/**
 * @param $user1ID
 * @param $user2ID
 * @param $state
 * @param null $conn
 * @throws couldNotFollowException
 * @throws dbConnNotCreatedException
 * @throws InvalidArgumentException
 */
function updateFollow($user1ID, $user2ID, $state, $conn=null){
    if(gettype($user1ID) != "integer" || gettype($user2ID) != "integer"){
        throw new InvalidArgumentException("userID1 and userID2 arguments must be integers.");
    }

    if(gettype($state) != "boolean" && gettype($state) != "integer"){
        throw new InvalidArgumentException("state argument must be of type boolean or an integer.");
    }

    // Make the $state argument usable
    if(gettype($state) == "integer"){
        if($state > 1) $state = 1;
        else if($state < 0) $state = 0;
    } else { // We can assume $state is a boolean as we have performed a check for it earlier
        $state = $state ? 1 : 0; // Set state to 1 if true and 0 if false
    }

    if(is_null($conn)) {
        require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/dbconn.php";
        $conn = getConn();
    }

    if(is_null($conn)){
        throw new dbConnNotCreatedException("Could not connect to database");
    }


    // Check the inputted users exists
    $sql1 = "SELECT username FROM users WHERE id=$user1ID";
    $sql2 = "SELECT username FROM users WHERE id=$user2ID";
    $result1 = $conn->query($sql1);
    $result2 = $conn->query($sql2);

    if($result1->num_rows == 0) {
        throw new InvalidArgumentException("user(1) with given ID does not exist.");
    }

    if($result2->num_rows == 0) {
        throw new InvalidArgumentException("user(2) with given ID does not exist.");
    }

    // Check for connections
    $sql = "SELECT connectionID 
            FROM user_connections
            WHERE firstUserID=$user1ID AND secondUserID=$user2ID";

    $result = $conn->query($sql);

    if($result->num_rows > 0){
        // Update current entry
        $row = $result->fetch_assoc();

        $connID = $row["connectionID"];

        // Check if user1 is blocked by user 2 first
        $sql = "SELECT isBlocked
                FROM user_connections
                WHERE firstUserID=$user2ID AND secondUserID=$user1ID";

        $result = $conn->query($sql);

        if($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if($row["isBlocked"] == 1){
                throw new couldNotFollowException("User is blocked.");
            }
        }

        // If we get here, user1 is not blocked, now update the friend state

        $sql = "UPDATE user_connections
                SET isFollowing=$state
                WHERE connectionID=$connID";
    } else {
        // Insert an entry
        $sql = "INSERT INTO user_connections (firstUserID, secondUserID, isFollowing, isBlocked)
                VALUES ($user1ID, $user2ID, $state, 0)";
    }

    // Execute the derived query
    $conn->query($sql);
    $conn->close();
}

/**
 * @param $user1ID
 * @param $user2ID
 * @param null $conn
 * @throws dbConnNotCreatedException
 * @throws couldNotFollowException
 */
function switchFollow($user1ID, $user2ID, $conn=null){
    // Check arguments
    if(gettype($user1ID) != "integer" || gettype($user2ID) != "integer"){
        throw new InvalidArgumentException("userID1 and userID2 arguments must be integers.");
    }

    if(is_null($conn)) {
        require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/dbconn.php";
        $conn = getConn();
    }

    if(is_null($conn)){
        throw new dbConnNotCreatedException("Could not connect to database");
    }

    // Get current state
    $sql = "SELECT isFollowing FROM user_connections WHERE firstUserID=$user1ID AND secondUserID=$user2ID";

    $result = $conn->query($sql);

    if($result->num_rows <= 0) { // If there is no entry, that means there is no follow, therefore switching the state creates a follow
        $state = 1;
    } else {
        $state = $result->fetch_assoc()["isFollowing"];

        $state = $state == 1 ? 0 : 1;
    }

    updateFollow($user1ID, $user2ID, $state, $conn);
}