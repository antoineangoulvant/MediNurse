<?php

//Affichage des erreurs en mode test
include 'config.php';
if($mode_test == 1 && !isset($_GET[ 'disconnect' ]))//Probleme de deconnexion avec CAS sinon
{
	ini_set('display_errors', 1);
	error_reporting(E_ALL);
}

/**
* Gestion des demandes de missions.
*
* La page des missions permet d'effectuer une demande de mission et de rechercher des missions.
*
* Date de création : 27 Fevrier 2008<br>
* Date de dernière modification : 19 Mai 2015
* @version 3.0.0
* @author Emmanuel Delage, Cedric Gagnevin <cedric.gagnevin@laposte.net>
* @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.html
* @copyright CNRS (c) 2015
* @package phpMyLab
* @subpackage Missions
*/

/*********************************************************************************
******************************  PLAN     *****************************************
*********************************************************************************/

//    | -A- Fonctions
//    | -B- Initialisation generale (configuration et php)
//    | -C- Initialisation Session et variables
//    | -D- Gestion des variables de recherche
//    | -E- Gestion des variables de mission
//    | -F- Fonctionnalites (MySQL)
//    | F1- Recherche simple des missions
//    | F2- Recherche evoluee
//    | F3- Reinitialiser page suivante et precedente la Recherche evoluee
//    | F4- Ajout d'une demande de mission 
//    | F5- Nouvelle demande mission
//    | F6- Valider demande mission
//    | F7- Annuler demande mission
//    | -G- Choix du module
//    | -H- HTML
//    | H1- Affichage Recherche Demande
//    | H2- Affichage des resultats de recherche evoluee
//    | H3- Affichage des resultats de recherche graphique/calendaire
//    | H4- Partie demande de mission


/*********************************************************************************
***********************  -A- Gestion de la déconnexion ***************************
**********************************************************************************/

if (isset($_REQUEST[ 'disconnect' ]) && file_exists("CAS/config_cas.php"))
{
	//On détruit la session
	session_regenerate_id();	
	session_unset();
	session_destroy();
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
* Envoyer un mail.
*
* Formate le message à envoyer de manière à être compatible avec la majorité des clients de messagerie et envoie le message au moyen de la fonction mail().
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
  $mail_sent = mail($to, $subject, $msg, $headers);
 
  ini_restore('sendmail_from');
 
  return $mail_sent;
}

/**
* Initialise des variables de mission
*/
function init_mission()
{
	$_SESSION[ 'mission' ][ 'id_mission' ] = 0;
	$_SESSION[ 'mission' ][ 'groupe' ] = '';
	$_SESSION[ 'mission' ][ 'depart' ] = '';
	$_SESSION[ 'mission' ][ 'destination' ] = '';
	$_SESSION[ 'mission' ][ 'objet' ] = '';
	$_SESSION[ 'mission' ][ 'type' ] = "0";
	$_SESSION[ 'mission' ][ 'transport' ] = '';
	$_SESSION[ 'mission' ][ 'date_aller' ] = strftime("%d/%m/%Y");
	$_SESSION[ 'mission' ][ 'heure_dep_aller' ] = 9;
	$_SESSION[ 'mission' ][ 'heure_arr_aller' ] = 13;
	$_SESSION[ 'mission' ][ 'date_retour' ] = strftime("%d/%m/%Y");
	$_SESSION[ 'mission' ][ 'heure_dep_retour' ] = 18;
	$_SESSION[ 'mission' ][ 'heure_arr_retour' ] = 22;
	$_SESSION[ 'mission' ][ 'valide' ] = 2;
	$_SESSION[ 'mission' ][ 'commentaire' ] = '';
	$_SESSION[ 'mission' ][ 'estimation_cout' ] = '';
}

/**
* Retourne un tableau avec les noms et prénoms des membres d'un groupe pour un SELECT
*
* @param string groupe 
* @param string chemin du fichier contenant les variables de connection de la base de données 
* @return array tableau des membres d'un groupe
*/
function membre_groupe($utilisateur, $chem_conn)
{
   $groupe_nm=array();
   $groupe_nm[0]="NULL,Nom,Pr&eacute;nom";

/**
**/
   include($chem_conn);

   $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
   or die('Could not connect: ' . mysqli_connect_error());
   mysqli_select_db($link,$mysql_base) or die('Could not select database');
   $query = 'SELECT GROUPE FROM T_UTILISATEUR WHERE UTILISATEUR=\''.$utilisateur.'\'';
   $result = mysqli_query($link,$query) or die('Query '.$query.'| failed: ' . mysqli_error());
   if ($result)
   {
	$line = mysqli_fetch_array($result, MYSQL_NUM);
	$the_groupe=$line[0];
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
	mysqli_free_result($result);
   }
   mysqli_close($link);

   return $groupe_nm;
}


/*********************************************************************************
*************** -B- Initialisation generale (configuration et php) ***************
**********************************************************************************/

/**
**/
include 'config.php';

