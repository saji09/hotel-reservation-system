<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';
?>

<div class="hero">
    <div class="hero-content">
        <h1>Welcome to Hotel Reserve</h1>
        <p>Book your perfect stay with us and enjoy world-class amenities</p>
        <a href="<?php echo BASE_URL; ?>/reservations.php" class="btn btn-primary">Book Now</a>
    </div>
</div>

<div class="features">
    <div class="feature">
        <i class="fas fa-hotel"></i>
        <h3>Luxury Rooms</h3>
        <p>Experience comfort in our beautifully designed rooms</p>
    </div>
    <div class="feature">
        <i class="fas fa-utensils"></i>
        <h3>Fine Dining</h3>
        <p>Enjoy exquisite cuisine from our award-winning restaurants</p>
    </div>
    <div class="feature">
        <i class="fas fa-spa"></i>
        <h3>Spa & Wellness</h3>
        <p>Relax and rejuvenate with our premium spa services</p>
    </div>
</div>

<div class="testimonials">
    <h2>What Our Guests Say</h2>
    <div class="testimonial">
        <p>"The best hotel experience I've ever had. The staff was incredibly attentive!"</p>
        <div class="author">- Sarah Johnson</div>
    </div>
    <div class="testimonial">
        <p>"Beautiful rooms and amazing service. Will definitely come back."</p>
        <div class="author">- Michael Chen</div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>