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
                if ($row["id"] == $curUser->getId()) {
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
                        // TODO Implement other page features once the settings data has been added to the user table
                        ?>
                        <div class="profile-img col-md-4">
                            <?php
                                if(is_null($pageUser->getDisplayImage())) {
                                    ?> <img src="http://<?php echo $_SERVER["SERVER_NAME"]; ?>/resource/images/profile/default.jpg"> <?php
                                } else {
                                    echo "<img src=\"http://" . $_SERVER["SERVER_NAME"] . "/resource/images/profile/" . $pageUser->getDisplayImage() . "\">";
                                }

                            ?>
                        </div>
                        <div class="profile-info col-md-8">
                            <h1 class="display-4"><?php echo $pageUser->getName(); ?></h1>
                            <p class="lead">@<?php echo $pageUser->getUsername() ?></p>
                            <hr class="my-4">
                            <p>Here is where you will find the users bio if they have one set.</p>
                            <?php
                                // TODO Change the follow button when following has been implemented.
                                // TODO Fix the button display issue where the follow button overlaps the bio (reproducible on half display 1080p monitor)
                                if(!$pageUser->getIsCurrentUser()){ // Only show the follow button if it is not the current user page
                                    ?> <a class="profile-btn btn btn-dark btn-md" href="#" role="button">Follow</a> <?php
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