// Fix magic_quotes_gpc garbage
if (get_magic_quotes_gpc())
{ 
   function stripslashes_deep($value)
   {
	return (is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value));
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
**************************  -C- Initialisation Session et variables **************
**********************************************************************************/
// Initialize session ID
//$sid = '';
//if (isset($_REQUEST[ 'sid' ])) $sid = substr(trim(preg_replace('/[^a-f0-9]/', '', $_REQUEST[ 'sid' ])), 0, 13);
//if ($sid == '') $sid = uniqid('');

// Start PHP session
//session_id();
session_name('phpmylab');//mission
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



/*********************************************************************************
********************  -D- Gestion des variables Recherche ************************
**********************************************************************************/
if (! isset($_SESSION[ 'r_gui' ])) $_SESSION[ 'r_gui' ] = 0;
if (isset($_REQUEST[ 'r_gui' ])) $_SESSION[ 'r_gui' ] = $_REQUEST[ 'r_gui' ];
//contient l'id de la demande
if (! isset($_SESSION[ 'id_dem' ])) 
{
	$_SESSION[ 'id_dem' ] = 0;
	$_SESSION[ 'mission' ][ 'utilisateur' ] = $_SESSION[ 'connection' ][ 'utilisateur' ];
	$_SESSION[ 'mission' ][ 'nom' ] = $_SESSION[ 'connection' ][ 'nom' ];
	$_SESSION[ 'mission' ][ 'prenom' ] = $_SESSION[ 'connection' ][ 'prenom' ];

	init_mission();
}
if (isset($_REQUEST[ 'id_dem' ])) $_SESSION[ 'id_dem' ] = $_REQUEST[ 'id_dem' ];

/**
* Initialisation des variables de recherche des missions
*
* @param string Année actuelle 
*/
function init_recherche($an_en_cours)
{
	$_SESSION[ 'recherche' ][ 'nom' ] = '';
	$_SESSION[ 'recherche' ][ 'prenom' ] = '';
	$_SESSION[ 'recherche' ][ 'groupe' ] = $_SESSION[ 'correspondance' ]['groupe'][0];
	$_SESSION[ 'recherche' ][ 'destination' ] = '';
	$_SESSION[ 'recherche' ][ 'date_aller' ] = '';
	$_SESSION[ 'recherche' ][ 'date_retour' ] = '';
	$_SESSION[ 'recherche' ][ 'date' ] = 4;
	$_SESSION[ 'recherche' ][ 'annee' ] = $an_en_cours;

	$_SESSION['recherche']['resultats']=0;
	$_SESSION['recherche']['limitebasse']=0;
	$_SESSION['recherche']['nb_par_page']=10;
}

if (! isset($_SESSION[ 'recherche' ]))
{
	init_recherche($annee_en_cours);
}

if (! isset($_SESSION[ 'recherche' ][ 'groupe_nom_prenom' ])) $_SESSION[ 'recherche' ][ 'groupe_nom_prenom' ]=-1;

if (isset($_REQUEST[ 'recherche' ][ 'nom' ])) $_SESSION[ 'recherche' ][ 'nom' ] = $_REQUEST[ 'recherche' ][ 'nom' ];
if (isset($_REQUEST[ 'recherche' ][ 'prenom' ])) $_SESSION[ 'recherche' ][ 'prenom' ] = $_REQUEST[ 'recherche' ][ 'prenom' ];
if (isset($_REQUEST[ 'recherche' ][ 'groupe' ])) $_SESSION[ 'recherche' ][ 'groupe' ] = $_REQUEST[ 'recherche' ][ 'groupe' ];
if (isset($_REQUEST[ 'recherche' ][ 'groupe_nom_prenom' ])) $_SESSION[ 'recherche' ][ 'groupe_nom_prenom' ] = $_REQUEST[ 'recherche' ][ 'groupe_nom_prenom' ];
if (isset($_REQUEST[ 'recherche' ][ 'destination' ])) $_SESSION[ 'recherche' ][ 'destination' ] = $_REQUEST[ 'recherche' ][ 'destination' ];
if (isset($_REQUEST[ 'recherche' ][ 'date_aller' ])) $_SESSION[ 'recherche' ][ 'date_aller' ] = $_REQUEST[ 'recherche' ][ 'date_aller' ];
if (isset($_REQUEST[ 'recherche' ][ 'date_retour' ])) $_SESSION[ 'recherche' ][ 'date_retour' ] = $_REQUEST[ 'recherche' ][ 'date_retour' ];
if (isset($_REQUEST[ 'recherche' ][ 'date' ])) $_SESSION[ 'recherche' ][ 'date' ] = $_REQUEST[ 'recherche' ][ 'date' ];
if (isset($_REQUEST[ 'recherche' ][ 'annee' ])) $_SESSION[ 'recherche' ][ 'annee' ] = $_REQUEST[ 'recherche' ][ 'annee' ];

if (! isset($_SESSION['groupe_nom_prenom_indice_recherche'])) $_SESSION['groupe_nom_prenom_indice_recherche']=0;

if (isset($_REQUEST[ 'uneannee' ])) $_SESSION[ 'uneannee' ] = $_REQUEST[ 'uneannee' ];

/*********************************************************************************
********************  -E- Gestion des variables de mission ***********************
**********************************************************************************/
//peut-etre inutile puisque deja dans "if (! isset($_SESSION[ 'connection' ]))"
if (!isset($_SESSION[ 'mission' ]))
{
	init_mission();
}

if (isset($_REQUEST[ 'mission' ]))  if (is_array($_REQUEST[ 'mission' ]))
{
if (isset($_REQUEST[ 'mission' ][ 'id_demande' ])) $_SESSION[ 'mission' ][ 'id_demande' ] = $_REQUEST[ 'mission' ][ 'id_demande' ];
if (isset($_REQUEST[ 'mission' ][ 'groupe' ])) $_SESSION[ 'mission' ][ 'groupe' ] = $_REQUEST[ 'mission' ][ 'groupe' ];
if (isset($_REQUEST[ 'mission' ][ 'depart' ])) $_SESSION[ 'mission' ][ 'depart' ] = $_REQUEST[ 'mission' ][ 'depart' ];
if (isset($_REQUEST[ 'mission' ][ 'destination' ])) $_SESSION[ 'mission' ][ 'destination' ] = $_REQUEST[ 'mission' ][ 'destination' ];
if (isset($_REQUEST[ 'mission' ][ 'objet' ])) $_SESSION[ 'mission' ][ 'objet' ] = $_REQUEST[ 'mission' ][ 'objet' ];
if (isset($_REQUEST[ 'mission' ][ 'type' ])) $_SESSION[ 'mission' ][ 'type' ] = $_REQUEST[ 'mission' ][ 'type' ];
if (isset($_REQUEST[ 'mission' ][ 'transport' ])) $_SESSION[ 'mission' ][ 'transport' ] = $_REQUEST[ 'mission' ][ 'transport' ];
if (isset($_REQUEST[ 'mission' ][ 'date_aller' ])) $_SESSION[ 'mission' ][ 'date_aller' ] = $_REQUEST[ 'mission' ][ 'date_aller' ];
if (isset($_REQUEST[ 'mission' ][ 'heure_dep_aller' ])) $_SESSION[ 'mission' ][ 'heure_dep_aller' ] = $_REQUEST[ 'mission' ][ 'heure_dep_aller' ];
if (isset($_REQUEST[ 'mission' ][ 'heure_arr_aller' ])) $_SESSION[ 'mission' ][ 'heure_arr_aller' ] = $_REQUEST[ 'mission' ][ 'heure_arr_aller' ];
if (isset($_REQUEST[ 'mission' ][ 'date_retour' ])) $_SESSION[ 'mission' ][ 'date_retour' ] = $_REQUEST[ 'mission' ][ 'date_retour' ];
if (isset($_REQUEST[ 'mission' ][ 'heure_dep_retour' ])) $_SESSION[ 'mission' ][ 'heure_dep_retour' ] = $_REQUEST[ 'mission' ][ 'heure_dep_retour' ];
if (isset($_REQUEST[ 'mission' ][ 'heure_arr_retour' ])) $_SESSION[ 'mission' ][ 'heure_arr_retour' ] = $_REQUEST[ 'mission' ][ 'heure_arr_retour' ];
if (isset($_REQUEST[ 'mission' ][ 'commentaire' ])) $_SESSION[ 'mission' ][ 'commentaire' ] = $_REQUEST[ 'mission' ][ 'commentaire' ];
if (isset($_REQUEST[ 'mission' ][ 'estimation_cout' ])) $_SESSION[ 'mission' ][ 'estimation_cout' ] = $_REQUEST[ 'mission' ][ 'estimation_cout' ];
}//fin de if (isset($_REQUEST[ 'mission' ]))  if (is_array($_REQUEST[ 'mission' ]))

if (!isset($_SESSION[ 'vehicule' ])) $_SESSION[ 'vehicule' ]='';
if (isset($_REQUEST[ 'vehicule' ])) $_SESSION[ 'vehicule' ] = $_REQUEST[ 'vehicule' ];
//Comparaison des 6 premier caracteres
if (strncmp($_SESSION[ 'vehicule' ],$vehicules[0],6)!=0)
{
   $_SESSION[ 'mission' ][ 'transport' ] = $_SESSION[ 'vehicule' ];
}

if (!isset($_SESSION[ 'objet' ])) $_SESSION[ 'objet' ]='';
if (isset($_REQUEST[ 'objet' ])) $_SESSION[ 'objet' ] = $_REQUEST[ 'objet' ];
//Comparaison des 6 premier caracteres
if (strncmp($_SESSION[ 'objet' ],$objets[0],6)!=0)
{
   $_SESSION[ 'mission' ][ 'objet' ] = $_SESSION[ 'objet' ];
}

/*********************************************************************************
******************     -F- Fonctionnalites (MySQL)     **************************
**********************************************************************************/
////////////////////////// F1 - Recherche simple des missions /////////////////////

if ($_SESSION[ 'connection' ][ 'status' ] !=0)
if ( isset($_REQUEST[ 'rechercher' ]) || isset($_GET["dem"]))
{
   if (isset($_GET["dem"])) $_SESSION["id_dem"]=$_GET["dem"];
   $annule=0;


   include $chemin_connection;

   $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
   mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

   $message_rechercher= '';
//cherche dans la base si cette demande existe:
  if (!$annule)
  {

	$a_rajouter='';
	$ind=$_SESSION["id_dem"];
	$query = 'SELECT * FROM T_MISSION WHERE ID_MISSION='.$ind.' '.$a_rajouter;
	$result = mysqli_query($link,$query) or die('Requete de selection d\'une mission: ' . mysqli_error());
	if ($result)
	{
	   $line = mysqli_fetch_array($result, MYSQL_ASSOC);
	   if ($line)
	   {
		$accordeledroit=0;
	//le chef de service qui peut regarder la mission déclarée pour
	//une équipe par un de ses ITAs 
		if ($_SESSION[ 'connection' ][ 'status' ]==4)
		{
		$query2 = 'SELECT GROUPE FROM T_UTILISATEUR WHERE UTILISATEUR=\''.$line["UTILISATEUR"].'\'';
		$result2 = mysqli_query($link,$query2);
		$line2 = mysqli_fetch_array($result2, MYSQL_NUM);
		if ($line2[0]==$_SESSION[ 'connection' ][ 'groupe' ]) $accordeledroit=1;
		mysqli_free_result($result2);
		}
	//le chef d'equipe peut regarder les missions pour ses lignes budgetaires.
	//le responsable2 (mission) peut aussi regarder
		if ($_SESSION[ 'connection' ][ 'status' ]==3 || $_SESSION[ 'connection' ][ 'status' ]==1)
		{
        	$query2 = 'SELECT RESPONSABLE,RESPONSABLE2 FROM T_CORRESPONDANCE WHERE GROUPE=\''.$line["GROUPE"].'\'';
		$result2 = mysqli_query($link,$query2);
		$line2 = mysqli_fetch_array($result2, MYSQL_NUM);
		if ($line2[0]==$_SESSION[ 'connection' ][ 'utilisateur' ]) $accordeledroit=1;
		if ($line2[1]==$_SESSION[ 'connection' ][ 'utilisateur' ]) $accordeledroit=1;
		mysqli_free_result($result2);
		}
	//ajout pour eviter que les statuts 1 et 2 accèdent aux informations des autres usagés
	//ajout pour eviter que les statuts 3 et 4 accèdent aux informations des autres groupes
		if ( (($_SESSION[ 'connection' ][ 'status' ] <= 2) && ($_SESSION[ 'connection' ][ 'utilisateur' ]== $line["UTILISATEUR"]))
		|| (($_SESSION[ 'connection' ][ 'status' ] > 2 && $_SESSION[ 'connection' ][ 'status' ] < 5) && (($_SESSION[ 'connection' ][ 'groupe' ]== $line["GROUPE"]) || ($_SESSION[ 'connection' ][ 'utilisateur' ]== $line["UTILISATEUR"])))
		|| ($accordeledroit==1)
		|| ($_SESSION[ 'connection' ][ 'status' ] >= 5) )
		{
		//si on trouve une demande, on peut empecher l'edition:
		$_SESSION[ 'edition' ] =0;
		//fermer le volet si une demande a ete touvee:
		$_SESSION[ 'r_gui' ]=0;
		//raffraichissement de l'affichage avec la demande trouvee
		$_SESSION['mission']['id_mission']=$line["ID_MISSION"];
		$_SESSION['mission']['utilisateur']=$line["UTILISATEUR"];
		//Rechercher le PRENOM et le SS a partir de UTILISATEUR dans T_UTILISATEUR//
		$query2 = 'SELECT NOM,PRENOM,SS FROM T_UTILISATEUR WHERE UTILISATEUR=\''.$_SESSION['mission']['utilisateur'].'\'';
		$result2 = mysqli_query($link,$query2);
		$line2 = mysqli_fetch_array($result2, MYSQL_NUM);
		$_SESSION['mission']['nom']=ucwords(strtolower($line2[0]));
		$_SESSION['mission']['prenom']=ucwords(strtolower($line2[1]));
		$_SESSION['mission']['ss']=$line2[2];
		mysqli_free_result($result2);
		//autres infos
		$_SESSION['mission']['groupe']=$line["GROUPE"];
		$_SESSION['mission']['depart']=$line["DEPART"];
		$_SESSION['mission']['destination']=$line["DESTINATION"];
		$_SESSION['mission']['objet']=$line["OBJET"];
		$_SESSION['mission']['type']=$line["TYPE"];
		$_SESSION['mission']['transport']=$line["TRANSPORT"];
		$_SESSION['mission']['estimation_cout']=$line["ESTIMATION_COUT"];
		//Dates & heures
		$query2 = 'SELECT DATE_FORMAT(\''.$line["ALLER_DATE"].'\',\'%d/%m/%Y\');';
		$result2 = mysqli_query($link,$query2);
		$line2 = mysqli_fetch_array($result2, MYSQL_NUM);
		$_SESSION['mission']['date_aller']=$line2[0];
		mysqli_free_result($result2);
		$_SESSION['mission']['heure_dep_aller']=$line["ALLER_H_DEPART"];
		$_SESSION['mission']['heure_arr_aller']=$line["ALLER_H_ARRIVEE"];
		$query2 = 'SELECT DATE_FORMAT(\''.$line["RETOUR_DATE"].'\',\'%d/%m/%Y\');';
		$result2 = mysqli_query($link,$query2);
		$line2 = mysqli_fetch_array($result2, MYSQL_NUM);
		$_SESSION['mission']['date_retour']=$line2[0];
		mysqli_free_result($result2);
		$_SESSION['mission']['heure_dep_retour']=$line["RETOUR_H_DEPART"];
		$_SESSION['mission']['heure_arr_retour']=$line["RETOUR_H_ARRIVEE"];
		//Autres infos
		$_SESSION['mission']['commentaire']=$line["COMMENTAIRE"];
		$_SESSION['mission']['valide']=$line["VALIDE"];
		}//fin du if chef ...
		else
		{
		$message_rechercher .= '<span class="rouge">L\'identifiant de la mission est inaccessible.</span>';
		$annule=1;
        	}

	   }//fin de if ($line)
	   else
	   {
		$message_rechercher .= '<span class="rouge">L\'indentifiant de la mission est introuvable.</span>';
		$annule=1;
           }
     mysqli_free_result($result);
	}//fin de if result
  }//fin de if (!$annule)
}


////////////////////////// F2 - Recherche evoluee ////////////////////////////
/**
* Retourne un tableau avec les noms et prénoms des membres d'un groupe pour un SELECT
*
* @param string chemin du fichier contenant les variables de connection de la base de données 
*/
function rech_evol($chem_conn)
{
   $message_evolue = '';

/**
**/
   include($chem_conn);

/**
**/
   include 'config.php';//pour avoir l'annee!!!!

   $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
   mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

//cherche dans la base si cette demande existe:
   if (!isset($annule) OR !$annule)
   {
	$ind=$_SESSION["id_dem"];

	$where='';
	$where_nom='';
	$where_prenom='';
	$where_groupe='';
	$where_dest='';
	$where_annee='';

	if ($_SESSION['recherche']['nom']!='') $where_nom='INSTR(T_UTILISATEUR.NOM,UPPER(\''.$_SESSION['recherche']['nom'].'\'))>0';
	if ($_SESSION['recherche']['prenom']!='') $where_prenom='INSTR(T_UTILISATEUR.PRENOM,UPPER(\''.$_SESSION['recherche']['prenom'].'\'))>0';
	if ($_SESSION[ 'recherche' ][ 'groupe' ] != '' && $_SESSION[ 'recherche' ][ 'groupe' ] !=$_SESSION['correspondance']['groupe'][0])
	$where_groupe='T_MISSION.GROUPE=\''.$_SESSION[ 'recherche' ][ 'groupe' ].'\'';
	if ($_SESSION['recherche']['destination']!='') $where_dest='INSTR(DESTINATION,UPPER(\''.$_SESSION['recherche']['destination'].'\'))>0';
	//echo $_SESSION['recherche']['annee'];echo 'e'.$annees[0];
	if ($_SESSION['recherche']['annee']!=$annees[0]) 
	$where_annee='YEAR(ALLER_DATE)=\''.$_SESSION['recherche']['annee'].'\'';
	$nb_clause=0;
	if ($where_nom!='' || $where_prenom!='' || $where_groupe!='' || $where_dest!='' || $where_annee!='') $where='WHERE ';
	if ($where_groupe!='') {$where.=$where_groupe;$nb_clause++;}
	if ($where_nom!='') {if ($nb_clause==1) {$where.=' AND ';} $where.=$where_nom; $nb_clause++;}
	if ($where_prenom!='') {if ($nb_clause>=1) {$where.=' AND ';} $where.=$where_prenom; $nb_clause++;}
	if ($where_dest!='') {if ($nb_clause>=1) {$where.=' AND ';} $where.=$where_dest; $nb_clause++;}
	if ($where_annee!='') {if ($nb_clause>=1) {$where.=' AND ';} $where.=$where_annee; $nb_clause++;}

   ////////////////calcul du nombre de résultat pour la recherche
	$query = 'SELECT COUNT(*) FROM T_MISSION INNER JOIN T_UTILISATEUR ON T_MISSION.UTILISATEUR=T_UTILISATEUR.UTILISATEUR '.$where;//.' ORDER BY T_MISSION.RETOUR_DATE DESC'
	$result = mysqli_query($link,$query);
	$line = mysqli_fetch_array($result, MYSQL_NUM);
   //comparaison avec le nombre actuel au cas ou modification des parametres
   // de recherche et appui sur recherche suivant ou precedente
	if ($_SESSION['recherche']['resultats']!=$line[0]) $_SESSION['recherche']['limitebasse']=0;
	$_SESSION['recherche']['resultats']=$line[0];
	mysqli_free_result($result);

	$orderby='';
	if ($_SESSION['recherche']['date']==1) {$orderby='ORDER BY ALLER_DATE ASC';}
	else if ($_SESSION['recherche']['date']==2) {$orderby='ORDER BY ALLER_DATE DESC';}
	else if ($_SESSION['recherche']['date']==3) {$orderby='ORDER BY RETOUR_DATE ASC';}
	else if ($_SESSION['recherche']['date']==4) {$orderby='ORDER BY RETOUR_DATE DESC';}

	$query = 'SELECT ID_MISSION,NOM,PRENOM,T_MISSION.GROUPE,DESTINATION,ALLER_DATE,RETOUR_DATE,VALIDE FROM T_MISSION INNER JOIN T_UTILISATEUR ON T_MISSION.UTILISATEUR=T_UTILISATEUR.UTILISATEUR '.$where.' '.$orderby.' LIMIT '.$_SESSION['recherche']['limitebasse'].','.$_SESSION['recherche']['nb_par_page'].'';

	$result = mysqli_query($link,$query) or die('Requete de recherche evolue des missions: ' . mysqli_error());
	if ($result)
	{
	$i=1;
	while ($line = mysqli_fetch_array($result, MYSQL_ASSOC))
	{
	//raffraichissement de l'affichage avec la demande trouvee
	$_SESSION['recherche'][$i]['id_mission']=$line["ID_MISSION"];
	$_SESSION['recherche'][$i]['nom']=ucwords(strtolower($line["NOM"]));
	$_SESSION['recherche'][$i]['prenom']=ucwords(strtolower($line["PRENOM"]));
	$_SESSION['recherche'][$i]['groupe']=$line["GROUPE"];
	$_SESSION['recherche'][$i]['destination']=$line["DESTINATION"];
	$query2 = 'SELECT DATE_FORMAT(\''.$line["ALLER_DATE"].'\',\'%d/%m/%Y\');';
	$result2 = mysqli_query($link,$query2);
	$line2 = mysqli_fetch_array($result2, MYSQL_NUM);
	$_SESSION['recherche'][$i]['date_aller']=$line2[0];
	mysqli_free_result($result2);
	$query2 = 'SELECT DATE_FORMAT(\''.$line["RETOUR_DATE"].'\',\'%d/%m/%Y\');';
	$result2 = mysqli_query($link,$query2);
	$line2 = mysqli_fetch_array($result2, MYSQL_NUM);
	$_SESSION['recherche'][$i]['date_retour']=$line2[0];
	mysqli_free_result($result2);
	$_SESSION['recherche'][$i]['valide']=$line["VALIDE"];
	
	$i++;
	}//fin de while ($line)

	mysqli_free_result($result);
	
	if ($_SESSION['recherche']['resultats']==0)
	{
	$message_evolue = '<span class="rouge">Pas de mission correspondant &agrave; la recherche.</span>';
	$annule=1;
	}
	}//fin de if result
   }//fin de if !annule

   return $message_evolue;
}

//Initialisation du calendrier lors du premier chargement
if (!isset($_SESSION['firsttime_missions'])) 
{
	$_SESSION['firsttime_missions']=1;
	if ($_SESSION[ 'connection' ][ 'groupe' ]!='DIRECTION' && $_SESSION[ 'connection' ][ 'admin' ]!=1)
	{
		$_SESSION[ 'recherche' ][ 'nom' ]=$_SESSION[ 'connection' ][ 'nom' ];
		$_SESSION[ 'recherche' ][ 'prenom' ]=$_SESSION[ 'connection' ][ 'prenom' ];
	}
}
if ( isset($_REQUEST[ 'rechercher_evol' ]) || $_SESSION['firsttime_missions']==1)
{
	$_SESSION['recherche']['limitebasse']=0;
	$message_evolue= '';
	$message_evolue=rech_evol($chemin_connection);
	$_SESSION['firsttime_missions']=0;
}


if ( isset($_REQUEST[ 'rechercher_evol' ]) )
{
	$_SESSION['recherche']['limitebasse']=0;
	$message_evolue= '';
	$message_evolue=rech_evol($chemin_connection);
}

///////////////// F3 - Reinitialiser page suivante et precedente la Recherche evoluee //////////
if ( isset($_REQUEST[ 'reinitialiser' ]) )
{
	init_recherche($annee_en_cours);
}

if ( isset($_REQUEST[ 'rechercher_suiv' ]) )
{
	if ($_SESSION['recherche']['limitebasse'] +$_SESSION['recherche']['nb_par_page'] <$_SESSION['recherche']['resultats'])
	$_SESSION['recherche']['limitebasse']+=$_SESSION['recherche']['nb_par_page'];
	rech_evol($chemin_connection);
}

if ( isset($_REQUEST[ 'rechercher_prec' ]) )
{
	if ($_SESSION['recherche']['limitebasse']-$_SESSION['recherche']['nb_par_page']>=0)
	$_SESSION['recherche']['limitebasse']-=$_SESSION['recherche']['nb_par_page'];
	rech_evol($chemin_connection);
}

if ( isset($_REQUEST[ 'rechercher_tlm' ]))
{
	$_SESSION['recherche']['nom']='';
	$_SESSION['recherche']['prenom']='';
	rech_evol($chemin_connection);
}
/////////////////////// G4 - Ajout d'une demande ///////////////////////
if (isset($_REQUEST[ 'mission' ]['envoyer']))
{
/**
**/
   include $chemin_connection;

   $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysql_connect_error());
   mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

   $annule=0;

//decoupage des dates:
   list($jour1, $mois1, $annee1) = explode('/', $_SESSION['mission']['date_aller']); 
   list($jour2, $mois2, $annee2) = explode('/', $_SESSION['mission']['date_retour']);
   $timestamp1 = mktime($_SESSION['mission']['heure_arr_aller'],0,0,$mois1,$jour1,$annee1); 
   $timestamp2 = mktime($_SESSION['mission']['heure_dep_retour'],0,0,$mois2,$jour2,$annee2);

//lire les champs, des qu'un champ indispensable est manquant, on annule la procedure.
   if (($_SESSION['mission']['groupe']=='') || ($_SESSION[ 'correspondance' ]['groupe'][0] == $_SESSION['mission']['groupe'])) 
   {
	$message_demande= '<span class="rouge"><b>La partie "Choisir une &eacute;quipe" n\'est pas renseign&eacute;e.<br/>Action annul&eacute;e</b>.</span>';
	$annule=1;
   }
   elseif ($_SESSION[ 'mission' ][ 'depart' ]=='') 
   {
	$message_demande= '<span class="rouge">Le champs "D&eacute;part" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e.</span>';
	$annule=1;
   }
   elseif ($_SESSION[ 'mission' ][ 'destination' ]=='') 
   {
	$message_demande= '<span class="rouge">Le champs "Destination" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e.</span>';
	$annule=1;
   }
   elseif ($_SESSION[ 'mission' ][ 'objet' ]=='') 
   {
	$message_demande= '<span class="rouge">Le champs "Objet" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e.</span>';
	$annule=1;
   }
   elseif ($_SESSION[ 'mission' ][ 'type' ]=='0') 
   {
	$message_demande= '<span class="rouge">Le champs "Type" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e.</span>';
	$annule=1;
   }
   //pas necessaire de faire le type (type par defaut)
   elseif ($_SESSION[ 'mission' ][ 'transport' ]=='') 
   {
	$message_demande= '<span class="rouge">Le champs "Transport" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e.</span>';
	$annule=1;
   }
   elseif(!checkdate($mois1,$jour1,$annee1))
   {
	$message_demande= '<span class="rouge">Le champs "Date aller" est invalide.<br>Action annul&eacute;e.</span>';
	$annule=1;
   }
   elseif ($_SESSION['mission']['heure_dep_aller']<0 || $_SESSION['mission']['heure_dep_aller']>23) 
   {
	$message_demande= '<span class="rouge">Le champs "Heure de depart aller" doit &ecirc;tre compris entre 0 et 23 heures.<br/>Action annul&eacute;e.</span>';
	$annule=1;
   }
   elseif ($_SESSION['mission']['heure_arr_aller']<0 || $_SESSION['mission']['heure_arr_aller']>23) 
   {
	$message_demande= '<span class="rouge">Le champs "Heure de depart aller" doit &ecirc;tre compris entre 0 et 23 heures.<br/>Action annul&eacute;e.</span>';
	$annule=1;
   }
   elseif(!checkdate($mois2,$jour2,$annee2))
   {
	$message_demande= '<span class="rouge">Le champs "Date retour" est invalide.<br/>Action annul&eacute;e.</span>';
	$annule=1;
   }
   elseif ($_SESSION['mission']['heure_dep_retour']<0 || $_SESSION['mission']['heure_dep_retour']>23) 
   {
	$message_demande= '<span class="rouge">Le champs "Heure de depart retour" doit &ecirc;tre compris entre 0 et 23 heures.<br/>Action annul&eacute;e.</span>';
	$annule=1;
   }
   elseif ($_SESSION['mission']['heure_arr_retour']<0 || $_SESSION['mission']['heure_arr_retour']>23) 
   {
	$message_demande= '<span class="rouge">Le champs "Heure de depart retour" doit &ecirc;tre compris entre 0 et 23 heures.<br/>Action annul&eacute;e.</span>';
	$annule=1;
   }
//on verifie que le retour s'effectue apres l'arrivee!!! (les coquains)
   elseif($timestamp2<$timestamp1)
   {
	$message_demande= '<span class="rouge">Le retour ne peut pas s\'effectuer avant l\'aller...<br/>Action annul&eacute;e.</span>';
	$annule=1;
   }

   if (!$annule)
   {
   //recherche de l'indice a ajouter
   //la numerotation des (ID_) commence a 1
	$ind=1;
	$query = 'SELECT MAX(ID_MISSION) FROM T_MISSION';
	$result = mysqli_query($link,$query) or die('Requete de comptage des demandes: ' . mysqli_error());
	if ($result)
	{
		$line = mysqli_fetch_array($result, MYSQL_NUM);
		$ind=$line[0]+1;
		mysqli_free_result($result);
	}
	$_SESSION['mission']['id_mission']=$ind;

   //insertion de la demande de mission:
	$query = 'INSERT INTO T_MISSION(ID_MISSION,UTILISATEUR,GROUPE,DEPART,DESTINATION,OBJET,TYPE,TRANSPORT,ALLER_DATE,ALLER_H_DEPART,ALLER_H_ARRIVEE,RETOUR_DATE,RETOUR_H_DEPART,RETOUR_H_ARRIVEE,COMMENTAIRE,VALIDE,ESTIMATION_COUT)
 VALUES ('.$ind.',"'.$_SESSION['mission']['utilisateur'].'","'.$_SESSION['mission']['groupe'].'","'.$_SESSION['mission']['depart'].'","'.$_SESSION['mission']['destination'].'","'.$_SESSION['mission']['objet'].'","'.$_SESSION['mission']['type'].'","'.$_SESSION['mission']['transport'].'",STR_TO_DATE(\''.$_SESSION['mission']['date_aller'].'\',\'%d/%m/%Y\'),'.$_SESSION['mission']['heure_dep_aller'].','.$_SESSION['mission']['heure_arr_aller'].',STR_TO_DATE(\''.$_SESSION['mission']['date_retour'].'\',\'%d/%m/%Y\'),'.$_SESSION['mission']['heure_dep_retour'].','.$_SESSION['mission']['heure_arr_retour'].',"'.$_SESSION['mission']['commentaire'].'",0,"'.$_SESSION['mission']['estimation_cout'].'"  )';
	$result = mysqli_query($link,$query) or die('Requete d\'insertion echouee: ' . mysqli_error());
	//mysql_free_result($result);

	$message_demande= "<span class='gras vert'>-> Demande ajoutee <-</span>";	
	mysqli_close($link);
   }

   if (!$annule)
   {
	$subject = "MISSION: Nouvelle demande [".$_SESSION['mission']['groupe']."] (".$_SESSION['mission']['objet']." du ".$_SESSION['mission']['date_aller']." au ".$_SESSION['mission']['date_retour'].")";

   //demandeur de mission (quelquesoit son status)
	if ($mode_test) $TO = $mel_test;
	else $TO = $_SESSION['mission']['utilisateur'].'@'.$domaine;
	$message = "<body>Bonjour ".$_SESSION['mission']['nom']." ".$_SESSION['mission']['prenom'].",<br> votre demande de mission a été effectuée,<br> ";  
	$message .= "Suivez le lien <a href=".$chemin_mel."?dem=".$ind.">".$chemin_mel."?dem=".$ind."</a> pour l'afficher.<br><br>";
	$message .= '================================================================<br>';
	$message .= 'Mission du '.$_SESSION['mission']['date_aller'].' au '.$_SESSION['mission']['date_retour'].'<br>';
	$message .= '================================================================<br><br>';
	$message .= 'Nom : '.$_SESSION['mission']['nom'].'<br>';
	$message .= 'Prénom : '.$_SESSION['mission']['prenom'].'<br>';
	$message .= 'Equipe/Service : '.$_SESSION['mission']['groupe'].'<br>';
	$message .= 'Lieu de départ : '.utf8_encode($_SESSION['mission']['depart']).'<br>';
	$message .= 'Lieu de destination : '.utf8_encode($_SESSION['mission']['destination']).'<br>';
	$message .= 'Objet : '.utf8_encode($_SESSION['mission']['objet']).'<br>';
	if($_SESSION['mission']['type'] == 1) $message .= 'Frais : Avec<br>'; else $message .= 'Frais : Sans<br>';
	$message .= 'Moyen de transport : '.utf8_encode($_SESSION['mission']['transport']).'<br><br>';
	$message .= 'Aller :<br>Départ le '.$_SESSION['mission']['date_aller'].' a '.$_SESSION['mission']['heure_dep_aller'].'h<br>';
	$message .= 'Arrivée a '.$_SESSION['mission']['heure_arr_aller'].'h<br><br>';
	$message .= 'Retour :<br>Depart le '.$_SESSION['mission']['date_retour'].' a '.$_SESSION['mission']['heure_dep_retour'].'h<br>';
	$message .= 'Arrivée a '.$_SESSION['mission']['heure_arr_retour'].'h<br><br><br>';
	$message .= 'Commentaire :<br>'.utf8_encode($_SESSION['mission']['commentaire']).'<br><br>';	   
	$message .= 'Estimation du coût :<br>'.utf8_encode($_SESSION['mission']['estimation_cout']).'<br><br>';	   
	$message .= '================================================================<br></body>';  
	   
	   
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['mission']['utilisateur'].'@'.$domaine, $_SESSION['mission']['nom']." ".$_SESSION['mission']['prenom']);

   //au responsable d'equipe ou de service du groupe selectionné pour la mission
	if ($_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']]!=$_SESSION[ 'connection' ][ 'utilisateur' ])
	if ($_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']]!=$_SESSION[ 'correspondance' ]['administratif'][$_SESSION['groupe_indice']])
	{
	if ($mode_test) $TO = $mel_test;
	else $TO = $_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']].'@'.$domaine;
	$message = "<body>Bonjour utilisateur ".$_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']].",<br> ";
	$message .= "Suivez le lien <a href=".$chemin_mel."?dem=".$ind.">".$chemin_mel."?dem=".$ind."</a> pour afficher la nouvelle mission émise par ".$_SESSION['mission']['prenom']." ".$_SESSION['mission']['nom']." pour ".$_SESSION['mission']['groupe'].".<br><br>";
	$message .= '================================================================<br>';
	$message .= 'Mission du '.$_SESSION['mission']['date_aller'].' au '.$_SESSION['mission']['date_retour'].'<br>';
	$message .= '================================================================<br><br>';
	$message .= 'Nom : '.$_SESSION['mission']['nom'].'<br>';
	$message .= 'Prénom : '.$_SESSION['mission']['prenom'].'<br>';
	$message .= 'Equipe/Service : '.$_SESSION['mission']['groupe'].'<br>';
	$message .= 'Lieu de départ : '.utf8_encode($_SESSION['mission']['depart']).'<br>';
	$message .= 'Lieu de destination : '.utf8_encode($_SESSION['mission']['destination']).'<br>';
	$message .= 'Objet : '.utf8_encode($_SESSION['mission']['objet']).'<br>';
	if($_SESSION['mission']['type'] == 1) $message .= 'Frais : Avec<br>'; else $message .= 'Frais : Sans<br>';
	$message .= 'Moyen de transport : '.utf8_encode($_SESSION['mission']['transport']).'<br><br>';
	$message .= 'Aller :<br>Départ le '.$_SESSION['mission']['date_aller'].' a '.$_SESSION['mission']['heure_dep_aller'].'h<br>';
	$message .= 'Arrivée a '.$_SESSION['mission']['heure_arr_aller'].'h<br><br>';
	$message .= 'Retour :<br>Depart le '.$_SESSION['mission']['date_retour'].' a '.$_SESSION['mission']['heure_dep_retour'].'h<br>';
	$message .= 'Arrivée a '.$_SESSION['mission']['heure_arr_retour'].'h<br><br><br>';
	$message .= 'Commentaire :<br>'.utf8_encode($_SESSION['mission']['commentaire']).'<br><br>';	
	$message .= 'Estimation du coût :<br>'.utf8_encode($_SESSION['mission']['estimation_cout']).'<br><br>';	   
	$message .= '================================================================<br></body>'; 	
		
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['mission']['utilisateur'].'@'.$domaine, $_SESSION['mission']['nom']." ".$_SESSION['mission']['prenom']);
	}

   //au responsable2 d'equipe ou de service du groupe selectionné pour la mission
   //s'il existe
	if ($_SESSION[ 'correspondance' ]['responsable2'][$_SESSION['groupe_indice']]!='')
	if ($_SESSION[ 'correspondance' ]['responsable2'][$_SESSION['groupe_indice']]!= $_SESSION[ 'connection' ][ 'utilisateur' ])
	if ($_SESSION[ 'correspondance' ]['responsable2'][$_SESSION['groupe_indice']]!= $_SESSION[ 'correspondance' ]['administratif'][$_SESSION['groupe_indice']])
	{
	if ($mode_test) $TO = $mel_test;
	else $TO = $_SESSION[ 'correspondance' ]['responsable2'][$_SESSION['groupe_indice']].'@'.$domaine;
	$message = "<body>Bonjour utilisateur ".$_SESSION[ 'correspondance' ]['responsable2'][$_SESSION['groupe_indice']].",<br> ";
	$message .= "Suivez le lien <a href=".$chemin_mel."?dem=".$ind.">".$chemin_mel."?dem=".$ind."</a> pour afficher la nouvelle mission émise par ".$_SESSION['mission']['prenom']." ".$_SESSION['mission']['nom']." pour ".$_SESSION['mission']['groupe'].".<br><br>";
	$message .= '================================================================<br>';
	$message .= 'Mission du '.$_SESSION['mission']['date_aller'].' au '.$_SESSION['mission']['date_retour'].'<br>';
	$message .= '================================================================<br><br>';
	$message .= 'Nom : '.$_SESSION['mission']['nom'].'<br>';
	$message .= 'Prénom : '.$_SESSION['mission']['prenom'].'<br>';
	$message .= 'Equipe/Service : '.$_SESSION['mission']['groupe'].'<br>';
	$message .= 'Lieu de départ : '.utf8_encode($_SESSION['mission']['depart']).'<br>';
	$message .= 'Lieu de destination : '.utf8_encode($_SESSION['mission']['destination']).'<br>';
	$message .= 'Objet : '.utf8_encode($_SESSION['mission']['objet']).'<br>';
	if($_SESSION['mission']['type'] == 1) $message .= 'Frais : Avec<br>'; else $message .= 'Frais : Sans<br>';
	$message .= 'Moyen de transport : '.utf8_encode($_SESSION['mission']['transport']).'<br><br>';
	$message .= 'Aller :<br>Départ le '.$_SESSION['mission']['date_aller'].' a '.$_SESSION['mission']['heure_dep_aller'].'h<br>';
	$message .= 'Arrivée a '.$_SESSION['mission']['heure_arr_aller'].'h<br><br>';
	$message .= 'Retour :<br>Depart le '.$_SESSION['mission']['date_retour'].' a '.$_SESSION['mission']['heure_dep_retour'].'h<br>';
	$message .= 'Arrivée a '.$_SESSION['mission']['heure_arr_retour'].'h<br><br><br>';
	$message .= 'Commentaire :<br>'.utf8_encode($_SESSION['mission']['commentaire']).'<br><br>';
	$message .= 'Estimation du coût :<br>'.utf8_encode($_SESSION['mission']['estimation_cout']).'<br><br>';	   
	$message .= '================================================================<br></body>';   
		
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['mission']['utilisateur'].'@'.$domaine, $_SESSION['mission']['nom']." ".$_SESSION['mission']['prenom']);
	}

   //si la chaine de caractere est supérieur à 3
   //responsable d'equipe ou de service de l'utilisateur connecte
   //pas 2 fois la meme mail pour le responsable de groupe
	if (strlen($_SESSION[ 'responsable_groupe'])>3)
	if ($_SESSION[ 'connection' ][ 'status' ]>1)
	if ($_SESSION[ 'responsable_groupe']!=$_SESSION[ 'connection' ][ 'utilisateur' ])
	if ($_SESSION[ 'responsable_groupe']!=$_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']])
	{
	if ($mode_test) $TO = $mel_test;
	else $TO = $_SESSION[ 'responsable_groupe'].'@'.$domaine;
	$message = "<body>Bonjour utilisateur ".$_SESSION[ 'responsable_groupe'].",<br> ";
	$message .= "Suivez le lien <a href=".$chemin_mel."?dem=".$ind.">".$chemin_mel."?dem=".$ind."</a> pour afficher la nouvelle mission émise par ".$_SESSION['mission']['prenom']." ".$_SESSION['mission']['nom'].".<br><br>";
	$message .= '================================================================<br>';
	$message .= 'Mission du '.$_SESSION['mission']['date_aller'].' au '.$_SESSION['mission']['date_retour'].'<br>';
	$message .= '================================================================<br><br>';
	$message .= 'Nom : '.$_SESSION['mission']['nom'].'<br>';
	$message .= 'Prénom : '.$_SESSION['mission']['prenom'].'<br>';
	$message .= 'Equipe/Service : '.$_SESSION['mission']['groupe'].'<br>';
	$message .= 'Lieu de départ : '.utf8_encode($_SESSION['mission']['depart']).'<br>';
	$message .= 'Lieu de destination : '.utf8_encode($_SESSION['mission']['destination']).'<br>';
	$message .= 'Objet : '.utf8_encode($_SESSION['mission']['objet']).'<br>';
	if($_SESSION['mission']['type'] == 1) $message .= 'Frais : Avec<br>'; else $message .= 'Frais : Sans<br>';
	$message .= 'Moyen de transport : '.utf8_encode($_SESSION['mission']['transport']).'<br><br>';
	$message .= 'Aller :<br>Départ le '.$_SESSION['mission']['date_aller'].' a '.$_SESSION['mission']['heure_dep_aller'].'h<br>';
	$message .= 'Arrivée a '.$_SESSION['mission']['heure_arr_aller'].'h<br><br>';
	$message .= 'Retour :<br>Depart le '.$_SESSION['mission']['date_retour'].' a '.$_SESSION['mission']['heure_dep_retour'].'h<br>';
	$message .= 'Arrivée a '.$_SESSION['mission']['heure_arr_retour'].'h<br><br><br>';
	$message .= 'Commentaire :<br>'.utf8_encode($_SESSION['mission']['commentaire']).'<br><br>';
	$message .= 'Estimation du coût :<br>'.utf8_encode($_SESSION['mission']['estimation_cout']).'<br><br>';	   	   
	$message .= '================================================================<br></body>';   	
		
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['mission']['utilisateur'].'@'.$domaine, $_SESSION['mission']['nom']." ".$_SESSION['mission']['prenom']);
	}

   //a(ux) l'administratif(s) concerné(s)
	if ($mode_test) $TO = $mel_test;
	else $TO = $mel_gestiolab;
	$message = "<body>Bonjour utilisateur ".$_SESSION[ 'correspondance' ]['administratif'][$_SESSION['groupe_indice']].",<br> ";
	$message .= "Suivez le lien <a href=".$chemin_mel."?dem=".$ind.">".$chemin_mel."?dem=".$ind."</a> pour afficher la nouvelle mission.<br><br>";
	$message .= '================================================================<br>';
	$message .= 'Mission du '.$_SESSION['mission']['date_aller'].' au '.$_SESSION['mission']['date_retour'].'<br>';
	$message .= '================================================================<br><br>';
	$message .= 'Nom : '.$_SESSION['mission']['nom'].'<br>';
	$message .= 'Prénom : '.$_SESSION['mission']['prenom'].'<br>';
	$message .= 'Equipe/Service : '.$_SESSION['mission']['groupe'].'<br>';
	$message .= 'Lieu de départ : '.utf8_encode($_SESSION['mission']['depart']).'<br>';
	$message .= 'Lieu de destination : '.utf8_encode($_SESSION['mission']['destination']).'<br>';
	$message .= 'Objet : '.utf8_encode($_SESSION['mission']['objet']).'<br>';
	if($_SESSION['mission']['type'] == 1) $message .= 'Frais : Avec<br>'; else $message .= 'Frais : Sans<br>';
	$message .= 'Moyen de transport : '.utf8_encode($_SESSION['mission']['transport']).'<br><br>';
	$message .= 'Aller :<br>Départ le '.$_SESSION['mission']['date_aller'].' a '.$_SESSION['mission']['heure_dep_aller'].'h<br>';
	$message .= 'Arrivée a '.$_SESSION['mission']['heure_arr_aller'].'h<br><br>';
	$message .= 'Retour :<br>Depart le '.$_SESSION['mission']['date_retour'].' a '.$_SESSION['mission']['heure_dep_retour'].'h<br>';
	$message .= 'Arrivée a '.$_SESSION['mission']['heure_arr_retour'].'h<br><br><br>';
	$message .= 'Commentaire :<br>'.utf8_encode($_SESSION['mission']['commentaire']).'<br><br>';	   
	$message .= 'Estimation du coût :<br>'.utf8_encode($_SESSION['mission']['estimation_cout']).'<br><br>';	   
	$message .= '================================================================<br></body>';    
	   
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['mission']['utilisateur'].'@'.$domaine, $_SESSION['mission']['nom']." ".$_SESSION['mission']['prenom']);

//DEBUT Pour le correspondant formation (spécial LPC)
//Cas qu'il est possible de commenter
if (($_SESSION['mission']['objet']=='Formation permanente') ||
(utf8_encode($_SESSION['mission']['objet'])=='Ecole thématique') ||
($_SESSION['mission']['objet']=='Colloque'))
{
	if ($mode_test) $TO = $mel_test;
	else $TO = $correspond_formation;
	$message = "<body>Bonjour Michèle,<br> ";
	$message .= "Suivez le lien <a href=".$chemin_mel."?dem=".$ind.">".$chemin_mel."?dem=".$ind."</a> pour afficher la nouvelle mission.<br><br>";
	$message .= '================================================================<br>';
	$message .= 'Mission du '.$_SESSION['mission']['date_aller'].' au '.$_SESSION['mission']['date_retour'].'<br>';
	$message .= '================================================================<br><br>';
	$message .= 'Nom : '.$_SESSION['mission']['nom'].'<br>';
	$message .= 'Prénom : '.$_SESSION['mission']['prenom'].'<br>';
	$message .= 'Equipe/Service : '.$_SESSION['mission']['groupe'].'<br>';
	$message .= 'Lieu de départ : '.utf8_encode($_SESSION['mission']['depart']).'<br>';
	$message .= 'Lieu de destination : '.utf8_encode($_SESSION['mission']['destination']).'<br>';
	$message .= 'Objet : '.utf8_encode($_SESSION['mission']['objet']).'<br>';
	if($_SESSION['mission']['type'] == 1) $message .= 'Frais : Avec<br>'; else $message .= 'Frais : Sans<br>';
	$message .= 'Moyen de transport : '.utf8_encode($_SESSION['mission']['transport']).'<br><br>';
	$message .= 'Aller :<br>Départ le '.$_SESSION['mission']['date_aller'].' a '.$_SESSION['mission']['heure_dep_aller'].'h<br>';
	$message .= 'Arrivée a '.$_SESSION['mission']['heure_arr_aller'].'h<br><br>';
	$message .= 'Retour :<br>Depart le '.$_SESSION['mission']['date_retour'].' a '.$_SESSION['mission']['heure_dep_retour'].'h<br>';
	$message .= 'Arrivée a '.$_SESSION['mission']['heure_arr_retour'].'h<br><br><br>';
	$message .= 'Commentaire :<br>'.utf8_encode($_SESSION['mission']['commentaire']).'<br><br>';	   
	$message .= 'Estimation du coût :<br>'.utf8_encode($_SESSION['mission']['estimation_cout']).'<br><br>';	   
	$message .= '================================================================<br></body>';    
	   
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['mission']['utilisateur'].'@'.$domaine, $_SESSION['mission']['nom']." ".$_SESSION['mission']['prenom']);
}
//FIN Pour le correspondant formation (spécial LPC)
	$message_demande.= '<br/><span class="gras vert">-> Envoi de mails <-</span>';
  }

   if (!$annule)
   {
	//on re initialise les champs pour eviter d'effectuer plusieurs fois la meme mission
	init_mission();
   }
}

