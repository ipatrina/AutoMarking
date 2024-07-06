<?php
    require_once('common.php');
    require_once('authorization.php');

    $id = isset($_GET['id']) ? $_GET['id'] : '00000000-0000-0000-0000-000000000000';
    $assignmentMark = getAssignmentMark($id, $sso_i, '');
    $assignmentMark_su = -1;

    $su_i = '';
    if ($sso_l > 3) {
        if (isset($_POST['switch_user'])) {
            $user_id = $_POST['user_id'];
            if (!empty($user_id)) {
                $_SESSION['su'] = $user_id;
                $_SESSION['su_assignment'] = $id;
            } else {
                unset($_SESSION['su']);
                unset($_SESSION['su_assignment']);
            }
        }
    }
    if (isset($_SESSION['su'])) {
        if ($_SESSION['su_assignment'] != $id) {
            unset($_SESSION['su']);
            unset($_SESSION['su_assignment']);
        } else {
            $su_i = $_SESSION['su'];
            $stmt = mysqli_prepare($sql_connection, "SELECT user, name FROM user WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 's', $su_i);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $su_u, $su_n);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    $query_i = empty($su_i) ? $sso_i : $su_i;
    $assignmentMark_su = getAssignmentMark($id, $query_i, '');

    $stmt = mysqli_prepare($sql_connection, "SELECT course, title, instruction, available, due, attachment, sample FROM assignment WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 's', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        if (!hasCourseAccess($row['course'])) {
            header('Location: index.php');
            exit;
        }
    }
    else {
        header('Location: index.php');
        exit;
    }
    $assignmentWeight = getAssignmentWeight($id);

    $referer = "location.href='course.php?id=".$row['course']."';";
    $viewas = false;
    if (isset($_GET['page'])) {
        $viewas = true;
        $stmt = mysqli_prepare($sql_connection, 'SELECT id, submission FROM submission WHERE user = ? AND assignment = ? ORDER BY RIGHT(id, 12) DESC LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'ss', $query_i, $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $loadAttachment = false;
        if (mysqli_num_rows($result) == 0){
            $submission_id = '00000000-0000-0000-0000-000000000000';
            $preview_id = $submission_id;
            if (!empty($row["attachment"])) {
                $preview_id = $row["attachment"];
                $loadAttachment = true;
            }
        } else {
            $submission_row = mysqli_fetch_assoc($result);
            if (empty($su_i)) {
                $submission_id = $submission_row['id'];
                $preview_id = $submission_row['submission'];
            } else {
                $submission_id = getMarkedSubmissionId($id, $su_i);
                $stmt = mysqli_prepare($sql_connection, 'SELECT submission FROM submission WHERE id = ?');
                mysqli_stmt_bind_param($stmt, 's', $submission_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 0) {
                    $preview_id = '00000000-0000-0000-0000-000000000000';
                }
                mysqli_stmt_bind_result($stmt, $preview_id);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);
            }
            $loadAttachment = true;
        }

        if ($loadAttachment) {
            $stmt = mysqli_prepare($sql_connection, 'SELECT attachment from attachment WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 's', $preview_id);
            mysqli_stmt_execute($stmt);
            $attachment_result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($attachment_result) > 0) {
                $attachment_row = mysqli_fetch_assoc($attachment_result);
                $attachment_data = $attachment_row['attachment'];
            } else {
                $attachment_data = '';
            }
        }

        if ($_GET['page'] == 'upload') {
            $referer = "location.href='assignment.php?id=".$id."';";

            if (isset($_POST['code'])) {
                if (!empty($su_i)) {
                    http_response_code(204);
                    exit;
                }
                if (strlen($_POST['code']) > 0) {
                    $attachment_id = getUUID();
                    $filename = '';
                    $stmt = mysqli_prepare($sql_connection, 'INSERT INTO attachment (id, filename, attachment) VALUES (?, ?, ?)');
                    $content = $_POST["code"];
                    $null = NULL;
                    mysqli_stmt_bind_param($stmt, 'ssb', $attachment_id, $filename, $null);
                    mysqli_stmt_send_long_data($stmt, 2, $content);
                    mysqli_stmt_execute($stmt);

                    $stmt = mysqli_prepare($sql_connection, 'INSERT INTO submission (id, assignment, user, submission) VALUES (?, ?, ?, ?)');
                    $submission_id = getUUID();
                    mysqli_stmt_bind_param($stmt, 'ssss', $submission_id, $id, $sso_i, $attachment_id);
                    mysqli_stmt_execute($stmt);

                    http_response_code(204);
                } else {
                    http_response_code(400);
                }
                exit;
            }
        } elseif ($_GET['page'] == 'preview') {
            $referer = "location.href='assignment.php?id=".$id."&page=upload';";
        } elseif ($_GET['page'] == 'feedback') {
            $referer = "location.href='assignment.php?id=".$id."';";
        } elseif ($_GET['page'] == 'report') {
            $viewas = false;
            $referer = "location.href='assignment.php?id=".$id."&page=feedback';";
            if ($sso_l < 4) {
                header("Location: assignment.php?id=".$id);
                exit;
            }
        }
    } else {
        $su_i = '';
    }

    showHeader();

echo '<div class="heading" style="margin-top: 5px; display: flex; align-items: center;">';
echo '<button class="back-btn" onclick="'.$referer.'"><</button>';
echo '<h1 style="margin-left: 20px; margin-right: auto; font-size: 24px; font-weight: bold; flex: 1; max-width: 80%; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;">' . $row["title"] . '</h1>';
$score_str = $assignmentMark_su.'/'.$assignmentWeight;
if ($assignmentMark_su < 0) {
    $score_str = $assignmentWeight.' Possible Points';
}
echo '<div style="border: 2px solid #ccc; border-radius: 10px; padding: 5px 10px; font-size: 24px; font-weight: bold;">'.$score_str.'</div>';
echo '</div>';

if (!empty($su_i) && $viewas) {
    echo '<div style="text-align: center;"><p style="font-size: 18px; margin-top: 10px; margin-bottom: 0px; color: #0052a5;">View as '.$su_n.' ('.$su_u.')</p></div>';
}

if (isset($_GET['page'])) {
    if ($_GET['page'] == 'upload') {
?>
<div style="display: flex; flex-direction: column; height: 100%; min-width: 1000px; max-width: 1000px; margin: 0 auto; margin-top: 2px;">
    <div style="display: flex; align-items: center;">
        <h1 style="margin-right: 25px; font-size: 28px;">Upload Submission</h1>
        <label for="file-input" style="flex-basis: 110px; display: flex; align-items: center; justify-content: center; background-color: #ddd; padding: 10px; border-radius: 5px; cursor: pointer; margin-right: 20px; font-size: 20px;">
            Import File
            <input type="file" id="file-input" style="display: none;">
        </label>
        <label id="file-output" style="margin-left: auto; flex-basis: 110px; display: flex; align-items: center; justify-content: center; background-color: #ddd; padding: 10px; border-radius: 5px; cursor: pointer; margin-right: 12px; font-size: 20px;">
            Save File
        </label>
        <div style="">
            <button id="submit-btn" class="attachment-btn" style="width: 220px; margin-right: 5px;">Save and Preview</button>
        </div>
    </div>
    <div style="flex-grow: 1; overflow: auto;">
        <div id="code-editor" class="editor"></div>
    </div>
</div>

<?php echo $ace; ?>
<script>
    var beforeUnloadHandler = function(event) {
        event.preventDefault();
        event.returnValue = '';
    };

    window.addEventListener('beforeunload', beforeUnloadHandler);

    const editorEl = document.querySelector('.editor');
    const editor = window.ace.edit(editorEl);
    editor.setTheme(`ace/theme/monokai`);
    editor.session.setMode(`ace/mode/html`);

    const fileInput = document.getElementById('file-input');
    const fileOutput = document.getElementById('file-output');
    const codeEditor = document.getElementById('code-editor');

    fileInput.addEventListener('change', (event) => {
        const file = event.target.files[0];

        if (file.size > 1048576) {
            alert("The file to be imported is too large.");
            return;
        }

        const reader = new FileReader();

        reader.onload = (event) => {
            const text = event.target.result;
            let isTextFile = true;
            for (let i = 0; i < Math.min(8, text.length); i++) {
                if (text.charCodeAt(i) < 0x20 || text.charCodeAt(i) > 0x7E) {
                    isTextFile = false;
                    break;
                }
            }

            if (!isTextFile) {
                alert("The file to be imported is not a text file of code.");
                return;
            }

            editor.session.setValue(text);
        };

        reader.readAsText(file);
    });

    fileOutput.addEventListener('click', () => {
        const code = editor.session.getValue();
        const fileName = window.prompt('File name to be saved:', '');
        if (fileName) {
            const blob = new Blob([code], { type: 'attachment/octet-stream' });
            const downloadLink = document.createElement('a');
            downloadLink.href = URL.createObjectURL(blob);
            downloadLink.download = fileName;
            downloadLink.click();
        }
    });

    function b64DecodeUnicode(str) {
        return decodeURIComponent(atob(str).split('').map(function(c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));
    }

    function setElementHeight() {
        const windowHeight = window.innerHeight;
        const newHeight = windowHeight - 240;
        editor.container.style.height = newHeight + 'px';
        editor.resize();
    }

    setElementHeight();
    window.addEventListener('resize', setElementHeight);

    var submitBtn = document.getElementById("submit-btn");
    var isRequestSent = false;
    submitBtn.addEventListener("click", function() {
        if (isRequestSent) {
            return;
        }
        isRequestSent = true;
        document.getElementById("loading-message").style.display = "flex";
        var code = editor.getValue();
        var xhr = new XMLHttpRequest();
        xhr.open("POST", window.location.href, true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                document.getElementById("loading-message").style.display = "none";
                if (xhr.status === 204) {
                    window.removeEventListener('beforeunload', beforeUnloadHandler);
                    window.location.href = "<?php echo 'assignment.php?id='.$id.'&page=preview'; ?>";
                } else {
                    setTimeout(function() {
                        alert("Upload submission failed!");
                    }, 50);
                }
                isRequestSent = false;
            }
        };
        xhr.send("code=" + encodeURIComponent(code));
    });

<?php
    if (!empty($attachment_data)) {
        echo "try { editor.session.setValue(b64DecodeUnicode('".base64_encode($attachment_data)."')); } catch (error) { }";
    }
?>
</script>
<?php
        showFooter();
        exit;
    } elseif ($_GET['page'] == 'preview') {
?>
<div style="display: flex; flex-direction: column; height: 100%; min-width: 1000px; max-width: 1000px; margin: 0 auto; margin-top: 2px;">
    <div style="display: flex; align-items: center;">
        <h1 style="margin-right: 25px; font-size: 28px;">Preview Submission</h1>
        <div style="margin-left: auto;">
            <button id="submit-btn" class="attachment-btn" style="width: 220px; margin-right: 5px;">Submit Assignment</button>
        </div>
    </div>
    <iframe id="myIframe" src="<?php echo 'preview.php?id='.$preview_id; ?>"></iframe>
</div>

<script>
    function setElementHeight() {
        const windowHeight = window.innerHeight;
        const newHeight = windowHeight - 240;
        document.getElementById("myIframe").style.height = newHeight + 'px';
    }

    setElementHeight();
    window.addEventListener('resize', setElementHeight);

    var submitBtn = document.getElementById("submit-btn");
    var isRequestSent = false;
    submitBtn.addEventListener("click", function() {
        if (isRequestSent) {
            return;
        }
        isRequestSent = true;
        document.getElementById("loading-message").style.display = "flex";
        var xhr = new XMLHttpRequest();
        var url = "assess.php?id=<?php echo $submission_id; ?>";
        xhr.open("GET", url, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                document.getElementById("loading-message").style.display = "none";
                if (xhr.status === 204) {
                    window.location.href = "<?php echo 'assignment.php?id='.$id.'&page=feedback'; ?>";
                } else if (xhr.status === 403) {
                    setTimeout(function() {
                        alert("You cannot submit an overdue assignment!");
                    }, 50);
                } else {
                    setTimeout(function() {
                        alert("The assignment cannot be submitted at this time, please contact your instructor for assistance.");
                    }, 50);
                }
                isRequestSent = false;
            }
        };
        xhr.send();
    });
</script>
<?php
        showFooter();
        exit;
    } elseif ($_GET['page'] == 'feedback') {
?>
<div style="display: flex; flex-direction: column; height: 100%; min-width: 1000px; max-width: 1000px; margin: 0 auto; margin-top: 2px; margin-bottom: 40px;">
    <div style="display: flex; align-items: center;">
        <h1 style="margin-right: 25px; font-size: 28px;">Review Feedback</h1>
        <div style="display: flex; align-items: center; margin-left: auto;">
            <div id="status" style="display: none; color: red; font-size: 24px; font-weight: bold; margin-right: 24px;">SUBMITTED</div>
            <div id="score-legacy" style="display: none; color: black; font-size: 18px; font-weight: bold; margin-right: 24px;">
                <span id="scorevalue-legacy" style="background-color: #ccc; border-radius: 10px; padding: 5px 10px; font-size: 24px;">0/<?php echo $assignmentWeight; ?></span>
            </div>
            <button id="submit-btn" class="attachment-btn" onclick="location.href='assignment.php?id=<?php echo $id; ?>&page=upload';" style="width: 148px;">Try Again</button>
        </div>
    </div>

<?php
        if ($sso_l > 3) {
            $stmt = mysqli_prepare($sql_connection, 'SELECT DISTINCT table1.id, table1.user, table1.name FROM user table1 JOIN submission table2 ON table1.id = table2.user JOIN result table3 ON table2.id = table3.submission WHERE table2.assignment = ?');
            mysqli_stmt_bind_param($stmt, 's', $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            echo '<div style="margin-bottom: 20px; margin-right: 10px;">';
            echo '<form action="" method="POST">';
            echo '<select id="users" name="user_id" style="float: left; width: 33%; height: 40px; font-size: 20px; padding: 5px; margin-right: 10px; border: 1px solid #ccc; border-radius: 3px;">';
            echo '<option value="">Current user</option>';
            while ($row = mysqli_fetch_assoc($result)) {
                $name = $row['name'];
                $user = $row['user'];
                $marked_id = $row['id'];
                $option_text = "$name ($user)";
                echo "<option value=\"$marked_id\">$option_text</option>";
            }
            echo '</select>';
            echo '<button type="submit" name="switch_user" style="float: left; width: 22%; height: 40px; display: flex; align-items: center; justify-content: center; background-color: #ddd; padding: 10px; border-radius: 5px; cursor: pointer; font-size: 20px; border: none; margin-right: 10px;">Switch to User View</button>';
            echo '<button type="button" onclick="location.href=\'assignment.php?id='.$id.'&page=report\';" style="float: right; width: 15%; height: 40px; display: flex; align-items: center; justify-content: center; background-color: #ddd; padding: 10px; border-radius: 5px; cursor: pointer; font-size: 20px; border: none;">View Report</button>';
            echo '<div style="clear: both;"></div>';
            echo '</form>';
            echo '</div>';
        }

        $stmt = mysqli_prepare($sql_connection, "SELECT id, title, weight FROM testcase WHERE assignment = ? ORDER BY title ASC");
        mysqli_stmt_bind_param($stmt, 's', $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $outputCount = 0;

        $submission_id_marked = getMarkedSubmissionId($id, $query_i);

        echo '<table style="border-collapse: collapse; border: 2px solid black; width: 100%;">';
        echo '<thead><tr><th style="text-align: center; font-size: 20px; padding: 8px; border: 1px solid black; background-color: #f2f2f2;">View Rubric</th></tr></thead>';
        echo '<tbody>';

        foreach ($rows as $row) {
            $stmt = mysqli_prepare($sql_connection, "SELECT score, feedback FROM result WHERE submission = ? AND testcase = ? ORDER BY RIGHT(id, 12) DESC LIMIT 1");
            mysqli_stmt_bind_param($stmt, 'ss', $submission_id_marked, $row['id']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $result_row = mysqli_fetch_assoc($result);
            if (!$result_row) {
                continue;
            }

            echo '<tr>';
            echo '<td style="text-align: left; padding: 12px; border: 1px solid black;">';
            echo '<div style="display: flex; justify-content: space-between; align-items: center;">
              <h3 style="word-break: keep-all; color: black; margin: 0; font-size: 18px;">Test Case ' . $row['title'] . '</h3>
              <div style="border: 2px solid #ccc; border-radius: 10px; padding: 5px 10px; font-size: 24px; font-weight: bold;">' . $result_row['score'] . '/' . $row['weight'] . '</div>
           </div>
           <p style="word-break: keep-all; color: black; margin-top: 8px; margin-bottom: 8px; font-size: 16px;">' . htmlentities($result_row['feedback']) . '</p>';
            echo '</td>';
            echo '</tr>';
            $outputCount += 1;
        }

        if ($outputCount < 1) {
            echo '<tr><td><div style="text-align: center;"><p style="word-break: keep-all; color: black; margin-top: 20px; margin-bottom: 20px; font-size: 16px;">No results were found for your assignment.</p></div></td></tr>';
        }

        echo '</tbody>';
        echo '</table>';
?>
</div>
<script>
    const status = document.getElementById('status');
    const assignmentMark = <?php echo $assignmentMark_su; ?>;
    const assignmentWeight = <?php echo $assignmentWeight; ?>;

    if (assignmentMark >= 0) {
        // document.getElementById('scorevalue').textContent = `${assignmentMark}/${assignmentWeight}`;
        if (assignmentMark >= assignmentWeight) {
            status.style.color = 'green';
            status.textContent = 'PASSED';
        }
        status.style.display = 'block';
        // document.getElementById('score').style.display = 'block';
    }
</script>
<?php
        showFooter();
        exit;
    } elseif ($_GET['page'] == 'report') {
?>
<div style="display: flex; flex-direction: column; height: 100%; min-width: 1000px; max-width: 1000px; margin: 0 auto; margin-top: 2px; margin-bottom: 30px;">
    <div style="display: flex; align-items: center;">
        <h1 style="margin-right: 25px; font-size: 28px;">Assignment Summary</h1>
        <div style="margin-left: auto;">
            <button id="toggle" class="attachment-btn" style="width: 150px; margin-right: 10px;">View Details</button>
        </div>
    </div>
    <div>
<?php
        echo '<table style="border-collapse: collapse; border: 2px solid black; width: 100%;">';
        echo '<thead><tr><th style="text-align: center; font-size: 20px; padding: 8px; border: 1px solid black; background-color: #f2f2f2;">Statistical Report</th></tr></thead>';
        echo '<tbody>';

        // Get course id
        $sql = "SELECT course FROM assignment WHERE id = ?";
        $stmt = mysqli_prepare($sql_connection, $sql);
        mysqli_stmt_bind_param($stmt, "s", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $course_id);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        // Get course enrolled users
        $user_list = array();
        $sql = "SELECT user FROM course WHERE id = ?";
        $stmt = mysqli_prepare($sql_connection, $sql);
        mysqli_stmt_bind_param($stmt, "s", $course_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $user_string);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        $user_list = array();
        if (!empty($user_string)) {
            $user_list = explode(" ", $user_string);

            // Query the user table to get the corresponding IDs for each username
            $id_list = array();
            foreach ($user_list as $username) {
                $user_id = '';
                $sql = "SELECT id FROM user WHERE user = ? AND level < 2 AND user != ?";
                $stmt = mysqli_prepare($sql_connection, $sql);
                mysqli_stmt_bind_param($stmt, "ss", $username, $sso_admin);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $user_id);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);

                if (!empty($user_id)) {
                    if (!in_array($user_id, $id_list)) {
                        $id_list[] = $user_id;
                    }
                }
            }

            // Replace the usernames in the $user_list array with their corresponding IDs
            $user_list = $id_list;
        } else {
            // The original design was to say that if the user list does not exist, all users have enrolled in this course by default.
            // However, it was later required by Party A to manually enroll users for each course.
            if (false) {
                $sql = "SELECT id FROM user WHERE level < 2 AND user != ? ORDER BY name ASC";
                $stmt = mysqli_prepare($sql_connection, $sql);
                mysqli_stmt_bind_param($stmt, "s", $sso_admin);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $username);
                while (mysqli_stmt_fetch($stmt)) {
                    $user_list[] = $username;
                }
                mysqli_stmt_close($stmt);
            }
        }

        if (empty($user_list)) {
            $user_list = array();
        }

        $completed_users = array();
        $not_completed_users = array();
        foreach ($user_list as $user_id) {
            $mark = getAssignmentMark($id, $user_id, '');
            if ($mark >= 0) {
                $completed_users[] = $user_id;
            } else {
                $not_completed_users[] = $user_id;
            }
        }

        $completion_rate = 0;
        if (count($user_list) > 0) {
            $completion_rate = round((count($completed_users) / count($user_list)) * 100, 1);
        }

        echo '<tr><td style="text-align: left; padding: 12px; border: 1px solid black;"><div style="display: flex; justify-content: space-between; align-items: center;">';
        echo '<h3 style="word-break: keep-all; color: black; margin: 0; font-size: 18px;">Completion Status</h3>';
        echo '<div style="border: 2px solid #ccc; border-radius: 10px; padding: 5px 10px; font-size: 24px; font-weight: bold;">' . count($completed_users) . '/' . count($user_list) . '</div>';
        echo '</div><p style="word-break: keep-all; color: black; margin-top: 8px; margin-bottom: 8px; font-size: 16px;">The current assignment has been completed by '.$completion_rate.'% of users.</p>';

        echo '<div class="user-list" style="display: none;">';
        if (count($completed_users) > 0) {
            echo '<h4 style="word-break: keep-all; color: black; margin-top: 0; margin-bottom: 8px; font-size: 16px;">Completed Users:</h4><ul style="margin-top: 0; margin-bottom: 0;">';
            foreach ($completed_users as $user) {
                $user_info = getUserInfo($user);
                echo '<li>' . $user_info['name'] . ' (' . $user_info['user'] . ')</li>';
            }
            echo '</ul>';
        }

        if (count($not_completed_users) > 0) {
            echo '<h4 style="word-break: keep-all; color: black; margin-top: 8px; margin-bottom: 8px; font-size: 16px;">Uncompleted Users:</h4><ul style="margin-top: 0; margin-bottom: 0;">';
            foreach ($not_completed_users as $user) {
                $user_info = getUserInfo($user);
                echo '<li>' . $user_info['name'] . ' (' . $user_info['user'] . ')</li>';
            }
            echo '</ul>';
        }

        echo '</div></td></tr>';

        $completed_users_info = array();
        $scores = array();

        foreach ($completed_users as $user) {
            $user_info = getUserInfo($user);
            $mark = getAssignmentMark($id, $user, '');
            if ($mark < 0) {
                $mark = 0;
            }
            $completed_users_info[] = array(
                'user' => $user_info['user'],
                'name' => $user_info['name'],
                'score' => $mark
            );
            $scores[] = $mark;
        }

        $pass_count = 0;
        foreach ($completed_users_info as $user_info) {
            if ($user_info['score'] >= $assignmentWeight) {
                $pass_count++;
            }
        }

        $pass_rate = 0;
        if (count($completed_users) > 0) {
            $pass_rate = round(($pass_count / count($completed_users)) * 100, 1);
        }

        $pass_rate_all = 0;
        if (count($user_list) > 0) {
            $pass_rate_all = round(($pass_count / count($user_list)) * 100, 1);
        }

        $highest_score = empty($scores) ? 0 : max($scores);
        $lowest_score = empty($scores) ? 0 : min($scores);
        $average_score = count($scores) <= 0 ? 0 : round(array_sum($scores) / count($scores), 1);

        echo '<tr><td style="text-align: left; padding: 12px; border: 1px solid black;"><div style="display: flex; justify-content: space-between; align-items: center;">';
        echo '<h3 style="word-break: keep-all; color: black; margin: 0; font-size: 18px;">Pass Rate</h3>';
        echo '<div style="border: 2px solid #ccc; border-radius: 10px; padding: 5px 10px; font-size: 24px; font-weight: bold;">' . $pass_count . '/' . count($completed_users) . '</div>';
        echo '</div><p style="word-break: keep-all; color: black; margin-top: 8px; margin-bottom: 8px; font-size: 16px;">The current assignment has a pass rate of '.$pass_rate.'%. The pass rate for users who have not completed the assignment included is '.$pass_rate_all.'%.</p>';
        echo '</td></tr>';


        echo '<tr><td style="text-align: left; padding: 12px; border: 1px solid black;"><div style="display: flex; justify-content: space-between; align-items: center;">';
        echo '<h3 style="word-break: keep-all; color: black; margin: 0; font-size: 18px;">Average Score</h3>';
        echo '<div style="border: 2px solid #ccc; border-radius: 10px; padding: 5px 10px; font-size: 24px; font-weight: bold;">' . $average_score . '/' . $assignmentWeight . '</div>';
        echo '</div><p style="word-break: keep-all; color: black; margin-top: 8px; margin-bottom: 8px; font-size: 16px;">The average score is '.$average_score.'. The highest score is '.$highest_score.'. The lowest score is '.$lowest_score.'.</p>';
        echo '<div class="user-list" style="display: none;">';

        if (count($completed_users) > 0) {
            $score_distribution = getScoreDistribution($completed_users_info);
            echo '<h4 style="word-break: keep-all; color: black; margin-top: 8px; margin-bottom: 8px; font-size: 16px;">Score Distribution:</h4><ul style="margin-top: 0; margin-bottom: 0;">';
            foreach ($score_distribution as $score => $users) {
                echo '<li>' . $score . ': ' . implode(', ', $users) . '</li>';
            }
            echo '</ul>';
        }
        echo '</div></td></tr>';

        $stmt = mysqli_prepare($sql_connection, "SELECT id, title, weight FROM testcase WHERE assignment = ? ORDER BY title ASC");
        mysqli_stmt_bind_param($stmt, 's', $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

        foreach ($rows as $row) {
            $completed_users_info = array();
            $scores = array();

            foreach ($completed_users as $user) {
                $user_info = getUserInfo($user);
                $mark = 0;

                $stmt = mysqli_prepare($sql_connection, 'SELECT score FROM result WHERE submission IN (SELECT id FROM submission WHERE assignment IN (SELECT assignment FROM testcase WHERE id = ?) AND user = ?) AND testcase = ? ORDER BY RIGHT(id, 12) DESC LIMIT 1');
                mysqli_stmt_bind_param($stmt, 'sss', $row["id"], $user, $row["id"]);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $mark);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);

                $completed_users_info[] = array(
                    'user' => $user_info['user'],
                    'name' => $user_info['name'],
                    'score' => $mark
                );
                $scores[] = $mark;
            }

            $pass_count = 0;
            foreach ($completed_users_info as $user_info) {
                if ($user_info['score'] >= $row["weight"]) {
                    $pass_count++;
                }
            }

            $pass_rate = 0;
            if (count($completed_users) > 0) {
                $pass_rate = round(($pass_count / count($completed_users)) * 100, 1);
            }

            $pass_rate_all = 0;
            if (count($user_list) > 0) {
                $pass_rate_all = round(($pass_count / count($user_list)) * 100, 1);
            }

            $highest_score = empty($scores) ? 0 : max($scores);
            $lowest_score = empty($scores) ? 0 : min($scores);
            $average_score = count($scores) <= 0 ? 0 : round(array_sum($scores) / count($scores), 1);

            echo '<tr><td style="text-align: left; padding: 12px; border: 1px solid black;"><div style="display: flex; justify-content: space-between; align-items: center;">';
            echo '<h3 style="word-break: keep-all; color: black; margin: 0; font-size: 18px;">Test Case ' . $row['title'] . '</h3>';
            echo '<div style="border: 2px solid #ccc; border-radius: 10px; padding: 5px 10px; font-size: 24px; font-weight: bold;">' . $average_score . '/' . $row["weight"] . '</div>';
            echo '</div><p style="word-break: keep-all; color: black; margin-top: 8px; margin-bottom: 8px; font-size: 16px;">This Test Case has a pass rate of '.$pass_rate.'%. The pass rate for users who have not completed the assignment included is '.$pass_rate_all.'%.</p>';
            echo '</div><p style="word-break: keep-all; color: black; margin-top: 8px; margin-bottom: 8px; font-size: 16px;">'.$pass_count.' out of '.count($completed_users).' users passed this Test Case. The average score is '.$average_score.'. The highest score is '.$highest_score.'. The lowest score is '.$lowest_score.'.</p>';
            echo '<div class="user-list" style="display: none;">';

            if (count($completed_users) > 0) {
                $score_distribution = getScoreDistribution($completed_users_info);
                echo '<h4 style="word-break: keep-all; color: black; margin-top: 8px; margin-bottom: 8px; font-size: 16px;">Score Distribution:</h4><ul style="margin-top: 0; margin-bottom: 0;">';
                foreach ($score_distribution as $score => $users) {
                    echo '<li>' . $score . ': ' . implode(', ', $users) . '</li>';
                }
                echo '</ul>';
            }
            echo '</div></td></tr>';
        }

        echo '</tbody></table>';

        ?>
    </div>
</div>

<script>
    var toggleAllBtn = document.getElementById("toggle");
    var userList = document.querySelectorAll(".user-list");
    var isUserListVisible = [];

    for (var i = 0; i < userList.length; i++) {
        isUserListVisible[i] = false;
    }

    toggleAllBtn.addEventListener("click", function() {
        for (var i = 0; i < userList.length; i++) {
            isUserListVisible[i] = !isUserListVisible[i];
            if (isUserListVisible[i]) {
                userList[i].style.display = "block";
                toggleAllBtn.innerHTML = "Hide Details";
                if (!userList[i].querySelector(":empty")) {
                    userList[i].style.marginTop = "20px";
                }
            } else {
                userList[i].style.display = "none";
                toggleAllBtn.innerHTML = "View Details";
                userList[i].style.marginTop = "0";
            }
        }
    });
</script>
<?php
        showFooter();
        exit;
    }
}

