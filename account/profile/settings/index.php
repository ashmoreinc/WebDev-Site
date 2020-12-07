<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/session_management.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/dbconn.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/common_functions.php";

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

$updateError = null;

// Check the form data
if($logged_on){
    // Profile
    if(isset($_POST["profile-settings"])) {
        $name = steriliseInput($conn, $_POST["name"]);
        $username = steriliseInput($conn, $_POST["username"]);
        $bio = steriliseInput($conn, $_POST["bio"]);

        // Check the current data and new data against each other
        $sql = "SELECT name, username, bio FROM users WHERE id=" . $curUser->getId();

        $results = $conn->query($sql);

        $updateName = false;
        $updateUser = false;
        $updateBio = false;

        if($results->num_rows > 0) {
            $row = $results->fetch_assoc();

            if($row["name"] != $name) $updateName = true;
            if($row["username"] != $username) $updateUser = true;
            if($row["bio"] != $bio) $updateBio = true;
        } else {
            $updateError = "Could not find user.";
        }

        if(!is_null($updateError)) {
            if (!$updateName && !$updateUser && $updateBio) {
                $updateError = "No changes found.";
            }
        }


        if(is_null($updateError)) {
            $sql = "UPDATE users SET ";

            $toAdd = [];

            if($updateName){
                array_push($toAdd, "name='$name'");
            }

            if($updateUser){
                array_push($toAdd, "username='$username'");
            }

            if($updateBio){
                array_push($toAdd, "bio='$bio'");
            }

            $sql .= implode(", ", $toAdd);

            $sql .= " WHERE id=" . $curUser->getId();

            $conn->query($sql);

        }
    }
}

?>
<html>
<head>
    <title>Settings</title>

    <?php
    require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/site-elements/standardhead.php";
    ?>
</head>

<body>
    <?php
    // Import the navbar
    require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/site-elements/navbar.php";
    ?>

    <div class="jumbotron">
        <div class="container">
            <h1>Settings</h1>
            <?php
            // Check login status
            if(!$logged_on) {
                ?> <p>To change your settings you must first <a href="http://<?php echo $_SERVER["SERVER_NAME"]; ?>/account/">Log in</a></p> <?php
            } else {
                ?> <p>Here you can change all settings associated with your account.</p> <?php
            }
            ?>
        </div>
    </div>

    <?php
    if($logged_on){ ?>
    <div class="container settings">
        <div class="settings-section">
            <h1>Profile settings.</h1>
            <hr>
            <form method="post">
                <input type="hidden" name="profile-settings" value="1">

                <div class="form-group">
                    <label for="nameInput">Name</label>
                    <input type="text" class="form-control" id="nameInput" aria-describedby="nameHelp" name="name" value="<?php echo $curUser->getName(); ?>" required>
                </div>

                <div class="form-group">
                    <label for="usernameInput">Username</label>
                    <input type="text" class="form-control" id="usernameInput" aria-describedby="usernameHelp"  name="username" value="<?php echo $curUser->getUsername(); ?>" required>
                    <?php // TODO: Implement username exists check client side alert :: Same as sign-up page ?>
                </div>

                <div class="form-group">
                    <label for="bioInput">Bio</label>
                    <textarea class="form-control" id="bioInput" placeholder="Tell us about yourself..." name="bio"><?php echo $curUser->getBio(); ?></textarea>
                </div>

                <button class="btn btn-primary" type="submit">Update</button>
            </form>
        </div>
        <div class="settings-section">
            <h1>Account settings.</h1>
            <hr>
            <form method="post">
                <input type="hidden" name="account-settings" value="1">
            </form>
        </div>
        <div class="settings-section">
            <h1>Interaction settings.</h1>
            <hr>
            <form method="post">
                <input type="hidden" name="interaction-settings" value="1">
            </form>
        </div>
    </div>
    <?php } ?>
</body>
</html>
