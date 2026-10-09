<?php

// Tells the browser not to keep a copy of this page.
// Used on every logged-in page, so after logging out the Back/Forward buttons
// can't show account pages again - the browser has to ask the server, and the
// server sends it to the login page.

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
