<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;


class Functions extends Connection{
    public function __construct()
    {
        // $this->generatePrivatePublicKey();
    }

    private function generatePrivatePublicKey() {
        $config = [
            "config" => "C:/xampp/php/extras/openssl/openssl.cnf",
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA
        ];
    
        $privateKey = openssl_pkey_new($config);
    
        openssl_pkey_export($privateKey, $privateKeyPEM, NULL, $config);
    
        $publicKeyDetails = openssl_pkey_get_details($privateKey);
        $publicKeyPEM = $publicKeyDetails["key"];
    
        file_put_contents('C:/xampp/htdocs/react_backend/keys/private_key.pem', $privateKeyPEM);
        file_put_contents('C:/xampp/htdocs/react_backend/keys/public_key.pem', $publicKeyPEM);
    
        echo "Private and public keys generated successfully";
    }
    public function generateToken($exp, $email): string {
        $privateKey = file_get_contents("C:/xampp/htdocs/react_backend/keys/private_key.pem");

        $payload = [
            "iss" => "Dule",
            "sub" => "token",
            "iat" => time(),
            "exp" => time() + $exp,
            "uid" => $email
        ];

        return JWT::encode($payload, $privateKey, "RS256");
    }
    public function validateToken($token) {
        if($token == null) return false;
        $publicKey = file_get_contents("C:/xampp/htdocs/react_backend/keys/public_key.pem");
        try{
            $decoded = JWT::decode($token, new Key($publicKey, 'RS256'));

            $conn = $this->connect();

            $stmt = $conn->prepare("SELECT Token, Id FROM users WHERE Email = ?");
            $stmt->bind_param("s", $decoded->uid);
            $stmt->execute();
            $stmt->bind_result($refreshToken, $userId);

            if($stmt->fetch()){
                $stmt->close();
                
                if($refreshToken == $token){
                    return $userId;
                }else{
                    return false;
                }

            }else {
                return false;
            }
        }catch(ExpiredException $e){
            if($e->getMessage() == "Expired token"){
                return false;
            }
        }catch(Exception $e){
            return false;
            exit;
        }
    }

    public function getEmailFromToken(){

    }
}
