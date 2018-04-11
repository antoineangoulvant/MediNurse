<?php

//Affichage des erreurs en mode test
include 'config.php';
if($mode_test == 1)
{
	ini_set('display_errors', 1);
	error_reporting(E_ALL);
}

/**
* Affichage des absences.
*
* Affichage des absences pour l'ensemble des utilisateurs.
*
* Date de création : 6 novembre 2009<br>
* Date de dernière modification : 7 Mai 2012
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
//    | -D- Fonctionnalites
//    | -E- Choix du module


/*********************************************************************************
***********************  -A- Gestion de la déconnexion ***************************
**********************************************************************************/

if (isset($_REQUEST[ 'disconnect' ]) && file_exists("CAS/config_cas.php"))
{
	//On détruit la session
	session_regenerate_id();	
	session_unset();
	session_destroy ( );
	$_SESSION[ 'connection' ][ 'utilisateur' ] = '';
	$_SESSION[ 'connection' ][ 'nom' ] = '';
	$_SESSION[ 'connection' ][ 'prenom' ] = '';
	$_SESSION[ 'connection' ][ 'mot_de_passe' ] = '';
	$_SESSION[ 'connection' ][ 'mot_de_passe_new1' ] = '';
	$_SESSION[ 'connection' ][ 'mot_de_passe_new2' ] = '';
	$_SESSION[ 'connection' ][ 'ss' ] = '';
	$_SESSION[ 'connection' ][ 'mel' ] = '';
	$_SESSION[ 'connection' ][ 'groupe' ] = '';
	$_SESSION[ 'connection' ][ 'status' ] = 0;

	// Load the CAS lib
	require_once 'CAS/CAS.php';
	require_once 'CAS/config_cas.php';

	// Uncomment to enable debugging
	phpCAS::setDebug();
	
	// Initialize phpCAS
	phpCAS::client(CAS_VERSION_2_0,$cas_host,$cas_port,$cas_context,false);
	
	// For quick testing you can disable SSL validation of the CAS server.
	// THIS SETTING IS NOT RECOMMENDED FOR PRODUCTION.
	// VALIDATING THE CAS SERVER IS CRUCIAL TO THE SECURITY OF THE CAS PROTOCOL!
	phpCAS::setNoCasServerValidation();
	
	//session_start();

	if(phpCAS::isAuthenticated())
	{
		include_once('CAS/config_cas.php');
		phpCAS::logoutWithRedirectService($url_reception);
	}
	else
	{		
		$self=$_SERVER[ 'PHP_SELF' ];
		$chemin_module=substr($self,0,-strlen(strrchr($self,"/")));
		$chemin_module.='/reception.php';
		header('location:'.$chemin_module);
	}
}	



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
//ini_set('url_rewriter.tags', '');

// Rather dumb character set detection:
// Try switching to UTF-8 automagically on stuff like "NLS_LANG=american_america.UTF8"
$charset = 'ISO-8859-1';
if (getenv('NLS_LANG'))
  if (strtoupper(substr(getenv('NLS_LANG'), -5)) == '.UTF8')
	$charset = 'UTF-8';
$charset = 'UTF-8';

//les dates en francais:
setlocale(LC_TIME, "fr_FR");

/*********************************************************************************
********************* -B- Initialisation Session et variables ********************
**********************************************************************************/

// Initialize session ID
//$sid = '';
//if (isset($_REQUEST[ 'sid' ])) $sid = substr(trim(preg_replace('/[^a-f0-9]/', '', $_REQUEST[ 'sid' ])), 0, 13);
//if ($sid == '') $sid = uniqid('');

// Start PHP session
//session_id($sid);
session_id();
session_name('phpmylab');
session_start();

if ($_SESSION[ 'connection' ][ 'utilisateur' ] == '')
{
	$self=$_SERVER[ 'PHP_SELF' ];
	$chemin_module=substr($self,0,-strlen(strrchr($self,"/")));
	$chemin_module.='/reception.php';
	header('location:'.$chemin_module);
}

if (isset($_REQUEST[ 'disconnect' ]))
{
	session_regenerate_id();	
	session_unset();
	session_destroy ( );
	$_SESSION[ 'connection' ][ 'utilisateur' ] = '';
	$_SESSION[ 'connection' ][ 'nom' ] = '';
	$_SESSION[ 'connection' ][ 'prenom' ] = '';
	$_SESSION[ 'connection' ][ 'mot_de_passe' ] = '';
	$_SESSION[ 'connection' ][ 'mot_de_passe_new1' ] = '';
	$_SESSION[ 'connection' ][ 'mot_de_passe_new2' ] = '';
	$_SESSION[ 'connection' ][ 'ss' ] = '';
	$_SESSION[ 'connection' ][ 'mel' ] = '';
	$_SESSION[ 'connection' ][ 'groupe' ] = '';
	$_SESSION[ 'connection' ][ 'status' ] = 0;
	
	$self=$_SERVER[ 'PHP_SELF' ];
	$chemin_module=substr($self,0,-strlen(strrchr($self,"/")));
	$chemin_module.='/reception.php';
	header('location:'.$chemin_module);
}

