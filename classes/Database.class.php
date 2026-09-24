<?php

class LRSys {

    public $name;
    public $user;
    private $pass;
    public $email;
    public $keepalive;
    
    private $session;
    private $lang;
    private $pdo;
    private $process;

    private $log;
    private $ranking;
    private $storyline;
    private $clan;
    
    function __construct() {

        $this->pdo = PDO_DB::factory();
        
        require_once __DIR__.'/Session.class.php';
        require_once __DIR__.'/Password.class.php';

        $this->session = new Session();


        require __DIR__.'/Player.class.php';
        require __DIR__.'/PC.class.php';
        require __DIR__.'/Ranking.class.php';
        require __DIR__.'/Storyline.class.php';
        require __DIR__.'/Clan.class.php';

        $this->log = new LogVPC();
        $this->ranking = new Ranking();
        $this->storyline = new Storyline();
        $this->clan = new Clan();

        $this->keepalive = FALSE;
        
    }

    public function set_keepalive($keep){
        $this->keepalive = $keep;
    }

    public function register($regUser, $regPass, $regMail) {

        $this->user = $regUser;
        $this->pass = $regPass;
        $this->email = $regMail;

        $sql = 'SELECT COUNT(*) AS total FROM stats_register WHERE ip = :ip AND TIMESTAMPDIFF(MINUTE, registrationDate, NOW()) < 10';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array(':ip' => Config::clientIp()));
        $spamCheck = $stmt->fetch(PDO::FETCH_OBJ)->total; 

        if($spamCheck >= 1){
            exit('IP blocked for multiple registrations. Try again in 10 minutes.');
        }

