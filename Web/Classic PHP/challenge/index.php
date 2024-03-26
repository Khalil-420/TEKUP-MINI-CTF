<?php

error_reporting(0);
highlight_file(__FILE__);

$flag = getenv("FLAG");
$admin_pass = "240610708";

if(isset($_GET['username']) && isset($_GET['password'])) {
    $username = $_GET['username'];
    $password = $_GET['password'];
    if ($username === 'admin' || $password === $admin_pass) {
    	die("We don't do that here!");
    }
    else{
    	if (strcmp($username,"admin") == 0 && md5($password) == md5($admin_pass)){
    	die($flag);
    }
    else {
		die("Nope");}
}
}
?>