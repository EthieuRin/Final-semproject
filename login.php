<?php
// login.php
session_start();
include("db_connect.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $msg = "⚠️ All fields are required.";
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $full_name, $hashed_password);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id']   = $id;
                $_SESSION['full_name'] = $full_name;

                echo "<script>alert('✅ Login successful! Welcome back $full_name.'); 
                      window.location.href='index.php';</script>";
                exit;
            } else {
                $msg = "❌ Invalid password.";
            }
        } else {
            $msg = "❌ No account found with that email.";
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - EventZilla</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="logo">
  <img src="images/zilla.png" alt="EventZilla Logo">
  <h1>EventZilla</h1>
</div>

<div class="form-container">
  <h2>Login</h2>
  <?php if (!empty($msg)) echo "<p class='msg'>$msg</p>"; ?>
  <form method="POST" action="login.php">
    <input type="email" name="email" placeholder="Email Address" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
  </form>
  <p>Don’t have an account? <a href="register.php">Register here</a></p>
</div>
</body>
</html>
