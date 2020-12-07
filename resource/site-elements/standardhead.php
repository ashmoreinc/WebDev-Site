<?php
// Use this method ($_SERVER['SERVER_NAME']) to get the address so that it works even through a VPN or if the site was to have a domain name
// Note: We could use $_SERVER["HTTP_HOST"] or $_SERVER["HTTPS_HOST"] But for the way we are using it,
// SERVER_NAME skips having to check for https or not, even though we don't actually need to do that check cause we are only using http
?>
<link rel="stylesheet" href=<?php echo "\"http://" . $_SERVER['SERVER_NAME'] . "/resource/css/main.css\""; ?>>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx" crossorigin="anonymous"></script>

<script src="<?php echo "http://" .  $_SERVER["SERVER_NAME"] . "/resource/js/common_functions.js"; ?>"></script>