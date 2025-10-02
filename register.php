<?php
// register.php
include("db_connect.php");
include("phpmailer/mail.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $password  = trim($_POST['password']);

    if (empty($full_name) || empty($email) || empty($password)) {
        $msg = "⚠️ All fields are required.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $msg = "⚠️ Email already registered. Please log in.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $full_name, $email, $hashed_password);

            if ($stmt->execute()) {
                $subject = "🎉 Welcome to EventZilla!";
                $message = "
                    <h2>Hello $full_name,</h2>
                    <p>Welcome to <b>EventZilla</b> 🎉</p>
                    <p>You can now log in and start exploring events.</p>
                    <br>
                    <p>Best regards,<br>Team EventZilla</p>
                ";
            
                if (sendMail($email, $subject, $message, $full_name)) {
                    echo "<script>alert('✅ Registered successfully! Check your email.'); 
                          window.location.href='index.php';</script>";
                } else {
                    echo "<script>alert('✅ Registered successfully, but email could not be sent.'); 
                          window.location.href='index.php';</script>";
                }
                exit;
            }
            
             else {
                $msg = "❌ Error: " . $stmt->error;
            }
            $stmt->close();
        }
        $check->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - EventZilla</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="logo">
  <img src="images/zilla.png" alt="EventZilla Logo">
  <h1>EventZilla</h1>
</div>

<div class="form-container">
  <h2>Register</h2>
  <?php if (!empty($msg)) echo "<p class='msg'>$msg</p>"; ?>
  <form method="POST" action="register.php">
    <input type="text" name="full_name" placeholder="Full Name" required>
    <input type="email" name="email" placeholder="Email Address" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Register</button>
  </form>
  <p>Already registered? <a href="login.php">Login here</a></p>
</div>
</body>
</html>
