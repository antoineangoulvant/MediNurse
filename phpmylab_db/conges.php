<?php

//Affichage des erreurs en mode test
include 'config.php';
if($mode_test == 1 && !isset($_GET[ 'disconnect' ]))//Probleme de deconnexion avec CAS sinon
{
	ini_set('display_errors', 1);
	error_reporting(E_ALL);
}

/**
* Gestion des demandes de congés.
*
* La page des congés permet d'effectuer une demande de congés et de rechercher des congés.
*
* Date de création : 10 Septembre 2009<br>
* Date de derniére modification : 19 mai 2015
* @version 3.0.0
* @author Emmanuel Delage, Cedric Gagnevin <cedric.gagnevin@laposte.net>
* @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.html
* @copyright CNRS (c) 2015
* @package phpMyLab
* @subpackage Conges
*/

/*********************************************************************************
******************************  PLAN     *****************************************
*********************************************************************************/

//    | -A- Fonctions
//    | -B- Initialisation generale (configuration et php)
//    | -C- Initialisation Session et variables
//    | -D- Gestion des variables de recherche
//    | -E- Gestion des variables de conge
//    | -F- Bouton: Calcul du nombre de jours ouvres
//    | -G- Fonctionnalites (MySQL)
//    | G1- Recherche simple des conges
//    | G2- Recherche evoluee
//    | G3- Reinitialiser page suivante et precedente la Recherche evoluee
//    | G4- Ajout d'une demande de conge 
//    | G5- Nouvelle demande conge
//    | G6- Valider demande conge
//    | G7- Annuler demande conge
//    | -H- Reinitialiser la saisie
//    | -I- Choix du module
//    | -J- HTML
//    | J1- Affichage Recherche Demande
//    | J2- Affichage des resultets de recherche evoluee
//    | J3- Affichage des resultats de recherche graphique/calendaire ///////////
//    | J4- Partie demande de conge


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
***********************  -A- Fonctions *******************************************
**********************************************************************************/

/**
* Calcul du nombre de jours ouvrés
*
* @param int date de début 
* @param int date de fin 
* @return int nombre de jours ouvrés
*/
function get_nb_open_days($date_start, $date_stop , $AM_inclus, $PM_inclus) {
$arr_bank_holidays = array(); // Tableau des jours feriés
// On boucle dans le cas ou l'année de départ serait différente de l'année d'arrivée
$diff_year = date('Y', $date_stop) - date('Y', $date_start);
for ($i = 0; $i <= $diff_year; $i++) {
$year = (int)date('Y', $date_start) + $i;
// Liste des jours feriés
$arr_bank_holidays[] = '1_1_'.$year; // Jour de l'an
$arr_bank_holidays[] = '1_5_'.$year; // Fete du travail
$arr_bank_holidays[] = '8_5_'.$year; // Victoire 1945
$arr_bank_holidays[] = '14_7_'.$year; // Fete nationale
$arr_bank_holidays[] = '15_8_'.$year; // Assomption
$arr_bank_holidays[] = '1_11_'.$year; // Toussaint
$arr_bank_holidays[] = '11_11_'.$year; // Armistice 1918
$arr_bank_holidays[] = '25_12_'.$year; // Noel
// Récupération de paques. Permet ensuite d'obtenir le jour de l'ascension et celui de la pentecote
$easter = easter_date($year);
$arr_bank_holidays[] = date('j_n_'.$year, $easter + 86400); // Paques
$arr_bank_holidays[] = date('j_n_'.$year, $easter + (86400*39)); // Ascension
$arr_bank_holidays[] = date('j_n_'.$year, $easter + (86400*50)); // Pentecote
}
//print_r($arr_bank_holidays);
//ajout date start sauve
$date_start_sauve=$date_start;
$nb_days_open = 0;
while ($date_start < $date_stop) {
// Si le jour suivant n'est ni un dimanche (0) ou un samedi (6), ni un jour férié, on incrémente les jours ouvrés
if (!in_array(date('w', $date_start), array(0, 6))
&& !in_array(date('j_n_'.date('Y', $date_start), $date_start), $arr_bank_holidays)) {
$nb_days_open++;
}
$date_start += 86400;
}
//ajout pour si debut de conge l'aprem et non ouvre
if ((!in_array(date('w', $date_start_sauve), array(0, 6))
&& !in_array(date('j_n_'.date('Y', $date_start_sauve), $date_start_sauve), $arr_bank_holidays)) && ($AM_inclus==1)) $nb_days_open-=0.5;
//ajout pour si fin de conge le matin et non ouvre
if ((!in_array(date('w', $date_stop), array(0, 6))
&& !in_array(date('j_n_'.date('Y', $date_stop), $date_stop), $arr_bank_holidays)) && ($PM_inclus==0)) $nb_days_open-=0.5;

return $nb_days_open;
} 

/**
* Envoyer un mail.
*
* Formate le message é envoyer de manière à etre compatible avec la majorité des clients de messagerie et envoie le message au moyen de la fonction mail().
* @param string Adresse mail du destinataire 
* @param string Message
* @param string Objet du mail.
* @param string Adresse mail de l'envoyeur
* @param string Nom de l'envoyeur
* @param bool Avec ou sans fichier attaché 
* @return Retourne le résultat de la fonction mail() 
*/
function send_mail($to, $body, $subject, $fromaddress, $fromname, $attachments=false)
{
  $eol="\r\n";
  $mime_boundary=md5(time());

  # Common Headers
  $headers = "From: ".$fromname."<".$fromaddress.">".$eol;
  $headers .= "Reply-To: ".$fromname."<".$fromaddress.">".$eol;
  $headers .= "Return-Path: ".$fromname."<".$fromaddress.">".$eol;    // these two to set reply address
  $headers .= "Message-ID: <".time()."-".$fromaddress.">".$eol;
  $headers .= "X-Mailer: PHP v".phpversion().$eol;          // These two to help avoid spam-filters

  # Boundry for marking the split & Multitype Headers
//  $headers .= 'MIME-Version: 1.0'.$eol.$eol;
  $headers .= 'MIME-Version: 1.0'.$eol;
  $headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"".$eol.$eol;

  # Open the first part of the mail
  $msg = "--".$mime_boundary.$eol;
 
  $htmlalt_mime_boundary = $mime_boundary."_htmlalt"; //we must define a different MIME boundary for this section
  # Setup for text OR html -
  $msg .= "Content-Type: multipart/alternative; boundary=\"".$htmlalt_mime_boundary."\"".$eol.$eol;

  # Text Version
  $msg .= "--".$htmlalt_mime_boundary.$eol;
  $msg .= "Content-Type: text/plain; charset=iso-8859-1".$eol;
  $msg .= "Content-Transfer-Encoding: 8bit".$eol.$eol;
  $msg .= strip_tags(str_replace("<br>", "\n", substr($body, (strpos($body, "<body>")+6)))).$eol.$eol;

  # HTML Version
  $msg .= "--".$htmlalt_mime_boundary.$eol;
  $msg .= "Content-Type: text/html; charset=iso-8859-1".$eol;
  $msg .= "Content-Transfer-Encoding: 8bit".$eol.$eol;
  $msg .= $body.$eol.$eol;

  //close the html/plain text alternate portion
  $msg .= "--".$htmlalt_mime_boundary."--".$eol.$eol;

  if ($attachments !== false)
  {
    for($i=0; $i < count($attachments); $i++)
    {
      if (is_file($attachments[$i]["file"]))
      {  
        # File for Attachment
        $file_name = substr($attachments[$i]["file"], (strrpos($attachments[$i]["file"], "/")+1));
       
        $handle=fopen($attachments[$i]["file"], 'rb');
        $f_contents=fread($handle, filesize($attachments[$i]["file"]));
        $f_contents=chunk_split(base64_encode($f_contents));    //Encode The Data For Transition using base64_encode();
        $f_type=filetype($attachments[$i]["file"]);
        fclose($handle);
       
        # Attachment
        $msg .= "--".$mime_boundary.$eol;
        $msg .= "Content-Type: ".$attachments[$i]["content_type"]."; name=\"".$file_name."\"".$eol;  // sometimes i have to send MS Word, use 'msword' instead of 'pdf'
        $msg .= "Content-Transfer-Encoding: base64".$eol;
        $msg .= "Content-Description: ".$file_name.$eol;
        $msg .= "Content-Disposition: attachment; filename=\"".$file_name."\"".$eol.$eol; // !! This line needs TWO end of lines !! IMPORTANT !!
        $msg .= $f_contents.$eol.$eol;
      }
    }
  }

  # Finished
  $msg .= "--".$mime_boundary."--".$eol.$eol;  // finish with two eol's for better security. see Injection.
 
  # SEND THE EMAIL
  ini_set('sendmail_from',$fromaddress);  // the INI lines are to force the From Address to be used !
//  $mail_sent = mail($to, $subject, $msg, $headers);
  $mail_sent = mail($to, utf8_decode($subject), $msg, $headers);
 
  ini_restore('sendmail_from');
 
  return $mail_sent;
}

/**
* Initialisation des variables des congés
*/
function init_conge()
{
	$_SESSION[ 'conge' ][ 'id_conge' ] = 0;
	$_SESSION[ 'conge' ][ 'type' ] = 0;
	$_SESSION[ 'conge' ][ 'sans_solde' ] = 0;//genre, utile si type=3
	$_SESSION[ 'conge' ][ 'date_debut' ] = strftime("%d/%m/%Y");
	$_SESSION[ 'conge' ][ 'date_AM' ] = 0;//matinee incluse =AM_inclus
	$_SESSION[ 'conge' ][ 'date_fin' ] = strftime("%d/%m/%Y");
	$_SESSION[ 'conge' ][ 'date_PM' ] = 1;//apres-midi incluse =PM_inclus
	$_SESSION[ 'conge' ]['nb_jours_ouvres'] = 0;
	$_SESSION[ 'conge' ][ 'valide' ] = 2;
	$_SESSION[ 'conge' ][ 'commentaire' ] = '';

	$_SESSION[ 'mailGroupe' ] = 0;
	$_SESSION[ 'nbjo' ]=0;
}

/**
* Retourne un tableau avec les noms et prénoms des membres d'un groupe pour un SELECT
*
* @param string groupe 
* @param string chemin du fichier contenant les variables de connexion de la base de données 
* @return array tableau des membres d'un groupe
*/
function membre_groupe($the_groupe,   $chem_conn)
{
   $groupe_nm=array();
   $groupe_nm[0]="NULL,Nom,Pr&eacute;nom";
/**
**/
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

/**
* Initialise les variables des soldes et calcule des soldes d'un utilisateur
*
* @param string Chemin de la connection de la base de données
*/
function init_solde($chem_conn)
{
   $_SESSION[ 'solde' ][ 'CA' ] = 0.;
   $_SESSION[ 'solde' ][ 'CA-1' ] = 0.;
   $_SESSION[ 'solde' ][ 'CET' ] = 0.;
   $_SESSION[ 'solde' ][ 'RECUP' ] = 0.;
   $_SESSION[ 'solde' ][ 'QUOTA' ] = 0.;
   $_SESSION[ 'solde' ][ 'QUOTITE' ] = 0.;

   include($chem_conn);
   // Connecting, selecting database:
   $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
   or die('Could not connect: ' . mysqli_connect_error());
   mysqli_select_db($link,$mysql_base) or die('Could not select database');

	$query2 = 'SELECT SOLDE_CA,SOLDE_CA_1,SOLDE_CET,SOLDE_RECUP,QUOTA_JOURS,QUOTITE FROM T_CONGE_SOLDE WHERE UTILISATEUR=\''.$_SESSION[ 'connection' ][ 'utilisateur' ].'\' ';
	$result2 = mysqli_query($link,$query2) or die('Query '.$query2.'| failed: ' . mysqli_error());
	if ($result2)
	{
		$line2 = mysqli_fetch_array($result2, MYSQL_NUM);
		$_SESSION[ 'solde' ][ 'CA' ] = $line2[0];
		$_SESSION[ 'solde' ][ 'CA-1' ] = $line2[1];
		$_SESSION[ 'solde' ][ 'CET' ] = $line2[2];
		$_SESSION[ 'solde' ][ 'RECUP' ] = $line2[3];
		$_SESSION[ 'solde' ][ 'QUOTA' ] = $line2[4];
		$_SESSION[ 'solde' ][ 'QUOTITE' ] = $line2[5];
	
		mysqli_free_result($result2);
	}

   // Closing connection:
   mysqli_close($link);
}

/**
* Initialisation des variables de recherche de congés
*
* @param string année actuelle
*/
function init_rech_conge($an_en_cours)
{
	$_SESSION[ 'rech_conge' ][ 'nom' ] = '';
	$_SESSION[ 'rech_conge' ][ 'prenom' ] = '';
	$_SESSION[ 'rech_conge' ][ 'groupe' ] = $_SESSION[ 'correspondance' ]['groupe'][0];
	$_SESSION[ 'rech_conge' ][ 'annee' ] = $an_en_cours;//prevoir de recuperer auto. l'annee en cours
	$_SESSION['rech_conge']['resultats']=0;
	$_SESSION['rech_conge']['limitebasse']=0;
	$_SESSION['rech_conge']['nb_par_page']=10;
}


/**
* Permet d'obtenir les congés à valider
*
* Renvoie un tableau contenant les congés.
*
* @return Tableau contenant les congés
*/
function conges_a_valider() {

	$tab_conges = array();

	include("config.php");
	include($chemin_connection);
	// Connecting, selecting database:
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
	or die('Could not connect: ' . mysqli_error());
	mysqli_select_db($link,$mysql_base) or die('Could not select database : '. mysqli_connect_error());
	
	if($_SESSION[ 'connection' ][ 'utilisateur' ]==$directeur)
	{
		$query = 'SELECT ID_CONGE,T_CONGE.UTILISATEUR,T_CONGE.GROUPE,DEBUT_DATE,FIN_DATE FROM T_CONGE, T_UTILISATEUR WHERE T_CONGE.UTILISATEUR=T_UTILISATEUR.UTILISATEUR AND T_UTILISATEUR.STATUS in (3,4,6) AND T_CONGE.VALIDE = 0';
		$result = mysqli_query($link,$query) or die('Query '.$query.'| failed: ' . mysqli_error());
	}
	elseif($_SESSION[ 'connection' ][ 'status' ]==6)
	{
		$query = 'SELECT ID_CONGE,T_CONGE.UTILISATEUR,T_CONGE.GROUPE,DEBUT_DATE,FIN_DATE FROM T_CONGE, T_UTILISATEUR WHERE T_CONGE.UTILISATEUR=T_UTILISATEUR.UTILISATEUR AND T_UTILISATEUR.STATUS=5 AND T_CONGE.GROUPE=\''.$_SESSION[ 'connection' ][ 'groupe' ].'\' AND T_CONGE.VALIDE = 0';
		$result = mysqli_query($link,$query) or die('Query '.$query.'| failed: ' . mysqli_error());
	}
	else
	{
	$query = 'SELECT * FROM T_CONGE WHERE VALIDE = 0 AND UTILISATEUR IN(SELECT UTILISATEUR FROM T_UTILISATEUR WHERE GROUPE=\''.$_SESSION[ 'connection' ][ 'groupe' ].'\' AND STATUS IN(1,2))';
		$result = mysqli_query($link,$query) or die('Query '.$query.'| failed: ' . mysqli_error());
	}

	while($donnee=mysqli_fetch_array($result, MYSQL_BOTH))
	{
		$conge[ 'id' ] = $donnee[ 'ID_CONGE' ];
		$conge[ 'login' ] = $donnee[ 'UTILISATEUR' ];
		$conge[ 'groupe' ] = $donnee[ 'GROUPE' ];
		$conge[ 'date_debut' ] = $donnee[ 'DEBUT_DATE' ];
		$conge[ 'date_fin' ] = $donnee[ 'FIN_DATE' ];
		array_push($tab_conges,$conge);
	}


	mysqli_free_result($result);
	mysqli_close($link);
	return $tab_conges;
}



/**********************************************************************************
*************** -B- Initialisation generale (configuration et php) ****************
**********************************************************************************/

/**
**/
include 'config.php';

// Fix magic_quotes_gpc garbage
if (get_magic_quotes_gpc())
{ 
   function stripslashes_deep($value)
   { return (is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value));}
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
**************************  -C- Initialisation Session et variables **************
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
	$_SESSION[ 'connection' ][ 'admin' ] = 0;

	$self=$_SERVER[ 'PHP_SELF' ];
	$chemin_module=substr($self,0,-strlen(strrchr($self,"/")));
	$chemin_module.='/reception.php';
	header('location:'.$chemin_module);
}

