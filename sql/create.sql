CREATE DATABASE auction;

USE auction;

CREATE TABLE Users (
    user_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role BOOL, -- 0 for seller, 1 for buyer
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    mobile_no VARCHAR(15) NOT NULL,
    address TEXT NOT NULL,
    UNIQUE(email)
);

CREATE TABLE Item (
    item_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description VARCHAR(511),
    category ENUM('Electronics', 'Fashion', 'Home', 'Books', 'Other') NOT NULL,
    colour ENUM('Red', 'Orange', 'Yellow', 'Green', 'Blue', 'Purple', 'Pink', 'White', 'Grey', 'Black', 'Brown', 'Other'),
    `condition` ENUM('Great', 'Good', 'Okay', 'Poor'),
    photo VARCHAR(255) -- filepath
);

CREATE TABLE Auction (
    auction_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    item_id INT(11),
    user_id INT(11),
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL, -- if end time is earlier than current time, then the auction is over
    auction_title VARCHAR(255),
    reserve_price FLOAT(2),
    starting_price FLOAT(2),
    FOREIGN KEY (item_id) REFERENCES Item(item_id),
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE Bids (
    bid_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    auction_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    time_of_bid DATETIME NOT NULL,
    price FLOAT(2),
    FOREIGN KEY (auction_id) REFERENCES Auction(auction_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE Watchlist (
    user_id INT(11) NOT NULL,
    item_id INT(11) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES Item(item_id) ON DELETE CASCADE,
    PRIMARY KEY (user_id, item_id)
);

-- New Payments table for tracking payment status of won auctions
CREATE TABLE Payments (
    payment_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    auction_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    payment_status ENUM('pending', 'paid') DEFAULT 'pending',
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (auction_id) REFERENCES Auction(auction_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE Admins (
    admin_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    UNIQUE(email)
);
