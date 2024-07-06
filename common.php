<?php

    require_once('config.php');

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';

    session_start();

    $ace = '<script src="https://unpkg.com/ace-builds@1.16.0/src-min-noconflict/ace.js"></script>';

    if (mysqli_connect_errno()) {
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Automarking</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 1.2em;
                line-height: 1.5;
                background-color: #f5f5f5;
            }
            .container {
                margin: 200px auto;
                padding: 20px;
                max-width: 650px;
                background-color: #fff;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            }
            h1 {
                font-size: 2em;
                text-align: center;
                margin-bottom: 30px;
                color: #333;
            }
            p {
                margin-bottom: 20px;
                color: #555;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>AutoMarking</h1>
            <p>Welcome to the AutoMarking system.</p>
            <p>Before start, please configure your database in "config.php".</p>
            <p>If the system is already in use, you are seeing this message because the connection to the database has been lost. Please troubleshoot in time.</p>
        </div>
    </body>
</html>
<?php
        exit;
    }

    $tables = array(
        'assignment' => 'CREATE TABLE assignment (id VARCHAR(64) NOT NULL, course VARCHAR(64) NOT NULL, title VARCHAR(64) NOT NULL, instruction TEXT, available BIGINT, due BIGINT, attachment VARCHAR(64), sample VARCHAR(64), PRIMARY KEY(id), INDEX(title))',
        'attachment' => 'CREATE TABLE attachment (id VARCHAR(64) NOT NULL, filename TEXT NOT NULL, attachment MEDIUMBLOB NOT NULL, PRIMARY KEY(id))',
        'course' => 'CREATE TABLE course (id VARCHAR(64) NOT NULL, code VARCHAR(64) NOT NULL, user TEXT, description TEXT, PRIMARY KEY(id), INDEX(code))',
        'result' => 'CREATE TABLE result (id VARCHAR(64) NOT NULL, submission VARCHAR(64) NOT NULL, testcase VARCHAR(64) NOT NULL, score INT NOT NULL, feedback TEXT NOT NULL, PRIMARY KEY(id), INDEX(submission), INDEX(testcase))',
        'submission' => 'CREATE TABLE submission (id VARCHAR(64) NOT NULL, assignment VARCHAR(64) NOT NULL, user VARCHAR(64) NOT NULL, submission VARCHAR(64) NOT NULL, PRIMARY KEY(id), INDEX(assignment), INDEX(user), INDEX(submission))',
        'testcase' => 'CREATE TABLE testcase (id VARCHAR(64) NOT NULL, assignment VARCHAR(64) NOT NULL, title VARCHAR(64) NOT NULL, weight INT NOT NULL, routine VARCHAR(64) NOT NULL, PRIMARY KEY(id), INDEX(assignment))',
        'user' => 'CREATE TABLE user (id VARCHAR(64) NOT NULL, user VARCHAR(64) NOT NULL, name VARCHAR(64) NOT NULL, level INT NOT NULL, password VARCHAR(64) NOT NULL, PRIMARY KEY(id), INDEX(user), INDEX(name))'
    );

    foreach($tables as $table_name => $table_structure) {
        $result = mysqli_query($sql_connection, "SELECT * FROM " . $table_name);
        if (!$result) {
            mysqli_query($sql_connection, $table_structure);
        }
    }

    function createAttachment($name, $tmpName, $currentId) {
        if (empty($name)) {
            return $currentId;
        }

        global $sql_connection;
        global $attachment;
        $stmt = mysqli_prepare($sql_connection, "SELECT id FROM attachment WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $currentId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $num_rows = mysqli_stmt_num_rows($stmt);
        if ($num_rows == 1) {
            deleteAttachment($currentId);
        }

        $attachment_id = getUUID();
        $filedata = NULL;
        $file = fopen($tmpName, 'rb');
        $stmt = mysqli_prepare($sql_connection, "INSERT INTO attachment (id, filename, attachment) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssb', $attachment_id, $name, $filedata);
        while (!feof($file)) {
            $chunk = fread($file, 8192);
            mysqli_stmt_send_long_data($stmt, 2, $chunk);
        }
        fclose($file);
        mysqli_stmt_execute($stmt);

        return $attachment_id;
    }

    function deleteAttachment($id) {
        global $sql_connection;
        global $attachment;
        $query = "DELETE FROM attachment WHERE id = ?";
        $stmt = mysqli_prepare($sql_connection, $query);
        mysqli_stmt_bind_param($stmt, 's', $id);
        mysqli_stmt_execute($stmt);
    }

    function endsWith($haystack, $needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($haystack, -$length) === $needle);
    }

    function escapeHTML($string) {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    function getAssignmentMark($assignment, $user, $submission) {
        global $sql_connection;

        if (empty($submission)) {
            $submission = getMarkedSubmissionId($assignment, $user);
        }

        $stmt = mysqli_prepare($sql_connection, "SELECT id FROM testcase WHERE assignment = ? ORDER BY title ASC");
        mysqli_stmt_bind_param($stmt, 's', $assignment);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 0) {
            return -1;
        }
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

        $score = -1;
        foreach ($rows as $row) {
            $stmt = mysqli_prepare($sql_connection, "SELECT score FROM result WHERE submission = ? AND testcase = ? ORDER BY RIGHT(id, 12) DESC LIMIT 1");
            mysqli_stmt_bind_param($stmt, 'ss', $submission, $row['id']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $result_row = mysqli_fetch_assoc($result);
            if (!$result_row) {
                continue;
            }
            if ($score == -1) {
                $score = 0;
            }
            $score += $result_row['score'];
        }

        return $score;
    }

    function getAssignmentWeight($id) {
        global $sql_connection;
        $sum_stmt = mysqli_prepare($sql_connection, 'SELECT SUM(weight) as sum FROM testcase WHERE assignment = ?');
        mysqli_stmt_bind_param($sum_stmt, 's', $id);
        mysqli_stmt_execute($sum_stmt);
        $sum_result = mysqli_stmt_get_result($sum_stmt);
        $sum_row = mysqli_fetch_assoc($sum_result);
        return $sum_row['sum'] ? $sum_row['sum'] : 0;
    }

    function getMarkedSubmissionId($assignment, $user) {
        global $sql_connection;
        $stmt = mysqli_prepare($sql_connection, 'SELECT table1.id FROM submission table1 JOIN result table2 ON table1.id = table2.submission WHERE table1.user = ? AND table1.assignment = ? ORDER BY RIGHT(table1.id, 12) DESC LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'ss', $user, $assignment);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 0){
            return '00000000-0000-0000-0000-000000000000';
        }
        $submission_row = mysqli_fetch_assoc($result);
        return $submission_row['id'];
    }

    function getScoreDistribution($completed_users_info) {
        $score_distribution = array();
        $scores = array();
        foreach ($completed_users_info as $user_info) {
            $score = $user_info['score'];
            if (!in_array($score, $scores)) {
                $scores[] = $score;
            }
            if (!isset($score_distribution[$score])) {
                $score_distribution[$score] = array();
            }
            $score_distribution[$score][] = $user_info['name'].' ('.$user_info['user'].')';
        }
        rsort($scores);
        $sorted_score_distribution = array();
        foreach ($scores as $score) {
            $sorted_score_distribution[$score] = $score_distribution[$score];
        }
        return $sorted_score_distribution;
    }

    function getUserInfo($userId) {
        global $sql_connection;
        $stmt = mysqli_prepare($sql_connection, 'SELECT user, name FROM user WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 's', $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $user, $name);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        return array("id" => $userId, "user" => $user, "name" => $name);
    }

    function getUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%012x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, time());
    }

    function hasCourseAccess($courseId) {
        global $sql_connection;
        global $sso_u;
        global $sso_l;
        $course_access = false;
        $stmt = mysqli_prepare($sql_connection, "SELECT user FROM course WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 's', $courseId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);
            if ($sso_l > 3 || empty($row['user']) || in_array($sso_u, explode(' ', $row['user']))) {
                $course_access = true;
            }
        }
        return $course_access;
    }

    function markAssignment($submissionId) {
        global $sql_connection;
        global $sso_l;

        // retrieve information from submission table
        $stmt = mysqli_prepare($sql_connection, 'SELECT assignment, user, submission FROM submission WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 's', $submissionId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) == 0) {
            return 400;
        }
        mysqli_stmt_bind_result($stmt, $assignment, $user, $submission);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        // retrieve due time from assignment table
        $stmt = mysqli_prepare($sql_connection, 'SELECT due FROM assignment WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 's', $assignment);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) == 0) {
            return 400;
        }
        mysqli_stmt_bind_result($stmt, $due);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        // check if current time is past due time
        if ($due > 1000000000 && time() > $due && $sso_l <= 3) {
            return 400;
        }

        // retrieve attachment from attachment table
        $stmt = mysqli_prepare($sql_connection, 'SELECT attachment FROM attachment WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 's', $submission);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $attachment);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        // save attachment to temporary file
        if ($attachment) {
            $temp_submission_file = tempnam(sys_get_temp_dir(), 'submission_');
            file_put_contents($temp_submission_file, $attachment);
        } else {
            return 400;
        }

        // retrieve sample from assignment table
        $stmt = mysqli_prepare($sql_connection, 'SELECT sample FROM assignment WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 's', $assignment);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $sample_id);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        // retrieve sample attachment from attachment table
        $stmt = mysqli_prepare($sql_connection, 'SELECT attachment FROM attachment WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 's', $sample_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $sample_attachment);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        // save sample attachment to temporary file
        if ($sample_attachment) {
            $temp_sample_file = tempnam(sys_get_temp_dir(), 'sample_');
            file_put_contents($temp_sample_file, $sample_attachment);
        } else {
            $temp_sample_file = '';
        }

        // prepare the query to select rows from 'testcase' table
        $stmt = mysqli_prepare($sql_connection, "SELECT id, weight, routine FROM testcase WHERE assignment = ?");
        mysqli_stmt_bind_param($stmt, "s", $assignment);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $testcase_id, $weight, $routine_id);

        // create an array to hold the data for each row
        $testcase_data = array();

        // loop through the rows and store the data in the array
        while (mysqli_stmt_fetch($stmt)) {
            $testcase_data[] = array(
                'testcase_id' => $testcase_id,
                'weight' => $weight,
                'routine_id' => $routine_id
            );
        }

        // close the first query statement
        mysqli_stmt_close($stmt);

        // no test case found
        if (empty($testcase_data)) {
            return 400;
        }

        // loop through the testcase_data array and process each row
        foreach ($testcase_data as $data) {
            $testcase_id = $data['testcase_id'];
            $weight = $data['weight'];
            $routine_id = $data['routine_id'];

            // prepare the query to select the 'mediumblob' data from 'attachment' table
            $stmt2 = mysqli_prepare($sql_connection, "SELECT attachment FROM attachment WHERE id = ?");
            mysqli_stmt_bind_param($stmt2, "s", $routine_id);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_bind_result($stmt2, $routine_data);
            mysqli_stmt_fetch($stmt2);
            mysqli_stmt_close($stmt2);

            // check if the routine data is valid
            if ($routine_data !== null) {
                // create a temporary file and write the routine data to it
                $temp_routine_file = tempnam(sys_get_temp_dir(), 'routine_');
                file_put_contents($temp_routine_file, $routine_data);
            } else {
                // if routine data is not valid, set the file path to null
                $temp_routine_file = null;
                continue;
            }

            // process the test case data
            $score = 0;
            $feedback = 'The system is unable to execute the test case, please contact your instructor for assistance.';
            $renderer = '';

            // Check the starting character of $routine_data file
            $routine_content = file_get_contents($temp_routine_file);

            // check the first character of the file content
            $first_char = substr(trim($routine_content), 0, 1);

            // pass state 0 = not assessed , 1 = passed , 2 = failed , 3 = user defined
            $passState = 0;

            if ($first_char === '!') {
                // the routine data contains instructions

                // set default values for $pass and $fail
                $pass = "You have passed the test case.";
                $fail = "You have not passed the test case.";

                // read the file line by line and process each instruction
                $lines = explode("\n", $routine_content);
                foreach ($lines as $line) {
                    // extract the instruction code and content
                    $line = trim($line);
                    if (empty($line)) {
                        // skip empty lines
                        continue;
                    }
                    $pos = strpos($line, " ");
                    if ($pos === false) {
                        // skip lines without a space
                        continue;
                    }
                    $instruction = strtolower(substr($line, 0, $pos));
                    $content = trim(substr($line, $pos));
                    switch (strtolower($instruction)) {
                        case '!pass':
                            $pass = $content;
                            break;
                        case '!fail':
                            $fail = $content;
                            break;
                        case '!renderer':
                            $renderer = $content;
                            break;
                        case '!include':
                            $temp_submission = file_get_contents($temp_submission_file);
                            $temp_submission = preg_replace('/\s+/', '', $temp_submission); // Remove all whitespace
                            $content = preg_replace('/\s+/', '', $content); // Remove all whitespace
                            if (stripos($temp_submission, $content) === false) {
                                // Content not found in submission file
                                $passState = 2;
                            }
                            else {
                                if ($passState == 0) {
                                    $passState = 1;
                                }
                            }
                            break;
                        case '!regex':
                            $temp_submission = file_get_contents($temp_submission_file);
                            $temp_submission = preg_replace('/[\s\n]+/', '', $temp_submission); // Remove all whitespace and newlines
                            if (!preg_match("/$content/i", $temp_submission)) {
                                // Regex pattern not found in submission file
                                $passState = 2;
                            }
                            else {
                                if ($passState == 0) {
                                    $passState = 1;
                                }
                            }
                            break;
                        default:
                            // Skip lines with invalid instruction codes
                            break;
                    }
                }
            } elseif ($first_char === '<') {
                $renderer = 'php';
            } else {
                $renderer = 'node';
            }

            if (!empty($renderer)) {
                try {
                    libxml_use_internal_errors(true);
                    $output = exec("$renderer $temp_routine_file $temp_submission_file $temp_sample_file");
                    $xml = simplexml_load_string($output);
                    $xml_score = (int) $xml->score;
                    if (is_numeric($score)) {
                        $score = $xml_score;
                    }
                    $xml_feedback = (string) $xml->feedback;
                    if (!empty($xml_feedback)) {
                       $feedback = $xml_feedback;
                    }
                } catch (Exception $e) {

                }
            }

            if ($passState == 1) {
                $feedback = $pass;
                $score = $weight;
            } elseif ($passState == 2) {
                $feedback = $fail;
            }

            // delete previous results
            $stmt = mysqli_prepare($sql_connection, 'DELETE table1 FROM result table1 INNER JOIN submission table2 ON table1.submission = table2.id WHERE table2.user = ? AND table1.testcase = ?');
            mysqli_stmt_bind_param($stmt, 'ss', $user, $testcase_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // save new result
            $stmt = mysqli_prepare($sql_connection, 'INSERT INTO result (id, submission, testcase, score, feedback) VALUES (?, ?, ?, ?, ?)');
            $result_id = getUUID();
            mysqli_stmt_bind_param($stmt, 'sssss', $result_id, $submissionId, $testcase_id, $score, $feedback);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // delete the temporary file for the routine data
            if ($temp_routine_file !== null) {
                unlink($temp_routine_file);
            }
        }

        // delete temporary files
        if ($temp_submission_file) {
            unlink($temp_submission_file);
        }
        if ($temp_sample_file) {
            unlink($temp_sample_file);
        }

        return 204;
    }

    function remarkAssignment($assignmentId) {
        global $sql_connection;

        $stmt = mysqli_prepare($sql_connection, 'SELECT DISTINCT user_submissions.id FROM (SELECT submission.id, MAX(RIGHT(submission.id, 12)) as latest_submission_id FROM submission INNER JOIN result ON submission.id = result.submission WHERE submission.assignment = ? GROUP BY submission.user) AS user_submissions GROUP BY user_submissions.latest_submission_id');
        mysqli_stmt_bind_param($stmt, 's', $assignmentId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $submission_id);

        $results = array();
        while (mysqli_stmt_fetch($stmt)) {
            $results[] = $submission_id;
        }
        mysqli_stmt_close($stmt);
        if (empty($results)) {
            return 400;
        }

        foreach ($results as $submission_id) {
            markAssignment($submission_id);
        }
        return 204;
    }

    function sendEmail($address, $subject, $body) {
        global $smtp;
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $smtp[0];
            $mail->Port       = $smtp[1];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp[2];
            $mail->Password   = $smtp[3];
            $mail->setFrom($smtp[4]);
            $mail->addAddress($address);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            return '';
        } catch (Exception $e) {
            return $mail->ErrorInfo;
        }
    }

    function showFooter() {
?>
    </body>
</html>
<?php
    }

    function showHeader() {
        global $sso_u;
        global $sso_n;
        global $sso_l;
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>AutoMarking</title>
        <style>
            a {
                text-decoration: none;
            }

            body {
                font-family: Arial, sans-serif;
            }
            
            .add-btn {
                line-height: 0.5em;
            }

            .description {
                font-size: 18px;
                font-family: Arial, sans-serif;
                white-space: pre-wrap;
            }

            .editor {
                display: block;
                position: relative;
                font-size: 16px;
                min-height: 100px;
                height: 100%;
            }

            .header {
                background-color: #0074d9;
                color: #fff;
                font-size: 18px;
                height: 60px;
                min-width: 960px;
                max-width: 960px;
                margin: 0 auto;
                padding: 0 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .heading {
                display: flex;
                justify-content: space-between;
                align-items: center;
                min-width: 960px;
                max-width: 960px;
                margin: 0 auto;
                padding-left: 20px;
                padding-right: 20px;
                min-height: 10px;
                background-color: #f1f1f1;
            }

            .back-btn {
                padding: 10px 20px;
                font-size: 20px;
                font-weight: bold;
                border: none;
                background-color: #0074d9;
                color: white;
                cursor: pointer;
                transition: background-color 0.2s;
            }

            .back-btn:hover {
                background-color: #0052a5;
            }

            .attachment-btn {
                background-color: #0074d9;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                margin-right: 10px;
                font-size: 20px;
                transition: background-color 0.3s;
            }

            .attachment-btn:hover {
                background-color: #0052a5;
                cursor: pointer;
            }

            .logo {
                height: 50px;
                vertical-align: middle;
            }

            .title {
                font-size: 24px;
                font-weight: bold;
                margin-left: 10px;
                vertical-align: middle;
            }

            .button {
                padding: 12px;
                background-color: #fff;
                color: #002060;
                margin-left: 5px;
                border: none;
                cursor: pointer;
                font-size: 18px;
                font-weight: bold;
                transition: 0.3s;
            }

            .button:hover {
                background-color: #f2f2f2;
                color: purple;
            }

            .username {
                font-size: 16px;
                margin-right: 20px;
                vertical-align: middle;
                display: inline-block;
                max-width: 350px;
                overflow: hidden;
                white-space: nowrap;
                text-overflow: ellipsis;
            }

            .username .bold {
                font-weight: bold;
            }

            #loading-message {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.8);
                z-index: 9999;
                display: none;
                justify-content: center;
                align-items: center;
            }

            #loading-message::after {
                content: "";
                display: block;
                width: 80px;
                height: 80px;
                margin: 10px;
                border-radius: 50%;
                border: 8px solid transparent;
                border-top-color: #fff;
                animation: spin 1.2s linear infinite;
            }

            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        </style>
    </head>
    <body>
        <div id="loading-message"></div>
        <div class="header">
            <div>
                <img src="logo.png" alt="AutoMarking" class="logo">
                <span class="title">AutoMarking</span>
            </div>
            <div>
                <span class="username"><span class="bold"><?php echo $sso_n;?></span><?php if (false) { echo '&nbsp;&nbsp;('; echo ($pos = strpos($sso_u, '@')) ? substr($sso_u, 0, $pos) : $sso_u; echo ')'; } // Party A said that the title bar does not need to show user email. ?></span>
                <button class="button" onclick="location.href='index.php';">Home</button>
                <?php
                    if ($sso_l > 3) {
                        echo '<button class="button" onclick="location.href=\'admin.php\';">Accounts</button>';
                    } else {
                        echo '<button class="button" onclick="location.href=\'user.php\';">My account</button>';
                    }
                ?>
                <button class="button" onclick="location.href='logout.php';">Log out</button>
            </div>
        </div>
<?php
    }

    function startsWith($haystack, $needle)	{
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    function timeToString($timestamp, $defaultString) {
        return ($timestamp <= 1000000000) ? $defaultString : date('d/m/Y H:i', $timestamp);
    }
?>