/*********************************************************************************
********************  -D- Gestion des variables Recherche ************************
**********************************************************************************/
if (! isset($_SESSION[ 'r_gui' ])) $_SESSION[ 'r_gui' ] = 0;
if (isset($_REQUEST[ 'r_gui' ])) $_SESSION[ 'r_gui' ] = $_REQUEST[ 'r_gui' ];

//contient l'id de la demande de conges
if (! isset($_SESSION[ 'id_dec' ])) 
{
	$_SESSION[ 'id_dec' ] = 0;
	$_SESSION[ 'conge' ][ 'utilisateur' ] = $_SESSION[ 'connection' ][ 'utilisateur' ];
	$_SESSION[ 'conge' ][ 'nom' ] = $_SESSION[ 'connection' ][ 'nom' ];
	$_SESSION[ 'conge' ][ 'prenom' ] = $_SESSION[ 'connection' ][ 'prenom' ];
	$_SESSION[ 'conge' ][ 'groupe' ] = $_SESSION[ 'connection' ][ 'groupe' ];
	init_conge();
	init_solde($chemin_connection);
}
if (isset($_REQUEST[ 'id_dec' ])) $_SESSION[ 'id_dec' ] = $_REQUEST[ 'id_dec' ];

// Initialisation et variables recherche evoluee
if (! isset($_SESSION[ 'rech_conge' ]))
{
	init_rech_conge($annee_en_cours);
}

if (isset($_REQUEST[ 'rech_conge' ][ 'nom' ])) $_SESSION[ 'rech_conge' ][ 'nom' ] = $_REQUEST[ 'rech_conge' ][ 'nom' ];
if (isset($_REQUEST[ 'rech_conge' ][ 'prenom' ])) $_SESSION[ 'rech_conge' ][ 'prenom' ] = $_REQUEST[ 'rech_conge' ][ 'prenom' ];
if (isset($_REQUEST[ 'rech_conge' ][ 'annee' ])) $_SESSION[ 'rech_conge' ][ 'annee' ] = $_REQUEST[ 'rech_conge' ][ 'annee' ];
if (isset($_REQUEST[ 'rech_conge' ][ 'groupe_nom_prenom' ])) 
{
$_SESSION[ 'rech_conge' ][ 'groupe_nom_prenom' ] = $_REQUEST[ 'rech_conge' ][ 'groupe_nom_prenom' ];
//TODO?: empecher l'affichage de la precedente recherche
//TODO?: effectuer la recherche evolue quand on utilise ce select
}
if (isset($_REQUEST[ 'rech_conge' ][ 'groupe' ])) $_SESSION[ 'rech_conge' ][ 'groupe' ] = $_REQUEST[ 'rech_conge' ][ 'groupe' ];

if (isset($_REQUEST[ 'uneannee' ])) $_SESSION[ 'uneannee' ] = $_REQUEST[ 'uneannee' ];

/*********************************************************************************
********************  -E- Gestion des variables de conge ***********************
**********************************************************************************/
if (!isset($_SESSION[ 'nbjo' ])) $_SESSION[ 'nbjo' ]=0.;

if (isset($_REQUEST[ 'conge' ]))  if (is_array($_REQUEST[ 'conge' ]))
{
if (isset($_REQUEST[ 'conge' ][ 'id_demande' ])) $_SESSION[ 'conge' ][ 'id_demande' ] = $_REQUEST[ 'conge' ][ 'id_demande' ];
if (isset($_REQUEST[ 'conge' ][ 'type' ])) $_SESSION[ 'conge' ][ 'type' ] = $_REQUEST[ 'conge' ][ 'type' ];
if (isset($_REQUEST[ 'conge' ][ 'sans_solde' ])) $_SESSION[ 'conge' ][ 'sans_solde' ] = $_REQUEST[ 'conge' ][ 'sans_solde' ];
if (isset($_REQUEST[ 'conge' ][ 'date_debut' ])) $_SESSION[ 'conge' ][ 'date_debut' ] = $_REQUEST[ 'conge' ][ 'date_debut' ];
if (isset($_REQUEST[ 'conge' ][ 'date_AM' ])) $_SESSION[ 'conge' ][ 'date_AM' ] = $_REQUEST[ 'conge' ][ 'date_AM' ];
if (isset($_REQUEST[ 'conge' ][ 'date_fin' ])) $_SESSION[ 'conge' ][ 'date_fin' ] = $_REQUEST[ 'conge' ][ 'date_fin' ];
if (isset($_REQUEST[ 'conge' ][ 'date_PM' ])) $_SESSION[ 'conge' ][ 'date_PM' ] = $_REQUEST[ 'conge' ][ 'date_PM' ];
if (isset($_REQUEST[ 'conge' ][ 'commentaire' ])) $_SESSION[ 'conge' ][ 'commentaire' ] = $_REQUEST[ 'conge' ][ 'commentaire' ];

if (isset($_REQUEST[ 'mailGroupe' ])) $_SESSION[ 'mailGroupe' ] = $_REQUEST[ 'mailGroupe' ];
}

/*********************************************************************************
*****************  -F- Bouton: Calcul du nombre de jours ouvres ******************
/*********************************************************************************/

if (isset($_REQUEST[ 'conge' ]['calcul_nbjo']))
{
//decoupage des dates:
list($jour1, $mois1, $annee1) = explode('/', $_SESSION['conge']['date_debut']); 
list($jour2, $mois2, $annee2) = explode('/', $_SESSION['conge']['date_fin']);
$timestamp1 = mktime(0,0,0,$mois1,$jour1,$annee1);
$timestamp2 = mktime(12,0,0,$mois2,$jour2,$annee2);//12 heure pour compter le dernier jour
$timestamp_solde=0;
if ((!checkdate($mois1,$jour1,$annee1)) || (!checkdate($mois2,$jour2,$annee2)) || ($timestamp2<$timestamp1))
{
	$_SESSION['nbjo'] = 0;
}
else {
	$nb_jours_ouvres = (float)get_nb_open_days($timestamp1, $timestamp2, $_SESSION['conge']['date_AM'],$_SESSION['conge']['date_PM']);
	//if ($_SESSION['conge']['date_AM']==1) $nb_jours_ouvres-=0.5;
	//if ($_SESSION['conge']['date_PM']==0) $nb_jours_ouvres-=0.5;
	$_SESSION['nbjo'] = $nb_jours_ouvres;
}
}

/********************************************************************************
******************    -G- Fonctionnalites (MySQL)     **************************
********************************************************************************/

////////////////////////// G1 - Recherche simple des conges /////////////////////
if ($_SESSION[ 'connection' ][ 'status' ] !=0)
if ( isset($_REQUEST[ 'rechercher' ]) || isset($_GET["dec"]))
{
if (isset($_GET["dec"])) {$_SESSION["id_dec"]=$_GET["dec"];}
$annule=0;

/**
**/
include $chemin_connection;

$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

$message_rechercher= '';

//cherche dans la base si cette demande existe:
  if (!$annule)
  {
     $a_rajouter='';
     $ind=$_SESSION["id_dec"];
     $query = 'SELECT * FROM T_CONGE WHERE ID_CONGE='.$ind.' '.$a_rajouter;
     $result = mysqli_query($link,$query) or die('Requete de selection d\'une conge: ' . mysqli_error());
     if ($result)
     {
	$line = mysqli_fetch_array($result, MYSQL_ASSOC);
	if ($line)
	{
	$accordeledroit=0;

     //l'admin ($accordeledroit=1) peut afficher tous les conges: 
        $query2 = 'SELECT ADMIN FROM T_UTILISATEUR WHERE UTILISATEUR=\''.$_SESSION[ 'connection' ][ 'utilisateur' ].'\'';
	$result2 = mysqli_query($link,$query2);
	$line2 = mysqli_fetch_array($result2, MYSQL_NUM);
	if ($line2[0]==1) $accordeledroit=1;
	mysqli_free_result($result2);

     //ajout pour eviter que les statuts 1 et 2 accédent aux informations des autres usagés
     //ajout pour eviter que les statuts 3 et 4 accèdent aux informations des autres groupes
	if ( ( (($_SESSION[ 'connection' ][ 'status' ] <= 2) || ($_SESSION[ 'connection' ][ 'status']==5)) && ($_SESSION[ 'connection' ][ 'utilisateur' ]== $line["UTILISATEUR"]))
	|| ( (($_SESSION[ 'connection' ][ 'status' ] > 2 && $_SESSION[ 'connection' ][ 'status' ] < 5)
|| $_SESSION[ 'connection' ][ 'status' ]==6) &&
	(($_SESSION[ 'connection' ][ 'groupe' ]== $line["GROUPE"]) || ($_SESSION[ 'connection' ][ 'utilisateur' ]== $line["UTILISATEUR"])))
	|| ($accordeledroit==1) 
	|| ($_SESSION[ 'connection' ][ 'groupe' ]=='DIRECTION') )
	{
     //si on trouve une demande, on peut empecher l'edition:
	$_SESSION[ 'edition' ] =0;
     //fermer le volet si une demande a ete touvee:
	$_SESSION[ 'r_gui' ]=0;
     //raffraichissement de l'affichage avec la demande trouvee
	$_SESSION['conge']['id_conge']=$line["ID_CONGE"];
	$_SESSION['conge']['utilisateur']=$line["UTILISATEUR"];
     //Rechercher le NOM,PRENOM a partir de UTILISATEUR dans T_UTILISATEUR
	$query2 = 'SELECT NOM,PRENOM FROM T_UTILISATEUR WHERE UTILISATEUR=\''.$_SESSION['conge']['utilisateur'].'\'';
	$result2 = mysqli_query($link,$query2);
	$line2 = mysqli_fetch_array($result2, MYSQL_NUM);
	$_SESSION['conge']['nom']=ucwords(strtolower($line2[0]));
	$_SESSION['conge']['prenom']=ucwords(strtolower($line2[1]));
	mysqli_free_result($result2);
     //Autres infos
	$_SESSION['conge']['groupe']=$line["GROUPE"];
	$_SESSION['conge']['type']=$line["TYPE"];
	$_SESSION['conge']['sans_solde']=$line["GENRE"];
     //Debut
	$query2 = 'SELECT DATE_FORMAT(\''.$line["DEBUT_DATE"].'\',\'%d/%m/%Y\');';
	$result2 = mysqli_query($link,$query2);
	$line2 = mysqli_fetch_array($result2, MYSQL_NUM);
	$_SESSION['conge']['date_debut']=$line2[0];
	mysqli_free_result($result2);
	$_SESSION['conge']['date_AM']=$line["DEBUT_AM"];
     //Fin
	$query2 = 'SELECT DATE_FORMAT(\''.$line["FIN_DATE"].'\',\'%d/%m/%Y\');';
	$result2 = mysqli_query($link,$query2);
	$line2 = mysqli_fetch_array($result2, MYSQL_NUM);
	$_SESSION['conge']['date_fin']=$line2[0];
	mysqli_free_result($result2);
	$_SESSION['conge']['date_PM']=$line["FIN_PM"];
     //Autres infos

	$_SESSION['conge']['nb_jours_ouvres']=$line["NB_JOURS_OUVRES"];
	//if (!isset($_SESSION['conge']['nb_jours_ouvres_recalc']))
	$_SESSION['conge']['nb_jours_ouvres_recalc']=$_SESSION['conge']['nb_jours_ouvres'];
	$_SESSION['conge']['commentaire']=$line["COMMENTAIRE"];
	$_SESSION['conge']['valide']=$line["VALIDE"];

	$_SESSION[ 'mailGroupe' ] = $line["INFORMER_GP"];
	}//fin du if chef ...
	else      {
	$message_rechercher .= '<span class="rouge">L\'indentifiant du cong&eacute; est inaccessible.</span>';
	$annule=1;
        }

	}//fin de if ($line)
	else      {
	$message_rechercher .= '<span class="rouge">L\'indentifiant du cong&eacute; est introuvable.</span>';
	$annule=1;
        }
     mysqli_free_result($result);

	$query2 = 'SELECT SOLDE_CA,SOLDE_CA_1,SOLDE_CET,SOLDE_RECUP,QUOTA_JOURS FROM T_CONGE_SOLDE WHERE UTILISATEUR=\''.$_SESSION[ 'conge' ][ 'utilisateur' ].'\' ';
	$result2 = mysqli_query($link,$query2) or die('Query '.$query2.'| failed: ' . mysqli_error());
	if ($result2)
	{
		$line2 = mysqli_fetch_array($result2, MYSQL_NUM);
		$_SESSION[ 'conge' ][ 'CA' ] = $line2[0];
		$_SESSION[ 'conge' ][ 'CA-1' ] = $line2[1];
		$_SESSION[ 'conge' ][ 'CET' ] = $line2[2];
		$_SESSION[ 'conge' ][ 'RECUP' ] = $line2[3];
		$_SESSION[ 'conge' ][ 'QUOTA' ] = $line2[4];
	
		mysqli_free_result($result2);
	}
   }//fin de if result
  }//fin de if !annulle
}

//MISE A JOUR DE $_SESSION['conge']['nb_jours_ouvres_recal']
if (isset($_REQUEST['conge']['nb_jours_ouvres_recalc'])) $_SESSION['conge']['nb_jours_ouvres_recalc']=$_REQUEST['conge']['nb_jours_ouvres_recalc'];

