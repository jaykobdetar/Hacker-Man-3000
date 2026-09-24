<?php

require_once __DIR__.'/bootstrap.php';
// 2019: If you host your own public game server, please update the Privacy Policy and TOS accordingly.

$l = 'en_US';

if(isset($_SERVER['HTTP_HOST'])){
    if($_SERVER['HTTP_HOST'] == 'br.hackerexperience.com' || $_SERVER['HTTP_HOST'] == 'www.br.hackerexperience.com'){
        $l = 'pt_BR';
    }
}

putenv("LANG=" . $l);
setlocale(LC_ALL, $l);

$domain = "messages";
bindtextdomain($domain, "locale");
bind_textdomain_codeset($domain, 'UTF-8');

textdomain($domain);

?>

<html>
<head>

<meta charset="UTF-8">
<title><?php echo _('Privacy Policy'); ?></title>

<style>

body {
	margin-left: 100px;
	margin-right: 100px;
}

h1, h6 {
	text-align: center;
}

li {
	font-weight: bold;
}

ul {
	margin: 4px;
}

</style>

</head>
<body>

<h1><?php echo _('Privacy Matters'); ?></h1>
<h6>Hacker Experience <?php echo _('Privacy Policy'); ?></h6>

<?php echo _('Privacy is top priority at Hacker Experience, and we like to explain our Privacy Policy in clear and simple words:'); ?><br/><br/>

<li><?php echo _('Personal information you provide us'); ?></li>

<ul>- <?php echo _('Your email. We keep them for verification purposes.'); ?></ul>

<li><?php echo _('We collect'); ?></li>

<ul>- <?php echo _('Browser family'); ?></ul>
<ul>- <?php echo _('Operating system'); ?></ul>
<ul>- <?php echo _('IP address'); ?></ul>
<ul>- <?php echo _('Referrer website'); ?></ul>
<br/>
<ul><?php echo _('All these information provides us better information as to where our users are having trouble accessing, in which parts of the world the page is taking too long to load, or how their gaming experience is.'); ?> </ul>

<li><?php echo _('We use'); ?></li>

<ul>- <?php echo _('Cookies - We use cookies to enable a more personal experience on the game. We do not track other sites than hackerexperience.com. Hacker Experience might not work without cookies.'); ?></ul>
 
<li><?php echo _('We protect'); ?></li>

<ul>- <?php echo _('Your data - We do not rent or sell your personal/identifying data, nor make it public. It is safely stored on a private, access-limited server.'); ?></ul>
<ul>- <?php echo _('Your password - Your password is uniquely salted using a slow hashing function.'); ?></ul>
<ul>- <?php echo _('Your email - We do not spam.'); ?></ul>


<li><?php echo _('Please note'); ?></li>

<ul>- <?php echo _('The use of the site constites your full acceptance of our Privacy Policy. If you do not agree with it, please refrain from using www.hackerexperience.com and all related domains.'); ?></ul>
<ul>- <?php echo _('We may change our privacy policy from time to time. Although efforts will be made to announce any changes on the Forum Announcements Board, we may do so in our sole discretion. Your continued use of this site after any change in this Privacy Policy will constitute your acceptance of such change. Therefore, we encourage you to visit this page regularly.'); ?></ul>

<br/><br/>
<center>
<strong><?php echo _('We use, follow and recommend the ACM code of ethics'); ?></strong><br/>

<a href="http://www.acm.org/about/code-of-ethics">http://www.acm.org/about/code-of-ethics</a>

<br/>
</center>

<br/><br/>

<center>
<?php echo sprintf(_('This Term of Service was first published on %s and last revised on %s.'), '30/08/2014', '30/08/2014'); ?>
</center>
<br/>
</body>
</html>