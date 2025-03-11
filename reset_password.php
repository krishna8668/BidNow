<?php
include_once("header.php");
require_once("database.php");

if (isset($_GET['token'])) {
    $token = $_GET['token'];
} else {
    die("Invalid request");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        echo '<div class="alert alert-danger">Passwords do not match!</div>';
    } else {
        $connection = db_connect();

        // Check if token is valid
        $query = "SELECT email FROM password_reset_tokens WHERE token = '$token' AND expires_at > NOW()";
        $result = db_query($connection, $query);

        if (db_num_rows($result) > 0) {
            $row = db_fetch_single($result);
            $email = $row['email'];

            // Hash the password and update
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            $updateQuery = "UPDATE Users SET password = '$hashedPassword' WHERE email = '$email'";
            db_query($connection, $updateQuery);

            // Delete the used token
            $deleteQuery = "DELETE FROM password_reset_tokens WHERE token = '$token'";
            db_query($connection, $deleteQuery);

            echo '<div class="alert alert-success">Password reset successful! <a href="login.php">Login</a></div>';
        } else {
            echo '<div class="alert alert-danger">Invalid or expired token!</div>';
        }

        db_disconnect($connection);
    }
}
?>

<div class="container">
    <h2 class="my-3">Reset Password</h2>
    <form method="POST" action="">
        <div class="form-group">
            <label>New Password:</label>
            <input type="password" class="form-control" name="new_password" required>
        </div>
        <div class="form-group">
            <label>Confirm Password:</label>
            <input type="password" class="form-control" name="confirm_password" required>
        </div>
        <button type="submit" class="btn btn-primary">Reset Password</button>
    </form>
</div>

<?php include_once("footer.php"); ?>
