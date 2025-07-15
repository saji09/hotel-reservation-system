<?php
require_once 'auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Reservation System</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <h1><a href="<?php echo BASE_URL; ?>">Hotel<span>Reserve</span></a></h1>
            </div>
            <nav>
                <ul>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="<?php echo BASE_URL; ?>/<?php echo $_SESSION['role']; ?>/dashboard.php">Dashboard</a></li>
                        <?php if ($_SESSION['role'] == 'customer'): ?>
                            <li><a href="<?php echo BASE_URL; ?>/reservations.php">Book a Room</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/customer/reservations.php">My Reservations</a></li>
                        <?php elseif ($_SESSION['role'] == 'clerk'): ?>
                            <li><a href="<?php echo BASE_URL; ?>/clerk/checkin.php">Check In</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/clerk/checkout.php">Check Out</a></li>
                        <?php elseif ($_SESSION['role'] == 'travel_company'): ?>
                            <li><a href="<?php echo BASE_URL; ?>/travel_company/block_bookings.php">Block Bookings</a></li>
                        <?php elseif ($_SESSION['role'] == 'admin'): ?>
                            <li><a href="<?php echo BASE_URL; ?>/admin/reports.php">Reports</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/admin/rooms.php">Manage Rooms</a></li>
                        <?php endif; ?>
                        <li class="user-menu">
                            <a href="#"><i class="fas fa-user-circle"></i> <?php echo $_SESSION['first_name']; ?></a>
                            <ul>
                                <li><a href="<?php echo BASE_URL; ?>/<?php echo $_SESSION['role']; ?>/profile.php">Profile</a></li>
                                <li><a href="<?php echo BASE_URL; ?>/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li><a href="<?php echo BASE_URL; ?>/login.php">Login</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/register.php">Register</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/reservations.php">Book a Room</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">