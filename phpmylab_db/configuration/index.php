<?php

if (file_exists("config.php"))
{
	//Affichage des erreurs en mode test
	include '../config.php';
	if($mode_test == 1)
	{
		ini_set('display_errors', 1);
		error_reporting(E_ALL);
	}
}

	/**
	 * Page d'accueil de la procedure de configuration de phpMyLab.
	 *
	 * La personne voulant installer l'application est redirigée vers cette page si les fichiers de configurations n'existent pas.
	 *
	 * Date de création : 17 Avril 2012<br/>
	 * Date de dernière modification : 12 Avril 2012
	 * @version 1.2.0
	 * @author Cedric Gagnevin <cedric.gagnevin@laposte.net>
	 * @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.html
	 * @copyright CNRS (c) 2015
	 * @package phpMyLab
	 */
	
	/*********************************************************************************
	 ******************************  PLAN     ****************************************
	 *********************************************************************************/
	
	//    | -A- Fonction de detection du navigateur
	//    | -B- HTML
	
	
/*********************************************************************************
***************** A- Fonction de detection du navigateur *************************
**********************************************************************************/
	
		session_start();
		session_unset();

		
		function getBrowser() // Source : http://fr2.php.net/manual/fr/function.get-browser.php#88923
		{
			$u_agent = $_SERVER['HTTP_USER_AGENT'];
			$bname = 'Unknown';
			$platform = 'Unknown';
			$version= "";
			
			//First get the platform
			if (preg_match('/linux/i', $u_agent)) {
				$platform = 'linux';
			}
			elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
				$platform = 'mac';
			}
			elseif (preg_match('/windows|win32/i', $u_agent)) {
				$platform = 'windows';
			}
			
			// Next get the name of the useragent yes seperately and for good reason
			if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent))
			{
				$bname = 'Internet Explorer';
				$ub = "MSIE";
			}
			elseif(preg_match('/Firefox/i',$u_agent))
			{
				$bname = 'Mozilla Firefox';
				$ub = "Firefox";
			}
			elseif(preg_match('/Chrome/i',$u_agent))
			{
				$bname = 'Google Chrome';
				$ub = "Chrome";
			}
			elseif(preg_match('/Safari/i',$u_agent))
			{
				$bname = 'Apple Safari';
				$ub = "Safari";
			}
			elseif(preg_match('/Opera/i',$u_agent))
			{
				$bname = 'Opera';
				$ub = "Opera";
			}
			
/*
			// finally get the correct version number
			$known = array('Version', $ub, 'other');
			$pattern = '#(?<browser>' . join('|', $known) .
			')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
			if (!preg_match_all($pattern, $u_agent, $matches)) {
				// we have no matching number just continue
			}
			
			// see how many we have
			$i = count($matches['browser']);
			if ($i != 1) {
				//we will have two since we are not using 'other' argument yet
				//see if version is before or after the name
				if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
					$version= $matches['version'][0];
				}
				else {
					$version= $matches['version'][1];
				}
			}
			else {
				$version= $matches['version'][0];
			}
			
			// check if we have a number
			if ($version==null || $version=="") {$version="?";}
*/

			$u_agent_split = explode(" ",$u_agent);
			if($bname == 'Mozilla Firefox' OR $bname == 'Opera')
			{
				$version = explode("/",$u_agent_split[sizeof($u_agent_split)-1]);
				if(is_numeric($version[1][0])) //Test si on tombe bien sur un numero
					$version = $version[1];
				else $version = '[?]';
			}
			else if($bname == 'Google Chrome' OR $bname == 'Apple Safari')
			{
				$version = explode("/",$u_agent_split[sizeof($u_agent_split)-2]);
				if(is_numeric($version[1][0])) //Test si on tombe bien sur un numero
					$version = $version[1];
				else $version = '[?]';
			}
			else if($bname == 'Internet Explorer')
			{
				$i=0;				
				while(isset($u_agent_split[$i]))
				{
					if($u_agent_split[$i] == "MSIE")
						$rang = $i+1; //La version est apres MSIE
					$i++;
				}

				if(is_numeric($u_agent_split[$rang][0])) //Test si on tombe bien sur un numero
					$version = substr($u_agent_split[$rang],0,-1);
				else $version = '[?]';
			}
			

			return array(
						 'userAgent' => $u_agent,
						 'name'      => $bname,
						 'version'   => $version,
						 'platform'  => $platform,
						 //'pattern'    => $pattern,
						 );
		}
	
