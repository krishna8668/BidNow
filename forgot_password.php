<?php
include_once("header.php");
require_once("database.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    if (!empty($email)) {
        $connection = db_connect();

        // Check if email exists
        $query = "SELECT user_id FROM Users WHERE email = '$email'";
        $result = db_query($connection, $query);

        if (db_num_rows($result) > 0) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store token in the database
            $insertQuery = "INSERT INTO password_reset_tokens (email, token, expires_at)
                            VALUES ('$email', '$token', '$expires')";
            db_query($connection, $insertQuery);

            // Send reset email
            $resetLink = "http://localhost/reset_password.php?token=$token";
            $subject = "Password Reset Request";
            $message = "Click the following link to reset your password: $resetLink";
            mail($email, $subject, $message);

            echo '<div class="alert alert-success">A reset link has been sent to your email.</div>';
        } else {
            echo '<div class="alert alert-danger">Email not found!</div>';
        }

        db_disconnect($connection);
    } else {
        echo '<div class="alert alert-danger">Please enter your email address.</div>';
    }
}
?>

<div class="container">
    <h2 class="my-3">Forgot Password</h2>
    <form method="POST" action="">
        <div class="form-group">
            <label>Email Address:</label>
            <input type="email" class="form-control" name="email" required>
        </div>
        <button type="submit" class="btn btn-primary">Send Reset Link</button>
    </form>
</div>

<?php include_once("footer.php"); ?>
