<?php
// callback.php
require_once 'config.php';
require_once 'functions.php';

session_start();

if (isset($_GET['error'])) {
    die('Error: ' . htmlspecialchars($_GET['error']));
}

if (!isset($_GET['code'])) {
    header('Location: index.php');
    exit;
}

$code = $_GET['code'];
$tokenData = getAccessToken($code);

if (isset($tokenData['error'])) {
    die('Error getting token: ' . htmlspecialchars($tokenData['error_description'] ?? $tokenData['error']));
}

$_SESSION['access_token'] = $tokenData['access_token'];
$_SESSION['refresh_token'] = $tokenData['refresh_token'];
$_SESSION['expires_in'] = time() + $tokenData['expires_in'];

header('Location: stats.php');
exit;
?>