////////////////////////// G2 - Recherche evoluee ////////////////////////////
function rech_evol($chem_conn)
{
$message_evolue = '';

include($chem_conn);

/**
**/
include 'config.php';//pour avoir les annees!!!!

$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

//cherche dans la base si cette demande existe:
  if (!isset($annule) OR !$annule)
  {
	$ind=$_SESSION["id_dec"];

	$where='';
	$where_nom='';
	$where_prenom='';
	$where_groupe='';
	$where_annee='';

	if ($_SESSION['rech_conge']['nom']!='') $where_nom='INSTR(T_UTILISATEUR.NOM,UPPER(\''.$_SESSION['rech_conge']['nom'].'\'))>0';
	if ($_SESSION['rech_conge']['prenom']!='') $where_prenom='INSTR(T_UTILISATEUR.PRENOM,UPPER(\''.$_SESSION['rech_conge']['prenom'].'\'))>0';

	if ($_SESSION[ 'rech_conge' ][ 'groupe' ] != $_SESSION[ 'correspondance' ]['groupe'][0]) 
	$where_groupe='T_CONGE.GROUPE=\''.$_SESSION[ 'rech_conge' ][ 'groupe' ].'\'';

	if ($_SESSION['rech_conge']['annee']!=$annees[0]) 
	$where_annee='YEAR(DEBUT_DATE)=\''.$_SESSION['rech_conge']['annee'].'\'';
	$nb_clause=0;
	if ($where_nom!='' || $where_prenom!='' || $where_groupe!='' ||  $where_annee!='') $where='WHERE ';
	if ($where_groupe!='') {$where.=$where_groupe;$nb_clause++;}
	if ($where_nom!='') {if ($nb_clause==1) {$where.=' AND ';} $where.=$where_nom; $nb_clause++;}
	if ($where_prenom!='') {if ($nb_clause>=1) {$where.=' AND ';} $where.=$where_prenom; $nb_clause++;}
	if ($where_annee!='') {if ($nb_clause>=1) {$where.=' AND ';} $where.=$where_annee; $nb_clause++;}

	////////////////calcul du nombre de résultat pour la recherche
	$query = 'SELECT COUNT(*) FROM T_CONGE INNER JOIN T_UTILISATEUR ON T_CONGE.UTILISATEUR=T_UTILISATEUR.UTILISATEUR '.$where;
	$result = mysqli_query($link,$query);
	$line = mysqli_fetch_array($result, MYSQL_NUM);
	//comparaison avec le nombre actuel au cas ou modification des parametres
	// de recherche et appui sur recherche suivant ou precedente
	if ($_SESSION['rech_conge']['resultats']!=$line[0]) $_SESSION['rech_conge']['limitebasse']=0;
	$_SESSION['rech_conge']['resultats']=$line[0];
	mysqli_free_result($result);

	//orderby
	$orderby='ORDER BY FIN_DATE DESC';
	$query = 'SELECT ID_CONGE,NOM,PRENOM,T_CONGE.GROUPE,DEBUT_DATE,FIN_DATE,NB_JOURS_OUVRES,TYPE,VALIDE FROM T_CONGE INNER JOIN T_UTILISATEUR ON T_CONGE.UTILISATEUR=T_UTILISATEUR.UTILISATEUR '.$where.' '.$orderby.' LIMIT '.$_SESSION['rech_conge']['limitebasse'].','.$_SESSION['rech_conge']['nb_par_page'].'';

 	$result = mysqli_query($link,$query) or die('Requete de recherche evolue des conges: ' . mysqli_error());
	if ($result)
	{
	$i=1;
	while ($line = mysqli_fetch_array($result, MYSQL_ASSOC))
	{
		//raffraichissement de l'affichage avec la demande trouvee
		$_SESSION['rech_conge'][$i]['id_conge']=$line["ID_CONGE"];
		$_SESSION['rech_conge'][$i]['nom']=ucwords(strtolower($line["NOM"]));
		$_SESSION['rech_conge'][$i]['prenom']=ucwords(strtolower($line["PRENOM"]));
		$_SESSION['rech_conge'][$i]['groupe']=$line["GROUPE"];
		$query2 = 'SELECT DATE_FORMAT(\''.$line["DEBUT_DATE"].'\',\'%d/%m/%Y\');';
		$result2 = mysqli_query($link,$query2);
		$line2 = mysqli_fetch_array($result2, MYSQL_NUM);
		$_SESSION['rech_conge'][$i]['date_debut']=$line2[0];
		mysqli_free_result($result2);
		$query2 = 'SELECT DATE_FORMAT(\''.$line["FIN_DATE"].'\',\'%d/%m/%Y\');';
		$result2 = mysqli_query($link,$query2);
		$line2 = mysqli_fetch_array($result2, MYSQL_NUM);
		$_SESSION['rech_conge'][$i]['date_fin']=$line2[0];
		mysqli_free_result($result2);
		$_SESSION['rech_conge'][$i]['nb_jours_ouvres']=$line["NB_JOURS_OUVRES"];
		$_SESSION['rech_conge'][$i]['type']=$line["TYPE"];
		$_SESSION['rech_conge'][$i]['valide']=$line["VALIDE"];
		
		$i++;
	}//fin de while ($line)

	mysqli_free_result($result);
	
	if ($_SESSION['rech_conge']['resultats']==0)
	{
		$message_evolue = '<span class="rouge">Pas de conge correspondant &agrave; la recherche.</span>';
		$annule=1;
    	}
   }//fin de if result
  }//fin de if !annule

return $message_evolue;
}

//Initialisation du calendrier lors du premier chargement
if (!isset($_SESSION['firsttime_conges'])) 
{
	$_SESSION['firsttime_conges']=1;
	if ($_SESSION[ 'connection' ][ 'groupe' ]!='DIRECTION' && $_SESSION[ 'connection' ][ 'admin' ]!=1)
	{
		$_SESSION[ 'rech_conge' ][ 'nom' ]=$_SESSION[ 'connection' ][ 'nom' ];
		$_SESSION[ 'rech_conge' ][ 'prenom' ]=$_SESSION[ 'connection' ][ 'prenom' ];
	}
}
if ( isset($_REQUEST[ 'rechercher_evol' ]) || $_SESSION['firsttime_conges']==1)
{
	$_SESSION['rech_conge']['limitebasse']=0;
	$message_evolue= '';
	$message_evolue=rech_evol($chemin_connection);
	$_SESSION['firsttime_conges']=0;
}

if ( isset($_REQUEST[ 'rechercher_tlm' ]))
{
	$_SESSION['rech_conge']['nom']='';
	$_SESSION['rech_conge']['prenom']='';
	$message_evolue= '';
	$message_evolue=rech_evol($chemin_connection);
}

///////////////// G3 - Reinitialiser page suivante et precedente la Recherche evoluee //////////
if ( isset($_REQUEST[ 'reinitialiser' ]) )
{
	init_rech_conge($annee_en_cours);
}

if ( isset($_REQUEST[ 'rechercher_suiv' ]) )
{
	if ($_SESSION['rech_conge']['limitebasse'] +$_SESSION['rech_conge']['nb_par_page'] <$_SESSION['rech_conge']['resultats'])
	$_SESSION['rech_conge']['limitebasse']+=$_SESSION['rech_conge']['nb_par_page'];
	rech_evol($chemin_connection);
}

if ( isset($_REQUEST[ 'rechercher_prec' ]) )
{
	if ($_SESSION['rech_conge']['limitebasse']-$_SESSION['rech_conge']['nb_par_page']>=0)
	$_SESSION['rech_conge']['limitebasse']-=$_SESSION['rech_conge']['nb_par_page'];
	rech_evol($chemin_connection);
}

