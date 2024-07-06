<?php
    ini_set('display_errors', true);
    error_reporting(E_ALL);

    require_once('common.php');

    function safe_eval($codeString) {
        $dangerousFuncs = array(
            'exec', 
            'system', 
            'passthru', 
            'shell_exec', 
            'include', 
            'include_once', 
            'require', 
            'require_once', 
            'file_get_contents', 
            'readfile', 
            'curl_exec', 
            'curl_multi_exec', 
            'socket_accept', 
            'socket_bind', 
            'socket_connect', 
            'socket_create', 
            'socket_listen', 
            'socket_read', 
            'socket_recv', 
            'socket_recvfrom', 
            'socket_select', 
            'socket_send', 
            'socket_sendto', 
            'socket_write', 
            'stream_socket_accept', 
            'stream_socket_client', 
            'stream_socket_server', 
            'putenv', 
            'header', 
            'mail', 
            'imap_open', 
            'pop3_open', 
            'ldap_bind', 
            'ldap_search', 
            'mysqli', 
            'mysqli_query', 
            'mysqli_prepare', 
            'mysqli_real_query',
            'mysqli_master_query',
            'mysqli_send_query',
            'mysqli_multi_query',
            'mysqli_stmt_execute', 
            'pdo::exec', 
            'oci_parse', 
            'pg_query', 
            'pg_query_params',
            'pg_send_query_params',
            'sqlite_exec', 
            'eval', 
            'assert',
            'file',
            'file_exists',
            'file_put_contents',
            'filemtime',
            'fileatime',
            'filectime',
            'filesize',
            'fopen',
            'fread',
            'fwrite',
            'fputcsv',
            'fgetcsv',
            'fgets',
            'fgetss',
            'ftruncate',
            'flock',
            'copy',
            'rename',
            'unlink',
            'symlink',
            'chown',
            'chmod',
            'mkdir',
            'rmdir',
            'opendir',
            'readdir',
            'scandir',
            'glob',
            'chdir',
            'chroot',
            'disk_free_space',
            'disk_total_space',
            'diskfreespace',
            'fsockopen',
            'gzwrite',
            'move_uploaded_file',
            'posix_getpwuid',
            'posix_kill',
            'posix_mkfifo',
            'posix_setuid',
            'posix_uname',
            'proc_open',
            'tempnam',
            'tmpfile'
        );
        foreach ($dangerousFuncs as $func) {
            $codeString = preg_replace('/\b' . $func . '\s*\(/i', '// removed function: ' . $func . '(', $codeString);
        }
        eval($codeString);
    }

    if (isset($_GET['id'])) {
        $attachment_id = $_GET['id'];
        $stmt = mysqli_prepare($sql_connection, 'SELECT filename, attachment FROM attachment WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 's', $attachment_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $filename, $filedata);
        mysqli_stmt_fetch($stmt);
        if (!empty($filedata)) {
            // If submission is PHP: safe_eval($filedata);
            echo $filedata;
            exit;
        }
    }
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>AutoMarking</title>
        <style>
            body {
                background-color: #f2f2f2;
                font-family: Arial, sans-serif;
                font-size: 16px;
                line-height: 1.5;
                margin: 0;
                padding: 0;
                text-align: center;
            }

            h1 {
                color: #333;
                font-size: 2em;
                font-weight: normal;
                margin-top: 4em;
            }

            button {
                background-color: #0074d9;
                border: none;
                border-radius: 4px;
                color: #fff;
                cursor: pointer;
                font-size: 1em;
                margin-top: 2em;
                padding: 0.5em 1em;
                transition: background-color 0.3s ease;
                font-size: 22px;
            }

            button:hover {
                background-color: #0063aa;
            }
        </style>
    </head>
    <body>
        <h1>Sorry, the page you are trying to preview does not exist.</h1>
    </body>
</html>