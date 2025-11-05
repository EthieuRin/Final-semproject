<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include("db_connect.php");

$event_id = $_GET['event_id'];
$result = $conn->query("SELECT * FROM events WHERE id = $event_id");
$event = $result->fetch_assoc();

// Safaricom Daraja API credentials
$consumerKey = 'yJfehX5yjbkrK6zGpJGUCjXFqycMvtT11L3RuAVSi4gJ26g2'; // Replace with your Consumer Key
$consumerSecret = 'EbzAvDafNf69331ExZAufZBlFfFDElS8weRJqTKH3OJZrTNCQzS1dH2aGIN2yIFY'; // Replace with your Consumer Secret
$shortCode = '174379'; // Sandbox Paybill number
$passKey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0b3c8a4f3c4e496b7b0c662f04b45c'; // Sandbox PassKey

// Function to generate access token
function generateAccessToken($consumerKey, $consumerSecret) {
    $credentials = base64_encode($consumerKey . ':' . $consumerSecret);
    $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . $credentials));
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($curl);
    curl_close($curl);

    $result = json_decode($response);
    return $result->access_token;
}

// Function to send STK Push
function sendSTKPush($accessToken, $shortCode, $passKey, $amount, $phoneNumber, $eventTitle) {
    $timestamp = date('YmdHis');
    $password = base64_encode($shortCode . $passKey . $timestamp);

    $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

    $payload = array(
        'BusinessShortCode' => $shortCode,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phoneNumber,
        'PartyB' => $shortCode,
        'PhoneNumber' => $phoneNumber,
        'CallBackURL' => 'https://yourdomain.com/callback_url.php', // Replace with your callback URL
        'AccountReference' => 'EventZilla',
        'TransactionDesc' => 'Ticket Purchase for ' . $eventTitle
    );

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessToken, 'Content-Type: application/json'));
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response, true);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mpesa_number_suffix = $_POST['mpesa_number_suffix']; // Get the user input (remaining part of the number)
    $mpesa_number = '254' . $mpesa_number_suffix; // Concatenate the fixed prefix with the user input
    $amount = $event['price'];
    $eventTitle = $event['title'];

    // Generate access token
    $accessToken = generateAccessToken($consumerKey, $consumerSecret);

    // Send STK Push
    $response = sendSTKPush($accessToken, $shortCode, $passKey, $amount, $mpesa_number, $eventTitle);

    if (isset($response['ResponseCode']) && $response['ResponseCode'] == '0') {
        $message = "Payment prompt sent to your phone. Complete the payment to confirm your ticket.";
    } else {
        $message = "Failed to send payment prompt. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout | Event Zilla</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav>
    <ul>
        <li><a href="home.html">Home</a></li>
        <li><a href="index.php">View Events</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>
<div class="checkout-container">
    <h1>Checkout</h1>
    <p>You are purchasing a ticket for the event: <strong><?php echo htmlspecialchars($event['title']); ?></strong></p>
    <p><strong>Price:</strong> <?php echo htmlspecialchars($event['price']); ?> KSH</p>
    <img src="images/pesa.jpg" alt="M-Pesa Logo">
    <?php if (isset($message)) echo "<p class='alert'>$message</p>"; ?>
    <form action="" method="POST">
        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
        <label for="mpesa_number_suffix">M-Pesa Number:</label>
        <div style="display: flex; align-items: center;">
            <span style="font-weight: bold; padding-right: 5px;">254</span>
            <input type="text" id="mpesa_number_suffix" name="mpesa_number_suffix" placeholder="7XXXXXXXX" required>
        </div>
        <button type="submit" class="btn">Checkout</button>
    </form>
</div>
</body>
</html>