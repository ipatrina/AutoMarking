<?php
    require_once('config.php');

    session_start();
    unset($_SESSION['sso_u']);
    session_destroy();

    header('Location: '.$sso_login);
?>