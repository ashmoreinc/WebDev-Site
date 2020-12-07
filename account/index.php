<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/session_management.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/dbconn.php";

// TODO: Consider asking the user if they want to log out
// Redirect to the site home page if already logged in.
try {
    $conn = getConn();

    if (isLoggedIn($conn)){
        $conn->close();
        header("Location: http://" . $_SERVER['SERVER_NAME']);
        die();
    }
} catch (dbConnNotCreatedException $e){
    $conn = null;
}
if (!is_null($conn)){

}
?>
<html lang="en">
    <head>
        <title>Login</title>

        <?php // Import the header from a central location
        require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/site-elements/standardhead.php";
        ?>
        <script src="<?php echo "http://" . $_SERVER["SERVER_NAME"] . "/resource/js/common_functions.js" ?>"></script>
        <script>
            function toggle_login_signup(){
                // Toggles whether the login panel or sign-up panel is showing.
                const loginPanel = document.getElementById("login-panel");
                const signupPanel = document.getElementById("signup-panel");
                if(loginPanel.classList.contains("hidden")){
                    loginPanel.classList.remove("hidden");
                    signupPanel.classList.add("hidden");
                } else {
                    loginPanel.classList.add("hidden");
                    signupPanel.classList.remove("hidden");
                }
            }

            function password_match(){
                // Checks whether confirmation password matches the original password and then displays or hides a message notifying the user.
                const password = document.getElementById("password");
                const passconf = document.getElementById("passwordconf");
                const passalert = document.getElementById("pass-no-match");

                // alert(password.value + " || " + passconf.value);
                if(password.value === passconf.value){
                    passalert.classList.add("hidden");
                } else {
                    passalert.classList.remove("hidden");
                }
            }

            function check_username(){
                let un_fld = document.getElementById("username-input-field");
                let un_lbl = document.getElementById("username-taken-lbl");
                let un_inp = document.getElementById("username");
                username_taken(un_inp.value, "<?php echo "http://" . $_SERVER["SERVER_NAME"] . "/resource/php/ajax/username_exists.php" ?>", function(data) {
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

        require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/site-elements/navbar.php";

        ?>

        <div class="account-section container-md">
            <div id="login-panel" class="login">
                <form action="login.php" method="POST">
                    <?php
                    // Check for successful account creation message
                    if (isset($_GET["success"])){
                        ?> <div class="alert alert-success" role="alert"> <?php
                        switch ($_GET["success"]){
                            case "loggedout":
                                echo "Successfully logged out.";
                                break;
                            case "created":
                                echo "Account successfully created.";
                                break;
                            default:
                                echo "Action performed successfully.";
                                break;
                        }
                        ?> </div> <?php
                    }

                    // Check for error messages
                    if (isset($_GET["error"])){
                        ?> <div  class="alert alert-danger" role="alert">  <?php
                        switch ($_GET["error"]){
                            case "sesserr":
                                echo "There was an issue creating a session. Ensure that cookies are enabled on your browser and try again.";
                                break;
                            case "login-incorrect":
                                echo "Incorrect username or password.";
                                break;
                            case "login-queryerr":
                                echo "There was an issue performing some checks. Please try again.";
                                break;
                            case "login-password":
                                echo "Password field cannot be left empty";
                                break;
                            case "login-username":
                                echo "Username field cannot be left empty.";
                                break;
                            case "login-connection":
                                echo "There was an issue connecting to the server. Please wait a little while and try again.";
                                break;
                            default:
                                echo "An unidentified error occurred. Please try again.";
                                break;
                        }
                        ?> </div> <?php
                    }

                    ?>
                    <div class="form-group">
                        <label for="login-username">Username</label>
                        <input class="form-control" type="text" name="username" id="login-username">
                    </div>
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input class="form-control" type="password" name="password" id="login-password">
                    </div>

                    <!-- Thought form-check is made for check boxes, there is no <a> tag formatting, but this will work-->
                    <div class="form-group form-check form-check-inline">
                        <a href="#" onclick="toggle_login_signup()">Create an account</a>
                    </div>

                    <br>

                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
            </div>
            <div id="signup-panel" class="sign-up hidden">
                <form action="create.php" method="POST">
                    <?php
                    // Check for error messages
                    if(isset($_GET["error"])){
                        ?> <div  class="alert alert-danger" role="alert"> <?php
                        switch ($_GET["error"]){
                            case "queryerr":
                                echo "There was an error performing some checks. Please try again.";
                                break;
                            case "passerr":
                                echo "Something went wrong when trying to secure your password. Because off this we have not created an account. Please try again";
                                break;
                            case "usertaken":
                                echo "That username was already taken.";
                                break;
                            case "passmatch":
                                echo "The passwords did not match up.";
                                break;
                            case "connection":
                                echo "There was an issue connecting to the server. Please wait a little while and try again.";
                                break;
                            case "name":
                                echo "Name was not set.";
                                break;
                            case "username":
                                echo "Username was not set.";
                                break;
                            case "password":
                                echo "Password not set.";
                                break;
                            case "passconf":
                                echo "Password confirmation not set.";
                                break;
                            default:
                                echo "An unidentified error occurred. Please try again.";
                                break;
                        }
                        ?> </div> <?php
                    }
                    ?>

                    <div class="form-group">
                        <label for="name">Name</label>
                        <input class="form-control" type="text" name="name" id="name">
                    </div>

                    <div id="username-input-field" class="form-group">
                        <label id="username-lbl" for="username">Username</label>
                        <small id="username-taken-lbl" class="form-text text-danger hidden">Username taken.</small>
                        <input class="form-control" type="text" name="username" id="username" onkeyup="check_username()">
                    </div>

                    <div class="form-group"> <!-- add password requirements -->
                        <label for="password">Password</label>
                        <input class="form-control" type="password" name="password" id="password" onkeyup="password_match()">
                    </div>

                    <div class="form-group">
                        <label for="passwordconf">Confirm password</label>
                        <small id="pass-no-match" class="form-text text-danger hidden">Passwords do not match.</small>
                        <input class="form-control" type="password" name="passwordconf" id="passwordconf" onkeyup="password_match()">
                    </div>


                    <div class="form-group form-check form-check-inline">
                        <a href="#" onclick="toggle_login_signup()">Login to an existing account</a>
                    </div>

                    <br>

                    <button type="submit" class="btn btn-primary">Sign-up</button>
                </form>
            </div>
        </div>
    </body>
    <?php
    // Toggle the panel which is shown, to show the sign up panel if the attribute is there
    if(isset($_GET["mode"])){
        if($_GET["mode"] == "sign-up"){
            ?> <script> toggle_login_signup(); </script> <?php
        }
    }
    ?>
</html>
<?php
$conn->close();
?>