<?php

// index.php

include 'connect.php';

include 'header.php';

echo '<h2>/Alpha Den/</h2>';

echo '<div style="position: absolute; top: 0; right: 0;">';
echo '<img src="patriot.gif" alt="Image">';

echo '</div>';




$sql = "
    SELECT t.thread_id, t.thread_title, t.thread_date, 
           GREATEST(COALESCE(MAX(r.post_date), '1970-01-01'), t.thread_date) AS latest_date
    FROM threads t
    LEFT JOIN replies r ON t.thread_id = r.post_thread
    GROUP BY t.thread_id, t.thread_title, t.thread_date
    ORDER BY latest_date DESC, t.thread_id DESC
";
$result = mysqli_query($conn, $sql);

if (!$result) {

    echo 'The threads could not be displayed, please try again later.';

} else {

    if (mysqli_num_rows($result) == 0) {

        echo 'No threads defined yet.';

    } else {

        // Prepare the table

        echo '<table border="1">

<tr>



</tr>';


while ($row = mysqli_fetch_assoc($result)) {
    $thread_id = $row['thread_id'];
    $reply_count_sql = "SELECT COUNT(*) AS reply_count FROM replies WHERE post_thread = $thread_id";
    $reply_count_result = mysqli_query($conn, $reply_count_sql);
    $reply_count_row = mysqli_fetch_assoc($reply_count_result);
    $reply_count = $reply_count_row['reply_count'];

    echo '<tr>';
    echo '<td class="leftpart">';
    echo '<h3><a href="threads.php?id=' . $row['thread_id'] . '">' . htmlspecialchars($row['thread_title']) . "(" . $reply_count . ")" .'</a></h3>';
    echo '</td>';
    echo '</tr>';
    

}

    }

}



include 'footer.php';

?>
