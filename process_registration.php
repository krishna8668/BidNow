<?php include_once("header.php")?>
<?php include_once("database.php") ?>
<?php require_once('utilities.php'); ?>

<div class="container my-5">

<?php

// Process the registration form data
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Create database connection
    $connection = db_connect();

    // Validate data: Check for required fields including mobile number and address
    if (empty($_POST['accountType']) || empty($_POST['email']) || 
        empty($_POST['password']) || empty($_POST['passwordConfirmation']) ||
        empty($_POST['firstName']) || empty($_POST['lastName']) ||
        empty($_POST['mobile_no']) || empty($_POST['address'])) {

        $errorMessage = "Error: Required fields are empty. Please fill in the following:";

        if (empty($_POST['accountType'])) {
            $errorMessage .= "<br>- Account Type";
        }
        if (empty($_POST['email'])) {
            $errorMessage .= "<br>- Email";
        }
        if (empty($_POST['password'])) {
            $errorMessage .= "<br>- Password";
        }
        if (empty($_POST['passwordConfirmation'])) {
            $errorMessage .= "<br>- Password Confirmation";
        }
        if (empty($_POST['firstName'])) {
            $errorMessage .= "<br>- First Name";
        }
        if (empty($_POST['lastName'])) {
            $errorMessage .= "<br>- Last Name";
        }
        if (empty($_POST['mobile_no'])) {
            $errorMessage .= "<br>- Mobile Number";
        }
        if (empty($_POST['address'])) {
            $errorMessage .= "<br>- Address";
        }

        echo '<div class="alert alert-danger mt-3" role="alert">' . $errorMessage . '</div>';
        db_disconnect($connection);
        exit();
    }

    // Check if the email already exists
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $emailCheckQuery = "SELECT * FROM Users WHERE email = '$email'";
    $emailCheckResult = db_query($connection, $emailCheckQuery);

    if (mysqli_num_rows($emailCheckResult) > 0) {
        // Email already exists, show an error message
        echo '<div class="text-center"> User already exists. <a href="login.php">Go to login page.</a></div>';
        db_disconnect($connection);
        exit();
    }

    // Extract and sanitize form data
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $accountType = $_POST['accountType'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $passwordConfirmation = $_POST['passwordConfirmation'];
    $mobile_no = $_POST['mobile_no'];
    $address = $_POST['address'];

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Error: Invalid email format.";
        echo '<div class="alert alert-danger mt-3" role="alert">' . $errorMessage . '</div>';
        db_disconnect($connection);
        exit();
    }

    // Validate password match
    if ($password !== $passwordConfirmation) {
        echo '<div class="alert alert-danger mt-3" role="alert">Error: Passwords do not match.</div>';
        db_disconnect($connection);
        exit();
    }

    // Optionally, hash the password (uncomment if needed)
    // $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Set role based on account type
    $role = ($accountType == 'seller') ? 0 : 1;

    // INSERT query for the Users table including mobile_no and address
    $query = "INSERT INTO Users (email, password, role, first_name, last_name, mobile_no, address) 
              VALUES ('$email', '$password', '$role', '$firstName', '$lastName', '$mobile_no', '$address')";   

    // Execute the query
    $result = db_query($connection, $query);

    // Check the result of the database operation
    if ($result) {
        echo '<div class="text-center"> Account successfully created! <a href="login.php">Go to login page.</a></div>';

        // ********************* Send out email **************************
        // Send email to user
        $recipient = $email;
        $subject = "Account Created!";
        $content = "<body> Welcome to Auction site! </body></br>";
        sendmail($recipient, $subject, $content);
        // ***************************************************************
    } else {
        echo '<div class="alert alert-danger mt-3" role="alert">Error: Registration failed.</div>';
    }

    // Close the database connection
    db_disconnect($connection);
}

?>

</div>

<?php include_once("footer.php"); ?>