/*********************************************************************************
******************** B- Controle des pre-requis **********************************
**********************************************************************************/

	
	//Recuperation des versions d'Apache 
	$tab_apache = explode(" ",apache_get_version());
	foreach($tab_apache as $versionA)
	{
		if(substr($versionA,0,6) == 'Apache')
			$version_apache = substr($versionA,7);
	}

	// Validation des versions utilisees
	if(phpversion() >= 4.1) $isPHPOK = 1; else $isPHPOK = 0; //PHP
	if($version_apache >= 2) $isApacheOK = 1; else $isApacheOK = 0; //Apache
	if(extension_loaded('gd')) $isGDOK = 1; else $isGDOK = 0; //GD
	if(mysqli_get_client_version() >= 5.0) $isMysqlOK = 1; else $isMysqlOK = 0; //MySQL

	//La configuration peut commencer
	if($isPHPOK == 1 && $isApacheOK == 1 && $isGDOK == 1 && $isMysqlOK == 1 && isset($_POST[ 'isJSOK' ]) && $_POST[ 'isJSOK' ] == 1)
		$requirements = 1;
	else $requirements = 0;
	
	if(isset($_POST[ 'commencer' ]) && $requirements == 1)
	{
		$_SESSION[ 'requirements' ] = 1;
		header('Location: etape1.php');
	}
	
/*********************************************************************************
******************************** C- HTML *******************************************
**********************************************************************************/

	header("Content-Type: text/html; charset=iso-8859-1");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
	<link rel="shortcut icon" type="image/x-icon" href="images/phpmylab.ico" />
	<link rel="stylesheet" href="style_config.css">
	<title>Installation de phpMyLab</title>
<noscript>
	<div class="noscript">
		<img src="images/attention.png" />
		<p>Attention ! Le javascript est actuellement d&eacute;sactiv&eacute; sur votre navigateur. 
		Vous devez l'activer pour continuer la configuration et pour profiter de l'application de mani&egrave;re optimale.</p>
	</div>
</noscript>
<script>
function indicateurJS() // Si le JS est actif, affiche une image de confirmation et remplit un champ caché pour indiqué que le JS est actif
{
	// Affiche la coche verte
	var imgCheckJS = document.getElementById('verifJS');
	imgCheckJS.src = "images/check.png";
	imgCheckJS.alt = "OK";
	//Rempli le champ caché
	document.getElementById('isJSOK').value = "1";
	var PHP = document.getElementById('isPHPOK').value;
	var APACHE = document.getElementById('isApacheOK').value;
	var MYSQL = document.getElementById('isMysqlOK').value;
	var GD = document.getElementById('isGDOK').value;
	
	if(PHP == 1 && APACHE == 1 && MYSQL == 1 && GD == 1)
		document.getElementById('commencer').className = "bouton";
}