/////////////////////// F5 - Nouvelle demande mission///////////////////////
if (isset($_REQUEST[ 'mission' ]['nouvelle']))
{
	$_SESSION['edition']=1;
	$_SESSION[ 'r_gui' ]=0;//fermeture de la zone de recherche (21 avril 2009)
	$_SESSION[ 'mission' ][ 'utilisateur' ] = $_SESSION[ 'connection' ][ 'utilisateur' ];
	$_SESSION[ 'mission' ][ 'nom' ] = $_SESSION[ 'connection' ][ 'nom' ];
	$_SESSION[ 'mission' ][ 'prenom' ] = $_SESSION[ 'connection' ][ 'prenom' ];
	$_SESSION[ 'mission' ][ 'ss' ] = $_SESSION[ 'connection' ][ 'ss' ];

	init_mission();
}

/////////////////////// F6 - Valider demande mission///////////////////////
if (isset($_REQUEST[ 'mission' ]['valider']))
{
/**
**/
   include $chemin_connection;

   $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
   mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

   $annule=0;
   if (!$annule)
   {
	$ind=$_SESSION["id_dem"];
   //validation de la demande:
	$_SESSION['demande']['date_fin']=strftime("%d/%m/%Y");
	$query = 'UPDATE T_MISSION SET VALIDE="1" WHERE ID_MISSION="'.$ind.'"';
	$result = mysqli_query($link,$query) or die('Requete de modification du status: ' . mysqli_error());
	mysqli_free_result($result);
	$message_demande= "<span class='vert'>-> Demande valid&eacute;e <-</span>";	
	mysqli_close($link);
	$_SESSION['mission']['valide']=1;
   }

   if (!$annule)
   {
	$subject = "MISSION: Validation demande de mission (ID=".$ind.")";

	if ($mode_test) $TO = $mel_test;
	else $TO = $_SESSION['mission']['utilisateur'].'@'.$domaine;
	$message = "<body>Bonjour ".$_SESSION['mission']['nom']." ".$_SESSION['mission']['prenom'].",<br> votre demande de mission a été validée,<br> ";
	$message .= "suivez le lien <a href=".$chemin_mel."?dem=".$ind.">".$chemin_mel."?dem=".$ind."</a> pour l'afficher.</body>";
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['connection']['utilisateur'].'@'.$domaine, $_SESSION['connection']['nom']." ".$_SESSION['connection']['prenom']);

   //au responsable d'equipe ou de service du groupe selectionné pour la mission
	if ($_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']]!=$_SESSION[ 'mission' ][ 'utilisateur' ])
	if ($_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']]!=$_SESSION[ 'correspondance' ]['administratif'][$_SESSION['groupe_indice']])
	{
	$tempo=$_SESSION['groupe_indice'];
	if ($mode_test) $TO = $mel_test;
	else $TO = $_SESSION[ 'correspondance' ]['responsable'][$tempo].'@'.$domaine;
	$message = "<body>Bonjour utilisateur ".$_SESSION[ 'correspondance' ]['responsable'][$tempo].",<br> ";
	$message .= "la mission <a href=".$chemin_mel."?dem=".$ind.">".$chemin_mel."?dem=".$ind."</a> émise par ".$_SESSION['mission']['prenom']." ".$_SESSION['mission']['nom']." est validée.</body>";
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['connection']['utilisateur'].'@'.$domaine, $_SESSION['connection']['nom']." ".$_SESSION['connection']['prenom']);
	}

   //au responsable2 d'equipe ou de service du groupe selectionné pour la mission
   //s'il existe
	if ($_SESSION[ 'correspondance' ]['responsable2'][$_SESSION['groupe_indice']]!='')
	if ($_SESSION[ 'correspondance' ]['responsable2'][$_SESSION['groupe_indice']]!= $_SESSION[ 'connection' ][ 'utilisateur' ])
	if ($_SESSION[ 'correspondance' ]['responsable2'][$_SESSION['groupe_indice']]!= $_SESSION[ 'correspondance' ]['administratif'][$_SESSION['groupe_indice']])
	{
	if ($mode_test) $TO = $mel_test;
	else $TO = $_SESSION[ 'correspondance' ]['responsable2'][$_SESSION['groupe_indice']].'@'.$domaine;
	$message = "<body>Bonjour utilisateur ".$_SESSION[ 'correspondance' ]['responsable2'][$_SESSION['groupe_indice']].",<br> ";
	$message .= "la mission <a href=".$chemin_mel."?dem=".$ind.">".$chemin_mel."?dem=".$ind."</a> émise par ".$_SESSION['mission']['prenom']." ".$_SESSION['mission']['nom']." est validée.</body>";
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['mission']['utilisateur'].'@'.$domaine, $_SESSION['mission']['nom']." ".$_SESSION['mission']['prenom']);
	}

   //a(ux) l'administratif(s) concerné(s)
	if ($mode_test) $TO = $mel_test;
	else $TO = $mel_gestiolab;
	$message = "<body>Bonjour utilisateur ".$_SESSION[ 'correspondance' ]['administratif'][$_SESSION['groupe_indice']].",<br> ";
	$message .= "la mission <a href=".$chemin_mel."?dem=".$ind.">".$chemin_mel."?dem=".$ind."</a> émise par ".$_SESSION['mission']['prenom']." ".$_SESSION['mission']['nom']." est validée.</body>";
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['connection']['utilisateur'].'@'.$domaine, $_SESSION['connection']['nom']." ".$_SESSION['connection']['prenom']);

	$message_demande.= "<br><span class='vert'>-> Envoi de mails <-</span>";
   }
}

