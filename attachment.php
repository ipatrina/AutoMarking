<?php
    require_once('common.php');

    if (isset($_GET['id'])) {
        $attachment_id = $_GET['id'];
        $stmt = mysqli_prepare($sql_connection, 'SELECT filename, attachment FROM attachment WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 's', $attachment_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $filename, $filedata);
        mysqli_stmt_fetch($stmt);
        if (!empty($filedata)) {
            if (empty($filename)) {
                $filename = 'attachment';
                if (isset($_GET['filename'])) {
                    $filename = $_GET['filename'];
                }
            }
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($filedata));
            echo $filedata;
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
        <h1>Sorry, the attachment you are trying to download could not be found.</h1>
        <button onclick="history.back();">Back</button>
    </body>
</html>