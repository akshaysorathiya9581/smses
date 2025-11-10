<?php 
session_start();
if(empty($_SESSION['username'])){
    header("Location:http://localhost/send/login.php");
}
?>