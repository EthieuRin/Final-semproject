<?php
session_start();
include("db_connect.php");

// If user not logged in, redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch published events
$result = $conn->query("SELECT * FROM events WHERE status = 'published' ORDER BY start_date ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Home | Event Zilla</title>
<link rel="stylesheet" href="css/style.css">
<style>
  :root {
    --bg-white: #ffffff;
    --text-black: #000000;
    --text-muted: #555555;
    --accent-red: #e63946;
    --card-border: #dddddd;
    --button-hover: #d62828;
  }

  * {
    box-sizing: border-box;
  }

  body {
    margin: 0;
    min-height: 100vh;
    background: var(--bg-white);
    font-family: "Poppins", system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial;
    color: var(--text-black);
    padding: 32px;
  }

  .events-container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
  }

  .headline {
    text-align: center;
    font-size: 32px;
    font-weight: bold;
    color: var(--accent-red);
    margin-bottom: 20px;
  }

  .event-card {
    width: 100%;
    max-width: 820px;
    border-radius: 12px;
    overflow: hidden;
    background: var(--bg-white);
    border: 1px solid var(--card-border);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
  }

  .event-hero {
    position: relative;
    height: 240px;
    background-size: cover;
    background-position: center;
  }

  .event-info {
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .event-name {
    font-size: 20px;
    font-weight: bold;
    color: var(--text-black);
    margin: 0;
  }

  .tagline {
    font-size: 14px;
    color: var(--text-muted);
    margin: 0;
  }

  .price-buy-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px;
  }

  .price {
    font-size: 16px;
    font-weight: bold;
    color: var(--accent-red);
    padding: 8px 14px;
    border-radius: 8px;
    background: rgba(230, 57, 70, 0.1);
    border: 1px solid rgba(230, 57, 70, 0.2);
  }

  .buy-btn {
    background: var(--accent-red);
    color: var(--bg-white);
    padding: 8px 14px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    text-transform: uppercase;
    transition: background 0.2s ease;
    text-align: center;
  }

  .buy-btn:hover {
    background: var(--button-hover);
  }
</style>
</head>
<body>
<div class="events-container">
    <h1 class="headline">Discover Amazing Events</h1>
    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="event-card">
                <div class="event-hero" style="background-image: url('data:image/jpeg;base64,<?php echo base64_encode($row['event_image']); ?>');">
                </div>
                <div class="event-info">
                    <h2 class="event-name"><?php echo htmlspecialchars($row['title']); ?></h2>
                    <p class="tagline"><strong>Description:</strong> <?php echo htmlspecialchars($row['description']); ?></p>
                    <p class="tagline"><strong>Start Date:</strong> <?php echo htmlspecialchars($row['start_date']); ?></p>
                    <p class="tagline"><strong>End Date:</strong> <?php echo htmlspecialchars($row['end_date']); ?></p>
                    <p class="tagline"><strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?></p>
                    <div class="price-buy-container">
                        <p class="price"><?php echo htmlspecialchars($row['price']); ?> KSH</p>
                        <button class="buy-btn">Buy Ticket</button>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No events available at the moment.</p>
    <?php endif; ?>
</div>
</body>
</html>