/////////////////////// G4 - Ajout d'une demande de conge ///////////////////////
if (isset($_REQUEST[ 'conge' ]['envoyer']))
{
/**
**/
   include $chemin_connection;

   $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
   mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

   $annule=0;
   $nb_jours_ouvres =0;
//decoupage des dates:
   list($jour1, $mois1, $annee1) = explode('/', $_SESSION['conge']['date_debut']); 
   list($jour2, $mois2, $annee2) = explode('/', $_SESSION['conge']['date_fin']);
   $timestamp1 = mktime(0,0,0,$mois1,$jour1,$annee1); 
   $timestamp2 = mktime(12,0,0,$mois2,$jour2,$annee2);//12 heure pour compter le dernier jour
   $timestamp3 = mktime(0,0,0,$mois2,$jour2,$annee2);//pour le calcul des chevauchements ET 
   //test AM PM le meme jour
   $mySolde=0;

//DEBUT calcul des chevauchements//on pourrait recuperer l'ID_CONGE pour info?
$query = 'SELECT UNIX_TIMESTAMP(DEBUT_DATE),UNIX_TIMESTAMP(FIN_DATE),DEBUT_AM,FIN_PM FROM T_CONGE WHERE 
((UNIX_TIMESTAMP(DEBUT_DATE) <= '.$timestamp1.' AND UNIX_TIMESTAMP(FIN_DATE) >= '.$timestamp1.') OR (UNIX_TIMESTAMP(DEBUT_DATE) <= '.$timestamp3.' AND UNIX_TIMESTAMP(FIN_DATE) >= '.$timestamp3.')
OR (UNIX_TIMESTAMP(DEBUT_DATE) > '.$timestamp1.' AND UNIX_TIMESTAMP(FIN_DATE) < '.$timestamp3.'))
 AND (UTILISATEUR="'.$_SESSION['conge']['utilisateur'].'") AND (VALIDE!=-1)'; 
$result = mysqli_query($link,$query) or die($query.'=====' . mysqli_error());
if ($result)
{
	while ($line = mysqli_fetch_array($result, MYSQL_NUM))
	{
	//traitement du cas particulier dernier jour de conge demande
	//a cheval avec un debut de conge deja pris
	if ($line[0]==$timestamp3)
	{
		if ($line[2]==0 || $_SESSION['conge']['date_PM']==1)
		{$annule=1;}//echo 'RAIS1 '.$line[4];echo ' <br>'.$query;}
	}
	//traitement du cas particulier premier jour de conge demande
	//a cheval avec une fin de conge deja pris
	else if ($line[1]==$timestamp1)
	{
		if ($line[3]==1 || $_SESSION['conge']['date_AM']==0) 
		{$annule=1;}//echo 'RAIS2 '.$line[4];echo ' <br>'.$query;}
	}
	else {$annule=1;}//echo 'RAIS3 '.$line[4];echo ' <br>'.$query;}

	if ($annule==1)
	{
		$message_demande= '<span class="rouge">La p&eacute;riode demand&eacute;e chevauche un cong&eacute; existant.<br>Action annul&eacute;e.</span>';
		break;
	}
	}//end of while
	mysqli_free_result($result);
}//else echo $query;
//FIN calcul des chevauchements

//lire les champs, des qu'un champ obligatoire est manquant ou incorrect, on annule la procedure.
   if(!checkdate($mois1,$jour1,$annee1))
   {
	$message_demande= '<span class="rouge">Le champs "Date debut" est invalide.<br>Action annul&eacute;e.</span>';
	$annule=1;
   }
   elseif(!checkdate($mois2,$jour2,$annee2))
   {
	$message_demande= '<span class="rouge">Le champs "Date fin" est invalide.<br>Action annul&eacute;e.</span>';
	$annule=1;
   }
//on verifie que le retour s'effectue apres l'arrivee!!! (coquins/betatesteur)
   elseif($timestamp2<$timestamp1)
   {
	$message_demande= '<span class="rouge">La date de fin pr&eacute;c&egrave;de la date de d&eacute;but...<br>Action annul&eacute;e.</span>';
	$annule=1;
   }
   elseif ($timestamp3==$timestamp1 && $_SESSION['conge']['date_AM']==1 && $_SESSION['conge']['date_PM']==0)
   {
	$message_demande= '<span class="rouge">Incoh&eacute;rance des choix "Matin et Apr&egrave;s midi"...<br>Action annul&eacute;e.</span>';
	$annule=1;
   }
   else 
   {//Dates OK
   $nb_jours_ouvres = (float)get_nb_open_days($timestamp1, $timestamp2, $_SESSION['conge']['date_AM'], $_SESSION['conge']['date_PM']);
   //if ($_SESSION['conge']['date_AM']==1) $nb_jours_ouvres-=0.5;
   //if ($_SESSION['conge']['date_PM']==0) $nb_jours_ouvres-=0.5;
   if ($nb_jours_ouvres<=0)// peut-etre inutile:
   {
	$annule=1;
	$message_demande= '<span class="rouge">Le nombre de jours ouvr&eacute;s est nul.</span><br>';
   }

   if ($_SESSION['conge']['type']==0) //choix conge annuel
   {
	if ($_SESSION[ 'solde' ][ 'CA' ]+$_SESSION[ 'solde' ][ 'CA-1' ]<0)
	{
		$annule=1;
		$message_demande= '<span class="rouge">Votre solde est actuellement n&eacute;gatif.</span><br>';
	}
	$mySolde=$_SESSION[ 'solde' ][ 'CA' ]+$_SESSION[ 'solde' ][ 'CA-1' ];
	if ($mySolde<$nb_jours_ouvres)
	{
		//$annule=1;
		//$message_demande= 'Votre solde "'.$conge_type[$_SESSION['conge']['type']].'" sera n&eacute;gatif.<br>';
	}
   }
   elseif ($_SESSION['conge']['type']==1) //choix conge cet
   {
	$mySolde=$_SESSION[ 'solde' ][ 'CET' ];
	if ($mySolde<$nb_jours_ouvres)
	{
		$annule=1;
		$message_demande= '<span class="rouge">Votre solde "'.$conge_type[$_SESSION['conge']['type']].'" n\'est pas suffisant.</span>';
	}
   }
   elseif ($_SESSION['conge']['type']==2) //choix conge cet
   {
	$mySolde=$_SESSION[ 'solde' ][ 'RECUP' ];
	if ($mySolde<$nb_jours_ouvres)
	{
		$annule=1;
		$message_demande= '<span class="rouge">Votre solde "'.$conge_type[$_SESSION['conge']['type']].'" n\'est pas suffisant.</span>';
	}
   }
   }//fin du else Dates OK

   if (!$annule)
   {
   //recherche de l'indice a ajouter
   //la numerotation des (ID_) commence a 1
	$indi=1;
	$query = 'SELECT MAX(ID_CONGE) FROM T_CONGE';
	$result = mysqli_query($link,$query) or die('Requete de comptage des demandes: ' . mysqli_error());
	if ($result)
	{
		$line = mysqli_fetch_array($result, MYSQL_NUM);
		$indi=$line[0]+1;
		mysqli_free_result($result);
	}
	$_SESSION['conge']['id_conge']=$indi;

   //insertion de la demande de conge:
	$query = 'INSERT INTO T_CONGE(ID_CONGE,UTILISATEUR,GROUPE,TYPE,DEBUT_DATE,DEBUT_AM,FIN_DATE,FIN_PM,NB_JOURS_OUVRES,GENRE,COMMENTAIRE,INFORMER_GP,VALIDE) VALUES ('.$indi.',"'.$_SESSION['conge']['utilisateur'].'","'.$_SESSION['conge']['groupe'].'",'.$_SESSION['conge']['type'].',STR_TO_DATE(\''.$_SESSION['conge']['date_debut'].'\',\'%d/%m/%Y\'),'.$_SESSION['conge']['date_AM'].',STR_TO_DATE(\''.$_SESSION['conge']['date_fin'].'\',\'%d/%m/%Y\'),'.$_SESSION['conge']['date_PM'].','.$nb_jours_ouvres.','.$_SESSION['conge']['sans_solde'].',"'.$_SESSION['conge']['commentaire'].'",'.$_SESSION['mailGroupe'].',0  )';
	$result = mysqli_query($link,$query) or die('Requete d\'insertion echouee: ' . mysqli_error());
	//mysql_free_result($result);

	$message_demande= "<span class='vert'>-> Demande ajoutee <-</span>";	
  }

  if (!$annule)
  {	
	$indi=$_SESSION['conge']['id_conge'];//attention $ind est une vairiable globale
	$subject = "CONGE: Nouvelle demande [".$_SESSION['conge']['groupe']."] (".$_SESSION['conge']['nom']." du ".$_SESSION['conge']['date_debut']." au ".$_SESSION['conge']['date_fin'].")";

   //demandeur de conge (quelquesoit son status)
	if ($mode_test) $TO = $mel_test;
	else $TO = $_SESSION['conge']['utilisateur'].'@'.$domaine;
	$message = "<body>Bonjour ".$_SESSION['conge']['nom']." ".$_SESSION['conge']['prenom'].",<br> votre demande de conge a été effectuée,<br> ";
	$message .= "Suivez le lien <a href=".$chemin_mel."?dec=".$indi.">".$chemin_mel."?dec=".$indi."</a> pour l'afficher.<br><br>";
//	$tab=explode("-",$_SESSION['conge']['date_debut']);
//	$date_debut=$tab[2].'/'.$tab[1].'/'.$tab[0];
	  
	$message .= '================================================================<br>';
//	$message .= 'Congé du '.$date_debut.' au '.$_SESSION['conge']['date_fin'].'<br>';
	$message .= 'Congé du '.$_SESSION['conge']['date_debut'].' au '.$_SESSION['conge']['date_fin'].'<br>';
	$message .= '================================================================<br><br>';
	$message .= 'Nom : '.$_SESSION['conge']['nom'].'<br>';
	$message .= 'Prénom : '.$_SESSION['conge']['prenom'].'<br>';
	$message .= 'Equipe/Service : '.$_SESSION['conge']['groupe'].'<br>';
	$message .= 'Début le '.$_SESSION['conge']['date_debut'].'<br>';
	$message .= 'Fin le '.$_SESSION['conge']['date_fin'].'<br>';
	
	//Nombres de jours ouvres
	$nb_jours_ouvres = (float)get_nb_open_days($timestamp1, $timestamp2, $_SESSION['conge']['date_AM'],$_SESSION['conge']['date_PM']);
	$message .= 'Nombre de jours ouvrés : '.$nb_jours_ouvres.' j.<br>';

	if(trim($_SESSION['conge']['commentaire']) != '') $message .= '<br><br>Commentaire :<br>'.utf8_encode($_SESSION['conge']['commentaire']).'<br>';	   
	$message .= '<br>================================================================<br></body>';    
	  
	  
	  
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['conge']['utilisateur'].'@'.$domaine, $_SESSION['conge']['nom']." ".$_SESSION['conge']['prenom']);

   //au responsable d'equipe ou de service du groupe selectionné pour le conge
   //Note: leresponsable administratif est traite en meme temps que responsable de service
	if ($directeur!=$_SESSION[ 'connection' ][ 'utilisateur' ])
	{
	$pourqui='responsable';
	$util=$_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']];
	if ($_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']]==$_SESSION[ 'connection' ][ 'utilisateur' ]) {$util=$directeur;$pourqui='directeur';}
	if ($mode_test) $TO = $mel_test;
	else $TO = $util.'@'.$domaine;
	$message = "<body>Bonjour ".$pourqui." ".$util.",<br> ";
	$message .= "suivez le lien <a href=".$chemin_mel."?dec=".$indi.">".$chemin_mel."?dec=".$indi."</a> pour afficher le nouveau conge émis par ".$_SESSION['conge']['prenom']." ".$_SESSION['conge']['nom']." pour ".$_SESSION['conge']['groupe'].".<br><br>";


	$message .= '================================================================<br>';
	$message .= 'Congé du '.$_SESSION['conge']['date_debut'].' au '.$_SESSION['conge']['date_fin'].'<br>';
	$message .= '================================================================<br><br>';
	$message .= 'Nom : '.$_SESSION['conge']['nom'].'<br>';
	$message .= 'Prénom : '.$_SESSION['conge']['prenom'].'<br>';
	$message .= 'Equipe/Service : '.$_SESSION['conge']['groupe'].'<br>';
	$message .= 'Début le '.$_SESSION['conge']['date_debut'].'<br>';
	$message .= 'Fin le '.$_SESSION['conge']['date_fin'].'<br>';
	
	//Nombres de jours ouvres
	$nb_jours_ouvres = (float)get_nb_open_days($timestamp1, $timestamp2, $_SESSION['conge']['date_AM'],$_SESSION['conge']['date_PM']);
	$message .= 'Nombre de jours ouvrés : '.$nb_jours_ouvres.' j.<br>';

	if(trim($_SESSION['conge']['commentaire']) != '') $message .= '<br><br>Commentaire :<br>'.utf8_encode($_SESSION['conge']['commentaire']).'<br>';	   
	$message .= '<br>================================================================<br></body>';    
 	
		
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['conge']['utilisateur'].'@'.$domaine, $_SESSION['conge']['nom']." ".$_SESSION['conge']['prenom']);
	}

   //envoie aux admins
	$query2 = 'SELECT UTILISATEUR FROM T_UTILISATEUR WHERE ADMIN=1';
	$result2 = mysqli_query($link,$query2);
	while ($line2 = mysqli_fetch_array($result2, MYSQL_NUM))
 	{
	if ($mode_test) $TO = $mel_test;
	else $TO = $line2[0].'@'.$domaine;
	$message = "<body>Bonjour administrateur ".$line2[0].",<br> ";
	$message .= "suivez le lien <a href=".$chemin_mel."?dec=".$indi.">".$chemin_mel."?dec=".$indi."</a> pour afficher le nouveau conge émis par ".$_SESSION['conge']['prenom']." ".$_SESSION['conge']['nom']." pour ".$_SESSION['conge']['groupe'].".<br><br>";


	$message .= '================================================================<br>';
	$message .= 'Congé du '.$_SESSION['conge']['date_debut'].' au '.$_SESSION['conge']['date_fin'].'<br>';
	$message .= '================================================================<br><br>';
	$message .= 'Nom : '.$_SESSION['conge']['nom'].'<br>';
	$message .= 'Prénom : '.$_SESSION['conge']['prenom'].'<br>';
	$message .= 'Equipe/Service : '.$_SESSION['conge']['groupe'].'<br>';
	$message .= 'Début le '.$_SESSION['conge']['date_debut'].'<br>';
	$message .= 'Fin le '.$_SESSION['conge']['date_fin'].'<br>';
	
	//Nombres de jours ouvres
	$nb_jours_ouvres = (float)get_nb_open_days($timestamp1, $timestamp2, $_SESSION['conge']['date_AM'],$_SESSION['conge']['date_PM']);
	$message .= 'Nombre de jours ouvrés : '.$nb_jours_ouvres.' j.<br>';

	if(trim($_SESSION['conge']['commentaire']) != '') $message .= '<br><br>Commentaire :<br>'.utf8_encode($_SESSION['conge']['commentaire']).'<br>';	   
	$message .= '<br>================================================================<br></body>';    
	
		
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['conge']['utilisateur'].'@'.$domaine, $_SESSION['conge']['nom']." ".$_SESSION['conge']['prenom']);
	}
	mysqli_free_result($result2);

	$message_demande.= "<br><span class='vert'>-> Envoi de mails <-</span>";
  }
  mysqli_close($link);

  if (!$annule)
  {
	//on re-initialise les champs pour ne pas effectuer plusieurs fois le meme conge
	init_conge();
  }
}

/////////////////////// G5 - Nouvelle demande conge///////////////////////
if (isset($_REQUEST[ 'conge' ]['nouvelle']))
{
	$_SESSION['edition']=1;
	$_SESSION[ 'r_gui' ]=0;//fermeture de la zone de recherche (21 avril 2009)
	$_SESSION[ 'conge' ][ 'groupe' ] = $_SESSION[ 'connection' ][ 'groupe' ];
	$_SESSION[ 'conge' ][ 'utilisateur' ] = $_SESSION[ 'connection' ][ 'utilisateur' ];
	$_SESSION[ 'conge' ][ 'nom' ] = $_SESSION[ 'connection' ][ 'nom' ];
	$_SESSION[ 'conge' ][ 'prenom' ] = $_SESSION[ 'connection' ][ 'prenom' ];
	$_SESSION[ 'conge' ][ 'ss' ] = $_SESSION[ 'connection' ][ 'ss' ];

	init_conge();
}

/////////////////////// G6 - Valider demande conge///////////////////////
if (isset($_REQUEST[ 'conge' ]['valider']))
{

/**
**/
   include $chemin_connection;

   $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
   mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

   $annule=0;
   if (!$annule)
   {
	$ind=$_SESSION["id_dec"];
   //validation de la demande:
	$query = 'UPDATE T_CONGE SET VALIDE="1", NB_JOURS_OUVRES="'.$_SESSION["conge"]["nb_jours_ouvres_recalc"].'" WHERE ID_CONGE="'.$ind.'"';
   	$result = mysqli_query($link,$query) or die('Requete de modification du status: ' . mysqli_error());
   	//mysql_free_result($result);
   	$message_demande= "<span class='vert'>-> Demande valid&eacute;e <-</span>";	
	$_SESSION['conge']['valide']=1;
   }

//mettre a jour le solde des conges
   if (!$annule && $_SESSION["conge"]["type"]<3)
   {
	$sca=$_SESSION[ 'conge' ][ 'CA' ];
	$sca1=$_SESSION[ 'conge' ][ 'CA-1' ];
	$srecup=$_SESSION[ 'conge' ][ 'CET' ];
	$scet=$_SESSION[ 'conge' ][ 'RECUP' ];

	if ($_SESSION["conge"]["type"]==0)
	{
	   if ($sca1>0)
	   {
		if ($sca1-$_SESSION["conge"]["nb_jours_ouvres_recalc"]<0) 
		{
	   //echo 'update ca-1';
		$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CA_1="0" WHERE UTILISATEUR="'.$_SESSION['conge']['utilisateur'].'"';
		$result = mysqli_query($query) or die('Requete de modification du status: ' . mysqli_error());
		//mysql_free_result($result);
		$_SESSION[ 'conge' ][ 'CA-1' ]=0;
		$_SESSION[ 'solde' ][ 'CA-1' ]=0;
	   //echo 'update ca';
		$diff=$_SESSION["conge"]["nb_jours_ouvres_recalc"]-$sca1;
		$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CA=SOLDE_CA-'.$diff.' WHERE UTILISATEUR="'.$_SESSION['conge']['utilisateur'].'"';
		$result = mysqli_query($link,$query) or die('Requete de modification du status: ' . mysqli_error());
		//mysql_free_result($result);
		$_SESSION[ 'conge' ][ 'CA' ]=$_SESSION[ 'conge' ][ 'CA' ]-$diff;
		$_SESSION[ 'solde' ][ 'CA' ]=$_SESSION[ 'conge' ][ 'CA' ];
		}
		else
		{
	   //echo 'update ca-1';
		$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CA_1=SOLDE_CA_1-'.$_SESSION["conge"]["nb_jours_ouvres_recalc"].' WHERE UTILISATEUR="'.$_SESSION['conge']['utilisateur'].'"';
		$result = mysqli_query($link,$query) or die('Requete de modification du status: ' . mysqli_error());
		//mysql_free_result($result);
		$_SESSION[ 'conge' ][ 'CA-1' ]=$_SESSION[ 'conge' ][ 'CA-1' ]- $_SESSION["conge"]["nb_jours_ouvres_recalc"];
		$_SESSION[ 'solde' ][ 'CA-1' ]=$_SESSION[ 'conge' ][ 'CA-1' ];
		}
	   }
	   else
	   {
	   //echo 'update ca';
		$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CA=SOLDE_CA-'.$_SESSION["conge"]["nb_jours_ouvres_recalc"].' WHERE UTILISATEUR="'.$_SESSION['conge']['utilisateur'].'"';
		$result = mysqli_query($link,$query) or die('Requete de modification du status: ' . mysqli_error());
		//mysql_free_result($result);
		$_SESSION[ 'conge' ][ 'CA' ]=$_SESSION[ 'conge' ][ 'CA' ] -$_SESSION["conge"]["nb_jours_ouvres_recalc"];
		$_SESSION[ 'solde' ][ 'CA' ]=$_SESSION[ 'conge' ][ 'CA' ];
	   }
	}
	else if ($_SESSION["conge"]["type"]==1)
	{
	//echo 'update cet';
		$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CET=SOLDE_CET-'.$_SESSION["conge"]["nb_jours_ouvres_recalc"].' WHERE UTILISATEUR="'.$_SESSION['conge']['utilisateur'].'"';
		$result = mysqli_query($link,$query) or die('Requete de modification du status: ' . mysqli_error());
		//mysql_free_result($result);
		$_SESSION[ 'conge' ][ 'CET' ]=$_SESSION[ 'conge' ][ 'CET' ]-$_SESSION["conge"]["nb_jours_ouvres_recalc"];
		$_SESSION[ 'solde' ][ 'CET' ]=$_SESSION[ 'conge' ][ 'CET' ];
	}
	else if ($_SESSION["conge"]["type"]==2)
	{
	//echo 'update recup';
		$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_RECUP=SOLDE_RECUP-'.$_SESSION["conge"]["nb_jours_ouvres_recalc"].' WHERE UTILISATEUR="'.$_SESSION['conge']['utilisateur'].'"';
		$result = mysqli_query($link,$query) or die('Requete de modification du status: ' . mysqli_error());
		//mysql_free_result($result);
		$_SESSION[ 'conge' ][ 'RECUP' ]=$_SESSION[ 'conge' ][ 'RECUP' ]-$_SESSION["conge"]["nb_jours_ouvres_recalc"];
		$_SESSION[ 'solde' ][ 'RECUP' ]=$_SESSION[ 'conge' ][ 'RECUP' ];
	}

	$message_demande.= "<br><span class='vert'>-> Mise &agrave; jour du solde <-</span>";	
   }//fin de if (!$annule && $_SESSION["conge"]["type"]<3)

   if (!$annule)
   {
	$subject = "CONGE: Validation demande de congé (ID=".$ind.")";

   //demandeur de conge (quelquesoit son status)
	if ($mode_test) $TO = $mel_test;
	else $TO = $_SESSION['conge']['utilisateur'].'@'.$domaine;
	$message = "<body>Bonjour ".$_SESSION['conge']['nom']." ".$_SESSION['conge']['prenom'].",<br> votre demande de congé a été validée,<br> ";
	$message .= "suivez le lien <a href=".$chemin_mel."?dec=".$ind.">".$chemin_mel."?dec=".$ind."</a> pour l'afficher.</body>";
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['connection']['utilisateur'].'@'.$domaine, $_SESSION['connection']['nom']." ".$_SESSION['connection']['prenom']);

   //au responsable d'equipe ou de service du groupe selectionné pour la conge
	if ($directeur!=$_SESSION[ 'connection' ][ 'utilisateur' ])
	{
	$pourqui='responsable';
	$util=$_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']];
	if ($_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']]==$_SESSION[ 'conge' ][ 'utilisateur' ]) {$util=$directeur;$pourqui='directeur';}
	if ($mode_test) $TO = $mel_test;
	else $TO = $util.'@'.$domaine;
	$message = "<body>Bonjour ".$pourqui." ".$util.",<br> ";
	$message .= "le congé <a href=".$chemin_mel."?dec=".$ind.">".$chemin_mel."?dec=".$ind."</a> émis par ".$_SESSION['conge']['prenom']." ".$_SESSION['conge']['nom']." est validé.</body>";
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['connection']['utilisateur'].'@'.$domaine, $_SESSION['connection']['nom']." ".$_SESSION['connection']['prenom']);
	}

   //envoie aux admins
	$query2 = 'SELECT UTILISATEUR FROM T_UTILISATEUR WHERE ADMIN=1';
	$result2 = mysqli_query($link,$query2);
	while ($line2 = mysqli_fetch_array($result2, MYSQL_NUM))
 	{
	if ($mode_test) $TO = $mel_test;
	else $TO = $line2[0].'@'.$domaine;
	$message = "<body>Bonjour administrateur ".$line2[0].",<br> ";
	$message .= "le congé <a href=".$chemin_mel."?dec=".$ind.">".$chemin_mel."?dec=".$ind."</a> émis par ".$_SESSION['conge']['prenom']." ".$_SESSION['conge']['nom']." est validé.</body>";
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['connection']['utilisateur'].'@'.$domaine, $_SESSION['connection']['nom']." ".$_SESSION['connection']['prenom']);
	}
	mysqli_free_result($result2);

   //envoie aux utilisateurs du meme groupe (si case cochee)
	if ($_SESSION[ 'mailGroupe' ]==1)
	{
	$query2 = 'SELECT UTILISATEUR FROM T_UTILISATEUR WHERE GROUPE=\''.$_SESSION[ 'conge' ][ 'groupe' ].'\'';
	$result2 = mysqli_query($link,$query2);
	while ($line2 = mysqli_fetch_array($result2, MYSQL_NUM))
	{
		if ($line2[0]!=$_SESSION['conge']['utilisateur'] && $line2[0]!=$_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']])
		{
		if ($mode_test) $TO = $mel_test;
		else $TO = $line2[0].'@'.$domaine;
	
//		$message = "<body>Bonjour collègue ".$line2[0].",<br> ";
		$message = "<body>Cher(e) collègue,<br> ";
		$debAMouPM="Matin";
		if ($_SESSION['conge']['date_AM']==1) $debAMouPM="Aprés-midi";
		$finAMouPM="Matin";
		if ($_SESSION['conge']['date_PM']==1) $finAMouPM="Après-midi";
		$message .= "je suis en congé du ".$_SESSION['conge']['date_debut']."(".$debAMouPM.") au  ".$_SESSION['conge']['date_fin']."(".$finAMouPM.").<br>Cordialement,<br>".$_SESSION['conge']['prenom']."  ".$_SESSION['conge']['nom']."</body>";
//		$message .= "je suis en congé du ".$_SESSION[conge][date_debut]." au  ".$_SESSION[conge][date_fin].".<br>Cordialement,<br>".$_SESSION[conge][prenom]."  ".$_SESSION[conge][nom]."</body>";
		$message=utf8_decode($message);
		send_mail($TO, $message, $subject, $_SESSION['conge']['utilisateur'].'@'.$domaine, $_SESSION['conge']['nom']." ".$_SESSION['conge']['prenom']);
		}
	}
	mysqli_free_result($result2);
	}

	$message_demande.= "<br><span class='vert'>-> Envoi de mails <-</span>";
  	mysqli_close($link);
   }
}

