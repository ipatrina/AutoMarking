<?php
    require_once('common.php');
    require_once('authorization.php');

    // Check if user is Coordinator
    if ($sso_l <= 3) {
        header('Location: index.php');
        exit;
    }

    if(isset($_GET['management'])) {
        // Add or change course
        if ($_GET['management'] == 'course') {
            if (isset($_POST['submit'])) {
                $id = $_POST['id'];
                $code = $_POST['code'];
                $description = $_POST['description'];
                $user = str_replace(["\r\n", "\n", "\r"], " ", $_POST['user']);
                $user = preg_replace('/\s+/', ' ', $user);

                if (empty($code)) {
                    $code = 'Default Course';
                }

                $query = mysqli_prepare($sql_connection, 'SELECT id FROM course WHERE code = ?');
                mysqli_stmt_bind_param($query, 's', $code);
                mysqli_stmt_execute($query);
                mysqli_stmt_store_result($query);
                if (mysqli_stmt_num_rows($query) > 0) {
                    mysqli_stmt_bind_result($query, $existing_id);
                    mysqli_stmt_fetch($query);
                    $id = $existing_id;
                }

                if (empty($id)) {
                    $id = getUUID();
                    $query = mysqli_prepare($sql_connection, 'INSERT INTO course (id, code, description, user) VALUES (?, ?, ?, ?)');
                    mysqli_stmt_bind_param($query, 'ssss', $id, $code, $description, $user);
                } else {
                    $query = mysqli_prepare($sql_connection, 'UPDATE course SET code = ?, description = ?, user = ? WHERE id = ?');
                    mysqli_stmt_bind_param($query, 'ssss', $code, $description, $user, $id);
                }
                mysqli_stmt_execute($query);

                header('Location: index.php');
                exit;
            } elseif (isset($_POST['delete'])) {
                $id = $_POST['id'];
                $query = mysqli_prepare($sql_connection, 'DELETE FROM course WHERE id = ?');
                mysqli_stmt_bind_param($query, 's', $id);
                mysqli_stmt_execute($query);

                header('Location: index.php?id='.$id);
                exit;
            }

            $id = isset($_GET['id']) ? $_GET['id'] : '';
            if (!empty($id)) {
                $query = mysqli_prepare($sql_connection, 'SELECT code, description, user FROM course WHERE id = ?');
                mysqli_stmt_bind_param($query, 's', $id);
                mysqli_stmt_execute($query);
                mysqli_stmt_bind_result($query, $code, $description, $user);
                mysqli_stmt_fetch($query);
            }

            showHeader();
?>
<div style="position: relative; margin: 0 auto; margin-top: 10px; margin-bottom: 10px; min-width: 1000px; max-width: 1000px; height: 50px;">
    <button class="back-btn" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%);" onclick="location.href='index.php';"><</button>
    <h2 style="position: absolute; left: 50%; top: 50%; transform: translateX(-50%) translateY(-50%); text-align: center; margin: 0;">Create or Change Course</h2>
</div>
<div style="width: 960px; margin: 0 auto; padding: 20px; background-color: #f5f5f5; border: 1px solid #ccc;">
    <form method="post" action="">
        <input type="hidden" name="id" value="<?php echo $id; ?>">

        <label for="code" style="display: block; margin-bottom: 10px; font-size: 18px; font-weight: bold;">Course Code</label>
        <input type="text" name="code" id="code" value="<?php echo isset($code) ? $code : ''; ?>" style="display: block; width: 100%; font-size: 20px; padding: 5px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box;">

        <label for="description" style="display: block; margin-bottom: 10px; font-size: 18px; font-weight: bold;">Description</label>
        <textarea name="description" id="description" rows="5" style="display: block; width: 100%; font-size: 18px; padding: 5px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box; resize: none;"><?php echo isset($description) ? $description : ''; ?></textarea>

        <label for="user" style="display: block; margin-bottom: 10px; font-size: 18px; font-weight: bold;">Enrolled users</label>
        <textarea name="user" id="user" rows="10" style="display: block; width: 100%; font-size: 18px; padding: 5px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box; resize: none;"><?php echo isset($user) ? str_replace(' ', "\n", $user) : ''; ?></textarea>

        <div style="text-align: center;">
            <input type="submit" name="submit" value="Submit" style="background-color: #0074D9; color: white; border: none; padding: 12px 24px; font-size: 20px; border-radius: 3px; cursor: pointer; margin-right: 10px; margin-top: 10px;">
            <input type="submit" name="delete" value="Delete" style="background-color: #f44336; color: white; border: none; padding: 12px 24px; font-size: 20px; border-radius: 3px; cursor: pointer; margin-top: 10px;">
        </div>
   </form>
</div>

<?php
        // Add or change assignment
        } elseif ($_GET['management'] == 'assignment') {
            $course = '00000000-0000-0000-0000-000000000000';
            if (isset($_GET['course'])) {
                $course = $_GET['course'];
            }

            if (isset($_POST['submit'])) {
                $id = $_POST['id'];
                $title = $_POST["title"];
                $instruction = $_POST["instruction"];
                $available = strtotime($_POST["available"]);
                $due = strtotime($_POST["due"]);

                if (empty($title)) {
                    $title = 'Default Assignment';
                }

                $attachment_id = '';
                $sample_id = '';

                $query = mysqli_prepare($sql_connection, 'SELECT id FROM assignment WHERE title = ?');
                mysqli_stmt_bind_param($query, 's', $title);
                mysqli_stmt_execute($query);
                mysqli_stmt_store_result($query);
                if (mysqli_stmt_num_rows($query) > 0) {
                    mysqli_stmt_bind_result($query, $existing_id);
                    mysqli_stmt_fetch($query);
                    $id = $existing_id;
                }

                $query = mysqli_prepare($sql_connection, 'SELECT attachment, sample FROM assignment WHERE id = ?');
                mysqli_stmt_bind_param($query, 's', $id);
                mysqli_stmt_execute($query);
                mysqli_stmt_store_result($query);
                if (mysqli_stmt_num_rows($query) > 0) {
                    mysqli_stmt_bind_result($query, $attachment_id, $sample_id);
                    mysqli_stmt_fetch($query);
                }

                if (isset($_FILES["attachment"]["name"])) {
                    $attachment_id = createAttachment($_FILES["attachment"]["name"], $_FILES["attachment"]["tmp_name"], $attachment_id);
                } else {
                    $attachment = null;
                }

                if (isset($_FILES["sample"]["name"])) {
                    $sample_id = createAttachment($_FILES["sample"]["name"], $_FILES["sample"]["tmp_name"], $sample_id);
                } else {
                    $sample = null;
                }

                if (empty($id)) {
                    $id = getUUID();
                    $query = mysqli_prepare($sql_connection, 'INSERT INTO assignment (id, course, title, instruction, available, due, attachment, sample) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    mysqli_stmt_bind_param($query, "ssssiiss", $id, $course, $title, $instruction, $available, $due, $attachment_id, $sample_id);
                } else {
                    $query = mysqli_prepare($sql_connection, 'UPDATE assignment SET course = ?, title = ?, instruction = ?, available = ?, due = ?, attachment = ?, sample = ? WHERE id = ?');
                    mysqli_stmt_bind_param($query, "sssiisss", $course, $title, $instruction, $available, $due, $attachment_id, $sample_id, $id);
                }
                mysqli_stmt_execute($query);

                header('Location: course.php?id='.$course);
                exit;
            } elseif (isset($_POST['delete'])) {
                $id = $_POST['id'];
                $query = mysqli_prepare($sql_connection, 'DELETE FROM assignment WHERE id = ?');
                mysqli_stmt_bind_param($query, 's', $id);
                mysqli_stmt_execute($query);

                header('Location: course.php?id='.$course);
                exit;
            }

            $id = isset($_GET['id']) ? $_GET['id'] : '';
            if (!empty($id)) {
                $query = mysqli_prepare($sql_connection, 'SELECT title, instruction, available, due FROM assignment WHERE id = ?');
                mysqli_stmt_bind_param($query, 's', $id);
                mysqli_stmt_execute($query);
                mysqli_stmt_bind_result($query, $title, $instruction, $available, $due);
                mysqli_stmt_fetch($query);
            }

            showHeader();
?>
<div style="position: relative; margin: 0 auto; margin-top: 10px; margin-bottom: 10px; min-width: 1000px; max-width: 1000px; height: 50px;">
    <button class="back-btn" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%);" onclick="location.href='course.php?id=<?php echo $course; ?>';"><</button>
    <h2 style="position: absolute; left: 50%; top: 50%; transform: translateX(-50%) translateY(-50%); text-align: center; margin: 0;">Create or Change Assignment</h2>
</div>
<div style="width: 960px; margin: 0 auto; padding: 20px; background-color: #f5f5f5; border: 1px solid #ccc;">
    <form method="post" action="" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $id; ?>">

        <label for="title" style="display: block; margin-bottom: 10px; font-size: 18px; font-weight: bold;">Assignment Name</label>
        <input type="text" name="title" id="title" value="<?php echo isset($title) ? $title : ''; ?>" style="display: block; width: 100%; font-size: 20px; padding: 5px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box;">

        <label for="instruction" style="display: block; margin-bottom: 10px; font-size: 18px; font-weight: bold;">Instruction</label>
        <textarea name="instruction" id="instruction" rows="8" style="display: block; width: 100%; font-size: 18px; padding: 5px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box; resize: none; "><?php echo isset($instruction) ? $instruction : ''; ?></textarea>

        <label for="available" style="display: block; margin-bottom: 10px; font-size: 18px; font-weight: bold;">Available date</label>
        <input type="datetime-local" name="available" id="available" value="<?php echo isset($available) ? date('Y-m-d\TH:i', $available) : ''; ?>" style="display: block; width: 100%; font-size: 20px; padding: 5px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box;">

        <label for="due" style="display: block; margin-bottom: 10px; font-size: 18px; font-weight: bold;">Due date</label>
        <input type="datetime-local" name="due" id="due" value="<?php echo isset($due) ? date('Y-m-d\TH:i', $due) : ''; ?>" style="display: block; width: 100%; font-size: 20px; padding: 5px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box;">

        <label for="attachment" style="display: block; margin-bottom: 10px; font-size: 18px; font-weight: bold;">Attachment</label>
        <input type="file" name="attachment" id="attachment" value="<?php echo isset($attachment) ? $attachment : ''; ?>" style="display: block; width: 100%; font-size: 20px; padding: 5px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box;">

        <label for="sample" style="display: block; margin-bottom: 10px; font-size: 18px; font-weight: bold;">Sample Answer</label>
        <input type="file" name="sample" id="sample" value="<?php echo isset($sample) ? $sample : ''; ?>" style="display: block; width: 100%; font-size: 20px; padding: 5px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box;">

        <div style="text-align: center;">
            <input type="submit" name="submit" value="Submit" style="background-color: #0074D9; color: white; border: none; padding: 12px 24px; font-size: 20px; border-radius: 3px; cursor: pointer; margin-right: 10px; margin-top: 10px;">
            <input type="submit" name="delete" value="Delete" style="background-color: #f44336; color: white; border: none; padding: 12px 24px; font-size: 20px; border-radius: 3px; cursor: pointer; margin-top: 10px;">
        </div>
    </form>
</div>

<?php
        // Add or change test case
        } elseif ($_GET['management'] == 'testcase') {
            $assignment = '00000000-0000-0000-0000-000000000000';
            if (isset($_GET['assignment'])) {
                $assignment = $_GET['assignment'];
            }

            if (isset($_POST['submit'])) {
                $id = $_POST['id'];
                $title = $_POST["title"];
                $weight = $_POST["weight"];
                if (is_numeric($weight)) {
                    $weight = intval($weight);
                    $weight = abs($weight);
                } else {
                    $weight = 0;
                }
                $code = $_POST["code"];

                if (empty($title)) {
                    $title = '01 - Default Test Case';
                }

                $query = mysqli_prepare($sql_connection, 'SELECT id FROM testcase WHERE assignment = ? AND title = ?');
                mysqli_stmt_bind_param($query, 'ss', $assignment, $title);
                mysqli_stmt_execute($query);
                mysqli_stmt_store_result($query);
                if (mysqli_stmt_num_rows($query) > 0) {
                    mysqli_stmt_bind_result($query, $existing_id);
                    mysqli_stmt_fetch($query);
                    $id = $existing_id;
                }

                if (strlen($code) > 0) {
                    $attachment_id = getUUID();
                    $filename = '';
                    $stmt = mysqli_prepare($sql_connection, 'INSERT INTO attachment (id, filename, attachment) VALUES (?, ?, ?)');
                    $content = $code;
                    $null = NULL;
                    mysqli_stmt_bind_param($stmt, 'ssb', $attachment_id, $filename, $null);
                    mysqli_stmt_send_long_data($stmt, 2, $content);
                    mysqli_stmt_execute($stmt);

                    if (empty($id)) {
                        $id = getUUID();
                        $query = mysqli_prepare($sql_connection, 'INSERT INTO testcase (id, assignment, title, weight, routine) VALUES (?, ?, ?, ?, ?)');
                        mysqli_stmt_bind_param($query, 'sssis', $id, $assignment, $title, $weight, $attachment_id);
                    } else {
                        $query = mysqli_prepare($sql_connection, 'DELETE table1 FROM attachment table1 INNER JOIN testcase table2 ON table2.routine = table1.id WHERE table2.id = ?');
                        mysqli_stmt_bind_param($query, 's', $id);
                        mysqli_stmt_execute($query);

                        $query = mysqli_prepare($sql_connection, 'UPDATE testcase SET assignment = ?, title = ?, weight = ?, routine = ? WHERE id = ?');
                        mysqli_stmt_bind_param($query, 'ssiss', $assignment, $title, $weight, $attachment_id, $id);
                    }
                    mysqli_stmt_execute($query);
                }

                header('Location: assignment.php?id='.$assignment);
                exit;
            } elseif (isset($_POST['delete'])) {
                $id = $_POST['id'];
                $query = mysqli_prepare($sql_connection, 'DELETE FROM testcase WHERE id = ?');
                mysqli_stmt_bind_param($query, 's', $id);
                mysqli_stmt_execute($query);

                header('Location: assignment.php?id='.$assignment);
                exit;
            }

            $id = isset($_GET['id']) ? $_GET['id'] : '';
            if (!empty($id)) {
                $stmt = mysqli_prepare($sql_connection, 'SELECT assignment, title, weight, routine FROM testcase WHERE id = ?');
                mysqli_stmt_bind_param($stmt, 's', $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);
                mysqli_stmt_bind_result($stmt, $assignment, $title, $weight, $routine);
                mysqli_stmt_fetch($stmt);

                $stmt = mysqli_prepare($sql_connection, 'SELECT attachment FROM attachment WHERE id = ?');
                mysqli_stmt_bind_param($stmt, 's', $routine);
                mysqli_stmt_execute($stmt);
                $attachment_result = mysqli_stmt_get_result($stmt);
                if (mysqli_num_rows($attachment_result) > 0) {
                    $attachment_row = mysqli_fetch_assoc($attachment_result);
                    $attachment_data = $attachment_row['attachment'];
                } else {
                    $attachment_data = '';
                }
            }

            showHeader();
    ?>
    <div style="position: relative; margin: 0 auto; margin-top: 10px; margin-bottom: 10px; min-width: 1000px; max-width: 1000px; height: 50px;">
        <button class="back-btn" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%);" onclick="location.href='assignment.php?id=<?php echo $assignment; ?>';"><</button>
        <h2 style="position: absolute; left: 50%; top: 50%; transform: translateX(-50%) translateY(-50%); text-align: center; margin: 0;">Create or Change Test Case</h2>
    </div>
    <div style="width: 960px; margin: 0 auto; padding: 20px; background-color: #f5f5f5; border: 1px solid #ccc;">
        <form id="form" method="post" action="" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $id; ?>">

            <label for="title" style="display: block; margin-bottom: 10px; font-size: 18px; font-weight: bold;">Title of the Test Case (Recommended format: "Sequence - Description", e.g. "01 - HTML element test")</label>
            <input type="text" name="title" id="title" value="<?php echo isset($title) ? $title : ''; ?>" style="display: block; width: 100%; font-size: 20px; padding: 5px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box;">

            <label for="weight" style="display: block; margin-bottom: 10px; font-size: 18px; font-weight: bold;">Weight (Score of a positive integer)</label>
            <input type="number" name="weight" id="weight" style="display: block; width: 100%; font-size: 20px; padding: 5px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box;" value="<?php echo isset($weight) ? $weight : ''; ?>">

            <label for="template" style="display: block; margin-bottom: 10px; font-size: 18px; font-weight: bold;">Routine Templates (Optional)</label>
            <select name="template" id="template" style="display: block; width: 100%; font-size: 20px; padding: 5px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 3px;" onchange="changeTemplate();">
                <option value="custom">Custom Script</option>
                <option value="keyword">Keyword Inclusion</option>
                <option value="regex">Regular Expression</option>
            </select>

            <div name="code" id="code-editor" class="editor"></div>

            <div style="text-align: center; margin-top: 10px;">
                <input type="submit" name="submit" value="Submit" style="background-color: #0074D9; color: white; border: none; padding: 12px 24px; font-size: 20px; border-radius: 3px; cursor: pointer; margin-right: 10px; margin-top: 10px;">
                <input type="submit" name="delete" value="Delete" style="background-color: #f44336; color: white; border: none; padding: 12px 24px; font-size: 20px; border-radius: 3px; cursor: pointer; margin-top: 10px;">
            </div>
        </form>
    </div>

    <?php echo $ace; ?>
        <script>
            const editorEl = document.querySelector('.editor');
            const editor = window.ace.edit(editorEl);
            editor.setTheme(`ace/theme/monokai`);
            editor.session.setMode(`ace/mode/text`);

            const codeEditor = document.getElementById('code-editor');

            function b64DecodeUnicode(str) {
                return decodeURIComponent(atob(str).split('').map(function(c) {
                    return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                }).join(''));
            }

            function setElementHeight() {
                const windowHeight = window.innerHeight;
                const newHeight = windowHeight - 520;
                editor.container.style.height = newHeight + 'px';
                editor.resize();
            }

            setElementHeight();
            window.addEventListener('resize', setElementHeight);
            
            function changeTemplate() {
                var select = document.getElementById("template");
                if (select.value === "keyword") {
                    editor.setValue('!include <Place your keyword here.>\n!pass <Put feedback on passing the test case here.>\n!fail <Put feedback on failing the test case here.>\n\nRemove the angle brackets and this line of prompt text.');
                } else if (select.value === "regex") {
                    editor.setValue('!regex <Place your regular expression here.>\n!pass <Put feedback on passing the test case here.>\n!fail <Put feedback on failing the test case here.>\n\nRemove the angle brackets and this line of prompt text.');
                } else {
                    editor.setValue('');
                }
            }

            document.getElementById("form").addEventListener("submit", function () {
                var code = editor.getValue();
                var hiddenInput = document.createElement("input");
                hiddenInput.setAttribute("type", "hidden");
                hiddenInput.setAttribute("name", "code");
                hiddenInput.setAttribute("value", code);
                this.appendChild(hiddenInput);
            });
        <?php
            if (!empty($attachment_data)) {
                echo "try { editor.session.setValue(b64DecodeUnicode('".base64_encode($attachment_data)."')); } catch (error) { }";
            }
        ?>
        </script>

        <?php
        }


        showFooter();
        exit;
    }