/////////////////////// F7 - Annuler demande mission///////////////////////
if (isset($_REQUEST[ 'mission' ]['annuler']))
{
/**
**/
   include $chemin_connection;

   $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
   mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

   $annule=0;

   if (!$annule)
   {
	$ind=$_SESSION["id_dem"];
   //validation de la demande:
	$_SESSION['demande']['date_fin']=strftime("%d/%m/%Y");
	$query = 'UPDATE T_MISSION SET VALIDE="-1" WHERE ID_MISSION="'.$ind.'"';
	$result = mysqli_query($link,$query) or die('Requete de modification du status: ' . mysqli_error());
	mysqli_free_result($result);
	$message_demande= "<span class='rouge'>-> Demande annul&eacute;e <-</span>";	
	mysqli_close($link);
	$_SESSION['mission']['valide']=-1;
   }

   if (!$annule)
   {
	$subject = "MISSION: Annulation demande de mission (ID=".$ind.")";

   //demandeur de mission (quelquesoit son status)
	if ($mode_test) $TO = $mel_test;
	else $TO = $_SESSION['mission']['utilisateur'].'@'.$domaine;
	$message = "<body>Bonjour ".$_SESSION['mission']['nom']." ".$_SESSION['mission']['prenom'].",<br> votre demande de mission a été annulée,<br> ";
	$message .= "suivez le lien <a href=".$chemin_mel."?dem=".$ind.">".$chemin_mel."?dem=".$ind."</a> pour l'afficher.</body>";
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['connection']['utilisateur'].'@'.$domaine, $_SESSION['connection']['nom']." ".$_SESSION['connection']['prenom']);

   //au responsable d'equipe ou de service du groupe selectionné pour la mission
	if ($_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']]!= $_SESSION[ 'mission' ][ 'utilisateur' ])
	if ($_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']]!= $_SESSION[ 'correspondance' ]['administratif'][$_SESSION['groupe_indice']])
	{
	$tempo=$_SESSION['groupe_indice'];
	if ($mode_test) $TO = $mel_test;
	else $TO = $_SESSION[ 'correspondance' ]['responsable'][$tempo].'@'.$domaine;
	$message = "<body>Bonjour utilisateur ".$_SESSION[ 'correspondance' ]['responsable'][$tempo].",<br> ";
	$message .= "la mission <a href=".$chemin_mel."?dem=".$ind.">".$chemin_mel."?dem=".$ind."</a> émise par ".$_SESSION['mission']['prenom']." ".$_SESSION['mission']['nom']." est annulée.</body>";
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['connection']['utilisateur'].'@'.$domaine, $_SESSION['connection']['nom']." ".$_SESSION['connection']['prenom']);
	}

   //au responsable2 d'equipe ou de service du groupe selectionné pour la mission
   //s'il existe
	if ($_SESSION[ 'correspondance' ]['responsable2'][$_SESSION['groupe_indice']]!='')
	if ($_SESSION[ 'correspondance' ]['responsable2'][$_SESSION['groupe_indice']]!= $_SESSION[ 'connection' ][ 'utilisateur' ])
	if ($_SESSION[ 'correspondance' ]['responsable2'][$_SESSION['groupe_indice']]!= $_SESSION[ 'correspondance' ]['administratif'][$_SESSION['groupe_indice']])
	{
	if ($mode_test) $TO = $mel_test;
	else $TO = $_SESSION[ 'correspondance' ]['responsable2'][$_SESSION['groupe_indice']].'@'.$domaine;
	$message = "<body>Bonjour utilisateur ".$_SESSION[ 'correspondance' ]['responsable2'][$_SESSION['groupe_indice']].",<br> ";
	$message .= "la mission <a href=".$chemin_mel."?dem=".$ind.">".$chemin_mel."?dem=".$ind."</a> émise par ".$_SESSION['mission']['prenom']." ".$_SESSION['mission']['nom']." est annulée.</body>";
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['mission']['utilisateur'].'@'.$domaine, $_SESSION['mission']['nom']." ".$_SESSION['mission']['prenom']);
	}

   //a(ux) l'administratif(s) concerné(s)
	if ($mode_test) $TO = $mel_test;
	else $TO = $mel_gestiolab;
	$message = "<body>Bonjour utilisateur ".$_SESSION[ 'correspondance' ]['administratif'][$_SESSION['groupe_indice']].",<br> ";
	$message .= "la mission <a href=".$chemin_mel."?dem=".$ind.">".$chemin_mel."?dem=".$ind."</a> émise par ".$_SESSION['mission']['prenom']." ".$_SESSION['mission']['nom']." est annulée.</body>";
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['connection']['utilisateur'].'@'.$domaine, $_SESSION['connection']['nom']." ".$_SESSION['connection']['prenom']);
	if ($_SESSION[ 'correspondance' ]['administratif2'][$_SESSION['groupe_indice']]!='')

	$message_demande.= "<br><span class='vert'>-> Envoi de mails <-</span>";
   }
}