/////////////////////// G7 - Annuler demande conge///////////////////////
if ((isset($_REQUEST[ 'conge' ]['annuler'])) || (isset($_REQUEST[ 'conge' ]['reattribuer'])))
{

/**
**/
   include $chemin_connection;

   $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
   mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

   $message_demande= "";
   $ind=$_SESSION["id_dec"];

   //Si le congé avait été validé alors, il faut réatribuer les jours déduits:
   if ($_SESSION['conge']['valide']==1)
   {
	$query = 'SELECT NB_JOURS_OUVRES,TYPE FROM T_CONGE WHERE ID_CONGE="'.$ind.'"';
	$result = mysqli_query($link,$query) or die('Requete de selection du nb jours (annulation): ' . mysqli_error());
	$line2 = mysqli_fetch_array($result, MYSQL_NUM);
	mysqli_free_result($result);
	$the_type=$line2[1];
	$the_nb_jours_ouvres=floatval($line2[0]);

   //mettre a jour le solde des conges
	if ($the_type<3)
	{
	   $sca=floatval($_SESSION[ 'conge' ][ 'CA' ]);
	   $sca1=floatval($_SESSION[ 'conge' ][ 'CA-1' ]);
	   $srecup=floatval($_SESSION[ 'conge' ][ 'CET' ]);
	   $scet=floatval($_SESSION[ 'conge' ][ 'RECUP' ]);
	   $squo=floatval($_SESSION[ 'conge' ][ 'QUOTA' ]);

	   if ($the_type==0)
	   {
		if ($sca1>0) //ajouter les jours dans ca-1
		{
		$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CA_1=SOLDE_CA_1+'.$the_nb_jours_ouvres.' WHERE UTILISATEUR="'.$_SESSION['conge']['utilisateur'].'"';
		$result = mysqli_query($link,$query) or die('Requete de modification du SOLDE_CA_1: ' . mysqli_error());
		//mysql_free_result($result);
		$_SESSION[ 'conge' ][ 'CA-1' ]+=$the_nb_jours_ouvres;
		$_SESSION[ 'solde' ][ 'CA-1' ]+=$the_nb_jours_ouvres;
		}
		else if ($sca+$the_nb_jours_ouvres>$squo)
		{
		$diff=$sca+$the_nb_jours_ouvres-$squo;
	   //ajouter la difference dans ca-1
		$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CA_1=SOLDE_CA_1+'.$diff.' WHERE UTILISATEUR="'.$_SESSION['conge']['utilisateur'].'"';
		$result = mysqli_query($link,$query) or die('Requete de modification du SOLDE_CA_1: ' . mysqli_error());
		//mysql_free_result($result);
		$_SESSION[ 'conge' ][ 'CA-1' ]+=$diff;
		$_SESSION[ 'solde' ][ 'CA-1' ]+=$diff;
	   //Mettre le quota dans ca
		$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CA='.$squo.' WHERE UTILISATEUR="'.$_SESSION['conge']['utilisateur'].'"';
		$result = mysqli_query($link,$query) or die('Requete de modification du SOLDE_CA: ' . mysqli_error());
		//mysql_free_result($result);
		$_SESSION[ 'conge' ][ 'CA' ]=$squo;
		$_SESSION[ 'solde' ][ 'CA' ]=$squo;
		}
		else //ajouter dans ca uniquement 
		{
		$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CA=SOLDE_CA+'.$the_nb_jours_ouvres.' WHERE UTILISATEUR="'.$_SESSION['conge'][utilisateur].'"';
		$result = mysqli_query($link,$query) or die('Requete de modification du SOLDE_CA: ' . mysqli_error());
		//mysql_free_result($result);
		$_SESSION[ 'conge' ][ 'CA' ]+=$the_nb_jours_ouvres;
		$_SESSION[ 'solde' ][ 'CA' ]+=$the_nb_jours_ouvres;
		}
	   }
	   else if ($the_type==1)
	   {
		$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CET=SOLDE_CET+'.$the_nb_jours_ouvres.' WHERE UTILISATEUR="'.$_SESSION['conge']['utilisateur'].'"';
		$result = mysqli_query($link,$query) or die('Requete de modification du CET: ' . mysqli_error());
		//mysql_free_result($result);
		$_SESSION[ 'conge' ][ 'CET' ]=$_SESSION[ 'conge' ][ 'CET' ]+$the_nb_jours_ouvres;
		$_SESSION[ 'solde' ][ 'CET' ]=$_SESSION[ 'conge' ][ 'CET' ];
	   }
	   else if ($the_type==2)
	   {
		$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_RECUP=SOLDE_RECUP+'.$the_nb_jours_ouvres.' WHERE UTILISATEUR="'.$_SESSION['conge']['utilisateur'].'"';
		$result = mysqli_query($link,$query) or die('Requete de modification du RECUP: ' . mysqli_error());
		//mysql_free_result($result);
		$_SESSION[ 'conge' ][ 'RECUP' ]=$_SESSION[ 'conge' ][ 'RECUP' ]+$the_nb_jours_ouvres;
		$_SESSION[ 'solde' ][ 'RECUP' ]=$_SESSION[ 'conge' ][ 'RECUP' ];
	   }

	   $message_demande= "<span class='vert'>-> Mise &agrave; jour du solde <-</span><br>";	
	}//fin de if ($the_type<3)
   }//fin de   if ($_SESSION['conge']['valide']==1)

   $annule=0;
   if (!$annule)
   {
	$query = 'UPDATE T_CONGE SET VALIDE="-1" WHERE ID_CONGE="'.$ind.'"';
	$result = mysqli_query($link,$query) or die('Requete de modification du status: ' . mysqli_error());
	//mysql_free_result($result);
	$message_demande.= "<span class='rouge'>-> Demande de cong&eacute; annul&eacute;e <-</span>";	
	$_SESSION['conge']['valide']=-1;
   }

   if (!$annule)
   {
	$subject = "CONGE: Annulation demande de congé (ID=".$ind.")";

   //demandeur de conge (quelquesoit son status)
	if ($mode_test) $TO = $mel_test;
	else $TO = $_SESSION['conge']['utilisateur'].'@'.$domaine;
	$message = "<body>Bonjour ".$_SESSION['conge']['nom']." ".$_SESSION['conge']['prenom'].",<br> votre demande de congé a été annulé,<br> ";
	$message .= "suivez le lien <a href=".$chemin_mel."?dec=".$ind.">".$chemin_mel."?dec=".$ind."</a> pour l'afficher.</body>";
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['connection']['utilisateur'].'@'.$domaine, $_SESSION['connection']['nom']." ".$_SESSION['connection']['prenom']);

   //son superieur s'il en a un
	if ($directeur!=$_SESSION[ 'connection' ][ 'utilisateur' ])
	{
	$pourqui='responsable';
	$util=$_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']];
	if ($_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']]==$_SESSION[ 'conge' ][ 'utilisateur' ]) {$util=$directeur;$pourqui='directeur';}
	if ($mode_test) $TO = $mel_test;
	else $TO = $util.'@'.$domaine;
	$message = "<body>Bonjour ".$pourqui." ".$util.",<br> ";
	$message .= "le congé <a href=".$chemin_mel."?dec=".$ind.">".$chemin_mel."?dec=".$ind."</a> émis par ".$_SESSION['conge']['prenom']." ".$_SESSION['conge']['nom']." est annulé.</body>";
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['connection']['utilisateur'].'@'.$domaine, $_SESSION['connection']['nom']." ".$_SESSION['connection']['prenom']);
	}

   //aux admin	
	$query2 = 'SELECT UTILISATEUR FROM T_UTILISATEUR WHERE ADMIN=1';
	$result2 = mysqli_query($link,$query2);
	while ($line2 = mysqli_fetch_array($result2, MYSQL_NUM))
 	{
	if ($mode_test) $TO = $mel_test;
	else $TO = $line2[0].'@'.$domaine;
	$message = "<body>Bonjour administrateur ".$line2[0].",<br> ";
	$message .= "le congé <a href=".$chemin_mel."?dec=".$ind.">".$chemin_mel."?dec=".$ind."</a> émis par ".$_SESSION['conge']['prenom']." ".$_SESSION['conge']['nom']." est annulé.</body>";
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['connection']['utilisateur'].'@'.$domaine, $_SESSION['connection']['nom']." ".$_SESSION['connection']['prenom']);
	}
	mysqli_free_result($result2);

	$message_demande.= "<br><span class='vert'>-> Envoi de mails <-</span>";
   }
   mysqli_close($link);
}

/*********************************************************************************
***********************  H - Reinitialiser la saisie *****************************
**********************************************************************************/
if ( isset($_REQUEST[ 'conge' ][ 'saisie' ]) )
{
	init_conge();
}

/*********************************************************************************
***********************  I - Choix du module *****************************
**********************************************************************************/
if (! isset($_SESSION[ 'choix_module' ])) $_SESSION[ 'choix_module' ] = $modules[0];
//Ajout pour que quand l'admin choisit un congé depuis le 1er affichage de la page administration.php
//le menu déroulant en haut é droite soit positionné sur CONGES:
if (isset($_GET["dec"])) $_SESSION[ 'choix_module' ]="CONGES";
if (isset($_REQUEST[ 'choix_module' ])) 
{
$_SESSION[ 'choix_module' ] = $_REQUEST[ 'choix_module' ];
for ($i=0;$i<sizeof($modules);$i++)
{
	if ($_SESSION['choix_module']==$modules[$i]) 
	{
		$self=$_SERVER[ 'PHP_SELF' ];
		$chemin_module=substr($self,0,-strlen(strrchr($self,"/")));
		$chemin_module.='/'.strtolower($_SESSION['choix_module']).'.php';
//		header('location:'.$chemin_module.'?sid=' . $sid);
		header('location:'.$chemin_module);
	}
}
}

/*********************************************************************************
******************************     J -HTML      **********************************
**********************************************************************************/

// En tete des modules
include "en_tete.php";
?>

<form name="form1" id="form1" method="post" action="<?php echo $_SERVER[ 'PHP_SELF' ]; ?>">

<!-- Autocomplete -->
<script type="text/javascript">
 