</script>
</head>
<body onload="indicateurJS();">
	<div id="corps">
		<h1>Bienvenue sur la proc&eacute;dure de configuration de phpMyLab</h1>

		<form action="<?php echo $_SERVER[ 'PHP_SELF' ]; ?>" method="POST">

		<input type=hidden name="isJSOK" id="isJSOK" value="0" /> <!-- Pour le JS -->
		<input type=hidden name="isPHPOK" id="isPHPOK" value="<?php echo $isPHPOK; ?>" /> <!-- Pour le PHP -->
		<input type=hidden name="isApacheOK" id="isApacheOK" value="<?php echo $isApacheOK; ?>" /> <!-- Pour Apache -->
		<input type=hidden name="isMysqlOK" id="isMysqlOK" value="<?php echo $isMysqlOK; ?>" /> <!-- Pour Mysql -->
		<input type=hidden name="isGDOK" id="isGDOK" value="<?php echo $isGDOK; ?>" /> <!-- Pour GD -->

		<table id="compatibilites">
			<tr>
				<td>
					<h2>Navigateurs compatibles</h2>
					<ul>
						<li><a href="http://www.mozilla.org/fr/firefox/new/" target="_blank"><img src="images/firefox.png" width=30/>Mozilla Firefox 4.0.1+</a></li>
						<li><a href="https://www.google.com/chrome?hl=fr" target="_blank"><img src="images/chrome.png" width=30/>Google Chrome 18+</a></li>
						<li><a href="http://www.opera-fr.com/telechargements/" target="_blank"><img src="images/opera.png" width=30/>Op&eacute;ra 11+</a></li>
						<li><a href="http://www.apple.com/fr/safari/download/" target="_blank"><img src="images/safari.png" width=30/>Safari 5+</a></li>
						<li><a href="http://windows.microsoft.com/fr-FR/internet-explorer/downloads/ie" target="_blank"><img src="images/ie.png" width=30/>Internet Explorer 8+</a></li>
					</ul>
				</td>
				<td>
					<h2>Configuration minimale requise</h2>
					<ul>
						<li><img src="images/apache.png" width=30/>Apache 2 <?php if($isApacheOK == 1) echo '<img src="images/check.png" width=20 alt="OK" />'; else echo '<img src="images/croix.png" width=20 alt="NO" />'; ?></li>
						<li><img src="images/mysql.png" width=30/>MySQL 5.0 (client) <?php if($isMysqlOK == 1) echo '<img src="images/check.png" width=20 alt="OK" />'; else echo '<img src="images/croix.png" width=20 alt="NO" />'; ?></li>
						<li><img src="images/php.png" width=30/>PHP 4.1 <?php if($isPHPOK == 1) echo '<img src="images/check.png" width=20 alt="OK" />'; else echo '<img src="images/croix.png" width=20 alt="NO" />'; ?> avec la librairie GD <?php if($isGDOK == 1) echo '<img src="images/check.png" width=20 alt="OK" />'; else echo '<img src="images/croix.png" width=20 alt="NO" />'; ?></li>
						<li><img src="images/js.png" height=20/>Javascript <img src="images/croix.png" width=20 alt="NO" id="verifJS" /></li>
	
					</ul>
				</td>
			</tr>
		</table>

		<?php
			$ua=getBrowser();

			if($ua['name'] != 'Unknown')
			{
				if(($ua['name'] == 'Internet Explorer' && $ua['version'] < 8)
				|| ($ua['name'] == 'Mozilla Firefox' && $ua['version'] < 4 && $ua['platform'] == 'mac') || ($ua['name'] == 'Mozilla Firefox' && $ua['version'] < 11 && $ua['platform'] == 'windows') || ($ua['name'] == 'Mozilla Firefox' && $ua['version'] < 11 && $ua['platform'] == 'linux') 
				|| ($ua['name'] == 'Google Chrome' && $ua['version'] < 18)
				|| ($ua['name'] == 'Apple Safari' && $ua['version'] < 5)
				|| ($ua['name'] == 'Opera' && $ua['version'] < 11))
				{
					echo '<p class="rouge centrer">Vous utilisez '.$ua['name']." ".$ua['version'].'. Sa version est trop ancienne, veuillez cliquer sur un des liens ci-dessus</p>';
				}
				else echo '<p class="vert centrer">Vous utilisez '.$ua['name']." ".$ua['version'].' (recommand&eacute;)</p>';
			}
			else echo '<p class="rouge centrer">Nous ne parvenons pas &agrave; d&eacute;tecter votre navigateur, veuillez t&eacute;l&eacute;charger un navigateur parmis ceux propos&eacute;s ci-dessus.</p>';
		?>
		<table class="case_bouton">
			<tr>
				<td id="etape_suivante">
					<input type=submit name="commencer" id="commencer" class="bouton opacityMin" title="Commencer la configuration de phpMyLab" value="Commencer" />
				</td>
			</tr>
		</table>
		</form>
	</div>
</body>
</html>

