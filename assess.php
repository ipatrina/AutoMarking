<?php
    require_once('common.php');
    require_once('authorization.php');

    $id = isset($_GET['id']) ? $_GET['id'] : '00000000-0000-0000-0000-000000000000';

    if (isset($_GET['assignment']) && $sso_l > 3) {
        http_response_code(remarkAssignment($_GET['assignment']));
        exit;
    }

    http_response_code(markAssignment($id));

?>