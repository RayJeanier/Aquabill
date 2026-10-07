<?php

session_start();

session_unset();
session_destroy();

// redirect with a flag
header("Location: ../index.html?logout=success");
exit;