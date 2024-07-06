<?php
    require_once('common.php');

    $sso_a = false;
    if(isset($_GET['sso_u']) && isset($_GET['sso_n']) && isset($_GET['sso_t']) && isset($_GET['sso_k'])) {
        $sso_u = strtolower(str_replace(' ', '', $_GET['sso_u']));
        $sso_n = $_GET['sso_n'];
        $sso_t = $_GET['sso_t'];
        $sso_k = $_GET['sso_k'];
        if(!is_numeric($sso_t) || floor($sso_t) != $sso_t || $sso_t < 300 || $sso_t > 3000000 || strlen($sso_u) < 1 || strlen($sso_n) < 1) {
            $sso_a = false;
        } elseif (strtolower($sso_k) != sha1($sso_u . $sso_n. $sso_t . $sso_secret)) {
            $sso_a = false;
        } else {
            @ini_set('session.gc_maxlifetime', $sso_t);
            session_start();
            @session_set_cookie_params($sso_t);
            $_SESSION['sso_u'] = $sso_u;

            $query = "SELECT COUNT(*) FROM user WHERE user = ?";
            $stmt = mysqli_prepare($sql_connection, $query);
            mysqli_stmt_bind_param($stmt, 's', $sso_u);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $count);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
            if ($count == 0) {
                $uuid = getUUID();
                $password = '0000000000000000000000000000000000000000';
                $insert_query = "INSERT INTO user (id, user, name, level, password) VALUES (?, ?, ?, 1, ?)";
                $stmt = mysqli_prepare($sql_connection, $insert_query);
                mysqli_stmt_bind_param($stmt, 'ssss', $uuid, $sso_u, $sso_n, $password);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            $stmt = mysqli_prepare($sql_connection, "SELECT name FROM user WHERE user = ?");
            mysqli_stmt_bind_param($stmt, 's', $sso_u);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $name);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
            if ($sso_n != $name) {
                $stmt = mysqli_prepare($sql_connection, "UPDATE user SET name = ? WHERE user = ?");
                mysqli_stmt_bind_param($stmt, 'ss', $sso_n, $sso_u);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            $sso_a = true;
        }
    }

    if ($sso_a) {
        if (isset($_GET['page']) && !empty($_GET['page'])) {
            header('Location: '.$_GET['page']);
        } else {
            header('Location: index.php');
        }
        exit;
    }
    else {
        header('Location: '.$sso_login);
        exit;
    }
?>