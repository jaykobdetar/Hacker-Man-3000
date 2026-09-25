<?php

class EmailVerification {

    private $code;
    private $session;
    private $pdo;
    
    public function __construct(){
        
        $this->session = new Session();
        $this->pdo = PDO_DB::factory();        
        
    }
    
    private function generateKey(){        
        // 25 characters, as expected by welcome.php and the email_verification table
        return 'he' . substr(bin2hex(random_bytes(12)), 0, 23);
    }
    
    private function saveKey($userID, $email){
                
        $this->session->newQuery();

        $sql = 'INSERT INTO email_verification (userID, email, code) VALUES (:userID, :email, :code)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array(':userID' => $userID, ':email' => $email, ':code' => $this->code));
                
        $this->session->newQuery();
        $sql = SqlQuery::make('SELECT COUNT(*) AS total FROM email_verification WHERE userID = ? LIMIT 1', [SqlQuery::num($userID)]);
        if($this->pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total == 1){
            return TRUE;
        } else {
            return FALSE;
        }
        
    }
    
    public function sendMail($userID, $email, $username){
        
        $this->code = self::generateKey();
        
        if(!self::saveKey($userID, $email)){
            return FALSE;
        }        
        
        require_once __DIR__.'/Mailer.class.php';
        $mailer = new Mailer();
        return $mailer->send('verify', Array('to' => $email, 'user' => $username, 'key' => $this->code), $this->session->l);        
    }
    
    private function issetCode($userID){
        
        $this->session->newQuery();

        $sql = 'SELECT COUNT(*) AS total FROM email_verification WHERE userID = :userID LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array(':userID' => $userID));
        
        if($stmt->fetch(PDO::FETCH_OBJ)->total == 1){
            return TRUE;
        }
            
        return FALSE;
        
    }
    
    private function getCode($userID){
        
        $this->session->newQuery();

        $sql = 'SELECT code FROM email_verification WHERE userID = :userID LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array(':userID' => $userID));
        
        return $stmt->fetch(PDO::FETCH_OBJ)->code;
        
    }   
    
    private function removeKey($userID){
        
        $this->session->newQuery();
        $sql = 'SELECT login, email FROM users WHERE id = :userID LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array(':userID' => $userID));
        $userInfo = $stmt->fetch(PDO::FETCH_OBJ);
        
        require_once __DIR__.'/Mailer.class.php';
        $mailer = new Mailer();
        $mailer->send('welcome', Array('to' => $userInfo->email, 'user' => $userInfo->login), $this->session->l);
        
        $this->session->newQuery();
        $sql = SqlQuery::make('DELETE FROM email_verification WHERE userID = ?', [SqlQuery::num($userID)]);
        $this->pdo->query($sql);
        
    }
    
    public function isVerified($userID){
        if(self::issetCode($userID)){
            return FALSE;
        }
        return TRUE;
    }
    
    public function verify($userID, $code){
        
        if(!self::issetCode($userID)){
            return TRUE;
        }
        
        if(is_string($code) && hash_equals((string) self::getCode($userID), $code)){
            self::removeKey($userID);
            return TRUE;
        } else {
            return FALSE;
        }        
        
    }
    
    public function codeOnlyVerification($code){
                
        $this->session->newQuery();

        $sql = 'SELECT COUNT(*) AS total, userID FROM email_verification WHERE code = :code LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array(':code' => $code));
        $data = $stmt->fetch(PDO::FETCH_OBJ);

        if($data->total == 1){
            
            self::removeKey($data->userid);
            return $data->userid;
            
        }
        
        return 0;
        
        
        
    }
    
    
}