// User management
if(isset($_POST['user_management_action'])) {
    $action = $_POST['user_management_action'];
    $notify = false;
    if ($action == 'remove_notify') {
        $notify = true;
        $action = 'remove';
    }
    $ids = isset($_POST['id']) ? $_POST['id'] : [];
    switch ($action) {
        case 'set_role_student':
            foreach ($ids as $id) {
                $sql = 'UPDATE user SET level = 1 WHERE id = ?';
                $stmt = mysqli_prepare($sql_connection, $sql);
                mysqli_stmt_bind_param($stmt, 's', $id);
                mysqli_stmt_execute($stmt);
            }
            break;
        case 'set_role_coordinator':
            foreach ($ids as $id) {
                $sql = 'UPDATE user SET level = 4 WHERE id = ?';
                $stmt = mysqli_prepare($sql_connection, $sql);
                mysqli_stmt_bind_param($stmt, 's', $id);
                mysqli_stmt_execute($stmt);
            }
            break;
        case 'remove':
            foreach ($ids as $id) {
                $notifyUser = false;
                $user_info = getUserInfo($id);
                if ($sso_l > 4) {
                    $sql = "DELETE FROM user WHERE id = ?";
                    $stmt = mysqli_prepare($sql_connection, $sql);
                    mysqli_stmt_bind_param($stmt, 's', $id);
                    mysqli_stmt_execute($stmt);
                    $row_count = mysqli_stmt_affected_rows($stmt);
                    if ($row_count > 0 && $notify) {
                        $notifyUser = true;
                    }
                } else {
                    $sql = "DELETE FROM user WHERE id = ? AND user != ? AND level < 4";
                    $stmt = mysqli_prepare($sql_connection, $sql);
                    mysqli_stmt_bind_param($stmt, 'ss', $id, $sso_admin);
                    mysqli_stmt_execute($stmt);
                    $row_count = mysqli_stmt_affected_rows($stmt);
                    if ($row_count > 0 && $notify) {
                        $notifyUser = true;
                    }
                    if ($row_count == 0) {
                        $alertMessage = 1;
                    }
                }
                if ($notifyUser) {
                    $user_info_name = $user_info['name'];
                    $subject = '[AutoMarking] User notification';
                    $message = <<<EOD
<html>
    <body>
        <div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f5f5f5; text-align: left;">
            <h2 style="text-align: center; font-size: 20px;">AutoMarking</h2>
            <p style="margin: 0; padding: 0; text-align: left; font-size: 16px;">Hello $user_info_name. Your instructor $sso_n has canceled your account from the AutoMarking system. You will not be able to access your account and data from now. If in doubt, please contact your instructor for assistance.</p>
        </div>
    </body>
</html>
EOD;
                    $notifyResult = sendEmail($user_info['user'], $subject, $message);
                }
            }
            break;
        }
    }

    showHeader();

    if (isset($alertMessage)) {
        echo "<script>alert('You cannot remove users with the same or higher privileges as you.');</script>";
    }
