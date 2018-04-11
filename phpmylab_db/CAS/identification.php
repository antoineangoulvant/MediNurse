<?php

// Load the CAS lib
require_once 'CAS.php';
require_once 'config_cas.php';

// Uncomment to enable debugging
phpCAS::setDebug();

// Initialize phpCAS
phpCAS::client(CAS_VERSION_2_0,$cas_host,$cas_port,$cas_context,false);

// For quick testing you can disable SSL validation of the CAS server.
// THIS SETTING IS NOT RECOMMENDED FOR PRODUCTION.
// VALIDATING THE CAS SERVER IS CRUCIAL TO THE SECURITY OF THE CAS PROTOCOL!
phpCAS::setNoCasServerValidation();


// force CAS authentication
phpCAS::forceAuthentication();

// at this step, the user has been authenticated by the CAS server
// and the user's login name can be read with phpCAS::getUser().

if(isset($_REQUEST[ 'logout' ]))
	phpCAS::logout();

//CAS OK

// Initialize session ID
$sid = '';
if (isset($_REQUEST[ 'sid' ])) $sid = substr(trim(preg_replace('/[^a-f0-9]/', '', $_REQUEST[ 'sid' ])), 0, 13);
if ($sid == '') $sid = uniqid('');

// Start PHP session
session_id($sid);
session_name('phpmylab');
session_start();

include '../config.php'; 
include '../'.$chemin_connection;


// Connecting, selecting database:
$link = mysql_connect($mysql_location,$mysql_user,$mysql_password)
		or die('Could not connect: ' . mysql_error());
mysql_select_db($mysql_base) or die('Could not select database');

$query = 'SELECT * FROM T_UTILISATEUR WHERE LOGIN_CAS = "'.phpCAS::getUser().'"';
$result = mysql_query($query) or die('Connection Mysql ; Query failed: ' . mysql_error());
$line = mysql_fetch_array($result, MYSQL_BOTH);

if($line['LOGIN_CAS']) //yLe login est présent dans la bdd
{
	$_SESSION[ 'connection' ][ 'utilisateur' ] = $line[0];
	$_SESSION[ 'connection' ][ 'nom' ] = ucwords(strtolower($line[1]));
	$_SESSION[ 'connection' ][ 'prenom' ] = ucwords(strtolower($line[2]));
	$_SESSION[ 'connection' ][ 'mot_de_passe' ] = $line[3]; 
	$_SESSION[ 'connection' ][ 'ss' ] = $line[4];
	$_SESSION[ 'connection' ][ 'mel' ] = $line[5];
	$_SESSION[ 'connection' ][ 'groupe' ] = $line[6];
	$_SESSION[ 'connection' ][ 'status' ] = $line[7];
	$_SESSION[ 'edition' ] = 1;

	$_SESSION[ 'connection' ][ 'admin' ] = $line[8];

	//init responsable_groupe ici:
	for ($i=1;$i<=$_SESSION[ 'nb_groupe'];$i++)
		if ($_SESSION[ 'connection' ][ 'groupe' ]==$_SESSION[ 'correspondance' ]['groupe'][$i])
		{
			$_SESSION[ 'responsable_groupe']=$_SESSION[ 'correspondance' ]['responsable'][$i];
			$_SESSION[ 'responsable2_groupe']=$_SESSION[ 'correspondance' ]['responsable2'][$i];
			break;
		}


/*********************************************************************************
********************  -D- Gestion des variables Correspondance *******************
**********************************************************************************/

	if (! isset($_SESSION[ 'correspondance' ]))
	{
	
	$link = mysql_connect($mysql_location,$mysql_user,$mysql_password)
	or die('Could not connect: ' . mysql_error());
	mysql_select_db($mysql_base) or die('Could not select database');
	
	$query2 = 'SELECT * FROM T_CORRESPONDANCE ORDER BY GROUPE';
	$result2 = mysql_query($query2) or die('Requete de selection des pieces associees a la demande: ' . mysql_error());
	//$_SESSION['groupe'][0]="Equipe, contrat ou service";
	$_SESSION[ 'correspondance' ]['groupe'][0]="Equipe, contrat ou service";
	$i=1;
	while ($line2 = mysql_fetch_array($result2, MYSQL_NUM)) {
		$_SESSION[ 'correspondance' ]['groupe'][$i]=$line2[0];
		//$_SESSION['groupe'][$i]=$line2[0];
	//retrouver l'indice a partice du nom de groupe:
		$_SESSION[ 'correspondance' ][$line2[0]]=$i;
		$_SESSION[ 'correspondance' ]['responsable'][$i]=$line2[1];
		$_SESSION[ 'correspondance' ]['responsable2'][$i]=$line2[2];
		$_SESSION[ 'correspondance' ]['administratif'][$i]=$line2[3];
		$_SESSION[ 'correspondance' ]['administratif2'][$i]=$line2[4];
		$_SESSION[ 'correspondance' ]['valid_missions'][$i]=$line2[5];
		$_SESSION[ 'correspondance' ]['valid_conges'][$i]=$line2[6];
		$_SESSION[ 'correspondance' ]['entite_depensiere'][$i]=$line2[7];
	
		if ($_SESSION[ 'connection' ][ 'groupe' ]==$_SESSION[ 'correspondance' ]['groupe'][$i])
			{
			$_SESSION[ 'responsable_groupe']=$_SESSION[ 'correspondance' ]['responsable'][$i];
			$_SESSION[ 'responsable2_groupe']=$_SESSION[ 'correspondance' ]['responsable2'][$i];
			}
	
		$i++;
		}
	$_SESSION[ 'nb_groupe']=$i-1;
	
	mysql_free_result($result2);
	mysql_close($link);
	}

	//print_r($_SESSION); //TEST
	header('location: ../'.strtolower($modules[0]).'.php?sid='.$sid);
}	
else //Le login n'existe pas dans la bdd
{
	$_SESSION[ 'login_cas' ] = phpCAS::getUser();
	header('location: ../reception.php?inscription=1&sid='.$sid);
}





?>
<html>
<head>
<title>phpCAS simple client</title>
</head>
<body>
<h1>Successfull Authentication!</h1>
<p>the user's login is <b><?php echo phpCAS::getUser(); ?></b>.</p>
<p>phpCAS version is <b><?php echo phpCAS::getVersion(); ?></b>.</p>
<p><a href="?logout=">Logout</a></p>
</body>
</html>