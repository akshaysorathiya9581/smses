<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
session_start();
$con = mysqli_connect("localhost", "root", "", "smses_send");
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "FDFD"; die();
?>