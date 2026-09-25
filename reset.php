<?php

require_once __DIR__.'/bootstrap.php';

require __DIR__.'/classes/Session.class.php';
require_once __DIR__.'/classes/Password.class.php';

if(isset($_SESSION['id'])){
	header("Location:index");
	exit();
}

// Reset links are valid for this many minutes.
const RESET_CODE_LIFETIME = 60;

function reset_find_code($pdo, $code){
	if(!is_string($code) || !preg_match('/^[a-f0-9]{32}$/', $code)){
		return false;
	}
	$sql = 'SELECT userID FROM email_reset WHERE code = :code AND requestDate > DATE_SUB(NOW(), INTERVAL '.RESET_CODE_LIFETIME.' MINUTE) LIMIT 1';
	$stmt = $pdo->prepare($sql);
	$stmt->execute(Array(':code' => hash('sha256', $code)));
	return $stmt->fetch(PDO::FETCH_OBJ);
}

$msg = '';

if(isset($_POST['email'])){

	require __DIR__.'/classes/System.class.php';

	$pdo = PDO_DB::factory();
	$system = new System();

	$email = (string) $_POST['email'];

	if($system->validate($email, 'email')){

		$sql = 'SELECT id, login FROM users WHERE email = :email LIMIT 1';
		$stmt = $pdo->prepare($sql);
		$stmt->execute(Array(':email' => $email));
		$userInfo = $stmt->fetch(PDO::FETCH_OBJ);

		if($userInfo){

			// Only a hash of the code is stored; the code itself is only ever in the email.
			$code = bin2hex(random_bytes(16));

			$stmt = $pdo->prepare('DELETE FROM email_reset WHERE userID = :uid');
			$stmt->execute(Array(':uid' => $userInfo->id));

			$sql = 'INSERT INTO email_reset (userID, code) VALUES (:uid, :code)';
			$stmt = $pdo->prepare($sql);
			$stmt->execute(Array(':uid' => $userInfo->id, ':code' => hash('sha256', $code)));

			require __DIR__.'/classes/Mailer.class.php';
			$mailer = new Mailer();
			$mailer->send('request_reset', Array('to' => $email, 'user' => $userInfo->login, 'code' => $code));

		}

	}

	// Same answer whether or not the address is registered, so emails can't be enumerated.
	$_SESSION['MSG'] = 'If this email is registered, a reset link was sent to it.';
	$_SESSION['TYP'] = 'REG';
	$_SESSION['MSG_TYPE'] = 'success';

	header("Location:index");
	exit();

}  elseif(isset($_POST['code'])){

	$pdo = PDO_DB::factory();

	$resetInfo = reset_find_code($pdo, $_POST['code']);

	if(!$resetInfo){
		exit("This code is invalid or has expired.");
	}

	$pwd = $_POST['pwd'] ?? '';

	if(!is_string($pwd) || $pwd !== ($_POST['pwd2'] ?? null)){
		exit("Passwords are different");
	}

	if(strlen($pwd) < Password::MIN_LENGTH){
		exit("Please use at least ".Password::MIN_LENGTH." characters");
	}

	$sql = 'UPDATE users SET password = :pwd WHERE id = :uid';
	$stmt = $pdo->prepare($sql);
	$stmt->execute(Array(':uid' => $resetInfo->userid, ':pwd' => Password::hash($pwd)));

	$sql = 'DELETE FROM email_reset WHERE userID = :uid';
	$stmt = $pdo->prepare($sql);
	$stmt->execute(Array(':uid' => $resetInfo->userid));

	// Log out every "keep me logged in" session of this account.
	$stmt = $pdo->prepare('DELETE FROM users_online WHERE id = :uid');
	$stmt->execute(Array(':uid' => $resetInfo->userid));

	exit("Password changed");

} elseif(isset($_GET['code'])){

	$pdo = PDO_DB::factory();

	if(!reset_find_code($pdo, $_GET['code'])){
		exit("This code is invalid or has expired.");
	}

?>

	<form action="" method="POST">
		<input type="hidden" name="code" value="<?php echo htmlspecialchars($_GET['code'], ENT_QUOTES); ?>">
		Password: <input type="password" name="pwd" minlength="<?php echo Password::MIN_LENGTH; ?>"> (<?php echo Password::MIN_LENGTH; ?> or more characters)<br/>
		Repeat plz: <input type="password" name="pwd2"><br/>
		<input type="submit" value="Change password">

	</form>

<?php

	exit();

}

?>

<html>
<head>
</head>
<body>

<?php if($msg != ''){ echo htmlspecialchars($msg).'<br/><br/>'; } ?>

	<form action="reset" method="POST">
		<input type="text" name="email" placeholder="Please insert your email"><br/><br/>
		<input type="submit" value="Request password reset">
	</form>
</body>
</html>
