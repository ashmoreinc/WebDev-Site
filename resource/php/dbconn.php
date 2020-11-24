<?php

// TODO Purge all data from this file for security, move it to an external file

/**a
 * @throws dbConnNotCreatedException
 */
function getConn() {
    error_reporting(0);
    $conn = new mysqli("127.0.0.1", "root", "", "socialmedia");

    if ($conn->connect_error) {
        throw new dbConnNotCreatedException("Reason: " . $conn->connect_error);
    }

    return $conn;
}