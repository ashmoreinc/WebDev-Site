<?php
require_once "../../../resource/php/session_management.php";
require_once "../../../resource/php/dbconn.php";
require_once "../../../resource/php/common_functions.php";

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

// Check the form data
$updateError = null;
$updateMessage = null;

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

        if(!is_null($updateError)) {
            // Check if the username is taken
            $sql = "SELECT * FROM user WHERE username='$username'";

            $result = $conn->query($sql);

            if($result->num_rows > 0){
                $updateError = "Username already taken.";
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

            if($conn->query($sql)) {
                $updateMessage = "Updated profile settings!";
            } else {
                $updateError = "Failed to update profile settings.";
            }

        }
    }
    if(isset($_POST["profile-image-settings"])) { // Use is trying to upload a picture
        if($_POST["submit"] == "delete") {
            // Remove the image name in the users table
            $sql = "UPDATE users SET displayImageFilename='' WHERE id=" . $curUser->getId();

            if ($conn->query($sql)) {
                $updateMessage = "You have successfully updated your profile image.";
            } else {
                $updateError = "We could not update your profile image at this time.";
            }

            // Delete the old image
            $imageName = $curUser->getDisplayImage();

            unlink("../../../resource/images/profile/" . $imageName);

        } else {
            // Check there is a file for upload
            if (!isset($_FILES["profile-image"])) {
                $updateError = "No image has been uploaded.";
            }

            // Check if the image is one of the following types: png, jpg, gif
            if (is_null($updateError)) {
                if (!($_FILES["profile-image"]["type"] == "image/png" || $_FILES["profile-image"]["type"] == "image/jpg" ||
                    $_FILES["profile-image"]["type"] == "image/jpeg" || $_FILES["profile-image"]["type"] == "image/gif")) {

                    $updateError = "Profile image upload has an invalid file type. PNG, JPG or GIF only.";
                }
            }

            // Validate the file contents to be of an image type
            if (is_null($updateError)) {
                $verifyImg = getimagesize($_FILES['profile-image']['tmp_name']);
                if (!($verifyImg["mime"] == "image/png" || $verifyImg["mime"] == "image/jpg" || $verifyImg["mime"] == "image/jpeg" || $verifyImg["mime"] == "image/gif")) {
                    $updateError = "Could not validate image data.";
                }
            }


            if(is_null($updateError)) {
                // Generate a random filename and add the extension
                $filenameLen = 32;
                $allowedChars = "abcdefghijklmnopqrstuvqxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";

                $newFilename = "";

                for ($i = 0; $i < $filenameLen; $i++) {
                    $newFilename .= $allowedChars[rand(0, strlen($allowedChars))];
                }

                $newFilename .= "." . pathinfo($_FILES["profile-image"]["name"], PATHINFO_EXTENSION);

                // Move the temp file to the new location
                $filepath = "../../../resource/images/profile/" . $newFilename;

                if (move_uploaded_file($_FILES['profile-image']['tmp_name'], $filepath)) {
                    // Now update the database.

                    $sql = "UPDATE users SET displayImageFilename='$newFilename' WHERE id=" . $curUser->getId();

                    if ($conn->query($sql)) {
                        $updateMessage = "You have successfully updated your profile image.";
                    } else {
                        $updateError = "We could not update your profile image at this time.";
                    }
                } else {
                    $updateError = "Image upload failed.";
                }
            }
        }
    }
    // Account
    if(isset($_POST["account-settings"])){
        $password = $_POST["oldPassword"];
        $newPassword = $_POST["newPassword"];
        $confPassword = $_POST["confPassword"];

        if($newPassword != $confPassword) {
            $updateError = "New passwords do not match.";
        } else {
            // Verify current password
            $results = $conn->query("SELECT password FROM users WHERE id=" . $curUser->getId());

            if(!$results) {
                $updateError = "There was an issue verifying your current password. Please try again.";
            } else {
                $passHash = $results->fetch_assoc()["password"];
                $curPassHash = password_hash($password, PASSWORD_BCRYPT);

                if(!password_verify($password, $passHash)){
                    $updateError = "Your current password was incorrect.";
                } else {
                    $newPassHash = password_hash($newPassword, PASSWORD_BCRYPT);

                    // Update the database records for the password
                    $sql = "UPDATE users SET password='$newPassHash' WHERE id=" . $curUser->getId();

                    if($conn->query($sql)) {
                        $updateMessage = "Password successfully updated.";
                    } else {
                        $updateError = "Unable to update your password at this time.";
                    }
                }
            }
        }

    }
    // Privacy
    if(isset($_POST["privacy-private-settings"])) {
        if(isset($_POST["private"])){
            $sql = null;
            if($_POST["private"] == "private") {
                $sql = "UPDATE users SET isPrivate=1 WHERE id=" . $curUser->getId();
            } else if($_POST["private"] == "open") {
                $sql = "UPDATE users SET isPrivate=0 WHERE id=" . $curUser->getId();
            } else {
                $updateError = "Invalid option selected.";
            }

            if(!is_null($sql)) {
                if($conn->query($sql)) {
                    $updateMessage = "Successfully updated privacy settings.";
                } else {
                    $updateError = "We could not update your privacy settings at this time.";
                }
            }
        }
    }
    if(isset($_POST["privacy-following-settings"])) {
        if(isset($_POST["following"])){
            $sql = null;
            if($_POST["following"] == "show") {
                $sql = "UPDATE users SET showFollowing=1 WHERE id=" . $curUser->getId();
            } else if($_POST["following"] == "hide") {
                $sql = "UPDATE users SET showFollowing=0 WHERE id=" . $curUser->getId();
            } else {
                $updateError = "Invalid option selected.";
            }

            if(!is_null($sql)) {
                if($conn->query($sql)) {
                    $updateMessage = "Successfully updated privacy settings.";
                } else {
                    $updateError = "We could not update your privacy settings at this time.";
                }
            }
        }
    }
    if(isset($_POST["privacy-followers-settings"])) {
        if(isset($_POST["followers"])){
            $sql = null;
            if($_POST["followers"] == "show") {
                $sql = "UPDATE users SET showFollowers=1 WHERE id=" . $curUser->getId();
            } else if($_POST["followers"] == "hide") {
                $sql = "UPDATE users SET showFollowers=0 WHERE id=" . $curUser->getId();
            } else {
                $updateError = "Invalid option selected.";
            }

            if(!is_null($sql)) {
                if($conn->query($sql)) {
                    $updateMessage = "Successfully updated privacy settings.";
                } else {
                    $updateError = "We could not update your privacy settings at this time.";
                }
            }
        }
    }
}

