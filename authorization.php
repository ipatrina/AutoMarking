<?php
    require_once('common.php');

    if (!isset($_SESSION['sso_u'])) {
        header('Location: '.$sso_login);
        exit;
    } else {
        $sso_u = $_SESSION['sso_u'];
        $sso_l = 1;
        $sso_i = '00000000-0000-0000-0000-000000000000';
        $sso_n = 'Guest User';
        $stmt = mysqli_prepare($sql_connection, "SELECT id, name, level FROM user WHERE user = ?");
        mysqli_stmt_bind_param($stmt, 's', $sso_u);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $sso_i, $sso_n, $sso_l);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        if ($sso_u == $sso_admin) {
            $sso_l = 5;
        }
    }
?>