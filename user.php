<?php
    require_once('common.php');
    require_once('authorization.php');

    if (isset($_POST['submit'])) {
        $id = $_POST['id'];
        $fullname = $_POST['fullname'];
        $username = $_POST['username'];
        $password1 = $_POST['password1'];
        $password2 = $_POST['password2'];

        $stmt = mysqli_prepare($sql_connection, "SELECT * FROM user WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "s", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && ($sso_l > 3 || $sso_i == $id)) {
            if ($sso_l > 3) {
                $stmt = mysqli_prepare($sql_connection, "UPDATE user SET user = ?, name = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "sss", $username, $fullname, $id);
                mysqli_stmt_execute($stmt);
            }

            if (!empty($password1) && ($password1 === $password2) && !($username == $sso_admin && $sso_l < 5)) {
                $password = sha1($password1);
                $stmt = mysqli_prepare($sql_connection, "UPDATE user SET password = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "ss", $password, $id);
                mysqli_stmt_execute($stmt);
            }

            if ($sso_l > 3) {
                header("Location: admin.php");
            } else {
                header("Location: index.php");
            }
            exit;
        }
    }

    $id = isset($_GET['id']) ? $_GET['id'] : $sso_i;
    $username = 'null';
    $fullname = 'Guest User';
    if (!empty($id)) {
        $query = mysqli_prepare($sql_connection, 'SELECT user, name FROM user WHERE id = ?');
        mysqli_stmt_bind_param($query, 's', $id);
        mysqli_stmt_execute($query);
        mysqli_stmt_bind_result($query, $username, $fullname);
        mysqli_stmt_fetch($query);
    }

    showHeader();
?>
    <div style="position: relative; margin: 0 auto; margin-top: 10px; margin-bottom: 10px; min-width: 1000px; max-width: 1000px; height: 50px;">
        <button class="back-btn" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%);" onclick="location.href = '<?php echo $sso_l > 3 ? 'admin.php' : 'index.php' ?>';"><</button>
        <h2 style="position: absolute; left: 50%; top: 50%; transform: translateX(-50%) translateY(-50%); text-align: center; margin: 0;">Account Settings</h2>
    </div>
    <div style="width: 960px; margin: 0 auto; padding: 20px; background-color: #f5f5f5; border: 1px solid #ccc;">
        <form method="post" action="" onsubmit="return validateForm()">
            <input type="hidden" name="id" value="<?php echo $id; ?>">

            <label for="fullname" style="display: block; margin-bottom: 10px; font-size: 18px; font-weight: bold;">Full Name</label>
            <input type="text" name="fullname" id="fullname" value="<?php echo isset($fullname) ? $fullname : ''; ?>" <?php if ($sso_l <= 3) { echo 'readonly';} ?> style="display: block; width: 100%; font-size: 20px; padding: 5px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box;">

            <label for="username" style="display: block; margin-bottom: 10px; font-size: 18px; font-weight: bold;">Email Address</label>
            <input type="text" name="username" id="username" value="<?php echo isset($username) ? $username : ''; ?>" <?php if ($sso_l <= 3) { echo 'readonly';} ?> style="display: block; width: 100%; font-size: 20px; padding: 5px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box;">

            <label for="password1" style="display: block; margin-bottom: 10px; font-size: 18px; font-weight: bold;">New Password</label>
            <input type="password" name="password1" id="password1" value="" style="display: block; width: 100%; font-size: 20px; padding: 5px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box;">

            <label for="password2" style="display: block; margin-bottom: 10px; font-size: 18px; font-weight: bold;">Confirm Password</label>
            <input type="password" name="password2" id="password2" value="" style="display: block; width: 100%; font-size: 20px; padding: 5px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box;">

            <p id="error-message" style="color: red; text-align: center; display: none;">Passwords do not match.</p>

            <div style="text-align: center;">
                <input type="submit" name="submit" value="Save" style="background-color: #0074D9; color: white; border: none; padding: 12px 24px; font-size: 20px; border-radius: 3px; cursor: pointer; margin-right: 10px; margin-top: 10px;">
            </div>
        </form>
    </div>
    <script>
        function validateForm() {
            var password1 = document.getElementById('password1').value;
            var password2 = document.getElementById('password2').value;

            if (password1 !== password2) {
                document.getElementById('error-message').style.display = 'block';
                return false;
            }

            return true;
        }
    </script>       

<?php
    showFooter();
?>