if (!empty($row["instruction"])) {
    echo '<div class="heading">';
    echo '<pre class="description">' . escapeHTML($row['instruction']) . '</pre>';
    echo '</div>';
}

echo '<div style="min-width: 1000px; max-width: 1000px; margin: 0 auto; margin-top: 20px; text-align: center;">';

$outputbutton = false;
if (!empty($row["attachment"])) {
    $outputbutton = true;
    echo '<button class="attachment-btn" onclick="location.href=\'attachment.php?id='.$row['attachment'].'\';">Download Attachment</button>';
}

$overdue = false;
if ($row["due"] < time() && $row["due"] > 1000000000) {
    $overdue = true;
}

if (!empty($row["sample"]) && ($sso_l > 3 || $overdue)) {
    $outputbutton = true;
    echo '<button class="attachment-btn" onclick="location.href=\'attachment.php?id='.$row['sample'].'\';">Download Sample Answer</button>';
}

if ($assignmentMark >= 0) {
    if (!$overdue) {
        $outputbutton = true;
        echo '<button class="attachment-btn" onclick="location.href=\'assignment.php?id='.$id.'&page=upload\';">Retry Assignment</button>';
    }
} else {
    if (!$overdue || $sso_l > 3) {
        $outputbutton = true;
        echo '<button class="attachment-btn" onclick="location.href=\'assignment.php?id='.$id.'&page=upload\';">Start Assignment</button>';
    }
}

