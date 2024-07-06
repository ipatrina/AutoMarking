<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Sign up</title>
        <style>
            a {
                color: #0052A5;
                text-decoration: none;
                font-size: 16px;
            }

            body {
                font-family: Arial, sans-serif;
                background-color: #f1f1f1;
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100vh;
            }
            .signup-box {
                width: 400px;
                background-color: #fff;
                border-radius: 5px;
                box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
                padding: 20px;
            }

            .signup-box h2 {
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

            .error-msg {
                color: red;
                font-size: 14px;
                margin-bottom: 10px;
                text-align: center;
            }

            .success-msg {
                color: green;
                font-size: 14px;
                margin-bottom: 10px;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="signup-box">
            <h2>Sign up</h2>
            <form action="sso_default.php" method="POST">
                <div class="user-box">
                    <input type="text" name="fullname" required="">
                    <label>Full Name</label>
                </div>
                <div class="user-box">
                    <input type="text" name="username" required="">
                    <label>Email Address</label>
                </div>
                <div class="user-box">
                    <input type="password" name="password" required="">
                    <label>Password</label>
                </div>
                <div class="user-box">
                    <input type="password" name="password-confirm" required="">
                    <label>Confirm Password</label>
                </div>
                <div style="text-align: center;">
                    <button type="submit" name="signup-submit">Sign up</button>
                    <p>Already have an account? <a href="sso_default.php">Login</a></p>
                </div>
            </form>
        </div>
    </body>
</html>