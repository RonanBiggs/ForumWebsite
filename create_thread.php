<?php

include 'connect.php';
include 'header.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] != 'POST') {

    // The form hasn't been posted yet, display it

    echo "<form method='post' action=''>

New Thread: <input type='text' name='thread_title' />

<input type='submit' value='submit' />

</form>";

} else {

    // The form has been posted, so save it

    $thread_title = $_POST['thread_title'];



    // Query the database for the maximum session ID from both threads and replies
    $max_thread_session_id_sql = "SELECT MAX(session_id) AS max_session_id FROM threads";
    $max_reply_session_id_sql = "SELECT MAX(session_id) AS max_session_id FROM replies";

    $max_thread_session_id_result = mysqli_query($conn, $max_thread_session_id_sql);
    $max_reply_session_id_result = mysqli_query($conn, $max_reply_session_id_sql);

    $max_thread_session_id_row = mysqli_fetch_assoc($max_thread_session_id_result);
    $max_reply_session_id_row = mysqli_fetch_assoc($max_reply_session_id_result);

    $max_thread_session_id = $max_thread_session_id_row['max_session_id'];
    $max_reply_session_id = $max_reply_session_id_row['max_session_id'];

    // Determine the maximum session ID from both tables
    $max_session_id = max($max_thread_session_id, $max_reply_session_id);

    // Set the new session ID to be 1 greater than the maximum existing session ID
    $new_session_id = $max_session_id + 1;


    // Set the new session ID to be 1 greater than the maximum existing session ID
    $_SESSION['session_id'] = $max_session_id + 1;

    // Prepare the SQL statement with placeholders

    $sql = "INSERT INTO threads (thread_title, thread_date, session_id)

VALUES (?, NOW(), ?)";

    // Create a prepared statement

    $stmt = mysqli_prepare($conn, $sql);

    // Bind parameters to the prepared statement

    mysqli_stmt_bind_param($stmt, 'si', $thread_title, $new_session_id);

    // Execute the prepared statement

    $result = mysqli_stmt_execute($stmt);

    if (!$result) {

        // Something went wrong, display the error

        echo 'Error: ' . mysqli_error($conn);

    } else {
            //set thread id in session
            $thread_id = mysqli_insert_id($conn);
            $_SESSION['current_thread_id'] = $thread_id;
            // Redirect to the same page to refresh the replies
            header("Location: index.php");
            exit();
        echo 'New thread successfully posted';

    }

    // Close the prepared statement

    mysqli_stmt_close($stmt);

}
include 'footer.php';
?>
