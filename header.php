<?php
session_start();
// Uncomment and set these variables as needed once you implement proper authentication
// $_SESSION['logged_in'] = false;
// $_SESSION['account_type'] = 'seller';
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <!-- Bootstrap and FontAwesome CSS -->
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awsome-4.7.0.min.css">

  <!-- Custom CSS file -->
  <link rel="stylesheet" href="css/custom.css">
  
  <title>Bid Now!</title>
</head>

<body>

  <!-- Navbars -->
  <nav class="navbar navbar-expand-lg navbar-light bg-light mx-2">
    <a class="navbar-brand" href="dashboard.php">Bid Now!</a>
  </nav>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <ul class="navbar-nav align-middle">
      <li class="nav-item mx-1">
        <a class="nav-link" href="browse.php">Browse</a>
      </li>
      <?php
      if (isset($_SESSION['account_type']) && $_SESSION['account_type'] == '1') {
        echo ('
          <li class="nav-item mx-1">
            <a class="nav-link" href="mybids.php">My Bids</a>
          </li>
          <li class="nav-item mx-1">
            <a class="nav-link" href="watchlist.php">Watchlist</a>
          </li>
          <li class="nav-item mx-1">
            <a class="nav-link" href="recommendations.php">Recommended</a>
          </li>
          <li class="nav-item mx-1">
            <a class="nav-link" href="won_auctions.php">My Won Auctions</a>
          </li>');
      }
      if (isset($_SESSION['account_type']) && $_SESSION['account_type'] == '0') {
        echo ('
          <li class="nav-item mx-1">
            <a class="nav-link" href="mylistings.php">My Listings</a>
          </li>
          <li class="nav-item ml-3">
            <a class="nav-link btn border-light" href="create_auction.php">+ Create auction</a>
          </li>');
      }
      ?>
    </ul>

    <ul class="navbar-nav ml-auto">
      <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] == true) { ?>
        <a href="logout.php" class="btn btn-link btn-sm">Logout</a>
      <?php } else { ?>
        <!-- Login button now directly links to login.php -->
        <a href="login.php" class="btn btn-link btn-sm">Login</a>
      <?php } ?>
    </ul>
  </nav>

</body>
</html>
