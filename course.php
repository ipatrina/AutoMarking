<?php
    require_once('common.php');
    require_once('authorization.php');

    $course_access = false;
    $id = isset($_GET['id']) ? $_GET['id'] : '00000000-0000-0000-0000-000000000000';

    $stmt = mysqli_prepare($sql_connection, "SELECT code, user, description FROM course WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 's', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        if ($sso_l > 3 || empty($row['user']) || in_array($sso_u, explode(' ', $row['user']))) {
            $course_access = true;
        }
    }

    if (!$course_access) {
        header('Location: index.php');
        exit;
    }

    showHeader();


    echo '<div class="heading" style="margin-top: 5px";>';
    echo '<button class="back-btn" onclick="location.href=\'index.php\';"><</button>';
    echo '<h1 style="margin-left: 20px; font-size: 24px; font-weight: bold; flex: 1;">' . $row["code"] . '</h1>';
    echo '</div>';

    if (isset($_GET['page'])) {
        if ($_GET['page'] == 'invite' && $sso_l > 3) {
?>
<div style="display: flex; flex-direction: column; height: 100%; min-width: 1000px; max-width: 1000px; margin: 0 auto; margin-top: 2px; margin-bottom: 2px;">
    <div style="display: flex; align-items: center;">
        <h1 style="margin-right: 25px; font-size: 28px;">Invite User to Course</h1>
        <label for="file-input" style="flex-basis: 120px; display: flex; align-items: center; justify-content: center; background-color: #ddd; padding: 10px; border-radius: 5px; cursor: pointer; margin-right: 20px; font-size: 20px;">
            Import CSV
            <input type="file" id="file-input" style="display: none;">
        </label>
        <div style="margin-left: auto;">
            <button id="submit-btn" class="attachment-btn" onclick="location.reload();" style="width: 130px; margin-right: 5px;">Restart</button>
        </div>
    </div>
</div>
<div style="width: 960px; margin: 0 auto; padding: 20px; background-color: #f5f5f5; border: 1px solid #ccc;">
    <label for="user" style="display: block; margin-bottom: 10px; font-size: 18px; font-weight: bold;">Users to be invited:</label>
    <label style="display: block; margin-bottom: 10px; font-size: 16px;">One user per line. Format: "Full name, Email address" (e.g. "John Citizen, john.c@gmail.com")</label>
    <textarea name="user" id="user" rows="15" style="display: block; width: 100%; font-size: 18px; padding: 5px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box; resize: none;"><?php echo isset($user) ? str_replace(' ', "\n", $user) : ''; ?></textarea>

    <div id="invite-controls" style="display: flex; align-items: center; width: 100%;">
        <div style="flex: 1.8;"></div>
        <div style="display: flex; justify-content: center; align-items: center;">
            <input type="button" name="submit" value="Invite" style="background-color: #0074D9; color: white; border: none; padding: 12px 24px; font-size: 20px; border-radius: 3px; cursor: pointer; margin-right: 10px;">
            <label style="display: flex; align-items: center; font-size: 18px; margin-left: 5px;">
                <input type="checkbox" name="send_email" style="margin-right: 5px; align-self: center;">Send invitation email
            </label>
        </div>
        <div style="flex: 1;"></div>
    </div>
</div>
<script>
    const fileInput = document.getElementById('file-input');
    const userTextarea = document.getElementById('user');
    const inviteBtn = document.getElementsByName('submit')[0];
    const sendEmailCheckbox = document.getElementsByName('send_email')[0];
    const inviteControls = document.getElementById('invite-controls');
    const logContainer = document.createElement('div');

    inviteControls.parentNode.insertBefore(logContainer, inviteControls.nextSibling);

    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        const reader = new FileReader();
        reader.onload = (event) => {
            const content = event.target.result.split('\n').slice(1).join('\n');
            userTextarea.value = content;
        };
        reader.readAsText(file);
    });

    inviteBtn.addEventListener('click', () => {
        if (userTextarea.value.trim() === '') {
            alert('Please fill in the users to be invited.');
            return;
        }

        inviteControls.style.display = 'none';

        const logContainer = document.createElement('div');
        inviteControls.parentNode.insertBefore(logContainer, inviteControls.nextSibling);

        const startLog = document.createElement('p');
        startLog.textContent = 'The invitation process has started, please be patient and stay on this page.';
        logContainer.appendChild(startLog);

        const users = userTextarea.value.trim().split('\n');
        const inviteUser = (user) => {
            const [name, email] = user.split(',');
            let resultLog = null;

            const updateLog = (message) => {
                if (!resultLog) {
                    resultLog = document.createElement('p');
                    logContainer.appendChild(resultLog);
                }
                resultLog.textContent += message;
            };

            updateLog(`Inviting ${name.trim()} (${email.trim()}) ...... `);

            const xhr = new XMLHttpRequest();
            xhr.open('GET', `invite.php?course=<?php echo $id; ?>&name=${encodeURIComponent(name.trim())}&email=${encodeURIComponent(email.trim())}&notify=${sendEmailCheckbox.checked}`);

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        const response = xhr.responseXML;
                        const status = response.getElementsByTagName('status')[0].textContent;
                        const message = response.getElementsByTagName('message')[0].textContent;
                        if (status == 'OK') {
                            updateLog(`[ OK ]`);
                        } else {
                            updateLog(`[ ${status}: ${message} ]`);
                        }
                    } else {
                        updateLog(`[ Request error: ${xhr.statusText} ]`);
                    }
                    resultLog = null;
                    if (users.length > 0) {
                        inviteUser(users.shift());
                    } else {
                        updateLog('The invitation process is complete.');
                    }
                } else if (xhr.readyState === 0) {
                    updateLog(`Inviting ${name.trim()} (${email.trim()}) ...... `);
                }
            };

            xhr.send();
        };

        inviteUser(users.shift());
    });
