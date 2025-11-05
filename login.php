<?php
session_start();
include("db_connect.php");

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    $stmt = $conn->prepare("SELECT id, full_name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $full_name, $hashed_password, $role);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['user_name'] = $full_name;

            if ($role === 'buyer') {
                header("Location: index.php");
            } elseif ($role === 'host') {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            $message = "Invalid password.";
        }
    } else {
        $message = "No account found with that email.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | Event Zilla</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="form-container">
<div class="logo">
  <img src="images/zilla.png" alt="EventZilla Logo">
  <h1>EventZilla</h1>
</div>
    <h2>Login</h2>
    <?php if ($message != "") echo "<p class='alert'>$message</p>"; ?>
    <form method="POST" action="">
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit" class="btn">Login</button>
        <p>Don't have an account? <a href="home.html">Register here</a></p>
    </form>
</div>
</body>
</html>