$(function() {

<?php
	include("config.php");
	include("".$chemin_connection);
	// Connecting, selecting database:
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
	or die('Could not connect: ' . mysqli_connect_error());
	mysqli_select_db($link,$mysql_base) or die('Could not select database : '. mysqli_error());

	$query = 'SELECT DISTINCT NOM FROM T_UTILISATEUR ORDER BY NOM ASC'; 
	$query2 = 'SELECT DISTINCT PRENOM FROM T_UTILISATEUR ORDER BY PRENOM ASC'; 
	$result=mysqli_query($link,$query) or die ('ERREUR dans requete select nom');
	$result2=mysqli_query($link,$query2) or die ('ERREUR dans requete select prenom');

	$listeNoms = 'var listeNoms = [';
	$listePrenoms = 'var listePrenoms = [';
	
	while($donnee = mysqli_fetch_row($result)) 
	{
		$listeNoms .= '"'.$donnee[0].'",';
	}

	while($donnee2 = mysqli_fetch_row($result2)) 
	{
		$listePrenoms .= '"'.$donnee2[0].'",';
	}

	if($listeNoms != 'var listeNoms = [')
		$listeNoms = substr($listeNoms,0,-1).'];';
	if($listePrenoms != 'var listePrenoms = [')
		$listePrenoms = substr($listePrenoms,0,-1).'];';

	echo $listeNoms;
	echo $listePrenoms;
	
	mysqli_free_result($result);
	mysqli_close($link);
?>

            $( "#rech_conge_nom" ).autocomplete({
                source: listeNoms
            });

            $( "#rech_conge_prenom" ).autocomplete({
                source: listePrenoms
            });

        });
</script>


<!-- ============ -->

<script type="text/javascript">
	function appelCalcNbJoursOuvres()
	{
		setTimeout("calcNbJoursOuvres()",100);
	}
	function calcNbJoursOuvres()
	{
		var date1 = document.getElementById('date1').value;
		var date2 = document.getElementById('date2').value;
		if(document.getElementById('debut_matin').checked)
			var dateAM = 0;
		else if(document.getElementById('debut_aprem').checked)
			var dateAM = 1;
		if(document.getElementById('fin_matin').checked)
			var datePM = 0;
		else if(document.getElementById('fin_aprem').checked)
			var datePM = 1;	
		var xhr = new XMLHttpRequest();
		xhr.onreadystatechange= function()
		{
			if(xhr.readyState == 4 && xhr.status == 200)
			{
				afficherNbJoursOuvres(xhr);
			}
		};
		xhr.open("POST", "AJAX/ajax_calcNbJours.php", true);
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		xhr.send("date1="+date1+"&date2="+date2+"&dateAM="+dateAM+"&datePM="+datePM);
	}

	function afficherNbJoursOuvres(xhr)
	{
		var reponse = xhr.responseText;
		document.getElementById('nbJours').innerHTML = reponse+' jour(s)';
	}

	
	function valider_conges()
	{	
		document.body.style.cursor = "wait";

		//Permet d'obtenir la liste des congés en attente de validation séparés par #
		var x = document.getElementById('popup_responsables').getElementsByTagName('input');
		var nodeLength = x.length;
		var liste= '';
		for (var i=0; i<nodeLength; i++)
		{
			if(x[i].type == 'checkbox' && x[i].checked==true)
			{	
				liste += x[i].name+'#';
			}		
		}
		
		if(liste != '')
			liste=liste.substring(0,(liste.length)-1);	
	
//		var sid = document.getElementById('sid').value;
		var xhr = new XMLHttpRequest();
		xhr.onreadystatechange= function()
		{
			if(xhr.readyState == 4 && xhr.status == 200)
			{
				afficherNbCongesValides(xhr);
			}
		};
		xhr.open("POST", "AJAX/ajax_valid_multi_conges.php", true);
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
//		xhr.send("sid="+sid+"&conges="+liste);
		xhr.send("conges="+liste);
	}

	function afficherNbCongesValides(xhr)
	{
		var reponse = xhr.responseText;
		var tab_reponse=reponse.split('##');
		document.getElementById('messageFenetreModale').innerHTML = tab_reponse[0];
		document.getElementById('h2_fenetreModale').innerHTML = tab_reponse[1];
		document.getElementById('listeDesCongesAValider').innerHTML = tab_reponse[2];
		document.body.style.cursor = "default";
	}

	//Permet de selectionner/deselectionner les checkbox de la fenetre modale
	function selectAll()
	{
		var btn = document.getElementById('selectionner');//Bouton de selection
		//Selectionne ou deselectionne tout
		if(btn.name == 'selectionner')
			var etat=true;
		else if(btn.name == 'deselectionner') var etat=false;
		
		var x = document.getElementById('popup_responsables').getElementsByTagName('input');
		var nodeLength = x.length;
		for (var i=0; i<nodeLength; i++)
		{
			if(x[i].type == 'checkbox')
			{	
				x[i].checked=etat;
			}		
		}
		
		if(etat==true)
		{
			btn.name="deselectionner";
			btn.value="Tout deselectionner";
		}
		else if(etat==false)
		{
			btn.name="selectionner";
			btn.value="Tout selectionner";
		}
	}


</script>

<?php
/*******************************************************************
*********************** Fenetre modale *****************************
*******************************************************************/
$tab_conges=conges_a_valider();//Tableau contenant les conges en attente de validation
$nb_conges_en_attente=count($tab_conges);//Nombre de congés en attente de validation

