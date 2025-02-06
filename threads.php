<?php
// threads.php


include 'connect.php';
include 'header.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// experiment. if broken delete everything from here
echo '<script>
function myFunction(element) {
   if (element.style.width === "auto") {
   		element.style.width = "500px";
   } else {
   		element.style.width = "auto";
        }
   	
   
}
</script>';
//to here

//note:this <style> section for some reason not saved from style.css hence it being here too. btw post-info is the session_id number displayed above all posts, easy to change to make look better w this class
echo '<style>
    .resizable-image {
        width: 500px;
        transition: width 0.3s ease;
    }
    .resizable-image.enlarged {
        width: auto;
    }
    .post-info {
    color: green;
    font-weight: bold;
    }
</style>';
//also broke image enlarging, here is part of the reimplementation
echo '<script>
function toggleImageSize(element) {
    if (element.classList.contains("enlarged")) {
        element.classList.remove("enlarged");
    } else {
        element.classList.add("enlarged");
    }
}
</script>';

//function display_page($conn){
if (isset($_GET['id'])) {
    $thread_id = intval($_GET['id']);

    // Retrieve the thread details from the database
   // $sql = "SELECT thread_title, thread_content FROM threads WHERE thread_id = $thread_id";
   $sql = "SELECT thread_title, thread_id, session_id FROM threads WHERE thread_id = $thread_id";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo '<p class = "post"><span<span class="post-info">' . htmlspecialchars($row['session_id']) . '</span><p>';
        echo '<h2>' . htmlspecialchars($row['thread_title']) . '</h2>';
    } else {
        echo 'Thread not found.';
    }
} else {
    echo 'No thread ID specified.';
}
//end display_page

// Generate a new session ID (post No.) if not already set
// Check if the current thread ID matches the session variable
if (!isset($_SESSION['current_thread_id']) || $_SESSION['current_thread_id'] != $thread_id) {
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
    $_SESSION['current_thread_id'] = $thread_id; // Update the current thread ID in the session
}


$sql_replies = "SELECT post_content, image, session_id FROM replies WHERE post_thread = ?";
$stmt_replies = mysqli_prepare($conn, $sql_replies);
mysqli_stmt_bind_param($stmt_replies, 'i', $thread_id);
mysqli_stmt_execute($stmt_replies);
$result_replies = mysqli_stmt_get_result($stmt_replies);
//display replies
if ($result_replies && mysqli_num_rows($result_replies) > 0) {
    while ($row_replies = mysqli_fetch_assoc($result_replies)) {
        if ($row_replies['image'] == 0)
        {
            //display text replies
            echo '<p class = "post"><span class="post-info">' . htmlspecialchars($row_replies['session_id']) . '</span><br>' . nl2br(htmlspecialchars($row_replies['post_content'])) . '</p>';
        }
        else //display image
        {
            $image_path = $row_replies['post_content'];
            echo '<p class = "post"><span class="post-info">'. htmlspecialchars($row_replies['session_id']) . '<br>' .'<img src="' . htmlspecialchars($image_path) . '" alt="Image" class="resizable-image" onclick="toggleImageSize(this)"><br>';
           
        }
    }
}

//reply form
echo "<form method='post' action=''>
reply: <textarea name='post_content'></textarea>
<input type='submit' value='reply' />
</form>";


//image upload
echo $message ?? null; //display message for file upload
//image upload form
echo "<form method='POST' action='' enctype='multipart/form-data'>
    <input type='file' name='upload' />
    <input type='submit' value='Upload' name='upload' />
    </form>";

    //image upload
if(isset($_POST['upload'])){
    $allowed_ext = array('png', 'jpg', 'jpeg', 'gif');
    if(!empty($_FILES['upload']['name'])){
        echo '<p style="color: green">upload success<p>';

        $file_name = $_FILES['upload']['name'];
        $file_size = $_FILES['upload']['size'];
        $file_tmp = $_FILES['upload']['tmp_name'];
        $target_dir = "uploads/${file_name}";

        //get file extension
        $file_ext = explode('.', $file_name);
        $file_ext = strtolower(end($file_ext));

        if(in_array($file_ext, $allowed_ext)){
            if($file_size < 5000000){
                move_uploaded_file($file_tmp, $target_dir);
                $message = '<p style="color: green">File uploaded successfully</p>';
                //sql image database addition

                // Prepare the SQL statement with placeholders
                $post_content = $target_dir;
                $image = 1;
                $sqli = "INSERT INTO replies (post_id, post_content, post_date, post_thread, image, session_id)
                VALUES (?, ?, NOW(), ?, ?, ?)";
                // Create a prepared statement
                $stmti = mysqli_prepare($conn, $sqli);
                // Bind parameters to the prepared statement
                mysqli_stmt_bind_param($stmti, 'sssii', $post_id, $post_content, $thread_id, $image, $_SESSION['session_id']);
                $resulti = mysqli_stmt_execute($stmti);
                if ($resulti) {
                    // Redirect to the same page to refresh the replies
                    header("Location: threads.php?id=$thread_id");
                    exit();
                } else {
                    echo 'Something went wrong while posting your reply. Please try again later.';
                }

            }
            else{
                $message = '<p style="color: red">File is too large</p>';
            }
        }
        else{
            $message = '<p style="color: red">Only PNG, JPG, JPEG and GIF files are allowed</p>';
        }
    }
}


//text reply upload
if ($_SERVER['REQUEST_METHOD'] != 'POST') {

    // nothing to do idk why my if statement is set this way



} else if (isset($_POST['post_content'])) {


// The form has been posted, so save it
    $post_content = $_POST['post_content'];
// Prepare the SQL statement with placeholders
    $sql = "INSERT INTO replies (post_id, post_content, post_date, post_thread, session_id)
    VALUES (?, ?, NOW(), ?, ?)";
// Create a prepared statement
    $stmt = mysqli_prepare($conn, $sql);
// Bind parameters to the prepared statement
    mysqli_stmt_bind_param($stmt, 'ssii', $post_id, $post_content, $thread_id, $_SESSION['session_id']);
// Execute the prepared statement
//making display a function causes this to throw an error
    $result = mysqli_stmt_execute($stmt);

    if ($result) {
        // Redirect to the same page to refresh the replies
        header("Location: threads.php?id=$thread_id");
        exit();
    } else {
        echo 'Something went wrong while posting your reply. Please try again later.';
    }


}


//TODO: var names, especially in sql database, kinda suck. maybe worth fixing.
   

include 'footer.php';
?>
