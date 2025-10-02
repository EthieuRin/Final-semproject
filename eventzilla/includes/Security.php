<?php
class Security {
    
    // Generate CSRF token
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    // Validate CSRF token
    public static function validateCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // Sanitize input
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
    
    // Validate email
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    // Validate password strength
    public static function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    // Validate phone number
    public static function validatePhone($phone) {
        // Remove all non-digit characters
        $cleanPhone = preg_replace('/\D/', '', $phone);
        
        // Check if it's a valid length (10-15 digits)
        return strlen($cleanPhone) >= 10 && strlen($cleanPhone) <= 15;
    }
    
    // Validate name
    public static function validateName($name) {
        // Name should be 2-50 characters, only letters, spaces, hyphens, and apostrophes
        return preg_match('/^[a-zA-Z\s\-\']{2,50}$/', $name);
    }
    
    // Rate limiting check
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 900) { // 15 minutes
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = 'rate_limit_' . $identifier;
        $now = time();
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }
        
        // Clean old attempts outside time window
        $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        // Check if exceeded limit
        if (count($_SESSION[$key]) >= $maxAttempts) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_time' => min($_SESSION[$key]) + $timeWindow
            ];
        }
        
        // Add current attempt
        $_SESSION[$key][] = $now;
        
        return [
            'allowed' => true,
            'remaining' => $maxAttempts - count($_SESSION[$key]),
            'reset_time' => null
        ];
    }
    
    // Check if request is AJAX
    public static function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
    
    // Get client IP address
    public static function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, 
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    // Generate secure random string
    public static function generateRandomString($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    // Hash sensitive data
    public static function hashData($data, $salt = '') {
        return hash('sha256', $data . $salt);
    }
    
    // Verify hash
    public static function verifyHash($data, $hash, $salt = '') {
        return hash_equals($hash, self::hashData($data, $salt));
    }
}

// Session management class
class SessionManager {
    
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            
            session_start();
            
            // Regenerate session ID periodically for security
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    public static function get($key, $default = null) {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    public static function remove($key) {
        self::start();
        unset($_SESSION[$key]);
    }
    
    public static function destroy() {
        if (session_status() !== PHP_SESSION_NONE) {
            session_destroy();
        }
    }
    
    public static function setUser($userData) {
        self::set('user_id', $userData['id']);
        self::set('user_email', $userData['email']);
        self::set('user_name', $userData['first_name'] . ' ' . $userData['last_name']);
        self::set('session_token', $userData['session_token']);
        self::set('login_time', time());
    }
    
    public static function getUser() {
        self::start();
        
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'],
            'name' => $_SESSION['user_name'],
            'session_token' => $_SESSION['session_token'],
            'login_time' => $_SESSION['login_time']
        ];
    }
    
    public static function isLoggedIn() {
        return self::getUser() !== null;
    }
    
    public static function logout() {
        self::start();
        
        // Clear user-related session data
        $keysToRemove = ['user_id', 'user_email', 'user_name', 'session_token', 'login_time'];
        foreach ($keysToRemove as $key) {
            self::remove($key);
        }
        
        // Regenerate session ID
        session_regenerate_id(true);
    }
}

// Form validation class
class Validator {
    
    private $errors = [];
    private $data = [];
    
    public function __construct($data) {
        $this->data = Security::sanitizeInput($data);
    }
    
    public function required($field, $message = null) {
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field][] = $message ?: ucfirst($field) . ' is required';
        }
        return $this;
    }
    
    public function email($field, $message = null) {
        if (isset($this->data[$field]) && !Security::validateEmail($this->data[$field])) {
            $this->errors[$field][] = $message ?: 'Invalid email format';
        }
        return $this;
    }
    
    public function minLength($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field][] = $message ?: ucfirst($field) . " must be at least $length characters";
        }
        return $this;
    }
    
    public function maxLength($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field][] = $message ?: ucfirst($field) . " cannot exceed $length characters";
        }
        return $this;
    }
    
    public function password($field, $message = null) {
        if (isset($this->data[$field])) {
            $validation = Security::validatePassword($this->data[$field]);
            if (!$validation['valid']) {
                $this->errors[$field] = array_merge($this->errors[$field] ?? [], $validation['errors']);
            }
        }
        return $this;
    }
    
    public function phone($field, $message = null) {
        if (isset($this->data[$field]) && !Security::validatePhone($this->data[$field])) {
            $this->errors[$field][] = $message ?: 'Invalid phone number format';
        }
        return $this;
    }
    
    public function name($field, $message = null) {
        if (isset($this->data[$field]) && !Security::validateName($this->data[$field])) {
            $this->errors[$field][] = $message ?: 'Invalid name format';
        }
        return $this;
    }
    
    public function match($field1, $field2, $message = null) {
        if (isset($this->data[$field1], $this->data[$field2])) {
            if ($this->data[$field1] !== $this->data[$field2]) {
                $this->errors[$field2][] = $message ?: 'Passwords do not match';
            }
        }
        return $this;
    }
    
    public function csrf($token, $message = null) {
        if (!Security::validateCSRFToken($token)) {
            $this->errors['csrf'][] = $message ?: 'Invalid security token';
        }
        return $this;
    }
    
    public function isValid() {
        return empty($this->errors);
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    public function getFirstError($field) {
        return $this->errors[$field][0] ?? null;
    }
    
    public function getAllErrors() {
        $allErrors = [];
        foreach ($this->errors as $field => $fieldErrors) {
            $allErrors = array_merge($allErrors, $fieldErrors);
        }
        return $allErrors;
    }
    
    public function getData() {
        return $this->data;
    }
    
   
    }

?>
