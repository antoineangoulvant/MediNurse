<?php
/**
* Page imprimable des absences des personnels.
*
* Date de création : 13 novembre 2010<br>
* Date de dernière modification : 30 septembre 2015
* @version 1.2.0
* @author Emmanuel Delage, Cedric Gagnevin <cedric.gagnevin@laposte.net>
* @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.html
* @copyright CNRS (c) 2015
* @package phpMyLab
* @subpackage Absences
*/

/*********************************************************************************
******************************  PLAN     *****************************************
*********************************************************************************/
//    | -A- Initialisation generale (configuration et php) 
//    | -B- Initialisation Session et variables
//    | -C- Gestion des variables Recherche
//    | -D- HTML

/*********************************************************************************
*************** -A- Initialisation generale (configuration et php) **************
**********************************************************************************/

/**
**/
include 'config.php';

// Fix magic_quotes_gpc garbage
if (get_magic_quotes_gpc())
  { 
function stripslashes_deep($value)
	  { return (is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value));
	  }
	$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
  }

// To allow multiple independent portail sessions,
// propagate session ID in the URL instead of a cookie.
ini_set('session.use_cookies', '1');

// We'll add the session ID to URLs ourselves - disable trans_sid
//ini_set('url_rewriter.tags', '');

// Rather dumb character set detection:
// Try switching to UTF-8 automagically on stuff like "NLS_LANG=american_america.UTF8"
$charset = 'ISO-8859-1';
if (getenv('NLS_LANG'))
  if (strtoupper(substr(getenv('NLS_LANG'), -5)) == '.UTF8')
	$charset = 'UTF-8';
$charset = 'UTF-8';

// Dates en francais:
setlocale(LC_TIME, "fr_FR");

/*********************************************************************************
**************************  -B- Initialisation Session et variables **************
**********************************************************************************/

// Initialize session ID:
//$sid = '';
//if (isset($_REQUEST[ 'sid' ])) $sid = substr(trim(preg_replace('/[^a-f0-9]/', '', $_REQUEST[ 'sid' ])), 0, 13);
//if ($sid == '') $sid = uniqid('');

// Start PHP session:
//session_id($sid);
session_name('phpmylab');
session_start();

if ($_SESSION[ 'connection' ][ 'utilisateur' ] == '')
{
	$self=$_SERVER[ 'PHP_SELF' ];
	$chemin_module=substr($self,0,-strlen(strrchr($self,"/")));
	$chemin_module.='/reception.php';
	header('location:'.$chemin_module);
}

/*********************************************************************************
******************** -C- Gestion des variables Recherche *********************
**********************************************************************************/

$mois=array("Janvier","Fevrier","Mars","Avril","Mai","Juin","Juillet","Aout","Septembre","Octobre","Novembre","Decembre");

/////////////////// Initialisation et variables recherche evoluee ////////////////////
//la déclaration qui suit n'est surement etre pas utile:
if (isset($_REQUEST[ 'affi_absences' ][ 'login' ])) $_SESSION[ 'affi_absences' ][ 'login' ] = $_REQUEST[ 'affi_absences' ][ 'login' ];
//la déclaration qui suit n'est surement etre pas utile:
if (isset($_REQUEST[ 'affi_absences' ][ 'nom' ])) $_SESSION[ 'affi_absences' ][ 'nom' ] = $_REQUEST[ 'affi_absences' ][ 'nom' ];
//la déclaration qui suit n'est surement etre pas utile:
if (isset($_REQUEST[ 'affi_absences' ][ 'prenom' ])) $_SESSION[ 'affi_absences' ][ 'prenom' ] = $_REQUEST[ 'affi_absences' ][ 'prenom' ];
if (isset($_REQUEST[ 'affi_absences' ][ 'annee' ])) $_SESSION[ 'affi_absences' ][ 'annee' ] = $_REQUEST[ 'affi_absences' ][ 'annee' ];
if (isset($_REQUEST[ 'affi_absences' ][ 'mois_deb' ])) $_SESSION[ 'affi_absences' ][ 'mois_deb' ] = $_REQUEST[ 'affi_absences' ][ 'mois_deb' ];
if (isset($_REQUEST[ 'affi_absences' ][ 'mois_fin' ])) $_SESSION[ 'affi_absences' ][ 'mois_fin' ] = $_REQUEST[ 'affi_absences' ][ 'mois_fin' ];
if (isset($_REQUEST[ 'affi_absences' ][ 'groupe_nom_prenom' ])) 
{
$_SESSION[ 'affi_absences' ][ 'groupe_nom_prenom' ] = $_REQUEST[ 'affi_absences' ][ 'groupe_nom_prenom' ];
}
if (isset($_REQUEST[ 'affi_absences' ][ 'groupe' ])) $_SESSION[ 'affi_absences' ][ 'groupe' ] = $_REQUEST[ 'affi_absences' ][ 'groupe' ];
//la déclaration qui suit n'est peut etre pas utile:
if (! isset($_SESSION['groupe_nom_prenom_indice_recherche'])) $_SESSION['groupe_nom_prenom_indice_recherche']=0;

/////////////////// Initialisation et variables recherche graphique ////////////////////
if (isset($_REQUEST[ 'uneannee' ])) $_SESSION[ 'uneannee' ] = $_REQUEST[ 'uneannee' ];

 
/*********************************************************************************
******************************     -D- HTML      *********************************
**********************************************************************************/

// Charset header
header('Content-Type: text/html; charset=' . $charset);
?>

<html>

<head>
<title>Calendrier des absences<?php
if ($_SESSION[ 'connection' ][ 'utilisateur' ] != '')
  {
	echo ' (' . $_SESSION[ 'connection' ][ 'utilisateur' ];
    	echo ')';
  }
?></title>

<SCRIPT>
function cacher()
{
	document.getElementById("elem1").style.visibility = "hidden";
}
</SCRIPT>

<?php
// CSS (habillage) 
?>
<link rel=stylesheet type="text/css" href="style.css">
</head>

<body>

<?php
$print=1;//l'image sera optimisee pour l'impression
echo '<img id="elem10" src="calendrier_absences.php?chemin='.$chemin_connection.'&uneannee='.$_SESSION['affi_absences']['annee'].'&groupe='.$_SESSION['affi_absences']['groupe'].'&login='.$_SESSION['affi_absences']['login'].'&moisdeb='.$_SESSION['affi_absences']['mois_deb'].'&moisfin='.$_SESSION['affi_absences']['mois_fin'].'&print='.$print.'"><br/>';
	
echo '<table id="legende_planning">';
echo '<tr>';
echo '<td id="legende"><h4>Legende</h4><ul>';
echo '<li style="color: #FFAAAA">Jour f&eacute;ri&eacute;</li>';
echo '<li style="color: #777777">Week end</li>';
echo '<li style="color: #7777FF">Mission</li>';
echo '<li style="color: #77FF77">Cong&eacute;</li></ul></td>';
echo '</ul></td></tr>';
echo '</table>';
	
echo '<table width=100%><tr><td class="centrer"><img title="Imprimer le planning et fermer la fen&ecirc;tre" src="images/imprimer.png" id="elem1" onclick="cacher();window.print();window.close()" name="IMPRIME" alt=" Imprimer le calendrier et fermer la fen&ecirc;tre" /></td></tr></table>';
	

?>

</body>
</html>
