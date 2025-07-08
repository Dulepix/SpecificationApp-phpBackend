<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
require_once "../Classes/WebLink/Link.php";


class Signup extends Connection {
    private $name;
    private $username;
    private $email;
    private $password;
    private $repeatpassword;

    public function __construct($name, $username, $email, $password, $repeatpassword) {
        $this->name = trim($name);
        $this->username = trim($username);
        $this->email = trim($email);
        $this->password = $password;
        $this->repeatpassword = $repeatpassword;
    }

    public function insertUser() {
        $conn = $this->connect();

        if ($conn->connect_error) {
            echo json_encode(["status" => "error", "message" => "Database connection failed."]);
            exit();
        }

        if (!$this->checkEmailAvailability($conn)) {
            echo json_encode(["status" => "error", "message" => "Email already taken!"]);
            $conn->close();
            exit();
        }

        if (!$this->checkPasswordMatch($conn)) {
            echo json_encode(["status" => "error", "message" => "Passwords do not match!"]);
            $conn->close();
            exit();
        }

        if (!$this->checkUsernameAvailability($conn)) {
            echo json_encode(["status" => "error", "message" => "Username already taken!"]);
            $conn->close();
            exit();
        }

        $hashedPwd = password_hash($this->password, PASSWORD_BCRYPT);

        $activation_token = bin2hex(random_bytes(16));
        $activation_token_hash = hash("sha256", $activation_token);

        $stmt = $conn->prepare("INSERT INTO users (Name, Email, Username, Password, Account_activation_hash) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssss", $this->name, $this->email, $this->username, $hashedPwd, $activation_token_hash);

            $link = new Link();

            $validationLink = $link->getLink() . "/validate?token=" . $activation_token;

            $email_subject = "Account Verification - SpecApp";
            $email_body = "Hello $this->name,<br><br>Click the following link to verify your account: <a href=\"$validationLink\">Verify Account</a><br><br>Thank you!";
            $email_altbody = "Hello $this->name, Please visit this link to verify your account: $validationLink";

            $emailResult = $this->sendVerificationEmail($this->name, $this->email, $email_subject, $email_body, $email_altbody);

            if ($stmt->execute() && $emailResult === true) {
                echo json_encode(["status" => "success", "message" => "User registered successfully. Please check your email for verification."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error: " . $emailResult]);
            }

            $stmt->close();
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to prepare SQL statement."]);
        }

        $conn->close();
    }

    private function checkPasswordMatch() {
        return $this->password === $this->repeatpassword;
    }

    private function checkUsernameAvailability($conn) {
        $stmt = $conn->prepare("SELECT Username FROM users WHERE Username = ?");

        if ($stmt) {
            $stmt->bind_param("s", $this->username);
            $stmt->execute();
            $stmt->store_result();

            $isAvailable = ($stmt->num_rows === 0);
            $stmt->close();
            return $isAvailable;
        } else {
            echo json_encode(["status" => "error", "message" => "Query preparation failed: " . $conn->error]);
            exit();
        }
    }

    private function sendVerificationEmail($fullname, $email, $subject, $body, $altbody) {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'specifications.app';
            $mail->Password   = 'brmd hcqk fdwx kojc';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            $mail->setFrom('specifications.app@gmail.com', 'Admin');
            $mail->addAddress($email, $fullname);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $altbody;

            $mail->send();
            return true;

        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => "Failed to send verification email!"]);
            exit();
        }
    }

    private function checkEmailAvailability($conn){
        $stmt = $conn->prepare("SELECT Email FROM users WHERE Email = ?");

        if($stmt){
            $stmt->bind_param("s", $this->email);
            $stmt->execute();
            $stmt->store_result();

            $isAvailable = ($stmt->num_rows() === 0);
            $stmt->close();
            return $isAvailable;
        }else{
            echo json_encode(["status" => "error", "message" => "Query preparation failed: " . $conn->error]);
        }
    }
}
