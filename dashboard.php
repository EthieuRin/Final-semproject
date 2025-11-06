<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include("db_connect.php");

// Fetch the logged-in user's name
$user_name = $_SESSION['user_name'];

// Fetch events created by the logged-in host
$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT * FROM events WHERE user_id = $user_id ORDER BY start_date ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | Event Zilla</title>
<link rel="stylesheet" href="css/style.css">
<style>
    .dashboard-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .event-row {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: center;
    }

    .event-card {
        flex: 1 1 calc(33.333% - 20px);
        max-width: calc(33.333% - 20px);
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .event-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    .event-hero {
        height: 150px;
        background-size: cover;
        background-position: center;
    }

    .event-info {
        padding: 16px;
    }

    .event-info h3 {
        margin: 0 0 8px;
        font-size: 18px;
        color: #333;
    }

    .event-info p {
        margin: 4px 0;
        font-size: 14px;
        color: #555;
    }
</style>
</head>
<body>
<nav>
    <ul>
        <li><a href="index.html">Home</a></li>
        <li><a href="index.php">View Events</a></li>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>
<div class="dashboard-container">
    <h1>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>
    <p>Here you can manage your events.</p>
    <a href="add_event.php" class="btn">Create New Event</a>
    <h2>Your Events</h2>
    <?php if ($result->num_rows > 0): ?>
        <div class="event-row">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="event-card">
                    <div class="event-hero" style="background-image: url('data:image/jpeg;base64,<?php echo base64_encode($row['event_image']); ?>');"></div>
                    <div class="event-info">
                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($row['description']); ?></p>
                        <p><strong>Start Date:</strong> <?php echo htmlspecialchars($row['start_date']); ?></p>
                        <p><strong>End Date:</strong> <?php echo htmlspecialchars($row['end_date']); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?></p>
                        <p><strong>Price:</strong> <?php echo htmlspecialchars($row['price']); ?> KSH</p>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p>You have not created any events yet.</p>
    <?php endif; ?>
</div>
</body>
</html>