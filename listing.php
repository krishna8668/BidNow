<?php 
// Set default timezone to Indian Standard Time (IST)
date_default_timezone_set('Asia/Kolkata');
?>
<?php include_once("header.php") ?>
<?php require("utilities.php") ?>
<?php require("database.php") ?>

<?php

$has_session = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$user_id = $_SESSION['user_id'];
$account_type = $_SESSION['account_type'];

// Get info from the URL:
$item_id = $_GET['item_id'] ?? null;

if (!$item_id) {
  echo ("<div>Error: Item ID is missing.</div>");
  exit;
}

// Establish database connection
$connection = db_connect();

// Fetch item details
$item_query = "SELECT name, description, photo FROM Item WHERE item_id = '$item_id'";
$item_result = db_query($connection, $item_query);

// Check if item exists
if (db_num_rows($item_result) == 0) {
  echo "<div>Error: Item not found.</div>";
  db_disconnect($connection);
  exit;
}

$item = db_fetch_single($item_result);
$name = $item['name'];
$description = $item['description'];
$item_photo = $item['photo'];

// Check if the item is part of an auction
// Modified auction query to include starting_price
$auction_query = "SELECT auction_id, start_time, end_time, auction_title, starting_price
                  FROM Auction WHERE item_id = '$item_id'";
$auction_result = db_query($connection, $auction_query);
$auction_exists = db_num_rows($auction_result) > 0;

if ($auction_exists) {
  // Item is part of an auction
  $auction_data = db_fetch_single($auction_result);
  $auction_id = $auction_data['auction_id'];
  $title = $auction_data['auction_title'];
  
  // Create DateTime objects with IST timezone
  $start_time = new DateTime($auction_data['start_time'], new DateTimeZone('Asia/Kolkata'));
  $end_time = new DateTime($auction_data['end_time'], new DateTimeZone('Asia/Kolkata'));
  $starting_price = $auction_data['starting_price'];

  // Get current price and number of bids
  $bid_query = "SELECT b.price AS current_price, u.user_id, u.first_name, u.last_name, COUNT(*) AS num_bids
  FROM Bids b
  INNER JOIN Users u ON b.user_id = u.user_id
  WHERE b.auction_id = '$auction_id'
  AND b.price = (SELECT MAX(price) FROM Bids WHERE auction_id = '$auction_id')
  GROUP BY b.price, u.user_id, u.first_name, u.last_name";

  $bid_result = db_query($connection, $bid_query);
  $bid_data = db_fetch_single($bid_result);
  
  if ($bid_data) {
    // If there is a bid, use the highest bid (or fallback to starting price if empty)
    $current_price = $bid_data['current_price'] ?: $starting_price;
    $current_winner = $bid_data['first_name'] . ' ' . $bid_data['last_name'];
    $num_bids = $bid_data['num_bids'];
  } else {
    // If no bids are found, use the starting price as the current minimum bid
    $current_price = $starting_price;
    $current_winner = 'None';
    $num_bids = 0;
  }

  db_free_result($bid_result);

  // Calculate time to auction end using IST for current time:
  $now = new DateTime("now", new DateTimeZone('Asia/Kolkata'));
  if ($now < $end_time) {
    $time_to_end = date_diff($now, $end_time);
    $time_remaining = ' (in ' . display_time_remaining($time_to_end) . ')';
  }
} else {
  // Item is not part of an auction
  $title = $name;
}

// Watchlist related
$watching = false;
if ($has_session) {
    $watchlist_query = "SELECT 1
                        FROM Watchlist
                        WHERE user_id = '$user_id' AND
                              item_id = $item_id";
    $watchlist_result = db_query($connection, $watchlist_query);
    $watching = db_num_rows($watchlist_result) > 0;
}

// Clean up the result sets
db_free_result($item_result);
if (isset($watchlist_result)) {
  db_free_result($watchlist_result);
}
?>

