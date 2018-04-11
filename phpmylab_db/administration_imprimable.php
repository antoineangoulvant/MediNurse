<?php
/**
* Page imprimable des soldes des congés.
*
* Date de création : 1 février 2010<br>
* Date de dernière modification : 1 février 2010
* @version 1.0.1
* @author Emmanuel Delage
* @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.html
* @copyright CNRS (c) 2015
* @package phpMyLab
* @subpackage Administration
*/

/*********************************************************************************
******************************  PLAN     *****************************************
*********************************************************************************/
//    | -A- Initialisation generale (configuration et php)
//    | -B- Initialisation Session et variables
//    | -C- Variables
//    | -D- -HTML

/*********************************************************************************
***************  -A- Initialisation generale (configuration et php) **************
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
//ini_set('session.use_cookies', '0');
ini_set('session.use_cookies', '1');

// We'll add the session ID to URLs ourselves - disable trans_sid
ini_set('url_rewriter.tags', '');

// Rather dumb character set detection:
// Try switching to UTF-8 automagically on stuff like "NLS_LANG=american_america.UTF8"
$charset = 'ISO-8859-1';
if (getenv('NLS_LANG'))
  if (strtoupper(substr(getenv('NLS_LANG'), -5)) == '.UTF8')
	$charset = 'UTF-8';

//les dates en francais:
setlocale(LC_TIME, "fr_FR");

/*********************************************************************************
***********************  -B- Initialisation Session et variables *****************
**********************************************************************************/

// Initialize session ID
//$sid = '';
//if (isset($_REQUEST[ 'sid' ])) $sid = substr(trim(preg_replace('/[^a-f0-9]/', '', $_REQUEST[ 'sid' ])), 0, 13);
//if ($sid == '') $sid = uniqid('');

// Start PHP session
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
**********************  -C- Variables ********************************************
**********************************************************************************/

$module_conge_charge=1;
$tous_les_utilisateurs='Tous les groupes';

 
/*********************************************************************************
**********************   -D- -HTML      ******************************************
**********************************************************************************/

// Charset header
header('Content-Type: text/html; charset=' . $charset);
?>

<html>

<head>
<title>Solde des cong&eacute;s</title>

<SCRIPT>
function cacher()
{
document.getElementById("elem1").style.visibility = "hidden";
}
</SCRIPT>

<?php
// Partie CSS (habillage) //////////////////////////
?>
<STYLE TYPE="text/css">
form{display:inline;}
body,a,p,span,td,th,input,select,textarea {
	font-family:verdana,arial,helvetica,geneva,sans-serif,serif;
	font-size:12px;
}
</style>
</head>

<body>

<form name="form1" method="post" enctype="multipart/form-data" action="<?php echo $_SERVER[ 'PHP_SELF' ]; ?>">
<?php
	echo '<center>'.ucwords(strftime("%A %d %B %Y")).'</center>';
	//tableau utilisateurs
	echo '<table align=center border=0 bordercolor="darkblue" cellspacing=0>';
	echo '<tr bgcolor="#E0C0D0">';
	echo '<td colspan=2 align=center style="border:solid gray 1px" ><b>Liste des utilisateurs</b></td>';

//	if ($module_conge_charge)
	{
	echo '<td style="background-color:white"></td>';
	echo '<td colspan=5 align=center style="border:solid gray 1px" ><b>Cong&eacute;s</b></td>';
	}
	echo '</tr><tr bgcolor="#E0C0D0">';
	echo '<td style="border:solid gray 1px;width:80px" align=center>Nom</td>';
	echo '<td style="border:solid gray 1px" align=center width="80px">Pr&eacute;nom</td>';

//	if ($module_conge_charge)
	{
	echo '<td style="background-color:white" align=center width="10px">&nbsp;</td>';
	echo '<td style="border:solid gray 1px" align=center width="40px">Solde CA</td>';
	echo '<td style="border:solid gray 1px" align=center width="40px">Solde CA-1</td>';
	echo '<td style="border:solid gray 1px" align=center width="40px">Solde R&eacute;cup.</td>';
	echo '<td style="border:solid gray 1px" align=center width="40px">Solde CET</td>';
	echo '<td style="border:solid gray 1px" align=center width="40px">Quota jours</td>';
	}
	echo '</tr>';

/////////////////////////////////////////////////////////////
//Affichage pour tous les utilisateurs	
	$taille_groupe=0;
	$i=1;
	$nombre=$_SESSION[ 'nb_utilisateur'];
	
	for ($i=1 ; $i<=$nombre ; $i++)
	{
	$groupes_visibles=0;
	if ($_SESSION[ 'selection_groupe' ]==$tous_les_utilisateurs) 
	{
	$groupes_visibles=1;
	$_SESSION['taille'][$_SESSION[ 'selection_groupe' ]]=0;
	}
	elseif ($_SESSION['utilisateur']['groupe'][$i]==$_SESSION[ 'selection_groupe' ]) 	$groupes_visibles=1;
	if ($groupes_visibles==1)
	{
	echo '<tr>';

	$proprie='';
	$taille_groupe++;
	if (($i==$nombre) || ($taille_groupe==$_SESSION['taille'][$_SESSION[ 'selection_groupe' ]])) $proprie='border-bottom:solid gray 1px;';
	$bgcol='';
	if ($i%2==0) $bgcol='background-color:F8F8F8;';

	echo '<td style="border-left:solid gray 1px;'.$proprie.''.$bgcol.'" align=center>'.ucwords(strtolower($_SESSION['utilisateur']['nom'][$i])).'</td>';
	echo '<td style="border-right:solid gray 1px;'.$proprie.''.$bgcol.'" align=center>'.ucwords(strtolower($_SESSION['utilisateur']['prenom'][$i])).'</td>';

//	if ($module_conge_charge)
	{
	echo '<td style="" align=center>&nbsp;</td>';

	echo '<td style="border-left:solid gray 1px;'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['utilisateur']['solde_ca'][$i].'</td>';
	echo '<td style="'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['utilisateur']['solde_ca_1'][$i].'</td>';
	echo '<td style="'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['utilisateur']['solde_recup'][$i].'</td>';
	echo '<td style="'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['utilisateur']['solde_cet'][$i].'</td>';
	echo '<td style="border-right:solid gray 1px;'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['utilisateur']['quota_jours'][$i].'</td>';
	}

	echo '</tr>';
	}//fin du if ($groupes_visibles)
	}//fin de boucle sur $i

	echo '</table>';

	echo '<center><input id="elem1" type="button" onclick="cacher();window.print();window.close()" name="IMPRIME" value=" Imprimer les cong&eacute;s et fermer la fen&ecirc;tre" /></center>';

echo '</form>';
?>

</body>
</html>
