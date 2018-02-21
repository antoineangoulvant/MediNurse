<?php
/**
 * Created by PhpStorm.
 * User: antoi
 * Date: 21/02/2018
 * Time: 22:58
 */

session_start();

// Suppression des variables de session et de la session
$_SESSION = array();
session_destroy();
header('Location: login.php');
?>