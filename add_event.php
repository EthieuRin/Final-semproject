<?php
session_start();
include('db_connect.php');

$message = '';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id']; // Get the logged-in user's ID from the session
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $start_date = trim($_POST["start_date"]);
    $end_date = trim($_POST["end_date"]);
    $location = trim($_POST["location"]);
    $max_attendees = intval($_POST["max_attendees"]);
    $price = floatval($_POST["price"]);
    $status = trim($_POST["status"]);

    // Handle file upload (store image as LONGBLOB)
    $event_image = null;
    if (isset($_FILES["event_image"]) && $_FILES["event_image"]["error"] == 0) {
        $event_image = file_get_contents($_FILES["event_image"]["tmp_name"]);
    }

    // Insert the event into the database
    $stmt = $conn->prepare("INSERT INTO events (user_id, title, description, start_date, end_date, location, event_image, max_attendees, price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssbdis", $user_id, $title, $description, $start_date, $end_date, $location, $event_image, $max_attendees, $price, $status);

    if ($stmt->send_long_data(6, $event_image) && $stmt->execute()) { // Send LONGBLOB data
        $message = "Event added successfully!";
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Event | Event Zilla</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav>
    <ul>
        <li><a href="dashboard.php">Back to Dashboard</a></li>
        <li><a href="index.php">View Events</a></li>
    </ul>
</nav>
<div class="form-container">
    <h2>Add New Event</h2>
    <?php if ($message != "") echo "<p class='alert'>$message</p>"; ?>
    <form method="POST" action="" enctype="multipart/form-data">
        <label>Title</label>
        <input type="text" name="title" required>

        <label>Description</label>
        <textarea name="description" required></textarea>

        <label>Start Date</label>
        <input type="datetime-local" name="start_date" required>

        <label>End Date</label>
        <input type="datetime-local" name="end_date" required>

        <label>Location</label>
        <input type="text" name="location">

        <label>Event Image</label>
        <input type="file" name="event_image" accept="image/*">

        <label>Max Attendees</label>
        <input type="number" name="max_attendees" min="0">

        <label>Price</label>
        <input type="number" name="price" step="0.01" min="0">

        <label>Status</label>
        <select name="status">
            <option value="draft">Draft</option>
            <option value="published">Published</option>
            <option value="cancelled">Cancelled</option>
        </select>

        <button type="submit" class="btn">Add Event</button>
    </form>
</div>
</body>
</html>