////////////////////////// Reinitialiser la saisie ////////////////////////////
if ( isset($_REQUEST[ 'mission' ][ 'saisie' ]) )
{
	init_mission();
}

/*********************************************************************************
***********************  -G- Choix du module *************************************
**********************************************************************************/
if (! isset($_SESSION[ 'choix_module' ])) $_SESSION[ 'choix_module' ] = $modules[0];
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
******************************    -H- HTML      **********************************
**********************************************************************************/

// En tete des modules
include "en_tete.php";
?>

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

            $( "#recherche_nom" ).autocomplete({
                source: listeNoms
            });

            $( "#recherche_prenom" ).autocomplete({
                source: listePrenoms
            });

        });
</script>


<!-- ============ -->

<form name="form1" id="form1" method="post" action="<?php echo $_SERVER[ 'PHP_SELF' ]; ?>">
<?php
/////////////////////////// H1 - Affichage Recherche Demande //////////////////////////
echo '<table id="lien_recherche_missions"><tr><td>';
$val_r_gui=1;
if ($_SESSION[ 'r_gui' ]==1) $val_r_gui=0; else $val_r_gui=1;
if ($val_r_gui==1) echo '<a href="'.$_SERVER[ 'PHP_SELF' ]. '?r_gui='.$val_r_gui.'"><< <img src="images/loupe.png" height=17 id="loupe_recherche"/> Cliquez pour afficher la recherche >></a>';
else echo '<a href="'.$_SERVER[ 'PHP_SELF' ]. '?r_gui='.$val_r_gui.'"><< Cliquez pour masquer la recherche >></a>';//sid=' . $sid.'&
echo '</td></tr></table>';

