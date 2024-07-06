<?php
    require_once('common.php');
    require_once('authorization.php');

    showHeader();
?>

<div style="min-width: 1000px; max-width: 1000px; margin: 0 auto;">
    <div style="text-align: center;">
        <h2>My Courses</h2>
    </div>
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between;">
        <?php
            if ($sso_l > 3) {
        ?>
        <div style="flex-basis: calc(100% - 0px); margin-bottom: 20px;">
            <a href="admin.php?management=course">
                <div style="border: 1px solid #ccc; border-radius: 5px; padding: 0px; text-align: center; color: black;">
                    <h3 class="add-btn">+ Add New Course</h3>
                </div>
           </a>
        </div>
        <?php
            }

        $query = "SELECT id, code, description, user FROM course ORDER BY code";
        $result = mysqli_query($sql_connection, $query);
        $course_count = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            // The original design was to say that if the user list does not exist, all users have enrolled in this course by default.
            // However, it was later required by Party A to manually enroll users for each course.
            if ($sso_l > 3 || (false && empty($row['user'])) || in_array($sso_u, explode(' ', $row['user']))) {
                $id = $row['id'];
                $code = $row['code'];
                $description = $row['description'];
                $edit_buttons = '';
                if ($sso_l > 3) {
    $edit_buttons = '<div style="display: flex; justify-content: space-between; margin-top: 10px; margin-left: 20px; margin-right: 20px;">
    <a href="course.php?id=' . $id . '&page=invite" style="flex: 1;">
        <div style="border: 1px solid #ccc; border-radius: 5px; padding: 10px; color: black; text-align: center;">Invite User</div>
    </a>
    <a href="admin.php?management=course&id=' . $id . '" style="flex: 1; margin-left: 10px;">
        <div style="border: 1px solid #ccc; border-radius: 5px; padding: 10px; color: black; text-align: center;">Edit Course</div>
    </a>
</div>';
                }

                echo '<div style="flex-basis: calc(50% - 5px); margin-bottom: 20px;">
        <a href="course.php?id=' . $id . '">
            <div style="border: 1px solid #ccc; border-radius: 5px; padding: 20px; color: black;">
                <h3 style="word-break: keep-all;">' . $code . '</h3>
                <p style="word-break: keep-all;">' . $description . '</p>
            </div>
        </a>' . $edit_buttons . '
    </div>';
                $course_count += 1;
            }
        }

        echo '</div>';

        if ($course_count < 1) {
            echo '<div style="text-align: center;">
        <p style="font-size: 16px;">No courses are currently available.</p>
    </div>';
    }
        echo '</div>';
    showFooter();
?>