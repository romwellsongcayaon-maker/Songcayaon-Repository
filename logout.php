<?php
session_start();

// remove all session data
$_SESSION = [];

// delete the session cookie in the browser
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

// destroy the session on the server
session_destroy();

header('Location: Login.html');
exit;