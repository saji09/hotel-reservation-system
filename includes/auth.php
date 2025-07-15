<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user role
function checkRole($allowedRoles) {
    if (!isLoggedIn() || !in_array($_SESSION['role'], $allowedRoles)) {
        header("Location: ".BASE_URL."/login.php");
        exit();
    }
}

// Login function
function login($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        
        // Redirect based on role
        switch ($user['role']) {
            case 'admin':
                header("Location: ".BASE_URL."/admin/dashboard.php");
                break;
            case 'clerk':
                header("Location: ".BASE_URL."/clerk/dashboard.php");
                break;
            case 'travel_company':
                header("Location: ".BASE_URL."/travel_company/dashboard.php");
                break;
            default:
                header("Location: ".BASE_URL."/customer/dashboard.php");
        }
        exit();
    }
    
    return false;
}

// Logout function
function logout() {
    session_unset();
    session_destroy();
    header("Location: ".BASE_URL."/login.php");
    exit();
}

// Register new user
function registerUser($data) {
    global $pdo;
    
    // Validate data
    $required = ['username', 'password', 'email', 'first_name', 'last_name', 'role'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'message' => "$field is required"];
        }
    }
    
    // Check if username exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$data['username']]);
    if ($stmt->fetchColumn() > 0) {
        return ['success' => false, 'message' => 'Username already exists'];
    }
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetchColumn() > 0) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    // Hash password
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, first_name, last_name, phone, address) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['username'],
        $hashedPassword,
        $data['email'],
        $data['role'],
        $data['first_name'],
        $data['last_name'],
        $data['phone'] ?? null,
        $data['address'] ?? null
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // Automatically add to customers table if role is customer
    if ($data['role'] == 'customer') {
        $stmt = $pdo->prepare("INSERT INTO customers (customer_id, credit_card_info) VALUES (?, ?)");
        $stmt->execute([
            $userId,
            $data['credit_card_info'] ?? null
        ]);
    } elseif ($data['role'] == 'travel_company') {
        $stmt = $pdo->prepare("INSERT INTO travel_companies (company_id, company_name, discount_rate, billing_address) 
                              VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $data['company_name'],
            $data['discount_rate'] ?? 0.10,
            $data['billing_address']
        ]);
    }
    
    return ['success' => true, 'user_id' => $userId];
}
?>