<div class="container">
  <div class="row mt-4 mb-4"> <!-- Top row with title and watch button -->
    <div class="col-md-8"> <!-- Left col -->
      <h2 class="my-3"><?php echo ($title); ?></h2>
    </div>

    <?php if ($auction_exists && $account_type == '1'): ?>
      <div class="col-md-4 align-self-center text-right"> <!-- Right col -->
        <!-- Watchlist functionality -->
        <?php if ($now < $end_time) : ?>
          <div id="watch_nowatch" <?php if ($has_session && $watching) echo ('style="display: none"'); ?>>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addToWatchlist()">
              <i class="fa fa-plus" aria-hidden="true"></i> Add to watchlist
            </button>
          </div>
          <div id="watch_watching" <?php if (!$has_session || !$watching) echo ('style="display: none"'); ?>>
            <button type="button" class="btn btn-success btn-sm" disabled>
              <i class="fa fa-eye" aria-hidden="true"></i> Watching
            </button>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeFromWatchlist()">
              <i class="fa fa-times" aria-hidden="true"></i> Remove
            </button>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="row"> <!-- Row with item image -->
    <div class="col-md-8">
      <?php if (!empty($item['photo'])) : ?>
        <img src="<?php echo $item['photo']; ?>" alt="Item Image" class="img-fluid rounded">
      <?php endif; ?>
    </div>
  </div>

  <div class="row mt-3"> <!-- Row with item description and bidding info -->
    <div class="col-md-8"> <!-- Left col with item info -->
      <div class="itemDescription">
        <p><?php echo ($description); ?></p>
      </div>
    </div>

    <?php if ($auction_exists): ?>
      <div class="col-md-4"> <!-- Right col with bidding info -->
        <div class="card">
          <div class="card-body">
            <?php if ($now > $end_time) : ?>
              <h5 class="card-title">Auction Ended</h5>
              <p class="card-text">
                <small class="text-muted"><?php echo htmlspecialchars($end_time->format('j M H:i')); ?></small>
              </p>
              <p>The winning bid was ₹<?php echo number_format($current_price, 2); ?></p>
              <?php if($current_winner != 'None'): ?>
                <p>Winner: <?php echo $current_winner; ?></p>
              <?php endif; ?>
            <?php elseif ($account_type == '1'): ?>
              <h5 class="card-title">Auction Details</h5>
              <p class="card-text">Ends: <?php echo date_format($end_time, 'j M H:i') . $time_remaining ?></p>
              <p class="lead">
                Current bid: ₹<?php echo number_format($current_price, 2); ?>
                <?php if($num_bids == 0): ?>
                  <br><small>(Starting price)</small>
                <?php else: ?>
                  <?php if($has_session && isset($bid_data) && $bid_data && $bid_data['user_id'] == $user_id): ?>
                    <br><small class="text-success">You are the highest bidder, you cannot bid further.</small>
                  <?php else: ?>
                    <br><small>Highest bidder: <?php echo $current_winner; ?></small>
                  <?php endif; ?>
                <?php endif; ?>
              </p>
              <!-- Only display the bidding form if the user is not the highest bidder -->
              <?php if(!($has_session && isset($bid_data) && $bid_data && $bid_data['user_id'] == $user_id)): ?>
              <form method="POST" action="place_bid.php" onsubmit="return validateBid();">
                  <div class="input-group mb-3">
                      <div class="input-group-prepend">
                          <span class="input-group-text">₹</span>
                      </div>
                      <input type="number" class="form-control" id="bid" name="bid" step="0.01">
                  </div>
                  <input type="hidden" name="auction_id" value="<?php echo $auction_id ?>">
                  <!-- Pass the minimum acceptable bid (current bid or starting price) -->
                  <input type="hidden" name="min_bid" id="min_bid" value="<?php echo $current_price ?>">
                  <button type="submit" class="btn btn-primary">Place bid</button>
              </form>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div> <!-- End of right col with bidding info -->
    <?php endif; ?>
  </div> <!-- End of row with item description and bidding info -->
</div> <!-- End of container -->

<?php include_once("footer.php") ?>

<script>
  // Watchlist functions
  function addToWatchlist(button) {
    $.ajax('watchlist_funcs.php', {
      type: "POST",
      data: {
        functionname: 'add_to_watchlist',
        arguments: [<?php echo ($item_id); ?>]
      },
      success: function(obj, textstatus) {
        var objT = obj.trim();
        console.log(objT);
        if (objT == "success") {
          $("#watch_nowatch").hide();
          $("#watch_watching").show();
        } else {
          var mydiv = document.getElementById("watch_nowatch");
          mydiv.appendChild(document.createElement("br"));
          mydiv.appendChild(document.createTextNode("Add to watch failed. Try again later."));
        }
      },
      error: function(obj, textstatus) {
        console.log("Error");
      }
    });
  }

  function removeFromWatchlist(button) {
    $.ajax('watchlist_funcs.php', {
      type: "POST",
      data: {
        functionname: 'remove_from_watchlist',
        arguments: [<?php echo ($item_id); ?>]
      },
      success: function(obj, textstatus) {
        console.log("Success");
        var objT = obj.trim();
        if (objT == "success") {
          $("#watch_watching").hide();
          $("#watch_nowatch").show();
        } else {
          var mydiv = document.getElementById("watch_watching");
          mydiv.appendChild(document.createElement("br"));
          mydiv.appendChild(document.createTextNode("Watch removal failed. Try again later."));
        }
      },
      error: function(obj, textstatus) {
        console.log("Error");
      }
    });
  }

  // Bid validation function: ensures the bid is higher than the minimum required (starting price or current bid)
  function validateBid() {
      var bidValue = parseFloat(document.getElementById("bid").value);
      var minBid = parseFloat(document.getElementById("min_bid").value);
      if (isNaN(bidValue)) {
          alert("Please enter a valid bid amount.");
          return false;
      }
      if (bidValue <= minBid) {
          alert("You need to bid higher than the starting price.");
          return false;
      }
      return true;
  }
</script>