/**
* Recuperation des noms et prenoms d'un groupe.
*
* @param string Adresse mail du destinataire 
* @param string Message
* @return array tableau des membres du groupe
*/
function membre_groupe2($the_groupe,   $chem_conn)
{
   $groupe_nm=array();
   //ATTENTION fonction différente de membre_groupe(..) car la premiere valeur differente:
   //$groupe_nm[0]="NULL,Nom,Pr&eacute;nom";
   $groupe_nm[0]="NULL,X,Y";

   include($chem_conn);

   // Connecting, selecting database:
   $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
   or die('Could not connect: ' . mysqli_connect_error());
   mysqli_select_db($link,$mysql_base) or die('Could not select database');

	$query2 = 'SELECT UTILISATEUR,NOM,PRENOM FROM T_UTILISATEUR WHERE GROUPE=\''.$the_groupe.'\' ORDER BY PRENOM';
	$result2 = mysqli_query($link,$query2) or die('Query '.$query2.'| failed: ' . mysqli_error());
	if ($result2)
	{
		$i=1;
		while ($line2 = mysqli_fetch_array($result2, MYSQL_NUM)) {
		$groupe_nm[$i]=''.$line2[0].','.$line2[1].','.$line2[2];
		$i++;
		} 
		mysqli_free_result($result2);
	}

   // Closing connection:
   mysqli_close($link);

   return $groupe_nm;
}

/*********************************************************************************
********************  -C- Gestion des variables   ********************************
**********************************************************************************/

$mois=array("Janvier","Fevrier","Mars","Avril","Mai","Juin","Juillet","Aout","Septembre","Octobre","Novembre","Decembre");

/////////////////// Initialisation et variables recherche evoluee ////////////////////
/**
* Initialisation pour l'affichage des absences.
*
* @param int année en cours 
* @param string mois en cours
*/
function init_affi_absences($an_en_cours,$les_mois)
{
	$_SESSION['affi_absences']['groupe_nom_prenom']=$_SESSION[ 'connection' ][ 'utilisateur' ];

	$_SESSION[ 'affi_absences' ][ 'login' ] = $_SESSION[ 'connection' ][ 'utilisateur' ];
	$_SESSION[ 'affi_absences' ][ 'nom' ] = '';
	$_SESSION[ 'affi_absences' ][ 'prenom' ] = '';
	//par defaut, utilisation du groupe de l'utilisateur
	$_SESSION[ 'affi_absences' ][ 'groupe' ] = $_SESSION[ 'connection' ][ 'groupe' ];
	$_SESSION[ 'affi_absences' ][ 'annee' ] = $an_en_cours;//prevoir de recuperer auto. 

	$mois_de_debut=date('n')-1;
	$mois_de_fin=$mois_de_debut+2;
	if ($mois_de_fin>11) $mois_de_fin=11;
	$_SESSION[ 'affi_absences' ][ 'mois_deb' ] = $mois_de_debut;
	$_SESSION[ 'affi_absences' ][ 'mois_fin' ] = $mois_de_fin;
}

if (! isset($_SESSION[ 'affi_abscences' ]))
{
	init_affi_absences($annee_en_cours,$mois);
}

