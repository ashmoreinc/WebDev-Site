<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/session_management.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/resource/php/dbconn.php";


$curUser = null;
$logged_on = false;

try {
    $conn = getConn();
} catch (dbConnNotCreatedException $e){
    $conn = null;
    header($_SERVER["SERVER_PROTOCOL"] . ' 500 Internal Server Error', true, 500);
    die();
}
if (!is_null($conn)){
    $logged_on = isLoggedIn($conn);
}

if ($logged_on){
    $curUser = getLoggedInUser($conn);
}


// Decide which nav bar to use based on the user log in status
// Though there is a fair bit of repeated code across each branch of this condition. It is more readable to have it this way
if($logged_on){ // Logged in
    ?>
    <nav class="navbar fixed-top navbar-expand-md navbar-dark bg-dark">
        <a class="navbar-brand" href="http://<?php echo $_SERVER['SERVER_NAME']; ?>">Exclusive</a>

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#main-nav" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="main-nav">
            <form class="form-inline my-2 my-lg-0 ml-auto" id="nav-searchbar" role="search" method="get" action="<?php echo "http://" . $_SERVER["SERVER_NAME"] . "/account/search/" ?>">
                <input class="form-control mr-sm-2" type="search" name="search-query" placeholder="Search" aria-label="Search">
                <button class="btn btn-outline-light my-2 my-sm-0" type="submit">Search</button>
            </form>

            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="accountMenu" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <?php echo $curUser->getName(); ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="accountMenu">
                        <a class="dropdown-item" href="http://<?php echo $_SERVER['SERVER_NAME']; ?>/account/profile/index.php?user=<?php echo $curUser->getUsername(); ?>"><?php echo $curUser->getUsername(); ?></a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="http://<?php echo $_SERVER["SERVER_NAME"]; ?>/account/profile/settings/">Settings</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="http://<?php echo $_SERVER['SERVER_NAME']; ?>/account/logout.php">Logout</a>
                    </div>
                </li>
            </ul>


        </div>
    </nav>

    <?php
} else { // Not logged in
    ?>
    <nav class="navbar fixed-top navbar-expand-md navbar-dark bg-dark">
        <a class="navbar-brand" href="http://<?php echo $_SERVER['SERVER_NAME']; ?>">Exclusive</a>

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#main-nav" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="main-nav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="http://<?php echo $_SERVER['SERVER_NAME']; ?>/account/">Login/Sign-up</a>
                </li>
            </ul>
        </div>
    </nav>

    <?php
}