if ($_SESSION[ 'r_gui' ]==1)
{
	// Partie recherche
	echo '<div id="recherche_missions">';
	echo '<table>';
/////////////////////////////////partie recherche simple:
	echo '<tr><td align="center">';
	echo 'Identifiant : <INPUT TYPE=text NAME="id_dem" value="'.$_SESSION[ 'id_dem' ].'" onKeypress="if((event.keyCode < 47 || event.keyCode > 57) &&  event.keyCode != 32 &&  event.keyCode != 8 &&  event.keyCode != 0) event.returnValue = false; if((event.which < 47 || event.which > 57)  &&  event.which != 32  &&  event.which != 8  &&  event.which != 0 ) return false;">';
	echo '<input type="submit" name="rechercher" value=" Rechercher " />';
	
	if(isset($message_rechercher))
		echo '<br><font color="#FF0000"><b>'.$message_rechercher.'</b></font>';

	echo '</td></table>';
	echo '<table>';
	echo '<caption>Recherche</caption>';
	echo '<td><label for="recherche_groupe">Equipe / Service</label></td>';
	echo '<td><select name="recherche[groupe]" id="recherche_groupe"';
	echo ' onChange="javascript:document.getElementById(\'form1\').submit()"';
	echo '>';
	for ($i=0;$i<sizeof($_SESSION[ 'correspondance' ]['groupe']);$i++)
	{
		echo '<option value="'.$_SESSION['correspondance']['groupe'][$i].'" ';
		if ($_SESSION['recherche']['groupe']==$_SESSION['correspondance']['groupe'][$i])
		{
			 echo 'selected';
			$_SESSION['groupe_indice_recherche']=$i;
		}
		echo '>'.$_SESSION[ 'correspondance' ]['groupe'][$i].'</option>';
	}
	echo '</select>';
//////////////////////////Pour chef d'equipe///////////////////////
	if ($_SESSION[ 'connection' ][ 'status' ]==4)
	{
	//si on affiche le select alors on charge le tableau $groupe_nom_prenom
	$groupe_nom_prenom=membre_groupe($_SESSION[ 'connection' ][ 'utilisateur' ],$chemin_connection);
	echo '<select name="recherche[groupe_nom_prenom]"';
	echo ' onChange="javascript:document.getElementById(\'form1\').submit()"';
	echo '>';
	for ($i=0;$i<sizeof($groupe_nom_prenom);$i++)
	{
		$np=explode(',',$groupe_nom_prenom[$i]);
		echo '<option value="'.$np[0].'" ';
		if ($_SESSION['recherche']['groupe_nom_prenom']==$np[0])
		{
			 echo 'selected';
			$_SESSION['groupe_nom_prenom_indice_recherche']=$i;
			if ($i>0)
			{
				$_SESSION[ 'recherche' ][ 'nom' ] = ucwords(strtolower($np[1]));
				$_SESSION[ 'recherche' ][ 'prenom' ] = ucwords(strtolower($np[2]));
			}
		}

		echo '>'.ucwords(strtolower($np[1])).' '.ucwords(strtolower($np[2])).'</option>';
	}
	echo '</select>';
	}
	//if (($_SESSION[ 'connection' ][ 'status' ] == 3 || $_SESSION[ 'connection' ][ 'status' ] == 4) && 
if ((isset($_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice_recherche']]) && $_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice_recherche']]== $_SESSION[ 'connection' ][ 'utilisateur' ]) || (isset($_SESSION[ 'correspondance' ]['responsable2'][$_SESSION['groupe_indice_recherche']]) && $_SESSION[ 'correspondance' ]['responsable2'][$_SESSION['groupe_indice_recherche']]== $_SESSION[ 'connection' ][ 'utilisateur' ])) echo '<input type="submit" name="rechercher_tlm" value=" Tous les membres " />';

	echo '</td></tr>';
	
	$a_rajouter='';
	if (($_SESSION[ 'connection' ][ 'status' ] < 5) && 
($_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice_recherche']] != $_SESSION[ 'connection' ][ 'utilisateur' ]) &&
 ($_SESSION[ 'correspondance' ]['responsable2'][$_SESSION['groupe_indice_recherche']] != $_SESSION[ 'connection' ][ 'utilisateur' ]) )
	{
	   $a_rajouter='readonly style="background-color: #E8E8E8;"';
	   if ($_SESSION['groupe_nom_prenom_indice_recherche']==0)
	   {
		$_SESSION[ 'recherche' ][ 'nom' ]=$_SESSION[ 'connection' ][ 'nom' ];
		$_SESSION[ 'recherche' ][ 'prenom' ]=$_SESSION[ 'connection' ][ 'prenom' ];
	   }
	}

	echo '<tr><td><label for="recherche_nom">Nom</label></td>';
	echo '<td><INPUT size=20 TYPE=text NAME="recherche[nom]" id="recherche_nom" value="'.$_SESSION[ 'recherche' ][ 'nom' ].'"';
	echo $a_rajouter;
	echo '></td></tr>';
	echo '<tr><td><label for="recherche_prenom">Pr&eacute;nom</label></td><td><INPUT size=20 TYPE=text NAME="recherche[prenom]" id="recherche_prenom" value="'.$_SESSION[ 'recherche' ][ 'prenom' ].'"';
	echo $a_rajouter;
	echo '></td></tr>';

	//destination
	echo '<tr><td><label for="recherche_destination">Destination</label></td>';
	echo '<td><INPUT size=20 TYPE=text NAME="recherche[destination]" id="recherche_destination" value="'.$_SESSION[ 'recherche' ][ 'destination' ].'">';
	echo '</td></tr>';
	//intervale de date
	//tri par dates
	echo '<tr><td><label for="recherche_date">Tri par dates</label></td>';
	echo '<td>';
	echo '<select name="recherche[date]" id="recherche_date">';
	echo '<option value="0" ';
	if ($_SESSION['recherche']['date']==0) { echo 'selected'; }
	echo '>Choisir un ordre de tri pour les dates</option>';
	echo '<option value="1" ';
	if ($_SESSION['recherche']['date']==1) { echo 'selected'; }
	echo '>Date de d&eacute;part par ordre croissant</option>';
	echo '<option value="2" ';
	if ($_SESSION['recherche']['date']==2) { echo 'selected'; }
	echo '>Date de d&eacute;part par ordre d&eacute;croissant</option>';
	echo '<option value="3" ';
	if ($_SESSION['recherche']['date']==3) { echo 'selected'; }
	echo '>Date d\'arriv&eacute;e par ordre croissant</option>';
	echo '<option value="4" ';
	if ($_SESSION['recherche']['date']==4) { echo 'selected'; }
	echo '>Date d\'arriv&eacute;e par ordre d&eacute;croissant</option>';
	echo '</select>';
	echo '</td></tr>';
//nouvel emplacement de recherche calendaire
	echo '<tr><td><label for="recherche_annee">Ann&eacute;e</label></td><td>';
	echo '<select name="recherche[annee]" id="recherche_annee"';
	echo '>';
	for ($i=0;$i<sizeof($annees);$i++)
	{
		echo '<option value="'.$annees[$i].'" ';
		if ($_SESSION['recherche']['annee']==$annees[$i])	 echo 'selected';
		echo '>'.$annees[$i].'</option>';
	}
	echo '</select>';
	echo '</td></tr>';

	//boutons de recherche evoluee et reinitialisation
	echo '<tr><td colspan=2 class="centrer">';
	echo '<input type="submit" name="rechercher_evol" value=" Rechercher " /><input type="submit" name="reinitialiser" value=" R&eacute;initialiser " />';
	if(isset($message_evolue))
	  echo '<br><b>'.$message_evolue.'</b>';	
	echo '</td></tr>';
	echo '</table>' . "\n";

/////////////////////////// H2 - Affichage des resultats de recherche evoluee ///////////
	if ($_SESSION['recherche']['resultats']>0) //si resultat de recherche evoluee
	{
		echo '<table id="res_rech_mission">';
		echo '<caption>R&eacute;sultats de la recherche</caption>';
		echo '<tr class="enteteTabMission">';
		echo '<td>Identifiant</td>';
		echo '<td>Nom</td>';
		echo '<td>Pr&eacute;nom</td>';
		echo '<td>Groupe</td>';
		echo '<td>Destination</td>';
		echo '<td>Date aller</td>';
		echo '<td>Date retour</td>';
		echo '<td>Validit&eacute;</td>';
		echo '</tr>';
		$i=1;

		$page=floor($_SESSION['recherche']['limitebasse']/$_SESSION['recherche']['nb_par_page'])+1;
		$nb_page = ceil($_SESSION['recherche']['resultats']/$_SESSION['recherche']['nb_par_page']);
		$nb_affich=$_SESSION['recherche']['nb_par_page'];
		if ($page==$nb_page && $_SESSION['recherche']['resultats'] <$_SESSION['recherche']['nb_par_page']*$nb_page)
		$nb_affich=$_SESSION['recherche']['resultats']%$_SESSION['recherche']['nb_par_page'];

		for ($i=1 ; $i<=$nb_affich ; $i++)
		{
			if ($i%2==0) echo '<tr class="ligne_claire">';
				else echo '<tr class="ligne_foncee">';

			echo '<td><a href="'.$_SERVER[ 'PHP_SELF' ]. '?r_gui=0&dem='.$_SESSION['recherche'][$i]['id_mission'].'#DEM_MISSION">'.$_SESSION['recherche'][$i]['id_mission'].'</a></td>';//?sid=' . $sid.'&r_gui
			echo '<td>'.$_SESSION['recherche'][$i]['nom'].'</td>';
			echo '<td>'.$_SESSION['recherche'][$i]['prenom'].'</td>';
			echo '<td>'.$_SESSION['recherche'][$i]['groupe'].'</td>';
			echo '<td>'.$_SESSION['recherche'][$i]['destination'].'</td>';
			echo '<td>'.$_SESSION['recherche'][$i]['date_aller'].'</td>';
			echo '<td>'.$_SESSION['recherche'][$i]['date_retour'].'</td>';
			$bcol='';
			if ($_SESSION['recherche'][$i]['valide']==-1) $bcol='bgcolor="#DD0000"';
			else if ($_SESSION['recherche'][$i]['valide']==0) 
			{
				$ind_=$_SESSION[ 'correspondance' ][$_SESSION['recherche'][$i]['groupe']];
				if ($_SESSION[ 'correspondance' ]['valid_missions'][$ind_]==1) $bcol='bgcolor="#3333DD"';
			}
			else if ($_SESSION['recherche'][$i]['valide']==1) $bcol='bgcolor="#00AA00"';
			echo '<td align="center" '.$bcol.'>'.$_SESSION['recherche'][$i]['valide'].'</td>';
			echo '</tr>';
		}
		echo '</table>';
		
		echo '<table id="pagination">';
		if ($page > 1) echo '<td id="precedent"><input type="submit" name="rechercher_prec" value=" Page pr&eacute;c&eacute;dente " /></td>';
		echo '<td id="page">page '.$page.'/'.$nb_page.'</td>';
		if ($page < $nb_page) echo '<td id="suivant"><input type="submit" name="rechercher_suiv" value=" Page suivante " /></td>';
		echo '</table>';
		echo '</td></tr>';
		echo '</table>';
	}
	echo '</div>';
}