if ($logged_on){
    $curUser = getLoggedInUser($conn);
}

?>
<html>
<head>
    <title>Settings</title>

    <?php
    require_once "../../../resource/site-elements/standardhead.php";
    ?>

    <script>
        // When we jump through id hashjumps, make it jump 75 px short to account for the Navbar
        window.addEventListener("hashchange", function () {
            window.scrollTo(window.scrollX, window.scrollY - 75);
        });


        function check_username(){
            let un_lbl = document.getElementById("username-taken-lbl");
            let un_inp = document.getElementById("usernameInput");
            username_taken(un_inp.value, "<?php echo "http://" . $_SERVER["SERVER_NAME"] . "/resource/ajax/username_exists.php" ?>", function(data) {
                switch (data) {
                    case "true":
                        if(un_lbl.classList.contains("hidden")) {
                            un_lbl.classList.remove("hidden");
                        }
                        break;
                    case "false":
                        if(!un_lbl.classList.contains("hidden")){
                            un_lbl.classList.add("hidden");
                        }
                        break;
                    case "username not set":
                    case "could not check username":
                        // here we can handle any issues needed. We don't need to in this case as its only a visual aide
                        break;
                    default:
                        // Add the hidden class so that user may try the username if they want it.
                        // If the username is in-fact taken and we just haven't handled the error, it will be corrected by the sign-up action
                        if(un_lbl.classList.contains("hidden")) un_lbl.classList.add("hidden");
                        break;
                }
            });
        }
    </script>
</head>