?>
<div style="position: relative; margin: 0 auto; margin-top: 10px; margin-bottom: 10px; min-width: 1000px; max-width: 1000px; height: 50px;">
    <button class="back-btn" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%);" onclick="location.href='index.php';"><</button>
    <h2 style="position: absolute; left: 50%; top: 50%; transform: translateX(-50%) translateY(-50%); text-align: center; margin: 0;">User Management</h2>
</div>
<div style="width: 1000px; margin: 0 auto;">
       <form action="" method="post">
           <div style="display: flex; align-items: center; justify-content: flex-end; margin-bottom: 10px;">
               <select name="user_management_action" style="margin-right: 10px; font-size: 22px; width: 250px; height: 30px;">
                <option value="set_role_student">Set role to Student</option>
                <option value="set_role_coordinator">Set role to Coordinator</option>
                <option value="remove">Remove user</option>
                <option value="remove_notify">Remove user (notify)</option>
               </select>
               <input type="submit" value="Submit" style="padding: 5px 10px; border-radius: 5px; background-color: #0074D9; color: white; border: none; font-size: 20px; width: 86px; height: 34px;">
           </div>
           <table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd; table-layout: fixed;">
               <colgroup>
                   <col style="width: 10%;">
                   <col style="width: 24%;">
                   <col style="width: 35%;">
                   <col style="width: 17%;">
               </colgroup>
               <thead>
                   <tr style="background-color: #f2f2f2; border-bottom: 2px solid #ddd;">
                       <th style="padding: 10px; text-align: center;"><input type="checkbox" id="select-all"></th>
                       <th style="padding: 10px; text-align: left;">Full Name</th>
                       <th style="padding: 10px; text-align: left;">Email Address</th>
                       <th style="padding: 10px; text-align: left;">Role</th>
                       <th style="padding: 10px; text-align: center;">Action</th>
                   </tr>
               </thead>
               <tbody>
                   <?php
                   $sql = 'SELECT id, user, name, level FROM user ORDER BY level DESC, name ASC';
                   $stmt = mysqli_prepare($sql_connection, $sql);
                   mysqli_stmt_execute($stmt);
                   mysqli_stmt_bind_result($stmt, $id, $user, $name, $level);

                   while (mysqli_stmt_fetch($stmt)) {
                       $level_display = ($level > 3) ? 'Coordinator' : 'Student';
                       if ($user == $sso_admin) {
                           $level_display = 'Administrator';
                       }
                       echo "<tr style='border-bottom: 1px solid #ddd;'>";
                       echo "<td style='padding: 10px; text-align: center;'><input type='checkbox' name='id[]' value='$id'></td>";
                       echo "<td style='padding: 10px; text-align: left;'>$name</td>";
                       echo "<td style='padding: 10px; text-align: left;'>$user</td>";
                       echo "<td style='padding: 10px; text-align: left;'>$level_display</td>";
                       echo "<td style='padding: 10px; text-align: center;'><a href='user.php?id=$id' style='text-decoration: none; color: #333; border: 1px solid #ccc; padding: 5px 20px; border-radius: 5px;'>Edit</a></td>";
                       echo "</tr>";
                   }
                   ?>
               </tbody>
           </table>
    </form>
</div>

<script>
    var selectAll = document.getElementById("select-all");
    var checkboxes = document.getElementsByName("id[]");
    selectAll.addEventListener("click", function(){
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = selectAll.checked;
        }
    });
    </script>

    <?php
    showFooter();
    ?>