///////////////////// H3 -Affichage des resultats de recherche graphique/calendaire 
	if ($_SESSION['recherche']['annee']!="----")
	{
		echo '<table id="calendrier_missions">';
		echo '<caption>Calendrier des missions</caption>';
		echo '<tr><td>';

		if ($_SESSION['recherche']['groupe']!=$_SESSION[ 'correspondance' ]['groupe'][0]) $thegroupe=$_SESSION['recherche']['groupe'];
		else $thegroupe='';

		echo '<img id="img_calendrier_missions" src="calendrier_missions.php?chemin='.$chemin_connection.'&uneannee='.$_SESSION['recherche']['annee'].'&nom='.$_SESSION['recherche']['nom'].'&prenom='.$_SESSION['recherche']['prenom'].'&groupe='.$thegroupe.'&dest='.$_SESSION['recherche']['destination'].'">';
		echo '</td>';
		echo '<td id="legende"><h4>L&eacute;gende</h4><ul>';
		echo '<li id="ferie">Jour f&eacute;ri&eacute;</li>';
		echo '<li id="we">Week end</li>';
		echo '<li id="mission">Mission</li>';
		echo '</ul></td></tr>';
		echo '</table>';
	}

/////////////////////////// H4 - Partie demande de mission //////////////////////////
echo '<table id="DEM_MISSION"><tr><td>Demande</tr></td></table>';

///////////////////////////Bandeau correspondant à l'état de la mission //////////////////
	if($_SESSION[ 'mission' ][ 'valide' ] == 2)//Nouvelle demande
		echo '<img src="images/bandeau_nouvelle_demande.png" id="bandeau_etat" alt="Nouvelle demande" />';
	elseif($_SESSION[ 'mission' ][ 'valide' ] == -1)//Annulée
		echo '<img src="images/bandeau_-1.png" id="bandeau_etat" alt="Demande annul&eacute;e" />';
	elseif($_SESSION[ 'mission' ][ 'valide' ] == 0)//En attente
	{
		include($chemin_connection);
   		$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
   		or die('Could not connect: ' . mysqli_connect_error());
   		mysqli_select_db($link,$mysql_base) or die('Could not select database');
   		$query = 'SELECT VALID_MISSIONS FROM T_CORRESPONDANCE WHERE GROUPE=\''.$_SESSION[ 'mission' ][ 'groupe' ].'\'';
   		$result = mysqli_query($link,$query) or die('Query '.$query.'| failed: ' . mysqli_error());
		$line = mysqli_fetch_array($result, MYSQL_NUM);
		if($line[0] == 0) //Le groupe ne valide pas
			echo '<img src="images/bandeau_1.png" id="bandeau_etat" alt="Demande valid&eacute;" />';
		else echo '<img src="images/bandeau_0.png" id="bandeau_etat" alt="Demande en attente" />';

	}
	elseif($_SESSION[ 'mission' ][ 'valide' ] == 1)//Validée
		echo '<img src="images/bandeau_1.png" id="bandeau_etat" alt="Demande valid&eacute;e" />';

	echo '<p class="centrer gras rouge correctionDecalage">* Champs obligatoires</p>';

	echo '<table id="demande_mission">';
echo '<tr><td><label>Nom <span class="gras rouge">*</span></label></td>';
echo '<td><INPUT size=25 TYPE=text NAME="mission[nom]" value="'.$_SESSION[ 'mission' ][ 'nom' ].'" readonly tabindex="1">';
echo ' <label class="marginLeft">Pr&eacute;nom <span class="gras rouge">*</span></label><INPUT size=28 TYPE=text NAME="mission[prenom]" value="'.$_SESSION[ 'mission' ][ 'prenom' ].'" readonly class="marginLeft" tabindex="2"></td></tr>';
echo '<tr><td><label for="mission_groupe">Equipe / Service <span class="gras rouge">*</span></label></td>';
echo '<td><select name="mission[groupe]" id="mission_groupe" tabindex="3" onChange="javascript:document.getElementById(\'form1\').submit()"';
echo '>';
for ($i=0;$i<sizeof($_SESSION[ 'correspondance' ]['groupe']);$i++)
{
	echo '<option value="'.$_SESSION['correspondance']['groupe'][$i].'" ';
	if ($_SESSION['mission']['groupe']==$_SESSION['correspondance']['groupe'][$i])
	{
		 echo 'selected';
		$_SESSION['groupe_indice']=$i;
	}
	echo '>'.$_SESSION[ 'correspondance' ]['groupe'][$i].'</option>';
}
echo '</select></td></tr>';
echo '<tr><td><label for="mission_depart">D&eacute;part <span class="gras rouge">*</span></label></td>';
echo '<td><INPUT size=25 TYPE=text NAME="mission[depart]" id="mission_depart" value="'.$_SESSION[ 'mission' ][ 'depart' ].'" tabindex="4"></td></tr>';
echo '<tr><td><label for="mission_destination">Destination <span class="gras rouge">*</span></label></td>';
echo '<td><INPUT size=25 TYPE=text NAME="mission[destination]" id="mission_destination" value="'.$_SESSION[ 'mission' ][ 'destination' ].'" tabindex="5"></td></tr>';
echo '<tr><td><label for="mission_objet">Objet <span class="gras rouge">*</span></label></td>';
echo '<td>';
//liste des objets
/////////////////////////////////////////////////////////////
//DEBUT Ajout spécifique équipe Atlas (spécial LPC)
//Cas qu'il est possible de commenter
if ($_SESSION['mission']['groupe']=='ATLAS')
$objets=array("Choisir un objet","[AT97] Mission R&D ponts","[AT96] Mission R&D m&eacute;canique","[AT95] Mission R&D ASIC","[AT90] Maintenance Tiroirs","[AT75] Laser bat. 175","[AT70] Laser USA15","[AT21] Shifts au CERN","[AT22] Ecole ou conf&eacute;rence","[AT20] Mission au CERN autre que ci-dessus","[AT24] Mission hors CERN autre que ci-dessus"); 
//FIN ajout spécifique
/////////////////////////////////////////////////////////////
echo '<select name="objet" id="choisir_objet" tabindex="6" onChange="javascript:document.getElementById(\'form1\').submit()"';
echo '>';
for ($i=0;$i<sizeof($objets);$i++)
{
	echo '<option value="'.$objets[$i].'" ';
	echo '>'.$objets[$i].'</option>';
}
echo '</select>';
echo '<INPUT size=40 TYPE=text NAME="mission[objet]" id="mission_objet" value="'.$_SESSION[ 'mission' ][ 'objet' ].'" tabindex="7" placeholder="Autre objet" class="marginLeft">';
echo '</td></tr>';
echo '<tr><td><label>Type <span class="gras rouge">*</span></label></td>';
echo '<td><input type="radio" name="mission[type]" id="avec_frais" tabindex="8" value="1" ';
if ($_SESSION[ 'mission' ][ 'type' ]=="1") echo ' checked ';
echo '/><label for="avec_frais">avec frais</label>';
echo '<input type="radio" name="mission[type]" id="sans_frais" tabindex="9" value="2" class="marginLeft"';
if ($_SESSION[ 'mission' ][ 'type' ]=="2") echo ' checked ';
echo '/><label for="sans_frais">sans frais</label></td></tr>';
echo '<tr><td><label for="mission_transport">Moyen de transport <span class="gras rouge">*</span></label></td>';
echo '<td>';
//liste des vehicules
echo '<select name="vehicule" tabindex="10" onChange="javascript:document.getElementById(\'form1\').submit()"';
echo '>';
for ($i=0;$i<sizeof($vehicules);$i++)
{
	echo '<option value="'.$vehicules[$i].'" ';
	echo '>'.$vehicules[$i].'</option>';
}
echo '</select>';
echo '<INPUT size=36 TYPE=text NAME="mission[transport]" tabindex="11" id="mission_transport" value="'.$_SESSION[ 'mission' ][ 'transport' ].'" placeholder="Autre moyen de transport" class="marginLeft">';
//echo '</td></tr>';
echo '<tr><td><label for="mission_estimation_cout">Estimation du co&ucirc;t </label></td>';
echo '<td><INPUT size=36 TYPE=text NAME="mission[estimation_cout]" tabindex="11" id="mission_estimation_cout" value="'.$_SESSION[ 'mission' ][ 'estimation_cout' ].'" placeholder="Facultatif" class="marginLeft">';
echo '</td></tr>';