if ($assignmentMark >= 0 || $sso_l > 3) {
    $outputbutton = true;
    echo '<button class="attachment-btn" onclick="location.href=\'assignment.php?id='.$id.'&page=feedback\';">Review Feedback</button>';
}

if (!$outputbutton) {
    echo '<p style="font-size: 16px; margin-top: 30px; margin-bottom: 30px;">There are no options available for the current assignment.</p>';
}

    if ($sso_l > 3) {
?>
<div style="flex-basis: calc(100% - 0px); margin-top: 40px; margin-bottom: 10px;">
    <a href="#" onclick="rerunAllTestCase(event)">
        <div style="border: 1px solid #ccc; border-radius: 5px; padding: 0px; text-align: center; color: black;">
            <h3 class="add-btn">🔄 Rerun All Test Case</h3>
        </div>
    </a>
</div>

<?php
        $stmt = mysqli_prepare($sql_connection, "SELECT id, title, weight FROM testcase WHERE assignment = ? ORDER BY title ASC");
        mysqli_stmt_bind_param($stmt, 's', $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $weight = $row['weight'];
            echo '<div style="flex-basis: calc(100% - 0px); text-align: left; margin-top: 10px; margin-bottom: 10px;">
              <a href="admin.php?management=testcase&assignment='.$id.'&id=' . $row['id'] . '" style="display: flex; justify-content: space-between; align-items: center; border: 1px solid #ccc; border-radius: 5px; padding: 20px;">
                  <div style="word-break: keep-all; color: black; font-size: 18px; font-weight: bold;">Test Case ' . $row['title'] . '</div>
                  <div style="color: black; margin-left: 10px; font-size: 18px; font-weight: bold;">
                      <span style="background-color: #ccc; border-radius: 10px; padding: 5px 10px; font-size: 24px;">' . $weight . '/' . $assignmentWeight . '</span>
                  </div>
              </a>
          </div>';
        }
?>
<div style="flex-basis: calc(100% - 0px); margin-top: 10px; margin-bottom: 40px;">
    <a href="admin.php?management=testcase&assignment=<?php echo $id; ?>">
        <div style="border: 1px solid #ccc; border-radius: 5px; padding: 0px; text-align: center; color: black;">
            <h3 class="add-btn">+ Add New Test Case</h3>
        </div>
    </a>
</div>
<script>
    var isRequestSent = false;

    function rerunAllTestCase(event) {
        event.preventDefault();
        if (isRequestSent) {
            return;
        }
        isRequestSent = true;
        document.getElementById("loading-message").style.display = "flex";
        var xhr = new XMLHttpRequest();
        var url = "assess.php?assignment=<?php echo $id; ?>";
        xhr.open("GET", url, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                document.getElementById("loading-message").style.display = "none";
                if (xhr.status === 204) {
                    setTimeout(function() {
                        alert("Rerun all Test Case success.");
                    }, 50);
                } else {
                    setTimeout(function() {
                        alert("Rerun all Test Case failed. At least one test case and user submission are required.");
                    }, 50);
                }
                isRequestSent = false;
            }
        };
        xhr.send();
    }
</script>
<?php
    }

    echo '</div>';
    showFooter();
?>