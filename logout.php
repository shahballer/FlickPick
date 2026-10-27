<?php
// will end the session. *tanner building real one*/
session_start();
$_SESSION = []; //forget user info
session_destroy(); //end session
header("Location: frontend/index.php");
exit;