//Ouverture de la fenetre grace à cette fonction JQuery onClick
if(isset($_SESSION[ 'fenetre_modale_conges' ]) && $_SESSION[ 'fenetre_modale_conges' ] == 1)
{	
	echo '<script>
		$(function(){
			$(".popup-light").click(function() {
				var popupWidth = \'800px\',
					objPopup =  $(\'.popup-block\');
			
					objPopup
					.css("width", popupWidth)
					.prepend(\'<img src="images/close_pop.png" class="popup-btn-close" title="Fermer la fen&ecirc;tre" alt="Fermer" />\')
					.css({
						// Si l\'on regroupe les deux blocs CSS, le popup n\'est pas bien positionné
						// Le popup doit avoir sa taille définitive avant le calcul de outerHeight et de outerWidth
						"margin-top":  -objPopup.outerHeight(true)/2,
						"margin-left": -objPopup.outerWidth(true)/2
					})
					.fadeIn();
			
					$("<div/>", {
					"class":"voile-noir",
					"css":{
						"filter":"alpha(opacity=80)"
					}
					}).appendTo("body").fadeIn();
			
			
			
				$("body").delegate(".popup-btn-close, .voile-noir", "click", function(){
					$(\'.voile-noir , .popup-block\').fadeOut(function(){
					$(".popup-btn-close, .voile-noir").remove();
					});
			
					return false;
				});
			});
		});
	</script>';

	//Fenetre à afficher
	echo '<div class="popup-block" id="popup_responsables">';
			if($nb_conges_en_attente == 1)
				echo '<h2 class="centrer" id="h2_fenetreModale">Vous avez '.$nb_conges_en_attente.' cong&eacute; &agrave; valider</h2>';
			else echo '<h2 class="centrer" id="h2_fenetreModale">Vous avez '.$nb_conges_en_attente.' cong&eacute;s &agrave; valider</h2>';
	
			echo '<table class="centrerBloc"  id="barreFenetreModale"><tr><td><input type="button" name="selectionner" id="selectionner" value="Tout selectionner" onclick="selectAll();" /></td><td class="centrer"><input name="valid_multi_conges" type="button" value="Valider les cong&eacute;s s&eacute;lectionn&eacute;s" onclick="valider_conges();" /></td><td id="messageFenetreModale"></td></tr></table>';
			
			echo '<div id="listeDesCongesAValider">';
			echo '<table class="centrerBloc">';
			foreach($tab_conges as $conge)
			{
				echo '<tr><td><input type="checkbox" name="'.$conge[ 'id' ].'" /> n&deg; <a href="conges.php?dec='.$conge[ 'id' ].'#DEMANDE_CONGES">'.$conge[ 'id' ].'</a>  |  <strong>'.$conge[ 'login' ].'</strong> ('.$conge[ 'groupe' ].') du <em>'.$conge[ 'date_debut' ].'</em> au <em>'.$conge[ 'date_fin' ].'</em>.</td></tr>';//conges.php?sid='.$sid.'&dec
			}
			echo '</table>';
			echo '</div>';
	echo '</div>';
}

//La fenetre modale n'apparait que pour les statuts 3,4 et 6. 
if(in_array($_SESSION[ 'connection' ][ 'status' ], array(3,4,6)) OR $_SESSION[ 'connection' ][ 'utilisateur' ]==$directeur)
	if($nb_conges_en_attente > 0)//S'il y a des congés à valider
		if(!isset($_SESSION[ 'fenetre_modale_conges' ]) OR $_SESSION[ 'fenetre_modale_conges' ] == 0)//N'est ouverte qu'à la connexion
		{	
			$_SESSION[ 'fenetre_modale_conges' ] = 1; //Fenetre ouverte
			//Script JQuery
			echo '<script>
				$(function(){
					var popupWidth = \'800px\',
					objPopup =  $(\'.popup-block\');
			
					objPopup
					.css("width", popupWidth)
					.prepend(\'<img src="images/close_pop.png" class="popup-btn-close" title="Fermer la fen&ecirc;tre" alt="Fermer" />\')
					.css({
						// Si l\'on regroupe les deux blocs CSS, le popup n\'est pas bien positionné
						// Le popup doit avoir sa taille définitive avant le calcul de outerHeight et de outerWidth
						"margin-top":  -objPopup.outerHeight(true)/2,
						"margin-left": -objPopup.outerWidth(true)/2
					})
					.fadeIn();
			
					$("<div/>", {
					"class":"voile-noir",
					"css":{
						"filter":"alpha(opacity=80)"
					}
					}).appendTo("body").fadeIn();
			
			
			
				$("body").delegate(".popup-btn-close, .voile-noir", "click", function(){
					$(\'.voile-noir , .popup-block\').fadeOut(function(){
					$(".popup-btn-close, .voile-noir").remove();
					});
			
					return false;
				});
				});
			</script>';
		
			//Fenetre à afficher
			echo '<div class="popup-block" id="popup_responsables">';
					if($nb_conges_en_attente == 1)
						echo '<h2 class="centrer" id="h2_fenetreModale">Vous avez '.$nb_conges_en_attente.' cong&eacute; &agrave; valider</h2>';
					else echo '<h2 class="centrer" id="h2_fenetreModale">Vous avez '.$nb_conges_en_attente.' cong&eacute;s &agrave; valider</h2>';
			
					echo '<table class="centrerBloc"  id="barreFenetreModale"><tr><td><input type="button" name="selectionner" id="selectionner" value="Tout selectionner" onclick="selectAll();" /></td><td class="centrer"><input name="valid_multi_conges" type="button" value="Valider les cong&eacute;s s&eacute;lectionn&eacute;s" onclick="valider_conges();" /></td><td id="messageFenetreModale"></td></tr></table>';
					
					echo '<div id="listeDesCongesAValider">';
					echo '<table class="centrerBloc">';
					foreach($tab_conges as $conge)
					{
						echo '<tr><td><input type="checkbox" name="'.$conge[ 'id' ].'" /> n&deg; <a href="conges.php?dec='.$conge[ 'id' ].'#DEMANDE_CONGES">'.$conge[ 'id' ].'</a>  |  <strong>'.$conge[ 'login' ].'</strong> ('.$conge[ 'groupe' ].') du <em>'.$conge[ 'date_debut' ].'</em> au <em>'.$conge[ 'date_fin' ].'</em>.</td></tr>';//conges.php?sid='.$sid.'&dec
					}
					echo '</table>';
					echo '</div>';
			echo '</div>';
		}
?>
<!-- FIN fenetre modale pour les chefs d'équipe-->


<?php

/////////////////////////// J1 - Affichage Recherche Demande //////////////////////////
	echo '<table id="lien_recherche_conges"><tr><td>';
	$val_r_gui=1;
	if ($_SESSION[ 'r_gui' ]==1) $val_r_gui=0; else $val_r_gui=1;
	if ($val_r_gui==1) echo '<a href="'.$_SERVER[ 'PHP_SELF' ]. '?r_gui='.$val_r_gui.'"><< <img src="images/loupe.png" height=17 id="loupe_recherche"/> Cliquez pour afficher la recherche >></a>';//$_SERVER[ 'PHP_SELF' ]. '?sid=' . $sid.'&r_gui
	else echo '<a href="'.$_SERVER[ 'PHP_SELF' ]. '?r_gui='.$val_r_gui.'"><< Cliquez pour masquer la recherche >></a>';//$_SERVER[ 'PHP_SELF' ]. '?sid=' . $sid.'&r_gui
	echo '</td></tr></table>';
	
	if ($_SESSION[ 'r_gui' ]==1)
	{
	echo '<div id="recherche_conges">';
	echo '<table>';
	echo '<caption>Recherche</caption>';

//partie recherche evoluee:
	$griser='';
	if ($_SESSION[ 'connection' ][ 'groupe' ]!='DIRECTION' && $_SESSION[ 'connection' ][ 'admin' ]!=1) 
		{
		$_SESSION['rech_conge']['groupe']=$_SESSION[ 'connection' ][ 'groupe' ];
		$griser='disabled';
		}
	echo '<td><label for="rech_conge_groupe">Equipe / Service</label></td>';
	echo '<td><select name="rech_conge[groupe]" id="rech_conge_groupe" '.$griser.' ';
	echo ' onChange="javascript:document.forms[1].submit()"';
	echo '>';
	for ($i=0;$i<sizeof($_SESSION[ 'correspondance' ]['groupe']);$i++)
	{
		if ($_SESSION['correspondance']['entite_depensiere'][$i]==0)
		{
		echo '<option value="'.$_SESSION['correspondance']['groupe'][$i].'" ';
		if ($_SESSION['rech_conge']['groupe'] ==$_SESSION['correspondance']['groupe'][$i])
		{
			 echo 'selected';
			$_SESSION['groupe_indice_reche_conge']=$i;
		}
		echo '>'.$_SESSION[ 'correspondance' ]['groupe'][$i].'</option>';
		}
	}
	echo '</select>';
	if ($_SESSION[ 'connection' ][ 'status' ] == 3 || $_SESSION[ 'connection' ][ 'status' ] == 4 || $_SESSION[ 'connection' ][ 'status' ] == 6) echo '<input type="submit" name="rechercher_tlm" value=" Tous les membres " />';
	echo '</td></tr>';
	
	$a_rajouter='';
	if ($_SESSION[ 'connection' ][ 'groupe' ]!='DIRECTION' && $_SESSION[ 'connection' ][ 'admin' ]!=1)
	if ($_SESSION[ 'connection' ][ 'status' ] < 3 || $_SESSION[ 'connection' ][ 'status' ] == 5)
	{
		$a_rajouter='readonly style="background-color: lightgrey;"';
		$_SESSION[ 'rech_conge' ][ 'nom' ]=$_SESSION[ 'connection' ][ 'nom' ];
		$_SESSION[ 'rech_conge' ][ 'prenom' ]=$_SESSION[ 'connection' ][ 'prenom' ];
	}

	echo '<tr><td><label for="rech_conge_nom">Nom</label></td>';
	echo '<td><INPUT size=20 TYPE=text NAME="rech_conge[nom]" id="rech_conge_nom" value="'.$_SESSION[ 'rech_conge' ][ 'nom' ].'"';
	echo $a_rajouter;
	echo '></td></tr>';
	echo '<tr><td><label for="rech_conge_prenom">Pr&eacute;nom</label></td>';
	echo '<td><INPUT size=20 TYPE=text NAME="rech_conge[prenom]" id="rech_conge_prenom" value="'.$_SESSION[ 'rech_conge' ][ 'prenom' ].'"';
	echo $a_rajouter;
	echo '></td></tr>';

//nouvel emplacement de recherche calendaire
	echo '<tr><td><label for="rech_conge_annee">Ann&eacute;e</label></td><td>';
	echo '<select name="rech_conge[annee]" id="rech_conge_annee"';
	echo '>';
	for ($i=0;$i<sizeof($annees);$i++)
	{
		echo '<option value="'.$annees[$i].'" ';
		if ($_SESSION['rech_conge']['annee']==$annees[$i])	 echo 'selected';
		echo '>'.$annees[$i].'</option>';
	}
	echo '</select>';
	echo '</td></tr>';

//boutons de recherche evoluee et reinitialisation
	echo '<tr><td colspan=2 class="centrer">';
	//Bouton pour voir la liste des congés en attente de validation
	if(isset($_SESSION[ 'fenetre_modale_conges' ]))
		echo '<input type=button value="Cong&eacute;s en attente" class="popup-light"/>';
	echo '<input type="submit" name="rechercher_evol" value=" Rechercher " />';
	echo '<input type="submit" name="reinitialiser" value=" R&eacute;initialiser " />';
	if(isset($message_evolue))
	  echo '<br/>'.$message_evolue;
	echo '</td></tr>';
	echo '</table>' . "\n";

/////////////////////////// J2 - Affichage des resultats de recherche evoluee ///////////
	if ($_SESSION['rech_conge']['resultats']>0) //si resultat de recherche evoluee
	{
		echo '<table id="res_rech_conges">';
		echo '<caption>R&eacute;sultats de la recherche</caption>';
		echo '<tr class="enteteTabConges">';
		echo '<td>Identifiant</td>';
		echo '<td>Nom</td>';
		echo '<td>Pr&eacute;nom</td>';
		echo '<td>Groupe</td>';
		echo '<td>Date d&eacute;but</td>';
		echo '<td>Date fin</td>';
		echo '<td>Type<b></td>';
		echo '<td>Jours</td>';
		echo '<td>Validit&eacute;</td>';
		echo '</tr>';
		$i=1;

		$page=floor($_SESSION['rech_conge']['limitebasse']/$_SESSION['rech_conge']['nb_par_page'])+1;
		$nb_page = ceil($_SESSION['rech_conge']['resultats']/$_SESSION['rech_conge']['nb_par_page']);
		$nb_affich=$_SESSION['rech_conge']['nb_par_page'];
		if ($page==$nb_page && $_SESSION['rech_conge']['resultats']< $_SESSION['rech_conge']['nb_par_page']*$nb_page)
		$nb_affich=$_SESSION['rech_conge']['resultats']%$_SESSION['rech_conge']['nb_par_page'];

		for ($i=1 ; $i<=$nb_affich ; $i++)
		{
			if ($i%2==0) echo '<tr class="ligne_claire">';
				else echo '<tr class="ligne_foncee">';

			echo '<td><a href="'.$_SERVER[ 'PHP_SELF' ]. '?r_gui=0&dec='.$_SESSION['rech_conge'][$i]['id_conge'].'#DEMANDE_CONGES">'.$_SESSION['rech_conge'][$i]['id_conge'].'</a></td>';//$_SERVER[ 'PHP_SELF' ]. '?sid=' . $sid.'&r_gui
			echo '<td>'.$_SESSION['rech_conge'][$i]['nom'].'</td>';
			echo '<td>'.$_SESSION['rech_conge'][$i]['prenom'].'</td>';
			echo '<td>'.$_SESSION['rech_conge'][$i]['groupe'].'</td>';
			echo '<td>'.$_SESSION['rech_conge'][$i]['date_debut'].'</td>';
			echo '<td>'.$_SESSION['rech_conge'][$i]['date_fin'].'</td>';
		$thetype='CA';
		if ($_SESSION['rech_conge'][$i]['type']==1) $thetype='CET'; 
		else if ($_SESSION['rech_conge'][$i]['type']==2) $thetype='RECUP'; 
		else if ($_SESSION['rech_conge'][$i]['type']==3) $thetype='Autre'; 
			echo '<td>'.$thetype.'</td>';
			echo '<td>'.$_SESSION['rech_conge'][$i]['nb_jours_ouvres'].'</td>';
			$bcol='';
			if ($_SESSION['rech_conge'][$i]['valide']==-1) $bcol='bgcolor="#FF513A"';
			else if ($_SESSION['rech_conge'][$i]['valide']==0) 
			{
				$ind_=$_SESSION[ 'correspondance' ][$_SESSION['rech_conge'][$i]['groupe']];
				if ($_SESSION[ 'correspondance' ]['valid_conges'][$ind_]==1) $bcol='bgcolor="#8888FF"';
			}
			else if ($_SESSION['rech_conge'][$i]['valide']==1) $bcol='bgcolor="#88FF88"';
			echo '<td align="center" '.$bcol.'>'.$_SESSION['rech_conge'][$i]['valide'].'</td>';
			echo '</tr>';
		}
		echo '</table>';
		
		echo '</td></tr><tr><td>';
		echo '<table id="pagination">';
		if ($page > 1) echo '<td id="precedent"><input type="submit" name="rechercher_prec" value=" Page pr&eacute;c&eacute;dente " /></td>';
		echo '<td id="page">page '.$page.'/'.$nb_page.'</td>';
		if ($page < $nb_page) echo '<td id="suivant"><input type="submit" name="rechercher_suiv" value=" Page suivante " /></td>';
		echo '</table>';
		echo '</td></tr>';
		echo '</table>';
	}//fin de if ($_SESSION['rech_conge']['resultats']>0)
		echo '</div>';
	}//fin de if ($_SESSION[ 'r_gui' ]==1) => affichage partie recherche

///////////////////// J3 -Affichage des resultats de recherche graphique/calendaire ///////////
	if ($_SESSION['rech_conge']['annee']!="----")
	{
		echo '<table id="calendrier_conges">';
		echo '<caption>Calendrier des cong&eacute;s</caption>';
		echo '<tr><td>';

		if ($_SESSION['rech_conge']['groupe']!=$_SESSION[ 'correspondance' ]['groupe'][0]) $thegroupe=$_SESSION['rech_conge']['groupe'];
		else $thegroupe = '';
//$thegroupe='EQUIPE1';
		echo '<img id="img_calendrier_conges"  src="calendrier_conges.php?chemin='.$chemin_connection.'&uneannee='.$_SESSION['rech_conge']['annee'].'&nom='.$_SESSION['rech_conge']['nom'].'&prenom='.$_SESSION['rech_conge']['prenom'].'&groupe='.$thegroupe .'">';
		echo '</td>';
		echo '<td id="legende"><h4>L&eacute;gende</h4><ul>';
		echo '<li id="ferie">Jour f&eacute;ri&eacute;</li>';
		echo '<li id="we">Week end</li>';
		echo '<li id="conge">Cong&eacute;</li>';
		echo '</ul></td></tr>';
		echo '</table>';
	}

/////////////////////////// J4 - Partie demande de conge //////////////////////////
	echo '<table id="DEMANDE_CONGES"><tr><td>Demande</td></tr></table>';

///////////////////////////Bandeau correspondant à l'état de l'expédition//////////////////
	if($_SESSION[ 'conge' ][ 'valide' ] == 2)//Nouvelle demande
		echo '<img src="images/bandeau_nouvelle_demande.png" id="bandeau_etat" alt="Nouvelle demande" />';
	elseif($_SESSION[ 'conge' ][ 'valide' ] == -1)//Annulée
		echo '<img src="images/bandeau_-1.png" id="bandeau_etat" alt="Demande annul&eacute;e" />';
	elseif($_SESSION[ 'conge' ][ 'valide' ] == 0)//En attente
		echo '<img src="images/bandeau_0.png" id="bandeau_etat" alt="Demande en attente" />';
	elseif($_SESSION[ 'conge' ][ 'valide' ] == 1)//Validée
		echo '<img src="images/bandeau_1.png" id="bandeau_etat" alt="Demande valid&eacute;e" />';

if($_SESSION[ 'conge' ][ 'valide' ] == 2)//Nouvelle demande
{
	echo '<p class="centrer gras rouge correctionDecalage">* Champs obligatoires</p>';

	echo '<table id="demande_conge">';
	echo '<tr><td><label for="conge_nom">Nom <span class="gras rouge">*</span></label></td>';
	echo '<td><INPUT size=20 tabindex="30" TYPE=text NAME="conge[nom]" id="conge_nom" value="'.$_SESSION[ 'conge' ][ 'nom' ].'" readonly ></td></tr>';
	echo '<tr><td><label for="conge_prenom">Pr&eacute;nom <span class="gras rouge">*</span></label></td>';
	echo '<td><INPUT size=20 tabindex="32" TYPE=text NAME="conge[prenom]" id="conge_prenom" value="'.$_SESSION[ 'conge' ][ 'prenom' ].'" readonly ></td></tr>';
	echo '<tr><td><label for="conge_groupe">Equipe / Service <span class="gras rouge">*</span></label></td>';
	echo '<td><INPUT size=20 tabindex="34" TYPE=text NAME="conge[groupe]" id="conge_groupe" value="'.$_SESSION[ 'conge' ][ 'groupe' ].'" readonly >';
	echo '</td></tr>';
//	for ($i=0;$i<sizeof($_SESSION['groupe']);$i++)
	for ($i=0;$i<sizeof($_SESSION['correspondance']['groupe']);$i++)
	{
		if ($_SESSION['conge']['groupe']==$_SESSION['correspondance']['groupe'][$i])
		{	
			$_SESSION['groupe_indice']=$i;
		}
	}
	echo '<tr><td><label for="conge_type">Type <span class="gras rouge">*</span></label></td>';

	echo '<td>';
	echo '<select name="conge[type]" id="conge_type" tabindex="36" onChange="javascript:document.getElementById(\'form1\').submit()"';
	echo '>';
	for ($i=0;$i<sizeof($conge_type);$i++)
	{
		echo '<option value="'.$i.'" ';
		if ($_SESSION['conge']['type']==$i) { echo 'selected';}
		echo '>'.$conge_type[$i].'</option>';
//je ne peux pas faire ce qui suit a cause des accents...?
//echo '<option value="'.$conge_type[$i].'" ';
//if ($_SESSION['conge']['type']==$conge_type[$i]) { echo 'selected';}
//echo '>'.$conge_type[$i].'</option>';
	}
	echo '</select>';
//conge_sans_solde
	if ($_SESSION['conge']['type']==3) //autres=>conges sans solde
	{
	echo '<select name="conge[sans_solde]" tabindex="38" onChange="javascript:document.getElementById(\'form1\').submit()"';
	echo '>';
	for ($i=0;$i<sizeof($conge_sans_solde);$i++)
	{
		echo '<option value="'.$i.'" ';
		if ($_SESSION['conge']['sans_solde']==$i) { echo 'selected';}
		echo '>'.$conge_sans_solde[$i].'</option>';
	}
	echo '</select>';
	}
	echo '</td></tr>';
//affichage du nombre de congés restant:	
	if (($_SESSION['conge']['type']<3))//conges avec solde
	{
	if ($_SESSION[ 'edition' ] == 1) //pas grand chose change par rapport au else de ce if...
	{
		echo '<tr><td><label>Solde :</label></td><td>';
		if (($_SESSION['conge']['type']==0) && ($_SESSION['solde']['CA-1']!=0))
		{
		echo ''.$_SESSION['solde']['CA-1'].' jour(s) de cong&eacute;(s) annuel(s) de l\'ann&eacute;e pr&eacute;c&eacute;dente.';
		if ($_SESSION['solde']['CA']>0) echo '<br>';
		}
		if ($_SESSION['conge']['type']==0)
		if ($_SESSION['solde']['CA']>0)
		echo ''.$_SESSION['solde']['CA'].' jour(s) de cong&eacute;(s) annuel(s) pour cette ann&eacute;e.';
		elseif ($_SESSION['solde']['CA']==0) echo 'pas de jour restant de cong&eacute; annuel.';
		else echo 'le solde est n&eacute;gatif de '.$_SESSION['solde']['CA'].' jour(s) de cong&eacute;(s) annuel(s).';
		if ($_SESSION['conge']['type']==1)
		{
		if ($_SESSION['solde']['CET']>0)
		echo ''.$_SESSION['solde']['CET'].' jour(s) de compte &eacute;pargne temps.';
		else echo 'pas de jour restant sur le compte &eacute;pargne temps.';
		echo '<br><b>Le cong&eacute; ne sera accept&eacute; que lors de la validation du dossier.</b>';
		}
		if ($_SESSION['conge']['type']==2)
		if ($_SESSION['solde']['RECUP']>0)
		echo ''.$_SESSION['solde']['RECUP'].' jour(s) de r&eacute;cup&eacute;ration.';
		else echo 'pas de jour de r&eacute;cup&eacute;ration.';
		echo '</td></tr>';
	}
	else //fin if ($_SESSION[ 'edition' ] == 1)
	{
		echo '<tr><td>Solde :</td><td>';
		if (($_SESSION['conge']['type']==0) && ($_SESSION['conge']['CA-1']>0))
		{
		echo ''.$_SESSION['conge']['CA-1'].' jour(s) de cong&eacute;(s) annuel(s) de l\'ann&eacute;e pr&eacute;c&eacute;dente.';
		if ($_SESSION['conge']['CA']>0) echo '<br>';
		}
		if ($_SESSION['conge']['type']==0)
		if ($_SESSION['conge']['CA']>0)
		echo ''.$_SESSION['conge']['CA'].' jour(s) de cong&eacute;(s) annuel(s) pour cette ann&eacute;e.';
		elseif ($_SESSION['conge']['CA']==0) echo 'pas de jour restant de cong&eacute; annuel.';
		else echo 'le solde est n&eacute;gatif de '.$_SESSION['conge']['CA'].' jour(s) de cong&eacute;(s) annuel(s).';
		if ($_SESSION['conge']['type']==1)
		if ($_SESSION['conge']['CET']>0)
		echo ''.$_SESSION['conge']['CET'].' jour(s) de compte &eacute;pargne temps.';
		else echo 'pas de jour restant sur le compte &eacute;pargne temps.';
		if ($_SESSION['conge']['type']==2)
		if ($_SESSION['conge']['RECUP']>0)
		echo ''.$_SESSION['conge']['RECUP'].' jour(s) de r&eacute;cup&eacute;ration.';
		else echo 'pas de jour de r&eacute;cup&eacute;ration.';
		echo '</td></tr>';
	}
	}

//tableau DEBUT FIN
	echo '<table id="debut_finANDcommentaire">';
	echo '<tr><td>';

	echo '<table id="debut_fin">';
	echo '<tr class="enteteTabConges">';
	echo '<td>DEBUT <span class="gras rouge">*</span></td><td>FIN <span class="gras rouge">*</span></td>';
	echo '</tr><tr>';
	echo '<td class="bordureLR date"><label for="conge_date_debut">Date :</label>'; 
	echo '<INPUT onblur="initDate();appelCalcNbJoursOuvres();" tabindex="40" id="date1" size=10 TYPE=text NAME="conge[date_debut]" title="JJ/MM/AAAA" value="'.$_SESSION[ 'conge' ][ 'date_debut' ].'"></td>';
	echo '<td class="bordureLR date"><label for="conge_date_fin">Date :</label>';
	echo '<INPUT id="date2" size=10 tabindex="46" onblur="appelCalcNbJoursOuvres();" TYPE=text NAME="conge[date_fin]" title="JJ/MM/AAAA" value="'.$_SESSION[ 'conge' ][ 'date_fin' ].'"></td>';
	echo '</tr><tr>';

	echo '<td class="bordureLR bordureBottom"><input type="radio" name="conge[date_AM]" onblur="appelCalcNbJoursOuvres();" tabindex="42" id="debut_matin" value=0 ';
	if ($_SESSION[ 'conge' ][ 'date_AM' ]==0) echo ' checked ';
	echo '/><label for="debut_matin">Matin (8h)</label>';
	echo '<input type="radio" name="conge[date_AM]" onblur="appelCalcNbJoursOuvres();" tabindex="44" id="debut_aprem" value=1 ';
	if ($_SESSION[ 'conge' ][ 'date_AM' ]==1) echo ' checked ';
	echo '/><label for="debut_aprem">Apr&egrave;s-midi (14h)</label></td>';
	echo '<td class="bordureLR bordureBottom"><input type="radio" onblur="appelCalcNbJoursOuvres();" tabindex="48" name="conge[date_PM]" id="fin_matin" value=0 ';
	if ($_SESSION[ 'conge' ][ 'date_PM' ]==0) echo ' checked ';
	echo '/><label for="fin_matin">Matin (12h)</label>'; 
	echo '<input type="radio" onblur="appelCalcNbJoursOuvres();" id="fin_aprem" name="conge[date_PM]" tabindex="50" value=1 ';
	if ($_SESSION[ 'conge' ][ 'date_PM' ]==1) echo ' checked ';
	echo '/><label for="fin_aprem">Apr&egrave;s-midi (18h)</label></td>';
	echo '</tr>';

	if ($_SESSION[ 'edition' ] == 1)//calcul du nombre de jours ouvrés
	{
	echo '<tr><td colspan=2>';
	echo '<strong class="centrer">Nombre de jours ouvr&eacute;s : </strong><span id="nbJours"></span>';
	//if ($_SESSION[ 'nbjo' ]>0) echo ' : '.$_SESSION[ 'nbjo' ].' jour(s)';
	echo '</td></tr>';
	}
	else if ($_SESSION['conge']['nb_jours_ouvres']>0) 
	{
		echo '<tr><td colspan=4>';
	//si l'utilisateur peut annuler ou valider
		if ($_SESSION[ 'correspondance' ]['valid_conges'][$_SESSION['groupe_indice']]==1
		&& $_SESSION[ 'conge' ][ 'valide' ]==0
		&& $_SESSION[ 'edition' ] != 1
		&& (($_SESSION[ 'connection' ][ 'utilisateur' ]==$_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']] && $_SESSION[ 'connection' ][ 'utilisateur' ]!= $_SESSION[ 'conge' ][ 'utilisateur' ]) || ($_SESSION[ 'connection' ][ 'utilisateur' ]==$directeur) || ($_SESSION[ 'connection' ][ 'admin' ]==1)))
		{
			$calc_deduc=$_SESSION['conge']['nb_jours_ouvres']*$_SESSION[ 'solde' ][ 'QUOTITE' ]/100.;
			echo 'Avec une quotit&eacute; &agrave; '.$_SESSION[ 'solde' ][ 'QUOTITE' ].', '.$calc_deduc.' jours devraient &ecirc;tre d&eacute;duits.<br>';
			echo '<input type="text" name="conge[nb_jours_ouvres_recalc]" value="'.$_SESSION['conge']['nb_jours_ouvres_recalc'].'" size=2> jours seront d&eacute;duits lors de la validation.';
		}
		else
		{
			echo $_SESSION['conge']['nb_jours_ouvres'].' jours ouvr&eacute;s.';
		
		}
	echo '</td></tr>';
	}
	echo '</table>';
//FIN (tableau DEBUT FIN)

//champ de commentaire
	echo '<tr><td>';
	echo '<br/>Commentaire<br/>';
	echo '<textarea tabindex="54" name="conge[commentaire]" rows="4" cols="60" maxlenght="256" onKeyPress="CaracMax(this, 256) ;">'.$_SESSION[ 'conge' ][ 'commentaire' ].'</textarea>';
	echo '</td></tr>';
}
else //===========Affichage de la demande sans possibilité de la modifier=======================
{
	echo '<table id="demande_conge" class="correctionDecalage">';
	echo '<tr><td><strong>Nom</strong></td>';
	echo '<td>'.$_SESSION[ 'conge' ][ 'nom' ].'</td></tr>';
	echo '<tr><td><strong>Pr&eacute;nom</strong></td>';
	echo '<td>'.$_SESSION[ 'conge' ][ 'prenom' ].'</td></tr>';
	echo '<tr><td><strong>Equipe / Service</strong></td>';
	echo '<td>'.$_SESSION[ 'conge' ][ 'groupe' ].'</td></tr>';

	echo '<tr><td><strong>Type</strong></td>';

	echo '<td>';
	for ($i=0;$i<sizeof($conge_type);$i++)
	{
		if ($_SESSION['conge']['type']==$i)
			echo $conge_type[$i];
	}

	if ($_SESSION['conge']['type']==3) //autres=>conges sans solde
	{
		for ($i=0;$i<sizeof($conge_sans_solde);$i++)
		{
			if ($_SESSION['conge']['sans_solde']==$i) 
				echo $conge_sans_solde[$i];
		}
	}
	echo '</td>';

	echo '<tr><td colspan=2><hr/></td></tr>';

	echo '<tr><td><strong>D&eacute;but </strong></td>
	      <td>'.$_SESSION[ 'conge' ][ 'date_debut' ].' - ';
	if ($_SESSION[ 'conge' ][ 'date_AM' ]==0) echo 'Matin (8h)'; else echo 'Apr&egrave;s-midi (14h)';
	echo '</td></tr>';

	echo '<tr><td><strong>Fin </strong></td>
	      <td>'.$_SESSION[ 'conge' ][ 'date_fin' ].' - ';
	if ($_SESSION[ 'conge' ][ 'date_PM' ]==0) echo 'Matin (12h)'; else echo 'Apr&egrave;s-midi (18h)';
 
	if($_SESSION['conge']['nb_jours_ouvres']>0) 
	{
	//si l'utilisateur peut annuler ou valider
		if ($_SESSION[ 'correspondance' ]['valid_conges'][$_SESSION['groupe_indice']]==1
		&& $_SESSION[ 'conge' ][ 'valide' ]==0
		&& $_SESSION[ 'edition' ] != 1
		&& (($_SESSION[ 'connection' ][ 'utilisateur' ]==$_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']] && $_SESSION[ 'connection' ][ 'utilisateur' ]!= $_SESSION[ 'conge' ][ 'utilisateur' ]) || ($_SESSION[ 'connection' ][ 'utilisateur' ]==$directeur) || ($_SESSION[ 'connection' ][ 'admin' ]==1)))
		{
			echo '<tr><td colspan=2>';
			$calc_deduc=$_SESSION['conge']['nb_jours_ouvres']*$_SESSION[ 'solde' ][ 'QUOTITE' ]/100.;
			echo 'Avec une quotit&eacute; &agrave; '.$_SESSION[ 'solde' ][ 'QUOTITE' ].', '.$calc_deduc.' jours devraient &ecirc;tre d&eacute;duits.<br>';
			echo $_SESSION['conge']['nb_jours_ouvres_recalc'].' jours seront d&eacute;duits lors de la validation.';
			echo '</td></tr>';
		}
		else
		{
			echo '<tr><td><strong>Nombre de jours ouvr&eacute;</strong></td><td>'.$_SESSION['conge']['nb_jours_ouvres'].'j</td></tr>';
		}
	
	}
//champ de commentaire
	if(!empty($_SESSION[ 'conge' ][ 'commentaire' ]))
	{
		echo '<tr><td colspan=2><br/></td></tr>';
		echo '<tr><td colspan=2 class="centrer gras">Commentaire</td></tr>';
		echo '<tr><td colspan=2>'.$_SESSION[ 'conge' ][ 'commentaire' ].'</td></tr>';
		echo '<tr><td colspan=2><hr/></td></tr>';
	}
	echo '<tr><td colspan=2><br/></td></tr>';
}
//Etat du conge
	//if ($_SESSION[ 'correspondance' ]['valid_conges'][$_SESSION['groupe_indice']]==1
	//	&& $_SESSION[ 'conge' ][ 'valide' ]==0)
 	//echo '<tr><td class="gras">Demande de cong&eacute;</td></tr>';
	//else 
	if ($_SESSION[ 'correspondance' ]['valid_conges'][$_SESSION['groupe_indice']]==1
		&& $_SESSION[ 'conge' ][ 'valide' ]==1)
	echo '<tr><td colspan=2>Demande de cong&eacute;  <span class="gras">valid&eacute;e</span>.</td></tr>';
	else if ($_SESSION[ 'conge' ][ 'valide' ]==-1)
 	echo '<tr><td colspan=2>Demande de cong&eacute; <span class="gras">annul&eacute;e</span>.</td></tr>';

//l'administrateur peut annuler un congé s'il a été validé. Dans ce cas les congés déduits seront reattribués
	if (($_SESSION[ 'edition' ] != 1) && ($_SESSION[ 'conge' ][ 'valide' ]==1) && ($_SESSION[ 'connection' ][ 'admin' ]==1))
	{
		echo '<tr class="centrer"><td colspan=2>';
		echo '<input type="submit" name="conge[reattribuer]" value=" Annuler le cong&eacute; valid&eacute; et reattribuer les jours d&eacute;duits" onClick="return confirm(\'Etes-vous s&ucirc;r(e) de vouloir annuler ce cong&eacute; valid&eacute;?\');"></td></tr>';
	}

//Annuler, valider, editer nouveau / envoyer, annuler saisie / annuler, editer nouveau
	$temps=explode("/",$_SESSION[ 'conge' ][ 'date_fin' ]);
	if ($_SESSION[ 'correspondance' ]['valid_conges'][$_SESSION['groupe_indice']]==1
	&& $_SESSION[ 'conge' ][ 'valide' ]==0
	&& $_SESSION[ 'edition' ] != 1
	&& (($_SESSION[ 'connection' ][ 'utilisateur' ]==$_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']] && $_SESSION[ 'connection' ][ 'utilisateur' ]!= $_SESSION[ 'conge' ][ 'utilisateur' ]) || ($_SESSION[ 'connection' ][ 'utilisateur' ]==$directeur) || ($_SESSION[ 'connection' ][ 'admin' ]==1)))
	{
		if (isset($_SESSION[ 'conge' ][ 'date_PM' ]) && time()<mktime($_SESSION[ 'conge' ][ 'date_PM' ]*6+12,0,0,$temps[1],$temps[0],$temps[2])
		|| ($_SESSION[ 'connection' ][ 'admin' ]==1)) //il peut encore annuler et valider
		    echo '<tr class="centrer"><td colspan=2><input type="submit" class="bouton_conges" name="conge[annuler]" value=" Annuler la demande de cong&eacute; " onClick="return confirm(\'Etes-vous s&ucirc;r(e) de vouloir annuler cette demande de cong&eacute;?\');"><input type="submit" class="bouton_conges" name="conge[valider]" value=" Valider la demande de cong&eacute; " onClick="return confirm(\'Etes-vous s&ucirc;r(e) de vouloir valider cette demande de cong&eacute;?\');"></td></tr>';
		echo '<tr><td colspan=2><input type="submit" class="bouton_conges" name="conge[nouvelle]" value=" Editer un nouveau cong&eacute; " ></td></tr>';
	}
	else
	if ($_SESSION[ 'edition' ] == 1)
	{
		echo '<tr class="centrer"><td colspan=2>';
		$checked="";
		if ($_SESSION[ 'mailGroupe' ]==1) $checked="checked";
		echo '<input type="hidden" name="mailGroupe" value="0" />';
		echo '<input type="checkbox" name="mailGroupe" id="mailGroupe" value="1" '.$checked.'/><label for="mailGroupe"> Informer par mail les membres du groupe '.$_SESSION[ 'connection' ][ 'groupe' ].'.</label><br>';

		$aqui=" Votre demande sera envoy&eacute;e  &agrave; l\'administrateur des cong&eacute;s";
		//aqui sera envoye la demande
		if ($directeur!=$_SESSION[ 'connection' ][ 'utilisateur' ])
		{
		$util=$_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']];
		if ($_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']] == $_SESSION[ 'connection' ][ 'utilisateur' ]) $util=$directeur;
		$aqui.=', &agrave '.$util;
		}
		$aqui.=' et &agrave; vous.';

		echo '<input type="submit" class="bouton_conges" name="conge[envoyer]" id="conge_envoyer" value=" Envoyer une demande de cong&eacute; " onClick="return confirm(\'Etes-vous s&ucirc;r(e) de vouloir envoyer cette demande de cong&eacute;?'.$aqui.'\');">';
		echo '<input type="submit" class="bouton_conges" name="conge[saisie]" id="conge_annuler" value=" Annuler saisie " >';
		echo '</td></tr>';
	}
	else 
	{
		echo '<tr class="centrer"><td colspan=2>';
	//Tant que la date de fin n'est pas depassee, le demandeur peut annuler le conge:
		if ($_SESSION[ 'connection' ][ 'utilisateur' ]==$_SESSION[ 'conge' ][ 'utilisateur' ] && $_SESSION[ 'conge' ][ 'valide' ]!=-1 && $_SESSION[ 'conge' ][ 'type']!=1 )
		if (isset($_SESSION[ 'conge' ][ 'date_PM' ]) && time()<mktime($_SESSION[ 'conge' ][ 'date_PM' ]*6+12,0,0,$temps[1],$temps[0],$temps[2]))
		echo '<input type="submit" class="bouton_conges" name="conge[annuler]" value=" Annuler la demande de cong&eacute; " onClick="return confirm(\'Etes-vous s&ucirc;r(e) de vouloir annuler cette demande de cong&eacute;?\');">';
		echo '<input type="submit" class="bouton_conges" name="conge[nouvelle]" value=" Editer un nouveau cong&eacute; " ></td></tr>';
	}

	if (isset($message_demande)) echo '<tr><td colspan=2><b>'.$message_demande.'</b></td></tr>';
	echo '</tr></td>';
	echo '</table><br/>';

//////////////PIED DE PAGE/////////////////
include "pied_page.php";

?>
