<?php 
// Include header and required files
include_once("header.php");
require("utilities.php");
require("database.php");

// Ensure session is started (if not already started in header.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<div class="container">
  <h2 class="my-3">Auctions I've Won</h2>
  
  <?php
  // Check if the user is logged in; if not, show a message and exit.
  if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
      echo "<p>Please log in to view your won auctions.</p>";
      exit;
  }
  
  // Get the logged-in user's ID from the session.
  $user_id = $_SESSION['user_id'];

  // Connect to the database.
  $connection = db_connect();

  // Build the SQL query to fetch auctions where the user was the highest bidder and the auction has ended.
  $won_auctions = "SELECT 
                      auction.auction_id,
                      item.item_id,
                      auction.auction_title,
                      item.description,
                      bids.price AS your_bid,
                      item.photo,
                      users.user_id AS seller_id
                   FROM auction
                   JOIN bids ON auction.auction_id = bids.auction_id
                   JOIN item ON auction.item_id = item.item_id
                   JOIN users ON auction.user_id = users.user_id
                   WHERE bids.user_id = '$user_id'
                     AND bids.price = (
                         SELECT MAX(price)
                         FROM bids
                         WHERE auction_id = auction.auction_id
                     )
                     AND auction.end_time < NOW()";

  // Execute the query.
  $won_results = db_query($connection, $won_auctions);
  confirm_result_set($won_results);

  // Count the number of results.
  $num_results = db_num_rows($won_results);

  if ($num_results == 0) {
      echo "<p>You haven't won any auctions.</p>";
  } else {
      echo '<ul class="list-group">';
      
      // Loop through each auction result.
      while ($row = db_fetch_single($won_results)) {
          $auction_id   = $row['auction_id'];
          $item_id      = $row['item_id'];
          $title        = $row['auction_title'];
          $description  = $row['description'];
          $your_bid     = $row['your_bid'];
          $photo        = $row['photo'];
          $seller_id    = $row['seller_id'];
          
          // Shorten the description if it's long.
          if (strlen($description) > 250) {
              $desc_shortened = substr($description, 0, 250) . '...';
          } else {
              $desc_shortened = $description;
          }
          
          echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
          
          // Display the item photo.
          echo '<img src="' . $photo . '" alt="' . htmlspecialchars($title) . '" 
                     class="img-fluid" style="max-width: 120px; max-height: 120px;">';
          
          // Auction details: title and description.
          echo '<div class="flex-grow-1 ml-3">';
          echo '<h5><a href="listing.php?item_id=' . $item_id . '">' . htmlspecialchars($title) . '</a></h5>';
          echo '<p>' . htmlspecialchars($desc_shortened) . '</p>';
          echo '</div>';
          
          // Right column: bid price, pay button, etc.
          echo '<div class="text-center">';
          echo '<p>Your Bid Price:</p>';
          echo '<p><span style="font-size: 1.5em">₹' . number_format($your_bid, 2) . '</span></p>';

          // === PAY BUTTON (ALWAYS SHOWN) ===
          // Simply show a Pay Now button linking to pay.php with the auction_id
          echo '<p><a href="pay.php?auction_id=' . $auction_id . '" class="btn btn-warning">Pay Now</a></p>';
          
          // === (Optional) Seller Rating Section ===
          // If you want to keep the rating system, you can leave your existing rating code here.
          // Example:
          /*
          if (checkRated($user_id, $seller_id, $item_id) == 0) {
              // Show rating form...
          } else {
              // Show the rating...
          }
          */
          
          echo '</div></li>';
      }
      echo '</ul>';
  }

  db_free_result($won_results);
  db_disconnect($connection);
  ?>
</div>

<?php include_once("footer.php"); ?>