if (isset($_REQUEST[ 'affi_absences' ][ 'login' ])) $_SESSION[ 'affi_absences' ][ 'login' ] = $_REQUEST[ 'affi_abscences' ][ 'login' ];
if (isset($_REQUEST[ 'affi_absences' ][ 'nom' ])) $_SESSION[ 'affi_absences' ][ 'nom' ] = $_REQUEST[ 'affi_abscences' ][ 'nom' ];
if (isset($_REQUEST[ 'affi_absences' ][ 'prenom' ])) $_SESSION[ 'affi_absences' ][ 'prenom' ] = $_REQUEST[ 'affi_abscences' ][ 'prenom' ];
if (isset($_REQUEST[ 'affi_absences' ][ 'annee' ])) $_SESSION[ 'affi_absences' ][ 'annee' ] = $_REQUEST[ 'affi_absences' ][ 'annee' ];
if (isset($_REQUEST[ 'affi_absences' ][ 'mois_deb' ])) $_SESSION[ 'affi_absences' ][ 'mois_deb' ] = $_REQUEST[ 'affi_absences' ][ 'mois_deb' ];
if (isset($_REQUEST[ 'affi_absences' ][ 'mois_fin' ])) $_SESSION[ 'affi_absences' ][ 'mois_fin' ] = $_REQUEST[ 'affi_absences' ][ 'mois_fin' ];
if (isset($_REQUEST[ 'affi_absences' ][ 'groupe_nom_prenom' ])) 
{
$_SESSION[ 'affi_absences' ][ 'groupe_nom_prenom' ] = $_REQUEST[ 'affi_absences' ][ 'groupe_nom_prenom' ];
}
if (isset($_REQUEST[ 'affi_absences' ][ 'groupe' ])) 
{
	if ($_SESSION[ 'affi_absences' ][ 'groupe' ] != $_REQUEST[ 'affi_absences' ][ 'groupe' ]) $_SESSION[ 'affi_absences' ][ 'login' ]='';
	$_SESSION[ 'affi_absences' ][ 'groupe' ] = $_REQUEST[ 'affi_absences' ][ 'groupe' ];
}

/////////////////// Initialisation et variables recherche graphique ////////////////////
if (isset($_REQUEST[ 'uneannee' ])) $_SESSION[ 'uneannee' ] = $_REQUEST[ 'uneannee' ];

/*********************************************************************************
******************  -D- Fonctionnalites ******************************************
**********************************************************************************/

if ( isset($_REQUEST[ 'rechercher_evol' ]) )
{
	$message_evolue= '';
}

if ( isset($_REQUEST[ 'reinitialiser' ]) )
{
	init_affi_absences($annee_en_cours,$mois);
}

/*********************************************************************************
***********************  -E- Choix du module *************************************
**********************************************************************************/

if (! isset($_SESSION[ 'choix_module' ])) $_SESSION[ 'choix_module' ] = $modules[0];
if (isset($_REQUEST[ 'choix_module' ])) {$_SESSION[ 'choix_module' ] = $_REQUEST[ 'choix_module' ];

for ($i=0;$i<sizeof($modules);$i++)
   {
	if ($_SESSION['choix_module']==$modules[$i]) 
	{
	   $self=$_SERVER[ 'PHP_SELF' ];
	   $chemin_module=substr($self,0,-strlen(strrchr($self,"/")));
	   $chemin_module.='/'.strtolower($_SESSION['choix_module']).'.php';
	   //header('location:'.$chemin_module.'?sid=' . $sid);
	   header('location:'.$chemin_module);
	}
   }
}
 
/*********************************************************************************
******************************  -F- HTML      ************************************
**********************************************************************************/

// En tete des modules
include "en_tete.php";
?>	
	
	
<form name="form1" id="form1" method="post" action="<?php echo $_SERVER[ 'PHP_SELF' ]; ?>">
<?php

///////////////////////////  Affichage Recherche //////////////////////////
   echo '<a href="planning.php" id="planning_vue_mensuelle">Vue calendaire</a>';//planning.php?sid='.$sid.'
   echo '<div id="recherche_planning">';
	echo '<table><tr><td colspan=2 id="titreTabRechPlanning" class="borderLR">Recherche</td></tr>';
   echo '<tr>';
   echo '<td class="paddingRight borderLR"><label for="affi_absences_groupe">Choix d\'un groupe<br> ou d\'un membre d\'un groupe</label></td>';
   echo '<td class="borderLR"><select name="affi_absences[groupe]" id="affi_absences_groupe" ';
   echo ' onChange="javascript:document.getElementById(\'form1\').submit()"';
   echo '>';
   for ($i=1;$i<sizeof($_SESSION[ 'correspondance' ]['groupe']);$i++)
   {
	if ($_SESSION['correspondance']['entite_depensiere'][$i]==0)
	echo '<option value="'.$_SESSION['correspondance']['groupe'][$i].'" ';
	if ($_SESSION['affi_absences']['groupe']==$_SESSION['correspondance']['groupe'][$i])
	{
		 echo 'selected';
		$_SESSION['groupe_indice_affi_absences']=$i;
	}
	echo '>'.$_SESSION[ 'correspondance' ]['groupe'][$i].'</option>';
   }
   echo '</select>';

   $groupe_nom_prenom=membre_groupe2($_SESSION['affi_absences']['groupe'],$chemin_connection);

   echo '<select name="affi_absences[groupe_nom_prenom]"';
   echo ' onChange="javascript:document.getElementById(\'form1\').submit()"';
   echo '>';
   for ($i=0;$i<sizeof($groupe_nom_prenom);$i++)
   {
	$np=explode(',',$groupe_nom_prenom[$i]);
	echo '<option value="'.$np[0].'" ';
	if ($_SESSION['affi_absences']['groupe_nom_prenom']==$np[0])
	{
		echo 'selected';
		if ($i>0)
		{
			$_SESSION[ 'affi_absences' ][ 'login' ] = $np[0];
			$_SESSION[ 'affi_absences' ][ 'nom' ] = ucwords(strtolower($np[1]));
			$_SESSION[ 'affi_absences' ][ 'prenom' ] = ucwords(strtolower($np[2]));
		}
		else $_SESSION[ 'affi_absences' ][ 'login' ] = '';
	}
	if ($i==0)
	echo '>Tous les membres</option>';
	else
	echo '>'.ucwords(strtolower($np[1])).' '.ucwords(strtolower($np[2])).'</option>';
   }
   echo '</select>';
   echo '</td></tr>';

