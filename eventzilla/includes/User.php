<?php
require_once '../config/database.php';
require_once '../config/email.php';

class User {
    private $conn;
    private $emailService;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->emailService = new EmailService();
    }
    
    // Register new user
    public function register($firstName, $lastName, $email, $password, $phone = null) {
        try {
            // Check if email already exists
            if ($this->emailExists($email)) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            // Generate verification token
            $verificationToken = bin2hex(random_bytes(32));
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user into database
            $query = "INSERT INTO users (first_name, last_name, email, password, phone, verification_token, status) 
                     VALUES (:first_name, :last_name, :email, :password, :phone, :verification_token, 'inactive')";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':first_name', $firstName);
            $stmt->bindParam(':last_name', $lastName);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':verification_token', $verificationToken);
            
            if ($stmt->execute()) {
                // Send verification email
                $emailSent = $this->emailService->sendVerificationEmail($email, $firstName, $verificationToken);
                
                return [
                    'success' => true,
                    'message' => 'Registration successful! Please check your email to verify your account.',
                    'email_sent' => $emailSent
                ];
            } else {
                return ['success' => false, 'message' => 'Registration failed'];
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred during registration'];
        }
    }
    
    // Verify email with token
    public function verifyEmail($token) {
        try {
            $query = "SELECT * FROM users WHERE verification_token = :token AND status = 'inactive'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Update user status
                $updateQuery = "UPDATE users SET email_verified = 1, status = 'active', verification_token = NULL WHERE id = :user_id";
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->bindParam(':user_id', $user['id']);
                
                if ($updateStmt->execute()) {
                    // Send welcome email
                    $this->emailService->sendWelcomeEmail($user['email'], $user['first_name']);
                    
                    return ['success' => true, 'message' => 'Email verified successfully! You can now login.'];
                } else {
                    return ['success' => false, 'message' => 'Verification failed'];
                }
            } else {
                return ['success' => false, 'message' => 'Invalid or expired verification token'];
            }
        } catch (Exception $e) {
            error_log("Email verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Verification failed'];
        }
    }
    
    // Login user
    public function login($email, $password) {
        try {
            $query = "SELECT * FROM users WHERE email = :email AND status = 'active'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($password, $user['password'])) {
                    // Check if email is verified
                    if (!$user['email_verified']) {
                        return ['success' => false, 'message' => 'Please verify your email before logging in'];
                    }
                    
                    // Create session
                    $sessionToken = $this->createSession($user['id']);
                    
                    return [
                        'success' => true,
                        'message' => 'Login successful',
                        'user' => [
                            'id' => $user['id'],
                            'first_name' => $user['first_name'],
                            'last_name' => $user['last_name'],
                            'email' => $user['email'],
                            'phone' => $user['phone'],
                            'profile_image' => $user['profile_image']
                        ],
                        'session_token' => $sessionToken
                    ];
                } else {
                    return ['success' => false, 'message' => 'Invalid password'];
                }
            } else {
                return ['success' => false, 'message' => 'User not found or inactive'];
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed'];
        }
    }
    
    // Create user session
    private function createSession($userId) {
        try {
            $sessionToken = bin2hex(random_bytes(64));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            // Remove old sessions for this user
            $deleteQuery = "DELETE FROM user_sessions WHERE user_id = :user_id";
            $deleteStmt = $this->conn->prepare($deleteQuery);
            $deleteStmt->bindParam(':user_id', $userId);
            $deleteStmt->execute();
            
            // Create new session
            $query = "INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (:user_id, :token, :expires_at)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':token', $sessionToken);
            $stmt->bindParam(':expires_at', $expiresAt);
            
            if ($stmt->execute()) {
                return $sessionToken;
            }
            return null;
        } catch (Exception $e) {
            error_log("Session creation error: " . $e->getMessage());
            return null;
        }
    }
    
    // Validate session
    public function validateSession($sessionToken) {
        try {
            $query = "SELECT u.* FROM users u 
                     JOIN user_sessions s ON u.id = s.user_id 
                     WHERE s.session_token = :token AND s.expires_at > NOW() AND u.status = 'active'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $sessionToken);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            return null;
        } catch (Exception $e) {
            error_log("Session validation error: " . $e->getMessage());
            return null;
        }
    }
    
    // Logout user
    public function logout($sessionToken) {
        try {
            $query = "DELETE FROM user_sessions WHERE session_token = :token";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $sessionToken);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            return false;
        }
    }
    
    // Request password reset
    public function requestPasswordReset($email) {
        try {
            $query = "SELECT * FROM users WHERE email = :email AND status = 'active'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $resetToken = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Update user with reset token
                $updateQuery = "UPDATE users SET reset_token = :token, reset_token_expires = :expires WHERE id = :user_id";
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->bindParam(':token', $resetToken);
                $updateStmt->bindParam(':expires', $expiresAt);
                $updateStmt->bindParam(':user_id', $user['id']);
                
                if ($updateStmt->execute()) {
                    // Send reset email
                    $emailSent = $this->emailService->sendPasswordResetEmail($user['email'], $user['first_name'], $resetToken);
                    
                    return [
                        'success' => true,
                        'message' => 'Password reset link sent to your email',
                        'email_sent' => $emailSent
                    ];
                }
            }
            
            // Return success even if email not found for security
            return ['success' => true, 'message' => 'If the email exists, a reset link will be sent'];
        } catch (Exception $e) {
            error_log("Password reset request error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Password reset request failed'];
        }
    }
    
    // Reset password with token
    public function resetPassword($token, $newPassword) {
        try {
            $query = "SELECT * FROM users WHERE reset_token = :token AND reset_token_expires > NOW()";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // Update password and clear reset token
                $updateQuery = "UPDATE users SET password = :password, reset_token = NULL, reset_token_expires = NULL WHERE id = :user_id";
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->bindParam(':password', $hashedPassword);
                $updateStmt->bindParam(':user_id', $user['id']);
                
                if ($updateStmt->execute()) {
                    return ['success' => true, 'message' => 'Password reset successful'];
                }
            } else {
                return ['success' => false, 'message' => 'Invalid or expired reset token'];
            }
            
            return ['success' => false, 'message' => 'Password reset failed'];
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Password reset failed'];
        }
    }
    
    // Check if email exists
    private function emailExists($email) {
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
    
    // Get user by ID
    public function getUserById($id) {
        try {
            $query = "SELECT id, first_name, last_name, email, phone, profile_image, email_verified, status, created_at FROM users WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            return null;
        } catch (Exception $e) {
            error_log("Get user by ID error: " . $e->getMessage());
            return null;
        }
    }
    
    // Update user profile
    public function updateProfile($userId, $firstName, $lastName, $phone) {
        try {
            $query = "UPDATE users SET first_name = :first_name, last_name = :last_name, phone = :phone WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':first_name', $firstName);
            $stmt->bindParam(':last_name', $lastName);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':user_id', $userId);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Profile updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Profile update failed'];
            }
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Profile update failed'];
        }
    }
}
?>