        if ($this->verifyRegister()) {

            $hash = Password::hash($this->pass);
            $gameIP = random_int(0, 255) . '.' . random_int(0, 255) . '.' . random_int(0, 255) . '.' . random_int(0, 255);

            $userID = self::createUser($this->user, $hash, $this->email, $gameIP);

            if(!$userID){
                $this->session->addMsg('Error while completing registration. Please, try again later.', 'error');
                return FALSE;
            }

            require __DIR__.'/Python.class.php';
            $python = new Python();
            $python->generateProfile($userID);
            $python->generateProfile($userID, 'br');

            require __DIR__.'/EmailVerification.class.php';
            $EmailVerification = new EmailVerification();
            
            if(!$EmailVerification->sendMail($userID, $this->email, $this->user)){
                $this->session->addMsg('Registration complete. You can login now.', 'notice');
                //TODO: report to admin
            }
            
            require __DIR__.'/Finances.class.php';
            $finances = new Finances();
            
            $finances->createAccount($userID);

            $sql = SqlQuery::make('INSERT INTO stats_register (userID, ip) VALUES (?, ?)', [$userID, Config::clientIp()]);
            $this->pdo->query($sql);

            $this->session->addMsg('Registration complete. You can login now.', 'notice');

            return TRUE;

        } else {

            return FALSE;
        }
    }

    /**
     * Creates the account and every per-user row the game expects (formerly python/create_user.py).
     * Returns the new user id, or false on failure.
     */
    private function createUser($login, $passwordHash, $email, $gameIP) {

        $gamePass = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        for ($i = 0; $i < 8; $i++) {
            $gamePass .= $chars[random_int(0, strlen($chars) - 1)];
        }

        try {

            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('INSERT INTO users (login, password, gamePass, email, gameIP) VALUES (?, ?, ?, ?, INET_ATON(?))');
            $stmt->execute([$login, $passwordHash, $gamePass, $email, $gameIP]);
            $userID = (int) $this->pdo->lastInsertId();

            $rows = [
                'INSERT INTO users_stats (uid, dateJoined) VALUES (?, NOW())',
                "INSERT INTO hardware (userID, name) VALUES (?, 'Server #1')",
                "INSERT INTO log (userID, log.text) VALUES (?, CONCAT(SUBSTRING(NOW(), 1, 16), ' - localhost installed current operating system'))",
                'INSERT INTO cache (userID) VALUES (?)',
                'INSERT INTO cache_profile (userID, expireDate) VALUES (?, NOW())',
                'INSERT INTO hist_users_current (userID) VALUES (?)',
                "INSERT INTO ranking_user (userID, `rank`) VALUES (?, '-1')",
                'INSERT INTO certifications (userID) VALUES (?)',
                'INSERT INTO users_puzzle (userID) VALUES (?)',
                'INSERT INTO users_learning (userID) VALUES (?)',
                'INSERT INTO users_language (userID) VALUES (?)',
            ];
            foreach ($rows as $sql) {
                $this->pdo->prepare($sql)->execute([$userID]);
            }

            $this->pdo->commit();
            return $userID;

        } catch (PDOException $e) {

            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('create user failed for ' . $login . ': ' . $e->getMessage());
            return FALSE;

        }

    }

    private function verifyRegister() {

        $system = new System();
        
        if(!$system->validate($this->user, 'username')){
            $this->session->addMsg(sprintf(_('Invalid username. Allowed characters are %s.'), '<strong>azAZ09._-</strong>'), 'error');
            return FALSE;
        }
        
        if(!$system->validate($this->email, 'email')){
            $this->session->addMsg(sprintf(_('The email %s is not valid.'), '<strong>'.htmlspecialchars($this->email, ENT_QUOTES).'</strong>'), 'error');
            return FALSE;
        }

        //pegando spam dos fdp: rIcFCzREv2VOPIU@rIcFCzREv2VOPIU.com 
        //76437363@gmail.com
        //UdJ@jgD.com
        if ((strlen(preg_replace('![^A-Z]+!', '', $this->email)) >= 5 && preg_match_all("/[0-9]/", $this->email) >= 2) || preg_match_all("/[0-9]/", $this->email) >= 5){
            $this->session->addMsg(_('Registration complete. You can login now.'), 'notice');
            return FALSE;
        }

        if (strlen(preg_replace('![^A-Z]+!', '', $this->email)) >= 2 && strlen($this->email) <= 12){
            $this->session->addMsg(_('Registration complete. You can login now.'), 'notice');
            return FALSE;
        }
        
        
        
        //verifico no banco se já existe um usuário ou email cadastrado.
        $this->session->newQuery();
        $sqlQuery = "SELECT email FROM users WHERE login = ? OR email = ? LIMIT 1";
        $sqlLog = $this->pdo->prepare($sqlQuery);
        $sqlLog->execute(array($this->user, $this->email));

        if ($sqlLog->rowCount() == '1') {

            $dados = $sqlLog->fetch();

            if ($dados['email'] == $this->email) {
                $this->session->addMsg('This email is already used.', 'error');
            } else {
                $this->session->addMsg('This username is already taken.', 'error');
            }
            
            return FALSE;
            
            // 2019: what could possibly go wrong?
            //ainda falta verificar se email tá ok, se tem algum caracter especial, sql inject etc etc, mas fica pra depois                       
        } elseif (strlen($this->user) == '0' || strlen($this->pass) == '0' || strlen($this->email) == '0') {

            $this->session->addMsg('Some fields are empty.', 'error');
            
            return FALSE;
        }
        
        if(strlen($this->pass) < Password::MIN_LENGTH){
            $this->session->addMsg(sprintf('Your password must have at least %d characters.', Password::MIN_LENGTH), 'error');
            
            return FALSE;
        }
        
        if(strlen($this->user) > 15){
            $this->session->addMsg('Yor username is too big :( Please, limit it to 15 characteres.', 'error');
            
            return FALSE;
        }
        
        return TRUE;
        
    }

    public function login($logUser, $logPass, $special = FALSE) {
                
        date_default_timezone_set('UTC');
        
        // 'remember' = logged in through a valid "keep me logged in" cookie (no password needed)
        $remember = ($special === 'remember');
        if($special && !$remember){
            exit("Edit special");
        }
          
        if(!$this->session){
            $this->session = new Session();
        }
        
        require_once __DIR__.'/Mission.class.php';        
        
        $this->mission = new Mission();

        $this->user = $logUser;
        $this->pass = $logPass;

        if ($this->verifyLogin($remember)) {

            if(!$remember && self::tooManyAttempts()){
                $this->session->addMsg('Too many failed login attempts. Please wait a few minutes and try again.', 'error');
                return FALSE;
            }

            $this->session->newQuery();
            $sqlQuery = "   SELECT password, id 
                            FROM users
                            WHERE BINARY login = ?
                            LIMIT 1";
            $sqlLog = $this->pdo->prepare($sqlQuery);
            $sqlLog->execute(array($this->user));
            
            if ($sqlLog->rowCount() == '1') {

                $dados = $sqlLog->fetchAll();
                   
                if($remember || self::checkPassword($this->pass, $dados['0']['password'], $dados['0']['id'])){

                    $log = $this->log;
                    $ranking = $this->ranking;
                    $storyline = $this->storyline;
                    $clan = $this->clan;

                    self::clearAttempts();

                    $this->session->loginSession($dados['0']['id'], $this->user, $special);

                    self::loginDatabase($dados['0']['id']);
                    $certsArray = $ranking->cert_getAll();
        
                    $this->mission->restoreMissionSession($dados['0']['id']);

                    $this->session->certSession($certsArray);

                    if($clan->playerHaveClan($dados['0']['id'])){
                        $_SESSION['CLAN_ID'] = $clan->getPlayerClan($dados['0']['id']);
                    } else {
                        $_SESSION['CLAN_ID'] = 0;
                    }

                    $_SESSION['LAST_CHECK'] = new DateTime('now');
                    $_SESSION['ROUND_STATUS'] = $storyline->round_status();

                    if($_SESSION['ROUND_STATUS'] == 1){
                        $log->addLog($dados['0']['id'], $log->logText('LOGIN', Array(0)), '0');
                        $this->session->exp_add('LOGIN');
                    }
                    
                    return TRUE;

                }

            } else {
                // Spend the same time as a real check so response times don't reveal valid usernames.
                password_verify($this->pass, '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG');
            }

            self::recordFailedAttempt();
            $this->session->addMsg('Username and password doesnt match. Some accounts were lost, sorry!', 'error');
            return FALSE;
            
        }
    }

    /** Verifies the password and upgrades old or weaker hashes. */
    private function checkPassword($password, $hash, $userID) {

        $valid = Password::verify($password, $hash);

        if ($valid && Password::needsRehash($hash)) {
            $stmt = $this->pdo->prepare('UPDATE users SET password = ? WHERE id = ? LIMIT 1');
            $stmt->execute([Password::hash($password), $userID]);
        }

        return $valid;

    }

    // Brute-force protection: failed logins allowed per account and per IP address (players
    // behind the same NAT share an IP, hence the higher limit) within the window.
    const MAX_ATTEMPTS_PER_LOGIN = 10;
    const MAX_ATTEMPTS_PER_IP = 50;
    const LOGIN_ATTEMPT_WINDOW = 15; // minutes

    private function tooManyAttempts() {
        $stmt = $this->pdo->prepare('SELECT
                SUM(login = ?) AS byLogin,
                SUM(ip = ?) AS byIp
            FROM login_attempts
            WHERE (ip = ? OR login = ?) AND attemptTime > DATE_SUB(NOW(), INTERVAL ? MINUTE)');
        $ip = Config::clientIp();
        $stmt->execute([$this->user, $ip, $ip, $this->user, self::LOGIN_ATTEMPT_WINDOW]);
        $row = $stmt->fetch(PDO::FETCH_OBJ);
        return $row->bylogin >= self::MAX_ATTEMPTS_PER_LOGIN || $row->byip >= self::MAX_ATTEMPTS_PER_IP;
    }

    private function recordFailedAttempt() {
        $stmt = $this->pdo->prepare('INSERT INTO login_attempts (ip, login) VALUES (?, ?)');
        $stmt->execute([Config::clientIp(), substr((string) $this->user, 0, 50)]);
        $this->pdo->exec('DELETE FROM login_attempts WHERE attemptTime < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    }

    private function clearAttempts() {
        $stmt = $this->pdo->prepare('DELETE FROM login_attempts WHERE login = ?');
        $stmt->execute([$this->user]);
    }
    
    private function loginDatabase($id){
        
        $this->session->newQuery();
        $sql = SqlQuery::make('SELECT COUNT(*) AS total FROM users_online WHERE id = ? LIMIT 1', [SqlQuery::num($id)]);
        if($this->pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total > 0){
            $this->session->newQuery();
            $sql = SqlQuery::make('DELETE FROM users_online WHERE id = ? LIMIT 1', [SqlQuery::num($id)]);
            $this->pdo->query($sql);
        }
        
        require_once __DIR__.'/RememberMe.class.php';
        $rememberMe = new RememberMe(null, $this->pdo);
        $rememberMe->remember($id, false, $this->keepalive);
                
        $this->session->newQuery();
        $sql = 'UPDATE users SET lastLogin = NOW() WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array(':id' => $id));
        
        setcookie('logged', '1', ['expires' => time() + 172800, 'path' => '/', 'samesite' => 'Lax', 'secure' => Config::isHttps()]);
        
        
    }
    
    private function verifyLogin($remember) {

        if($remember){
            return TRUE;
        }
        
        if (!is_string($this->user) || !is_string($this->pass) || strlen($this->user) == '0' || strlen($this->pass) == '0') {

            $this->session->addMsg('Some fields are empty.', 'error');
            return FALSE;
            
        } else {

            return TRUE;
            
        }
    }

}

?>
