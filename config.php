<?php

// ================ Configuration ================ \\
// MySQL database: ( Host / Username / Password / Database Name )
@$sql_connection = mysqli_connect('localhost', 'username', 'password', 'automarking');
//
// Outgoing mail server: ( SMTP host / SMTP port / SMTP username / SMTP password / Sender address )
@$smtp = ['smtp.example.com', '25', 'smtp_username', 'smtp_password', 'sender@example.com'];
//
// Default admin username
$sso_admin = 'supervisor';
//
// SSO login page
$sso_login = 'sso_default.php';
//
// SSO secret
$sso_secret = 'sso_secret';
//
// Time zone
date_default_timezone_set('UTC');
//
// =============================================== \\

?>