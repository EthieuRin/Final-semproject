<?php
// Email configuration
class EmailConfig {
    // SMTP Configuration
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 587;
    const SMTP_USERNAME = 'lowengel10@gmail.com'; 
    const SMTP_PASSWORD = 'alnd qqjf tilc pgqk'; 
    const SMTP_ENCRYPTION = 'tls';
    
    
    // Email settings
    const FROM_EMAIL = 'noreply@eventzilla.com';
    const FROM_NAME = 'Eventzilla';
    const REPLY_TO = 'support@eventzilla.com';
    
    // Email templates
    const VERIFICATION_SUBJECT = 'Verify Your Email - Eventzilla';
    const RESET_PASSWORD_SUBJECT = 'Reset Your Password - Eventzilla';
    const WELCOME_SUBJECT = 'Welcome to Eventzilla!';
}

// Email service class
class EmailService {
    private $mailer;
    
    public function __construct() {
        // Check if PHPMailer is available
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            // For development, we'll use a simple mail function
             
            return;
        }
        
        $this->mailer = new PHPMailer\PHPMailer\PHPMailer(true);
        $this->configureSMTP();
    }
    
    private function configureSMTP() {
        if (!$this->mailer) return;
        
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host       = EmailConfig::SMTP_HOST;
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = EmailConfig::SMTP_USERNAME;
            $this->mailer->Password   = EmailConfig::SMTP_PASSWORD;
            $this->mailer->SMTPSecure = EmailConfig::SMTP_ENCRYPTION;
            $this->mailer->Port       = EmailConfig::SMTP_PORT;
            
            // Default sender
            $this->mailer->setFrom(EmailConfig::FROM_EMAIL, EmailConfig::FROM_NAME);
            $this->mailer->addReplyTo(EmailConfig::REPLY_TO, EmailConfig::FROM_NAME);
        } catch (Exception $e) {
            error_log("SMTP Configuration Error: " . $e->getMessage());
        }
    }
    
    public function sendVerificationEmail($email, $firstName, $verificationToken) {
        $subject = EmailConfig::VERIFICATION_SUBJECT;
        $verificationLink = $this->getBaseUrl() . "/pages/auth/verify.php?token=" . $verificationToken;
        
        $htmlBody = $this->getVerificationEmailTemplate($firstName, $verificationLink);
        $textBody = "Hi $firstName,\n\nPlease verify your email by clicking this link: $verificationLink\n\nThank you,\nEventzilla Team";
        
        return $this->sendEmail($email, $firstName, $subject, $htmlBody, $textBody);
    }
    
    public function sendPasswordResetEmail($email, $firstName, $resetToken) {
        $subject = EmailConfig::RESET_PASSWORD_SUBJECT;
        $resetLink = $this->getBaseUrl() . "/pages/auth/reset-password.php?token=" . $resetToken;
        
        $htmlBody = $this->getPasswordResetEmailTemplate($firstName, $resetLink);
        $textBody = "Hi $firstName,\n\nClick this link to reset your password: $resetLink\n\nIf you didn't request this, please ignore this email.\n\nEventzilla Team";
        
        return $this->sendEmail($email, $firstName, $subject, $htmlBody, $textBody);
    }
    
    public function sendWelcomeEmail($email, $firstName) {
        $subject = EmailConfig::WELCOME_SUBJECT;
        $loginLink = $this->getBaseUrl() . "/pages/auth/login.php";
        
        $htmlBody = $this->getWelcomeEmailTemplate($firstName, $loginLink);
        $textBody = "Hi $firstName,\n\nWelcome to Eventzilla! Your email has been verified successfully.\n\nLogin here: $loginLink\n\nEventzilla Team";
        
        return $this->sendEmail($email, $firstName, $subject, $htmlBody, $textBody);
    }
    
    private function sendEmail($email, $name, $subject, $htmlBody, $textBody) {
        if ($this->mailer) {
            return $this->sendWithPHPMailer($email, $name, $subject, $htmlBody, $textBody);
        } else {
            return $this->sendWithBuiltInMail($email, $subject, $htmlBody);
        }
    }
    
    private function sendWithPHPMailer($email, $name, $subject, $htmlBody, $textBody) {
        try {
            // Recipients
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $name);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $htmlBody;
            $this->mailer->AltBody = $textBody;
            
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendWithBuiltInMail($email, $subject, $htmlBody) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: ' . EmailConfig::FROM_NAME . ' <' . EmailConfig::FROM_EMAIL . '>' . "\r\n";
        
        return mail($email, $subject, $htmlBody, $headers);
    }
    
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = dirname($_SERVER['SCRIPT_NAME']) ?? '';
        return $protocol . '://' . $host . rtrim($path, '/');
    }
    
    private function getVerificationEmailTemplate($firstName, $verificationLink) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Verify Your Email</title>
        </head>
        <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background-color: #f8f9fa; padding: 30px; border-radius: 10px;'>
                <h1 style='color: #333; text-align: center;'>Welcome to Eventzilla!</h1>
                <p style='font-size: 16px; color: #555;'>Hi $firstName,</p>
                <p style='font-size: 16px; color: #555;'>Thank you for registering with Eventzilla. Please verify your email address by clicking the button below:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$verificationLink' style='background-color: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px;'>Verify Email</a>
                </div>
                <p style='font-size: 14px; color: #666;'>If the button doesn't work, copy and paste this link into your browser:</p>
                <p style='font-size: 14px; color: #007bff; word-break: break-all;'>$verificationLink</p>
                <hr style='margin: 30px 0;'>
                <p style='font-size: 12px; color: #999; text-align: center;'>© 2024 Eventzilla. All rights reserved.</p>
            </div>
        </body>
        </html>";
    }
    
    private function getPasswordResetEmailTemplate($firstName, $resetLink) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Reset Your Password</title>
        </head>
        <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background-color: #f8f9fa; padding: 30px; border-radius: 10px;'>
                <h1 style='color: #333; text-align: center;'>Password Reset Request</h1>
                <p style='font-size: 16px; color: #555;'>Hi $firstName,</p>
                <p style='font-size: 16px; color: #555;'>You requested to reset your password. Click the button below to create a new password:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$resetLink' style='background-color: #dc3545; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px;'>Reset Password</a>
                </div>
                <p style='font-size: 14px; color: #666;'>If you didn't request this, please ignore this email. The link will expire in 1 hour.</p>
                <p style='font-size: 14px; color: #666;'>If the button doesn't work, copy and paste this link into your browser:</p>
                <p style='font-size: 14px; color: #007bff; word-break: break-all;'>$resetLink</p>
                <hr style='margin: 30px 0;'>
                <p style='font-size: 12px; color: #999; text-align: center;'>© 2024 Eventzilla. All rights reserved.</p>
            </div>
        </body>
        </html>";
    }
    
    private function getWelcomeEmailTemplate($firstName, $loginLink) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Welcome to Eventzilla</title>
        </head>
        <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background-color: #f8f9fa; padding: 30px; border-radius: 10px;'>
                <h1 style='color: #333; text-align: center;'>Welcome to Eventzilla!</h1>
                <p style='font-size: 16px; color: #555;'>Hi $firstName,</p>
                <p style='font-size: 16px; color: #555;'>Your email has been verified successfully! You can now start creating and managing events with Eventzilla.</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$loginLink' style='background-color: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px;'>Login to Dashboard</a>
                </div>
                <p style='font-size: 14px; color: #666;'>Get ready to:</p>
                <ul style='font-size: 14px; color: #666;'>
                    <li>Create amazing events</li>
                    <li>Manage attendees</li>
                    <li>Track registrations</li>
                    <li>And much more!</li>
                </ul>
                <hr style='margin: 30px 0;'>
                <p style='font-size: 12px; color: #999; text-align: center;'>© 2024 Eventzilla. All rights reserved.</p>
            </div>
        </body>
        </html>";
    }
}
?>
