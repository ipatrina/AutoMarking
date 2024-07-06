<?php

require_once('common.php');
require_once('authorization.php');

if ($sso_l <= 3) {
    http_response_code(403);
    exit;
}

if (!isset($_GET['course']) || empty($_GET['course'])
    || !isset($_GET['name']) || empty($_GET['name'])
    || !isset($_GET['email']) || empty($_GET['email'])
    || !isset($_GET['notify'])) {
    http_response_code(400);
    exit;
}

$course_id = $_GET['course'];
$name = trim($_GET['name']);
$email = trim($_GET['email']);
$notify = $_GET['notify'] === 'true';

// Check if course exists
$stmt = mysqli_prepare($sql_connection, 'SELECT code, user, description FROM course WHERE id = ?');
mysqli_stmt_bind_param($stmt, 's', $course_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $course_code, $user_str, $course_description);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (!$course_code) {
    http_response_code(400);
    exit;
}

// Update enrolled users in the course
$user_arr = explode(' ', $user_str);
$user_arr = array_filter($user_arr, function($value) {
    return $value !== '';
});
if (!in_array($email, $user_arr)) {
    $user_arr[] = $email;
    $new_user_str = implode(' ', $user_arr);
    $stmt = mysqli_prepare($sql_connection, 'UPDATE course SET user = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'ss', $new_user_str, $course_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Check if user exists
$stmt = mysqli_prepare($sql_connection, 'SELECT id FROM user WHERE user = ?');
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $user_id);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (!$user_id) {
    // Create user if not exist
    $user_id = getUUID();
    $password = str_repeat('0', 40);
    $level = 1;
    $stmt = mysqli_prepare($sql_connection, 'INSERT INTO user (id, user, name, level, password) VALUES (?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'sssis', $user_id, $email, $name, $level, $password);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Send invite email
if ($notify) {
    $currentUrl = "http" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
    $login_time = 86400;
    $login_url = dirname($currentUrl).'/login.php?sso_u='.urlencode($email).'&sso_n='.urlencode($name).'&sso_t='.$login_time.'&sso_k='.sha1($email . $name . $login_time . $sso_secret).'&page=user.php';
    $subject = '[AutoMarking] Invitation to Course '.$course_code;
    $message = <<<EOD
<html>
    <body>
        <div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f5f5f5; text-align: left;">
            <h2 style="text-align: center; font-size: 20px;">AutoMarking</h2>
            <p style="margin: 0; padding: 0; text-align: left; font-size: 16px;">Hello $name. Your instructor $sso_n has invited you to join the course $course_code. If you are using the AutoMarking system for the first time, please click the button below to create your password.</p>
            <a href="$login_url" style="display: block; margin: 20px auto 0; padding: 10px 20px; background-color: #0052a5; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 18px; font-weight: bold; text-decoration: none; text-align: center; max-width: 120px; margin-left: auto; margin-right: auto;" target="_blank">Join Course</a>
        </div>
    </body>
</html>
EOD;
    $notifyResult = sendEmail($email, $subject, $message);
}

header('Content-Type: application/xml');
echo '<?xml version="1.0" encoding="UTF-8"?>';
if ($notify && !empty($notifyResult)) {
    echo '<response><status>Email error</status><message>'.$notifyResult.'</message></response>';
} else {
    echo '<response><status>OK</status><message>Success.</message></response>';
}

?>