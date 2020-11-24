<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/dbconn.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/session_management.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/classes/dbConnNotCreatedException.php";
$curUser = null;
$logged_on = false;


try {
    $conn = getConn();
    $logged_on = isLoggedIn($conn);
} catch (dbConnNotCreatedException $e) {
    $conn = null;
    header($_SERVER["SERVER_PROTOCOL"] . ' 500 Internal Server Error', true, 500);
    require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/site-elements/no_dbconn.php";
    die();
}

if ($logged_on){
    $curUser = getLoggedInUser($conn);
}
?>
<html lang="en">
    <head>
        <title>Exclusive</title>

        <?php // Import the header from a central location
            require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/site-elements/standardhead.php";
        ?>
    </head>
    <body>
    <?php

    require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/site-elements/navbar.php";

    ?>
    </body>
</html>

<?php
$conn->close();
?>