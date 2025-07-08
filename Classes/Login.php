<?php

class Login extends Connection{
    private $email;
    private $password;

    public function __construct($email, $password)
    {
        $this->email = $email;
        $this->password = $password;
    }

    public function CheckLoginDetails($link) {
        $conn = $this->connect();
    
        if ($conn->connect_error) {
            echo json_encode(["status" => "error", "message" => "Database connection failed."]);
            exit();
        }
    
        $stmt = $conn->prepare("SELECT Password FROM users WHERE Email=? AND Account_activation_hash IS NULL");
    
        if ($stmt) {
            $stmt->bind_param("s", $this->email);
            $stmt->execute();
            $stmt->store_result();
    
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($storedHash);
                $stmt->fetch();
    
                if (password_verify($this->password, $storedHash)) {
                    $token = (new Functions)->generateToken(3600, $this->email);
                    $stmt = $conn->prepare("UPDATE users SET Token = ? WHERE Email = ?");
                    $stmt->bind_param("ss", $token, $this->email);
                    if($stmt->execute()){
                        setcookie("refreshToken", $token, [
                            "expires" => time() + 3600,
                            "path" => "/",
                            "domain" => $link,
                            "secure" => false,
                            "httponly" => true,
                            "samesite" => "Lax"
                        ]);
                    
                        echo json_encode(["status" => "success", "message" => "Login successful"]);
                    } else {
                        echo json_encode(["status" => "error", "message" => "Failed to save a token, please try again"]);
                    }
                    

                    
                } else {
                    echo json_encode(["status" => "error", "message" => "Invalid password"]);
                }
            } else {
                echo json_encode(["status" => "error", "message" => "User not found or account not activated"]);
            }
    
            $stmt->close();
        } else {
            echo json_encode(["status" => "error", "message" => "Database query failed"]);
        }
    
        $conn->close();
    }
    
}