</script>

<?php

            showFooter();
            exit;
        }
    }

if (!empty($row["description"])) {
    echo '<div class="heading">';
    echo '<pre class="description">' . escapeHTML($row["description"]) . '</pre>';
    echo '</div>';
}
?>
<div style="min-width: 1000px; max-width: 1000px; margin: 0 auto; margin-top: 20px;">
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between;">
        <?php
        if ($sso_l > 3) {
        ?>
        <div style="flex-basis: calc(100% - 0px); margin-bottom: 20px;">
            <a href="admin.php?management=assignment&course=<?php echo $id; ?>">
                <div style="border: 1px solid #ccc; border-radius: 5px; padding: 0px; text-align: center; color: black;">
                    <h3 class="add-btn">+ Add New Assignment</h3>
                </div>
            </a>
        </div>
        <?php
        }

        $stmt = mysqli_prepare($sql_connection, "SELECT id, title, available, due FROM assignment WHERE course = ? ORDER BY due DESC, title ASC");
        mysqli_stmt_bind_param($stmt, 's', $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $assignment_count = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            if ($sso_l > 3 || $row['available'] <= 1000000000 || $row['available'] < time()) {
                $edit_button = '';
                if ($sso_l > 3) {
                    $edit_button = '<a href="admin.php?management=assignment&course='.$id.'&id=' . $row['id'] . '"><div style="border: 1px solid #ccc; border-radius: 5px; padding: 10px; color: black; margin-top: 10px; text-align: center;">Edit Assignment</div></a>';
                }
                $mark = '';
                $assignmentMark = getAssignmentMark($row['id'], $sso_i, '');
                $assignmentWeight = getAssignmentWeight($row['id']);
                if ($assignmentMark >= 0) {
                    $mark = '&nbsp;&nbsp;&nbsp;&nbsp;Score: '.$assignmentMark.'/'.$assignmentWeight;
                } elseif ($assignmentWeight > 0) {
                    $mark = '&nbsp;&nbsp;&nbsp;&nbsp;Score: - /'.$assignmentWeight;
                }
                echo '<div style="flex-basis: calc(100% - 0px); margin-bottom: 20px;">
                          <a href="assignment.php?id=' . $row['id'] . '">
                              <div style="border: 1px solid #ccc; border-radius: 5px; padding: 20px;">
                                  <h3 style="word-break: keep-all; color: black;">' . $row['title'] . '</h3>
                                  <p style="word-break: keep-all; color: black;">Due: ' . timeToString($row['due'], "No due date") . '&nbsp;&nbsp;&nbsp;&nbsp;Available: ' . timeToString($row['available'], "No time limit") . $mark . '</p>
                              </div>
                          </a>'.$edit_button.'
                      </div>' ;
                $assignment_count += 1;
            }
        }
        echo '</div>';
        if ($assignment_count < 1) {
            echo '<div style="text-align: center;">
        <p style="font-size: 16px;">No assignments are currently available.</p>
    </div>';
        }
        echo '</div>';

    showFooter();
?>