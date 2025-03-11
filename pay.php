<?php
// payment_success.php

session_start();
include_once("header.php");
include_once("database.php");
include_once("utilities.php");

// Ensure required GET parameters are present
if (!isset($_GET['orderID']) || !isset($_GET['auction_id'])) {
    echo "<div class='alert alert-danger'>Missing parameters.</div>";
    exit;
}

$orderID = $_GET['orderID'];
$auction_id = intval($_GET['auction_id']);

// PayPal sandbox credentials (provided)
$clientID = "ARjxwfxZoJiF5o4HogB8QYPk9XJdemSnxv9ci7KW5QwBbx2JmtEukmpnOQUNDFN4KMWsI1fm4UG5_1iN";
$secret   = "EPO4wh20Sr2cQZRDpljSThrM1YZ_5G74mIkknz6vCZxJ7mTnwFAS9CV7y9Hozyt6GV7RfCXrho7vN-OY";

// Function to get PayPal access token via OAuth2
function getAccessToken($clientID, $secret) {
    $url = "https://api-m.sandbox.paypal.com/v1/oauth2/token";
    
    $headers = [
        "Accept: application/json",
        "Accept-Language: en_US"
    ];
    
    $postFields = "grant_type=client_credentials";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_USERPWD, $clientID . ":" . $secret);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $result = curl_exec($ch);
    if(curl_errno($ch)){
        echo "<div class='alert alert-danger'>Curl error: " . curl_error($ch) . "</div>";
        curl_close($ch);
        return false;
    }
    curl_close($ch);
    
    $json = json_decode($result, true);
    return $json['access_token'] ?? false;
}

// Get access token
$accessToken = getAccessToken($clientID, $secret);
if(!$accessToken){
    echo "<div class='alert alert-danger'>Error obtaining access token.</div>";
    exit;
}

// Retrieve order details using the Orders API
$orderUrl = "https://api-m.sandbox.paypal.com/v2/checkout/orders/" . urlencode($orderID);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $orderUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $accessToken
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$orderResponse = curl_exec($ch);
if(curl_errno($ch)){
    echo "<div class='alert alert-danger'>Curl error: " . curl_error($ch) . "</div>";
    curl_close($ch);
    exit;
}
curl_close($ch);

$orderData = json_decode($orderResponse, true);

// Check if the order status is COMPLETED
if(isset($orderData['status']) && $orderData['status'] === "COMPLETED"){
    // Payment is confirmed
    // For example, update your Payments table or mark the auction as paid.
    // (Assuming you have the logged-in user's ID stored in session as $_SESSION['user_id'])
    
    $connection = db_connect();
    $updateQuery = "UPDATE Payments 
                    SET payment_status = 'paid', payment_date = NOW() 
                    WHERE auction_id = '$auction_id' AND user_id = '" . $_SESSION['user_id'] . "'";
    $result = db_query($connection, $updateQuery);
    db_disconnect($connection);
    
    echo "<div class='alert alert-success'>Payment confirmed! Your order ($orderID) is completed.</div>";
} else {
    $status = htmlspecialchars($orderData['status'] ?? 'Unknown');
    echo "<div class='alert alert-danger'>Payment not confirmed. Current order status: $status</div>";
}

include_once("footer.php");
?>
