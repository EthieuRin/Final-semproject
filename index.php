<?php
// index.php - Home page
session_start();
include("db_connect.php");

// If user not logged in, redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch events
$sql = "SELECT id, title, description, start_date, end_date, location, price, event_image 
        FROM events WHERE status = 'published' ORDER BY start_date ASC";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>EventZilla - Home</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="logo">
  <img src="images/zilla.png" alt="EventZilla Logo">
  <h1>EventZilla</h1>
</div>

<div class="navbar">
  <span>👋 Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</span>
  <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="content">
  <h2>Upcoming Events</h2>
  <div class="events-grid">
    <?php if ($result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <div class="event-card">
          <img src="uploads/<?php echo htmlspecialchars($row['event_image']); ?>" alt="Event Image">
          <h3><?php echo htmlspecialchars($row['title']); ?></h3>
          <p class="date">
            📅 <?php echo date("M d, Y H:i", strtotime($row['start_date'])); ?> 
            - <?php echo date("M d, Y H:i", strtotime($row['end_date'])); ?>
          </p>
          <p class="location">📍 <?php echo htmlspecialchars($row['location']); ?></p>
          <p class="desc"><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
          <p class="price">💵 <?php echo $row['price'] > 0 ? "$" . number_format($row['price'], 2) : "Free"; ?></p>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p>No events available yet. Stay tuned!</p>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
