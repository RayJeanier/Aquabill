<?php

// Session setup shared by all consumer pages.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
