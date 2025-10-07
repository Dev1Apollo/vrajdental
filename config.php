<?php
ob_start();
session_start();
date_default_timezone_set("Asia/Calcutta");

$websiteName = "Vraj Dental Clinic";
$ProjectName = "Vraj Dental Clinic";

if ($_SERVER['SERVER_NAME'] == 'localhost') {
    $dbhost = "localhost";
    $dbuser = "root";
    $dbpass = "";
    $dbname = "vrajdental";
    $web_url = "http://localhost/vrajdental/";

    $dbconn = mysqli_connect("$dbhost", "$dbuser", "$dbpass", "$dbname") or die('Could not connect: ' . mysqli_error($dbconn));

    $cateperpaging = 10;
    $mailHost = "mail.getdemo.in";
    $mailUsername = "info@getdemo.in";
    $mailPassword = "info@123";
    $mailSMTPSecure = 'tls';
    $mailFrom = "no-replay@getdemo.in";
    $mailFromName = "LMS";
    $mailAddReplyTo = "no-replay@getdemo.in";
}
else if ($_SERVER['SERVER_NAME'] == 'getdemo.in' || $_SERVER['SERVER_NAME'] == 'www.getdemo.in') {

    $dbhost = "localhost";
    $dbuser = "getdemo";
    $dbpass = "pjoo*bHxEE0u";
    $dbname = "getdemo_vrajdental";
    $web_url = 'http://' . $_SERVER['SERVER_NAME'] . '/vrajdental/';
    $dbconn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname) or die('Could not connect: ' . mysqli_connect_error());

    $cateperpaging = 12;

    // $mailHost = "mail.getdemo.in";
    // $mailUsername = "info@getdemo.in";
    // $mailPassword = "Info@123@1@";
    // $mailSMTPSecure = 'tls';
    // $mailFrom = "no-replay@getdemo.in";
    // $mailFromName = "book-my-home-tuition";
    // $mailAddReplyTo = "no-replay@getdemo.in";
    // $adminmail = "deep.desai90@gmail.com";
} 
?>