//Affichages des liens d'aide
echo '<tr><td colspan=2 align=center>';
$numLien=1;
while(!empty(${'libelle_lien'.$numLien}) && !empty(${'adresse_lien'.$numLien}))
{
	echo '<INPUT type="button" value="'.${'libelle_lien'.$numLien}.'" onclick="window.open(\''.${'adresse_lien'.$numLien}.'\');">';
	$numLien++;
}
echo '</td></tr>';

//tableau ALLER RETOUR
echo '<table id="aller_retourANDcommentaire">';
echo '<tr><td>';
echo '<table id="aller_retour">';
echo '<tr>';
echo '<td colspan=2 class="enteteTabMissions">ALLER <span class="gras rouge">*</span></td><td colspan=2 class="enteteTabMissions">RETOUR <span class="gras rouge">*</span></td>';
echo '</tr><tr>';
echo '<td><label for="mission_date_aller">Date</label></td>';
echo '<td class="bordureCentrale date"><INPUT onblur="initDate()" tabindex="12" id="date1" size=10 TYPE=text NAME="mission[date_aller]" class="centrer" value="'.$_SESSION[ 'mission' ][ 'date_aller' ].'" placeholder="JJ/MM/AAAA" title="JJ/MM/AAAA"></td>';
echo '<td><label for="mission_date_retour">Date</label></td>';
echo '<td class="date"><INPUT id="date2" size=10 tabindex="15" TYPE=text NAME="mission[date_retour]" class="centrer" value="'.$_SESSION[ 'mission' ][ 'date_retour' ].'" placeholder="JJ/MM/AAAA" title="JJ/MM/AAAA"></td>';
echo '</tr><tr>';
echo '<td><label for="mission_heure_dep_aller">Heure de d&eacute;part</label></td>';
echo '<td class="bordureCentrale"><INPUT size=5 tabindex="13" class="centrer" TYPE=number min=0 max=23 step=1 NAME="mission[heure_dep_aller]" id="mission_heure_dep_aller" title="[0...23]" value="'.$_SESSION[ 'mission' ][ 'heure_dep_aller' ].'" onKeypress="if((event.keyCode < 47 || event.keyCode > 57) &&  event.keyCode != 32 &&  event.keyCode != 8 &&  event.keyCode != 0) event.returnValue = false; if((event.which < 47 || event.which > 57)  &&  event.which != 32  &&  event.which != 8  &&  event.which != 0 ) return false;"></td>';
echo '<td><label for="mission_heure_dep_retour">Heure de d&eacute;part</label></td>';
echo '<td><INPUT size=5 tabindex="16" TYPE=number min=0 max=23 step=1 class="centrer" NAME="mission[heure_dep_retour]" id="mission_heure_dep_retour" title="[0...23]" value="'.$_SESSION[ 'mission' ][ 'heure_dep_retour' ].'" onKeypress="if((event.keyCode < 47 || event.keyCode > 57) &&  event.keyCode != 32 &&  event.keyCode != 8 &&  event.keyCode != 0) event.returnValue = false; if((event.which < 47 || event.which > 57)  &&  event.which != 32  &&  event.which != 8  &&  event.which != 0 ) return false;"></td>';
echo '</tr><tr>';
echo '<td><label for="mission_heure_arr_aller">Heure d\'arriv&eacute;e</label></td>';
echo '<td class="bordureCentrale"><INPUT size=5 tabindex="14" TYPE=number min=0 max=23 step=1 class="centrer" NAME="mission[heure_arr_aller]" id="mission_heure_arr_aller" title="[0...23]" value="'.$_SESSION[ 'mission' ][ 'heure_arr_aller' ].'" onKeypress="if((event.keyCode < 47 || event.keyCode > 57) &&  event.keyCode != 32 &&  event.keyCode != 8 &&  event.keyCode != 0) event.returnValue = false; if((event.which < 47 || event.which > 57)  &&  event.which != 32  &&  event.which != 8  &&  event.which != 0 ) return false;"></td>';
echo '<td><label for="mission_heure_arr_retour">Heure d\'arriv&eacute;e</label></td>';
echo '<td><INPUT size=5 TYPE=number min=0 max=23 tabindex="17" step=1 class="centrer" NAME="mission[heure_arr_retour]" id="mission_heure_arr_retour" title="[0...23]" value="'.$_SESSION[ 'mission' ][ 'heure_arr_retour' ].'" onKeypress="if((event.keyCode < 47 || event.keyCode > 57) &&  event.keyCode != 32 &&  event.keyCode != 8 &&  event.keyCode != 0) event.returnValue = false; if((event.which < 47 || event.which > 57)  &&  event.which != 32  &&  event.which != 8  &&  event.which != 0 ) return false;"></td>';
echo '</tr>';
echo '</table>';
//FIN (tableau ALLER RETOUR)
//champ de commentaire
echo '<tr><td colspan=2 class="centrer">';
echo '<br/>Commentaire<br/>';
echo '<textarea name="mission[commentaire]" tabindex="18" rows="4" cols="60"  onKeyPress="CaracMax(this, 256);">'.$_SESSION[ 'mission' ][ 'commentaire' ].'</textarea>';
echo '</td></tr>';

//bouton d'envoie de mission; validation et annulation
//echo $_SESSION['groupe_indice'].'<br>';
//echo $_SESSION[ 'correspondance' ]['valid_missions'][$_SESSION['groupe_indice']].'<br>';
if (isset($_SESSION['groupe_indice']) && isset($_SESSION[ 'correspondance' ]['valid_missions'][$_SESSION['groupe_indice']]) && $_SESSION[ 'correspondance' ]['valid_missions'][$_SESSION['groupe_indice']]==1
&& $_SESSION[ 'mission' ][ 'valide' ]==0)
echo '<tr><td colspan=2 align=center><font color="#0000FF">Demande de mission <b>non valid&eacute;e</b>.</font></td></tr>';
else if (isset($_SESSION['groupe_indice']) && isset($_SESSION[ 'correspondance' ]['valid_missions'][$_SESSION['groupe_indice']]) && $_SESSION[ 'correspondance' ]['valid_missions'][$_SESSION['groupe_indice']]==1
&& $_SESSION[ 'mission' ][ 'valide' ]==1)
echo '<tr><td colspan=2 align=center><font color="#0000FF">Demande de mission  <b>valid&eacute;e</b>.</font></td></tr>';
else if ($_SESSION[ 'mission' ][ 'valide' ]==-1)
 echo '<tr><td colspan=2 align=center><font color="#0000FF">Demande de mission <b>annul&eacute;e</b>.</font></td></tr>';

$temps=explode("/",$_SESSION[ 'mission' ][ 'date_retour' ]);
if (isset($_SESSION['groupe_indice']) && isset($_SESSION[ 'correspondance' ]['valid_missions'][$_SESSION['groupe_indice']]) && $_SESSION[ 'correspondance' ]['valid_missions'][$_SESSION['groupe_indice']]==1
&& $_SESSION[ 'mission' ][ 'valide' ]==0
&& ($_SESSION[ 'connection' ][ 'utilisateur' ]== $_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']] || $_SESSION[ 'connection' ][ 'utilisateur' ]== $_SESSION[ 'correspondance' ]['responsable2'][$_SESSION['groupe_indice']])
&& $_SESSION[ 'edition' ] != 1)
{
	if (time()<mktime($_SESSION[ 'mission' ][ 'heure_arr_retour' ],0,0,$temps[1],$temps[0],$temps[2])) //il peut encore annuler et valider
	echo '<tr><td align=center colspan=2><input type="submit" class="bouton_missions" name="mission[annuler]" value=" Annuler la demande de mission " onClick="return confirm(\'Etes-vous s&ucirc;r(e) de vouloir annuler cette demande de mission?\');"><input type="submit" class="bouton_missions" name="mission[valider]" value=" Valider la demande de mission " onClick="return confirm(\'Etes-vous s&ucirc;r(e) de vouloir valider cette demande de mission?\');"></td></tr>';
}
else
if ($_SESSION[ 'edition' ] == 1)
{
	$aqui=' Votre demande sera envoy&eacute;e aux administratifs';
	//aqui sera envoye la demande
	if (isset($_SESSION['groupe_indice']) && isset($_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']]) && $_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']]!= $_SESSION[ 'connection' ][ 'utilisateur' ])
	if ($_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']]!= $_SESSION[ 'correspondance' ]['administratif'][$_SESSION['groupe_indice']])
	$aqui.=', &agrave '.$_SESSION[ 'correspondance' ]['responsable'] [$_SESSION['groupe_indice']];
	if (isset($_SESSION['groupe_indice']) && isset($_SESSION[ 'correspondance' ]['responsable2'][$_SESSION['groupe_indice']]) && $_SESSION[ 'correspondance' ]['responsable2'][$_SESSION['groupe_indice']]!='')
	$aqui.=', &agrave '.$_SESSION[ 'correspondance' ]['responsable2'] [$_SESSION['groupe_indice']];
	if (strlen($_SESSION[ 'responsable_groupe'])>3)
	if ($_SESSION[ 'connection' ][ 'status' ]>1)
	if ($_SESSION[ 'responsable_groupe']!=$_SESSION[ 'connection' ][ 'utilisateur' ])
	if (isset($_SESSION['groupe_indice']) && isset($_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']]) && $_SESSION[ 'responsable_groupe']!=$_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']])
	$aqui.=', &agrave '.$_SESSION[ 'responsable_groupe'];
	$aqui.=' et &agrave; vous.';
	//bouton envoie
	echo '<tr><td align=center colspan=2><input type="submit" class="bouton_missions" tabindex="19" name="mission[envoyer]" id="mission_envoyer" value=" Envoyer une demande de mission " onClick="return confirm(\'Etes-vous s&ucirc;r(e) de vouloir envoyer cette demande de mission?'.$aqui.'\');">';
	echo '<input type="submit" name="mission[saisie]" id="mission_annuler" value=" Annuler saisie " class="marginLeft">';
	echo '</td></tr>';
}
else 
{
	echo '<tr><td align=center colspan=2>';
	//le demandeur peut aussi annuler la mission:
	if ($_SESSION[ 'connection' ][ 'utilisateur' ]==$_SESSION[ 'mission' ][ 'utilisateur' ] && $_SESSION[ 'mission' ][ 'valide' ]!=-1)
	if (time()<mktime($_SESSION[ 'mission' ][ 'heure_arr_retour' ],0,0,$temps[1],$temps[0],$temps[2])) //il peut encore annuler
	echo '<input type="submit" name="mission[annuler]" class="bouton_missions" tabindex="20" value=" Annuler la demande de mission " onClick="return confirm(\'Etes-vous s&ucirc;r(e) de vouloir annuler cette demande de mission?\');">';
	echo '<input type="submit" class="bouton_missions" name="mission[nouvelle]" tabindex="21" value=" Editer une nouvelle mission " ></td></tr>';
}

if (isset($message_demande)) echo '<tr><td colspan=2 align=center><font color="#FF0000"><b>'.$message_demande.'</b></font></td></tr>';
echo '</tr></td>';
echo '</table>';

//////////////PIED DE PAGE/////////////////
include "pied_page.php";
?>

