<?php
/**
 * TecaVirtual - Script de Logout
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION = array();
session_destroy();
header("Location: login.php");
exit;
