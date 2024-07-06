<?php
    require_once('common.php');

    if (isset($_POST['login-submit'])) {
        $username = strtolower($_POST['username']);
        $password = $_POST['password'];

        $stmt = mysqli_prepare($sql_connection, "SELECT name, password FROM user WHERE user = ?");
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $name, $hashed_password);
        mysqli_stmt_fetch($stmt);

        if (sha1($password) === $hashed_password) {
            $token_expiry = 86400;
            $token_key = sha1($username . $name . $token_expiry . $sso_secret);
            header("Location: login.php?sso_u=$username&sso_n=$name&sso_t=$token_expiry&sso_k=$token_key");
            exit;
        }
        $errorMessage = 'Invalid email address or password.';
    }
    elseif (isset($_POST['signup-submit'])) {
        $fullname = $_POST['fullname'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $confirm_password = $_POST['password-confirm'];

        if (empty($fullname) || empty($username) || empty($password) || empty($confirm_password) || strpos($username, ' ') !== false) {
            $errorMessage = 'Sign up failed. Incomplete or incorrect form.';
        } elseif ($password !== $confirm_password) {
            $errorMessage = 'Sign up failed. Inconsistent password input.';
        } else {
            $password = sha1($password);
            $stmt = mysqli_prepare($sql_connection, 'SELECT id FROM user WHERE user = ?');
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $errorMessage = 'Sign up failed. User exists.';
            } else {
                $id = getUUID();
                $level = 1;
                $stmt = mysqli_prepare($sql_connection, 'INSERT INTO user (id, user, name, level, password) VALUES (?, ?, ?, ?, ?)');
                mysqli_stmt_bind_param($stmt, 'sssis', $id, $username, $fullname, $level, $password);
                mysqli_stmt_execute($stmt);
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $errorMessage = 'Sign up success. Please log in.';
                } else {
                    $errorMessage = 'Sign up failed. Database error.';
                }
            }
        }
    }
    else {
        $errorMessage = '';
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>AutoMarking</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f1f1f1;
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100vh;
            }

            .login-box {
                width: 400px;
                background-color: #fff;
                border-radius: 5px;
                box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
                padding: 20px;
            }

            .login-box h2 {
                font-size: 28px;
                margin-top: 20px;
                margin-bottom: 40px;
                text-align: center;
            }

            .user-box {
                position: relative;
                margin-bottom: 20px;
            }

            .user-box input {
                width: 100%;
                padding: 10px 0;
                font-size: 16px;
                color: #333;
                margin-bottom: 20px;
                border: none;
                border-bottom: 2px solid #ccc;
                outline: none;
                background: none;
            }

            .user-box label {
                position: absolute;
                top: 0;
                left: 0;
                padding: 10px 0;
                font-size: 16px;
                color: #999;
                pointer-events: none;
                transition: 0.5s;
            }

            .user-box input:focus ~ label,
            .user-box input:valid ~ label {
                top: -20px;
                font-size: 12px;
                color: #5264AE;
            }

            button[type="submit"] {
                border: none;
                outline: none;
                background-color: #0052A5;
                color: #fff;
                padding: 10px 20px;
                border-radius: 5px;
                cursor: pointer;
                font-size: 18px;
                margin-bottom: 20px;
            }

            a {
                color: #0052A5;
                text-decoration: none;
                font-size: 18px;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>AutoMarking</h2>
            <form action="" method="POST">
                <div class="user-box">
                    <input type="text" name="username" required="">
                    <label>Email Address</label>
                </div>
                <div class="user-box">
                    <input type="password" name="password" required="">
                    <label>Password</label>
                </div>
                <div style="text-align: center;">
                    <?php if (!empty($errorMessage)) { echo '<span style="color: red;">'.$errorMessage.'</span><br><br><br>'; }?>
                    <button type="submit" name="login-submit">Login</button>
                    <a href="sso_signup.php" style="padding-left: 10px;">Sign up</a>
                </div>

            </form>
        </div>
    </body>
</html>