<body>
    <?php
    // Import the navbar
    require_once "../../../resource/site-elements/navbar.php";
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
    <div class="container-fluid row">
        <div class="col-md-2 settings-nav">
            <div class="dropdown float-right">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="navDropdownButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Jump too
                </button>
                <div class="dropdown-menu" aria-labelledby="navDropdownButton">
                    <a class="dropdown-item" href="#profile-settings">Profile Settings</a>
                    <a class="dropdown-item" href="#account-settings">Account Settings</a>
                    <a class="dropdown-item" href="#privacy-settings">Privacy Settings</a>
                </div>
            </div>
        </div>
        <div class="col-md-8 settings">
            <?php

            if(!is_null($updateError)) {
                ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $updateError; ?>
                </div>
                <?php
            }

            if(!is_null($updateMessage)) {
                ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $updateMessage; ?>
                </div>
                <?php
            }
            ?>
            <div id="profile-settings" class="settings-section profile-settings">
                <h1>Profile settings</h1>
                <hr>
                <form method="post">
                    <input type="hidden" name="profile-settings" value="1">

                    <div class="form-group">
                        <label for="nameInput">Name</label>
                        <input type="text" class="form-control" id="nameInput" aria-describedby="nameHelp" name="name" value="<?php echo $curUser->getName(); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="usernameInput">Username</label>
                        <input type="text" class="form-control" id="usernameInput" aria-describedby="usernameHelp"  name="username" value="<?php echo $curUser->getUsername(); ?>"  onkeyup="check_username()" required>
                        <small id="username-taken-lbl" class="form-text text-danger hidden">Username taken.</small>
                    </div>

                    <div class="form-group">
                        <label for="bioInput">Bio</label>
                        <textarea class="form-control" id="bioInput" placeholder="Tell us about yourself..." name="bio"><?php echo $curUser->getBio(); ?></textarea>
                    </div>

                    <button class="btn btn-primary" type="submit">Update</button>
                </form>
                <hr>
                <h3>Display image</h3>
                <hr>
                <div class="row profile-image-section">
                    <div class="col-md-3 profile-image-container">
                        <?php
                            if(is_null($curUser->getDisplayImage())){
                                ?>
                                <img src="http://<?php echo $_SERVER["SERVER_NAME"]; ?>/resource/images/profile/default.jpg">
                                <?php
                            } else if ($curUser->getDisplayImage() == ""){
                                ?>
                                <img src="http://<?php echo $_SERVER["SERVER_NAME"]; ?>/resource/images/profile/default.jpg">
                                <?php
                            } else {
                                ?>
                                <img src="http://<?php echo $_SERVER["SERVER_NAME"]; ?>/resource/images/profile/<?php echo $curUser->getDisplayImage(); ?>">
                                <?php
                            }
                        ?>
                    </div>
                    <div class="col-md-9 form-section">
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="profile-image-settings" value="1">

                            <div class="form-group">
                                <label for="image-upload">Upload file</label>
                                <input id="image-upload" class="form-control-file" type="file" name="profile-image"
                                       accept="image/png, image/jpeg, image/jpg, image/gif">
                            </div>

                            <div class="form-group btn-section">
                                <button class="btn btn-primary" type="submit" name="submit">Update</button>
                                <button class="btn btn-danger" type="submit" name="submit" value="delete">Remove</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div id="account-settings" class="settings-section account-settings">
                <h1>Account settings</h1>
                <hr>
                <form method="post">
                    <input type="hidden" name="account-settings" value="1">

                    <div class="form-group">
                        <label for="oldPasswordInput">Current Password</label>
                        <input type="text" class="form-control" id="oldPasswordInput" aria-describedby="oldPasswordHelp" name="oldPassword" required>
                    </div>

                    <div class="form-group">
                        <label for="newPasswordInput">New Password</label>
                        <input type="text" class="form-control" id="newPasswordInput" aria-describedby="newPasswordHelp" name="newPassword" required>
                    </div>

                    <div class="form-group">
                        <label for="confPasswordInput">Confirm Password</label>
                        <input type="text" class="form-control" id="confPasswordInput" aria-describedby="newPasswordHelp" name="confPassword" required>
                    </div>

                    <div class="form-group">
                        <button class="btn btn-primary" type="submit">Update</button>
                    </div>
                </form>
            </div>
            <div id="privacy-settings" class="settings-section privacy-settings">
                <h1>Privacy settings</h1>
                <hr>
                <form method="POST" id="private-form">
                    <input type="hidden" name="privacy-private-settings" value="1">

                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <label class="input-group-text" for="private-setting">Privacy setting</label>
                        </div>
                        <select class="custom-select" name="private" id="private-setting" onchange="document.getElementById('private-form').submit()">
                            <option value="private" <?php if($curUser->getIsPrivate()) echo "selected='selected'" ?>>Private</option>
                            <option value="open" <?php if(!$curUser->getIsPrivate()) echo "selected='selected'" ?>>Open</option>
                        </select>
                    </div>
                </form>

                <form method="POST" id="following-form">
                    <input type="hidden" name="privacy-following-settings" value="1">

                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <label class="input-group-text" for="following-setting">Following</label>
                        </div>
                        <select class="custom-select" name="following" id="following-setting" onchange="document.getElementById('following-form').submit()">
                            <option value="show" <?php if($curUser->getShowFollowing()) echo "selected='selected'" ?>>Show</option>
                            <option value="hide" <?php if(!$curUser->getShowFollowing()) echo "selected='selected'" ?>>Hide</option>
                        </select>
                    </div>
                </form>

                <form method="POST" id="followers-form">
                    <input type="hidden" name="privacy-followers-settings" value="1">

                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <label class="input-group-text" for="followers-setting">Followers</label>
                        </div>
                        <select class="custom-select" name="followers" id="followers-setting" onchange="document.getElementById('followers-form').submit()">
                            <option value="show" <?php if($curUser->getShowFollowers()) echo "selected='selected'" ?>>Show</option>
                            <option value="hide" <?php if(!$curUser->getShowFollowers()) echo "selected='selected'" ?>>Hide</option>
                        </select>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <?php }
    $conn->close();
    ?>
</body>
</html>
