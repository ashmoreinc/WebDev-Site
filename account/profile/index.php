<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/session_management.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/dbconn.php";

// Get the currently logged on user if any
$curUser = null;
$logged_on = false;

try {
    $conn = getConn();
    $logged_on = isLoggedIn($conn);
} catch (dbConnNotCreatedException $error) {
    die($error->getMessage());
}

if ($logged_on){
    $curUser = getLoggedInUser($conn);
}
// Get the data for the users page
$pageUser = null;
// If there is a user id given, check the database for it
if(isset($_GET["user"])){
    $uname = $_GET["user"];
    $result = $conn->query("SELECT id FROM users WHERE username='$uname'");

    if($result){
        if($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            try {
                if(is_null($curUser)){
                    $pageUser = new User($row["id"]);
                } else if ($row["id"] == $curUser->getId()) {
                    $pageUser = new User($row["id"], true);
                } else {
                    $pageUser = new User($row["id"]);
                }
            } catch (userNotFoundException $ignored) {}
        }
    }

}

// TODO: Check the current page is not a private account
// TODO: Check the current page has not blocked the logged in user
?>
<html lang="en">
    <head>
        <?php
            // Create the title as the user name if there is a user
            if(!is_null($pageUser)){
                echo "<title>" . $pageUser->getName() . "</title>";
            } else {
                echo "<title>User not found.</title>";
            }

            // Import the header from a central location
            require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/site-elements/standardhead.php";
        ?>
    </head>
    <body>
    <?php
        // Import the navbar
        require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/site-elements/navbar.php";
    ?>

    <div class="profile-banner jumbotron">
        <div class="container-md profile-container">
            <div class="row">
                <?php
                    if(!is_null($pageUser)){ //  If a user for this page has been set, create the header features for a full profile
                        ?>
                        <div class="profile-img col-md-4">
                            <?php
                                if(is_null($pageUser->getDisplayImage()) or $pageUser->getDisplayImage() == "") {
                                    ?> <img src="http://<?php echo $_SERVER["SERVER_NAME"]; ?>/resource/images/profile/default.jpg"> <?php
                                } else {
                                    echo "<img src=\"http://" . $_SERVER["SERVER_NAME"] . "/resource/images/profile/" . $pageUser->getDisplayImage() . "\">";
                                }

                            ?>
                        </div>
                        <div class="profile-info col-md-8">
                            <div class="row">
                                <div class="col">
                                    <h1 class="display-4"><?php echo $pageUser->getName(); ?></h1>
                                    <p class="lead">@<?php echo $pageUser->getUsername() ?></p>
                                </div>
                                <div class="col">
                                    <?php
                                        if(!$pageUser->getIsCurrentUser()){
                                    ?>
                                        <div class="dropdown float-right user-options">
                                            <button class="btn btn-outline-dark" type="button" id="userOptions" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-three-dots" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                                </svg>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userOptions">
                                                <?php
                                                    // Check for block
                                                    $sql = "SELECT isBlocked FROM user_connections WHERE firstUserID=" . $curUser->getId() . " AND secondUserID=" . $pageUser->getId();

                                                    $result = $conn->query($sql);

                                                    if($result->num_rows <= 0) {
                                                        ?> <a class="dropdown-item" onmouseup="block('<?php echo $pageUser->getUsername(); ?>', this);">Block</a> <?php
                                                    } else {
                                                        $isBlocked = $result->fetch_assoc()["isBlocked"];

                                                        if($isBlocked == 0) {
                                                            ?> <a class="dropdown-item" onmouseup="block('<?php echo $pageUser->getUsername(); ?>', this);">Block</a> <?php
                                                        } else {
                                                            ?> <a class="dropdown-item" onmouseup="unblock('<?php echo $pageUser->getUsername(); ?>', this);">Unblock</a> <?php
                                                        }
                                                    }
                                                ?>
                                            </div>
                                        </div>
                                        <?php
                                        }
                                        ?>

                                </div>
                            </div>
                            <hr class="my-4">
                            <p><?php

                                $bio = $pageUser->getBio();

                                if(is_null($bio)){
                                    echo "This user does not have a bio.";
                                } else if($bio == "") {
                                    echo "This user does not have a bio.";
                                } else {
                                    echo $bio;
                                }

                                ?></p>
                            <?php
                                if(!$pageUser->getIsCurrentUser()){ // Only show the follow button if it is not the current user page
                                    // Check follow status

                                    $isFollowing = false;

                                    $sql = "SELECT isFollowing FROM user_connections WHERE firstUserID=" . $curUser->getId() . " AND secondUserID=" . $pageUser->getId();

                                    $result = $conn->query($sql);

                                    if($result->num_rows > 0) {
                                        $status = $result->fetch_assoc()["isFollowing"];

                                        $isFollowing = $status == 1;
                                    }

                                    if($isFollowing) {
                                        ?>
                                            <button class="profile-btn btn btn-dark btn-md" role="button" onmouseup="unfollow('<?php echo $pageUser->getUsername(); ?>', this)">Unfollow</button>
                                        <?php

                                    } else {
                                        ?>
                                            <button class="profile-btn btn btn-dark btn-md" role="button" onmouseup="follow('<?php echo $pageUser->getUsername(); ?>', this);">Follow</button>
                                        <?php
                                    }
                                }
                            ?>
                        </div>
                        <?php
                    } else { // Identify that no user has been selected.
                        ?>
                        <div class="profile-img col-md-4">
                            <img src="http://<?php echo $_SERVER["SERVER_NAME"]; ?>/resource/images/profile/default.jpg">
                        </div>
                        <div class="profile-info col-md-8">
                            <h1 class="display-4">User not found</h1>
                            <p class="lead"></p>
                            <hr class="my-4">
                            <p>There is no user selected. Try search again.</p>
                        </div>
                        <?php
                    }
                ?>
            </div>
        </div>
    </div>

    </body>
</html>

<?php
$conn->close();
?>