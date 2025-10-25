<?php
session_start();
include('db_connect.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    if ($password !== $confirm_password) {
        $message = "Passwords do not match!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $full_name, $email, $hashed_password);

        if ($stmt->execute()) {
            // Start session for the new user
            $_SESSION['user_id'] = $conn->insert_id;
            $_SESSION['user_name'] = $full_name;

            // Send Welcome Email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'lowengel10@gmail.com'; 
                $mail->Password   = 'alnd qqjf tilc pgqk'; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;

                $mail->setFrom('lowengel10@gmail.com', 'Event Zilla'); // Replace with your email
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Welcome to Event Zilla';
                $mail->Body    = '<h2>Welcome, ' . htmlspecialchars($full_name) . '!</h2>
                                  <p>Thank you for registering at Event Zilla. We’re happy to have you onboard!</p>';

                $mail->send();
                $message = "Registered successfully! Check your email for a welcome message.";
            } catch (Exception $e) {
                $message = "User registered, but email could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }

            header("Location: index.php");
            exit();
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register | Event Zilla</title>
<link rel="stylesheet" href="css/style.css">
<script>
function validatePasswords() {
    const pass = document.getElementById('password').value;
    const confirmPass = document.getElementById('confirm_password').value;
    const message = document.getElementById('passMessage');
    
    if (pass !== confirmPass) {
        message.textContent = "Passwords do not match!";
        message.style.color = "red";
        return false;
    } else {
        message.textContent = "";
        return true;
    }
}
</script>
</head>
<body>
<div class="form-container">
<div class="logo">
  <img src="images/zilla.png" alt="EventZilla Logo">
  <h1>EventZilla</h1>
</div>
    <h2>Create an Account</h2>
    <?php if ($message != "") echo "<p class='alert'>$message</p>"; ?>
    <form method="POST" action="" onsubmit="return validatePasswords()">
        <label>Full Name</label>
        <input type="text" name="full_name" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" id="password" required>

        <label>Confirm Password</label>
        <input type="password" name="confirm_password" id="confirm_password" required>
        <p id="passMessage" class="message"></p>

        <button type="submit" class="btn">Register</button>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </form>
</div>
</body>
</html>