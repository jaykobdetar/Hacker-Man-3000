<?php

/*
 * "Keep me logged in" cookie.
 *
 * The cookie holds the user id, a random token and an HMAC signature (keyed with APP_KEY).
 * Only a SHA-256 hash of the token is stored in users_online, and the token is rotated every
 * time the cookie is used.
 *
 * Based on http://stackoverflow.com/questions/1354999/keep-me-logged-in-the-best-approach/17267718#17267718
 */

class RememberMe {

    const COOKIE = 'auto';
    const LIFETIME = 172800; // 2 days

    private $key;
    private $pdo;

    function __construct($privatekey = null, $db = null) {
        $this->key = $privatekey ?? self::appKey();
        $this->pdo = $db ?? PDO_DB::factory();
    }

    /** Secret used to sign cookies, from APP_KEY (generate with `php -r "echo bin2hex(random_bytes(32));"`). */
    public static function appKey() {
        $key = Config::require('APP_KEY');
        if (strpos($key, 'base64:') === 0) {
            return base64_decode(substr($key, 7), true);
        }
        return ctype_xdigit($key) && strlen($key) % 2 === 0 ? hex2bin($key) : $key;
    }

    /**
     * @return array|false|int user info when the cookie is valid, false when there is no usable cookie,
     *                         -1 when the cookie was tampered with
     */
    public function auth() {

        if (empty($_COOKIE[self::COOKIE]) || !is_string($_COOKIE[self::COOKIE])) {
            return false;
        }

        $cookie = json_decode($_COOKIE[self::COOKIE], true);
        if (!is_array($cookie) || !isset($cookie['user'], $cookie['token'], $cookie['signature'])
            || !is_scalar($cookie['user']) || !is_string($cookie['token']) || !is_string($cookie['signature'])) {
            return false;
        }

        if (!$this->verify($cookie['user'] . $cookie['token'], $cookie['signature'])) {
            return -1;
        }

        $stored = $this->getdb($cookie['user']);
        if (!$stored) {
            return false; // logged out elsewhere, or the account was deleted
        }

        if (!hash_equals($stored, hash('sha256', $cookie['token']))) {
            return -1;
        }

        // Rotate the token on every use.
        $this->remember($cookie['user'], true, true);
        return ['user' => (int) $cookie['user']];

    }

    public function getdb($user){

        $sql = SqlQuery::make('SELECT token FROM users_online WHERE id = ? LIMIT 1', [SqlQuery::num($user)]);
        $row = $this->pdo->query($sql)->fetch(PDO::FETCH_OBJ);
        return $row ? $row->token : false;

    }

    public function setdb($user, $tokenHash, $update, $expire){

        if($update){
            $sql = 'UPDATE users_online SET token = :token WHERE id = :id';
        } else {
            $sql = 'INSERT INTO users_online (id, token)
                    VALUES (:id, :token)';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array(':id' => $user, ':token' => $tokenHash));

        if($expire){

            $sql = 'REPLACE INTO users_expire (userID, expireDate)
                    VALUES (:id, DATE_ADD(NOW(), INTERVAL 2 HOUR))';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array(':id' => $user));

        }

        $sql = 'INSERT INTO stats_login (userID)
                VALUES (:id)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array(':id' => $user));

    }

    public function remember($user, $updateQuery, $createCookie) {
        $token = bin2hex(random_bytes(32));
        $cookie = [
            'user' => $user,
            'token' => $token,
            'signature' => $this->hash($user . $token),
        ];

        $this->setdb($user, hash('sha256', $token), $updateQuery, !$createCookie);

        if($createCookie){
            setcookie(self::COOKIE, json_encode($cookie), [
                'expires' => time() + self::LIFETIME,
                'path' => '/',
                'secure' => Config::bool('SESSION_SECURE_COOKIE', Config::isHttps()),
                'httponly' => true,
                'samesite' => Config::get('SESSION_SAMESITE', 'Strict'),
            ]);
        }
    }

    public static function forget() {
        setcookie(self::COOKIE, '', ['expires' => time() - 3600, 'path' => '/']);
    }

    private function verify($data, $signature) {
        return hash_equals($this->hash($data), $signature);
    }

    private function hash($value) {
        return hash_hmac('sha256', (string) $value, $this->key);
    }

    public function rememberlogin(){

        $data = self::auth();

        if ($data === -1) { // invalid or tampered cookie
            self::forget();
            $_SESSION = [];
            session_destroy();
            exit("Invalid token");
        }

        if ($data) {

            require_once __DIR__.'/Database.class.php';
            $database = new LRSys();

            require_once __DIR__.'/Player.class.php';
            $player = new Player();

            if($player->verifyID($data['user'])){

                $redirect = 'index';
                if(isset($_SESSION['GOING_ON'])){
                    // only ever redirect to a local page
                    if (is_string($_SESSION['GOING_ON']) && preg_match('#^[A-Za-z0-9_\-]+(\.php)?(\?[^\s]*)?$#', $_SESSION['GOING_ON'])) {
                        $redirect = $_SESSION['GOING_ON'];
                    }
                    unset($_SESSION['GOING_ON']);
                }

                $username = $player->getPlayerInfo($data['user'])->login;
                $database->login($username, '', 'remember');

                header("Location:".$redirect);
                exit();

            }

        }

    }

}
