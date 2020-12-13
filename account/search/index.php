<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/dbconn.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/session_management.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/common_functions.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/classes/dbConnNotCreatedException.php";

$logged_on = false;
$curUser = null;
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


if(isset($_GET["search-query"])) {
    $query = steriliseInput($conn, $_GET["search-query"]);
} else {
    $query = null;
}


?>
<html>
<head>
    <title>Search<?php if(!is_null($query)) echo ": " .  $query; ?></title>

    <?php // Import the header from a central location
    require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/site-elements/standardhead.php";
    ?>

    <style>
        /* Hide the search bar in the nav on this page only */
        #nav-searchbar {
            display: none;
        }
    </style>
</head>
<body>
<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/site-elements/navbar.php";

?>

<div class="jumbotron search-head">
    <div class="container">
        <h1>Search</h1>
        <form action="" method="get">
            <div class="form-group">
                <input type="search" name="search-query" class="form-control" id="searchInput" aria-describedby="search field">
            </div>
            <button type="submit" class="btn btn-dark">Search</button>
        </form>
    </div>
</div>

<div class="container search-results">
        <?php
            if(!is_null($query)){
                ?>
                    <div class="alert alert-light" role="alert">
                        Results for: <?php echo $query; ?>
                    </div>
                <?php

                // Users Search
                ?> <div class="users-results"> <?php


                if($logged_on && !is_null($curUser)) {
                    $curUserID = $curUser->getId();
                    $sql = "SELECT users.name, users.username, users.bio, users.displayImageFilename, users.id, user_connections.firstUserID, user_connections.secondUserID, user_connections.isFollowing, user_connections.isBlocked
                            FROM users
                            LEFT JOIN user_connections
                            ON ((users.id = user_connections.firstUserID AND user_connections.secondUserID=$curUserID) OR (users.id=$curUserID AND users.id=user_connections.secondUserID)) XOR
                                    (users.id=user_connections.firstUserID XOR users.id=user_connections.secondUserID)
                            WHERE (user_connections.isBlocked=0 OR user_connections.isBlocked IS NULL) 
                                    AND users.id != $curUserID
                                    AND (username LIKE '%$query%' 
                                        OR name LIKE '%$query%' 
                                        OR bio LIKE '%$query%')";
                } else {
                    $sql = "SELECT users.name, users.username, users.bio, users.displayImageFilename, users.id
                            FROM users
                            WHERE username LIKE '%$query%'
                                OR name LIKE '$query'
                                OR bio LIKE '%$query%'";
                }

                $results = $conn->query($sql);

                $output = 0;

                if($results->num_rows > 0) { // TODO Load limit, load more button and users tab
                    while($row = $results->fetch_assoc()){
;
                        $output += 1;
                        ?>

                        <div class="result">
                            <div class="row">
                                <div class="profile-image col-md-2 d-flex justify-content-center">
                                    <a href="<?php echo "http://" . $_SERVER["SERVER_NAME"] . "/account/profile/?user=" . $row["username"] ?>">
                                        <img class="mr-auto ml-auto" src="<?php echo "http://" . $_SERVER["SERVER_NAME"] . "/resource/images/profile/" . ((is_null($row["displayImageFilename"]) || $row["displayImageFilename"] == "") ? "default.jpg" : $row["displayImageFilename"]); ?>">
                                    </a>
                                </div>
                                <div class="content col-md-8">
                                    <div class="user-info row">
                                        <h1><?php echo $row["name"] ?></h1>
                                        <h5><a href="<?php echo "http://" . $_SERVER["SERVER_NAME"] . "/account/profile/?user=" . $row["username"] ?>">@<?php echo $row["username"] ?></a></h5>
                                    </div>
                                    <hr>
                                    <div class="user-bio row">
                                        <p><?php

                                            if(is_null($row["bio"])){
                                                echo "This user does not have a bio.";
                                            } else if($row["bio"] == "") {
                                                echo "This user does not have a bio.";
                                            } else {
                                                echo $row["bio"];
                                            }

                                            ?></p>
                                    </div>
                                </div>
                                <div class="interact col-md-2">
                                    <?php
                                    $createFollowButton = true;

                                    if($logged_on){
                                        if(isset($row["firstUserID"])) {
                                            if ($row["firstUserID"] == $curUser->getId()) {
                                                if($row["isFollowing"] == 1) {
                                                    $createFollowButton = false;
                                                }
                                            }
                                        }
                                    }

                                    if($createFollowButton) {
                                        ?> <button type="button" class="btn btn-block btn-dark" onmouseup="follow('<?php echo  $row["username"]; ?>', this)">Follow</button> <?php
                                    } else {
                                        ?> <button type="button" class="btn btn-block btn-dark" onmouseup="unfollow('<?php echo $row["username"]; ?>', this)">Unfollow</button> <?php
                                    }


                                    ?>

                                </div>
                            </div>
                        </div>
                        <?php
                    }
                }
                if($output == 0){
                    ?>
                    <div class="alert alert-light" role="alert">
                        No users found.
                    </div>
                    <?php
                }

                ?> </div> <?php
            }
        ?>

</div>

</body>
</html>
