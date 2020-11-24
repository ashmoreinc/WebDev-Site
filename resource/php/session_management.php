<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/classes/User.php";

function createSession($conn, $userID){
    endSession($conn);
    if(!isset($_SESSION)){
        session_start();
    }

    // Generate new id
    session_regenerate_id();
    $sessID = session_id();

    // For some basic verification take the user id and store the hash, just so a session cant be brute force faked.
    // This is still vulnerable to session jacking, however there isn't much we can do about this, especially without https
    try {
        $secretKey = random_int(0, 2147483647);
    } catch (Exception $e) {
        return False;
    }
    // Generate hash from UserID
    $_SESSION["uid"] = hash_hmac("md5", $userID, $secretKey);

    // Store the session data in active_sessions with a 1 day life time
    $result = $conn->query("INSERT INTO active_sessions (sessionid, userid, ends, secret) 
                    VALUES ('$sessID', '$userID', NOW() + interval 1 day, $secretKey)");

    // Return false if the data could not be inserted
    if(!$result){
        return false;
    }

    return True;
}

function endSession($conn){
    if(!isset($_SESSION)){
        session_start();
    }

    $sessID = session_id();

    // Remove from active sessions table
    if(!is_null($conn)) {
        $conn->query("DELETE FROM active_sessions WHERE sessionID=$sessID");
    }

    // unset session data
    session_unset();
    unset($_SESSION);
    session_destroy();
    session_abort();
}

function isLoggedIn($conn){
    if(!isset($_SESSION)){
        session_start();
    }

    $sessID = session_id();
    // Get sessions with matching ID from the server
    $results = $conn->query("SELECT userid, secret FROM active_sessions WHERE sessionID='$sessID'");


    if(!isset($_SESSION["uid"])){
        return false;
    }
    if($results->num_rows <= 0){
        // If the session ID cant be found on the server theres no log on session
        return false;
    } else {
        // Verify session
        $row = $results->fetch_assoc();
        $userid = $row["userid"];
        $secret = $row["secret"];

        // Compare the users hashed uid with the one stored on the server
        if(hash_hmac('md5', $userid, $secret) == $_SESSION["uid"]){
            return true;
        } else{
            echo "Its i";
            return false;
        }
    }
}

function getLoggedInUser($conn){
    if(isLoggedIn($conn)){
        $sessID = session_id();
        $userID = -1;

        $result = $conn->query("SELECT userid FROM active_sessions WHERE sessionID='$sessID'");

        if($result){// Get the user id
            $row = $result->fetch_assoc();
            $userID = $row["userid"];
        } else {
            return null;
        }

        // Get the user profile.
        $result = $conn->query("SELECT name, username, isAdmin FROM users WHERE id=$userID");

        if($result){// Get the user id
            $row = $result->fetch_assoc();
            if ($row["isAdmin"]) {
                $isAdmin = true;
            } else {
                $isAdmin = false;
            }

            return new User($userID);
        } else {
            return null;
        }
    } else {
        return null;
    }
}