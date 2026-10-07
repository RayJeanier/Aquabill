<?php

require "../includes/session.php";

// Only sign out the consumer; an admin signed in on the same browser stays signed in
unset($_SESSION["consumer"]);
session_regenerate_id(true);

header("Location: ../../index.html?logout=success");
exit;