//nouvel emplacement de recherche calendaire
   echo '<tr><td class="borderLR borderBottom"><label for="affi_absences_annee">R&eacute;glage de la p&eacute;riode</label></td><td class="borderBottom borderLR">';
   echo 'Ann&eacute;e ';
   echo '<select name="affi_absences[annee]" id="affi_absences_annee" onChange="javascript:document.getElementById(\'form1\').submit()">';
   for ($i=1;$i<sizeof($annees);$i++)
   {
	echo '<option value="'.$annees[$i].'" ';
	if ($_SESSION['affi_absences']['annee']==$annees[$i])	 echo 'selected';
	echo '>'.$annees[$i].'</option>';
   }
   echo '</select>';

   echo ' entre ';

   echo '<select name="affi_absences[mois_deb]" onChange="javascript:document.getElementById(\'form1\').submit()">';
   for ($i=0;$i<sizeof($mois);$i++)
   {
	echo '<option value="'.$i.'" ';
	if ($_SESSION['affi_absences']['mois_deb']==$i)	echo 'selected';
	echo '>'.$mois[$i].'</option>';
   }
   echo '</select>';

   echo ' et ';

   echo '<select name="affi_absences[mois_fin]" onChange="javascript:document.getElementById(\'form1\').submit()">';
   for ($i=0;$i<sizeof($mois);$i++)
   {
	echo '<option value="'.$i.'" ';
	if ($_SESSION['affi_absences']['mois_fin']==$i)	 echo 'selected';
	echo '>'.$mois[$i].'</option>';
   }
   echo '</select>';
   echo '</td></tr>';

//boutons de recherche evoluee et reinitialisation
   echo '<tr class="noBorder"><td colspan=2 class="centrer">';
   if(isset($message_evolue))
      echo '<br><font color="#FF0000"><b>'.$message_evolue.'</b></font>';	
   echo '</td></tr>';

   echo '</table></div>' . "\n";

/////////////////////////// Affichage/construction Image resultat ///////////

   echo '<table id="img_planning">';
   echo '<tr><td colspan=3>';

   $print=0;//l'image ne sera pas optimisee pour l'impression
	echo '<img src="calendrier_absences.php?chemin='.$chemin_connection.'&uneannee='.$_SESSION['affi_absences']['annee'].'&groupe='.$_SESSION['affi_absences']['groupe'].'&login='.$_SESSION['affi_absences']['login'].'&moisdeb='.$_SESSION['affi_absences']['mois_deb'].'&moisfin='.$_SESSION['affi_absences']['mois_fin'].'&print='.$print.'">';

   	echo '</td></tr>';
	echo '<tr><td>';
	echo '<table id="legende_planning">';
   	echo '<tr id="legende"><td class="libelle_legende"><h4>L&eacute;gende</h4></td>';
   	echo '<td class="contenu_legende"><ul><li style="color: #FFAAAA">Jour f&eacute;ri&eacute;</li>';
	echo '<li style="color: #777777">Week end</li>';
	echo '<li style="color: #7777FF">Mission</li>';
	echo '<li style="color: #77FF77">Cong&eacute;</li></ul></td>';
//	echo '<td><img title="Imprimer le planning" src="images/imprimer.png" onclick="window.open(\'absences_globales_imprimable.php?sid='.$sid.'\',\'Calendrier\',\'scrollbars=yes,width=725,height=600\')"  name="IMPRIME" alt="Version imprimable" />';
	echo '<td><img title="Imprimer le planning" src="images/imprimer.png" onclick="window.open(\'absences_globales_imprimable.php\',\'Calendrier\',\'scrollbars=yes,width=725,height=600\')"  name="IMPRIME" alt="Version imprimable" />';
	echo '</td></tr>';
	echo '</table>';
   echo '</td></tr></table>';

//////////////PIED DE PAGE/////////////////
include "pied_page.php";
?>
