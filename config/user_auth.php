<?php
/**
 * User Authentication Helper Functions
 */

function ensureSessionStarted() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

function connectUserDB() {
    // Create database connection directly
    $host = 'localhost';
    $db   = 'testbook'; // Change to your database name
    $user = 'root';    // Change to your DB user
    $pass = '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        throw new Exception('Database connection failed: ' . $e->getMessage());
    }
}

function registerUser($firstName, $lastName, $email, $password, $phone = null, $dateOfBirth = null, $gender = null, $healthQuestionnaire = null) {
    try {
        $pdo = connectUserDB();
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Email address already registered');
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Encode health questionnaire as JSON if provided
        $healthJson = null;
        if ($healthQuestionnaire && is_array($healthQuestionnaire)) {
            $healthQuestionnaire['completed_at'] = date('Y-m-d H:i:s');
            $healthJson = json_encode($healthQuestionnaire);
        }
        
        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users (first_name, last_name, email, password_hash, phone, date_of_birth, gender, health_questionnaire) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$firstName, $lastName, $email, $passwordHash, $phone, $dateOfBirth, $gender, $healthJson]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log('User registration error: ' . $e->getMessage());
        throw new Exception('Registration failed: ' . $e->getMessage());
    }
}

function loginUser($email, $password) {
    try {
        $pdo = connectUserDB();
        
        $stmt = $pdo->prepare("
            SELECT id, first_name, last_name, email, password_hash, status 
            FROM users 
            WHERE email = ? AND status = 'active'
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new Exception('Invalid email or password');
        }
        
        // Set session variables
        ensureSessionStarted();
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_first_name'] = $user['first_name'];
        
        return $user;
    } catch (PDOException $e) {
        error_log('User login error: ' . $e->getMessage());
        throw new Exception('Login failed: ' . $e->getMessage());
    }
}

function logoutUser() {
    ensureSessionStarted();
    session_unset();
    session_destroy();
}

function isUserLoggedIn() {
    ensureSessionStarted();
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

function requireUserLogin() {
    if (!isUserLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function getUserInfo() {
    if (!isUserLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'],
        'name' => $_SESSION['user_name'],
        'first_name' => $_SESSION['user_first_name']
    ];
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    // At least 8 characters, contains letter and number
    return strlen($password) >= 8 && preg_match('/[A-Za-z]/', $password) && preg_match('/[0-9]/', $password);
}

function validateDateOfBirth($dob) {
    if (empty($dob)) return true; // Optional field
    
    $date = DateTime::createFromFormat('Y-m-d', $dob);
    if (!$date || $date->format('Y-m-d') !== $dob) {
        return false;
    }
    
    // Check age limits (must be at least 13 years old, max 120 years old)
    $now = new DateTime();
    $age = $now->diff($date)->y;
    return $age >= 13 && $age <= 120;
}

function validateGender($gender) {
    if (empty($gender)) return true; // Optional field
    return in_array($gender, ['male', 'female', 'other', 'prefer_not_to_say']);
}

function validateHealthQuestionnaire($questionnaire) {
    if (empty($questionnaire) || !is_array($questionnaire)) {
        return true; // Optional field
    }
    
    // Check required emergency contact fields if any health issues are indicated
    $hasHealthIssues = ($questionnaire['has_medical_conditions'] ?? false) || 
                      ($questionnaire['takes_medication'] ?? false) || 
                      ($questionnaire['has_injuries'] ?? false) || 
                      ($questionnaire['has_allergies'] ?? false);
    
    if ($hasHealthIssues) {
        $requiredFields = ['emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship'];
        foreach ($requiredFields as $field) {
            if (empty($questionnaire[$field])) {
                return false;
            }
        }
    }
    
    // Validate consent
    if (!isset($questionnaire['consent_medical_emergency']) || $questionnaire['consent_medical_emergency'] !== true) {
        return false;
    }
    
    return true;
}
?>