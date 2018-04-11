<?php

//Affichage des erreurs en mode test
include 'config.php';
if($mode_test == 1 && !isset($_GET[ 'disconnect' ]))//Probleme de deconnexion avec CAS sinon
{
	ini_set('display_errors', 1);
	error_reporting(E_ALL);
}


/**
* Interface d'administration de phpMyLab.
*
* La page d'administration permet à l'administrateur de gérer les utilisateurs et les groupes. Au premier chargement de la page, un certain nombre d'actions sont suggérées à l'administrateur (congés non validés en particulier, etc.).
*
* Date de création : 6 décembre 2009<br>
* Date de dernière modification : 19 mai 2015
* @version 3.0.0
* @author Emmanuel Delage, Cedric Gagnevin <cedric.gagnevin@laposte.net>, Benjamin Grosjean <benjamin.grosjean@etu.udamail.fr>
* @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.html
* @copyright CNRS (c) 2015
* @package phpMyLab
* @subpackage Administration
*/

/*********************************************************************************
******************************  PLAN     *****************************************
*********************************************************************************/
//     | -A- Gestion de la déconnexion
//     | -B- Fonctions
//     | -C- Initialisation generale (configuration et php)
//     | -D- Initialisation Session et variables connection
//     | -E- Variables GUI
//     | -F- Variables correspondance(groupe)
//     | -E- Variables utilisateurs
//     | -H- Variables Messages 1ere connection
//     | -I- FONCTIONS GROUPE
//     | -J- FONCTIONS UTILISATEURS
//     | -K- BOUTONS SPECIAUX
//     | -L- HTML


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

//Deconnexion sans CAS
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
***********************  -B- Fonctions *******************************************
**********************************************************************************/

/**
* Retourne si l'année est bissextile ou pas
*
* @param int année à vérifier
* @return bool bissextilité
*/
function isBissextile($year)
{
if ($year % 4 ==0 && $year % 100 !=0 || $year %400 ==0) return true;
else return false;
}

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
//        $f_contents=chunk_split(base64_encode($f_contents));    //Encode The Data For Transition using base64_encode();
        $f_contents=chunk_split($f_contents);    //déja Encode 
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

/*********************************************************************************
*************** -C- Initialisation generale (configuration et php) **************
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
**********  -D- Initialisation Session et variables connection  ******************
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
**********************  -E- Variables GUI  ***************************************
**********************************************************************************/
if (!isset( $_SESSION[ 'choix_gestion' ])) $_SESSION[ 'choix_gestion' ]=0;
if (isset( $_REQUEST[ 'choix_gestion' ])) $_SESSION[ 'choix_gestion' ]=$_REQUEST[ 'choix_gestion' ];

$tous_les_utilisateurs='Tous les groupes';

if (!isset( $_SESSION[ 'selection_groupe' ])) $_SESSION[ 'selection_groupe' ]=$tous_les_utilisateurs;
if (isset( $_REQUEST[ 'selection_groupe' ])) {
$_SESSION[ 'selection_groupe' ]=$_REQUEST[ 'selection_groupe' ];
if ($_SESSION[ 'selection_groupe' ]!=$tous_les_utilisateurs && !isset($_REQUEST[ 'utilisateur' ][ 'nouvel' ])) 
{
   $_REQUEST[ 'utilisateur' ][ 'groupe_' ]=$_SESSION[ 'selection_groupe' ];
}
}

//action:
if (!isset( $_SESSION[ 'action' ])) $_SESSION[ 'action' ]='';
if (isset($_GET["actions"])) $_SESSION[ 'action' ]=$_GET["actions"];

$module_conge_charge=1;

/*********************************************************************************
**********************  -F- Variables correspondance(groupe)  ********************
**********************************************************************************/
///////////////////////////////groupes
if (!isset( $_SESSION[ 'correspondance' ][ 'groupe_' ])) $_SESSION[ 'correspondance' ][ 'groupe_' ]='';
if (!isset( $_SESSION[ 'correspondance' ][ 'responsable_' ])) $_SESSION[ 'correspondance' ][ 'responsable_' ]='';
if (!isset( $_SESSION[ 'correspondance' ][ 'responsable2_' ])) $_SESSION[ 'correspondance' ][ 'responsable2_' ]='';
if (!isset( $_SESSION[ 'correspondance' ][ 'administratif_' ])) $_SESSION[ 'correspondance' ][ 'administratif_' ]='';
if (!isset( $_SESSION[ 'correspondance' ][ 'administratif2_' ])) $_SESSION[ 'correspondance' ][ 'administratif2_' ]='';
if (!isset( $_SESSION[ 'correspondance' ][ 'valid_mission_' ])) $_SESSION[ 'correspondance' ][ 'valid_mission_' ]=0;
if (!isset( $_SESSION[ 'correspondance' ][ 'valid_conge_' ])) $_SESSION[ 'correspondance' ][ 'valid_conge_' ]=1;
if (!isset( $_SESSION[ 'correspondance' ][ 'entite_depensiere_' ])) $_SESSION[ 'correspondance' ][ 'entite_depensiere_' ]=0;

if (isset( $_REQUEST[ 'correspondance' ][ 'groupe_' ])) $_SESSION[ 'correspondance' ][ 'groupe_' ]=strtoupper($_REQUEST[ 'correspondance' ][ 'groupe_' ]);
if (isset( $_REQUEST[ 'correspondance' ][ 'responsable_' ])) $_SESSION[ 'correspondance' ][ 'responsable_' ]=strtolower($_REQUEST[ 'correspondance' ][ 'responsable_' ]);
if (isset( $_REQUEST[ 'correspondance' ][ 'responsable2_' ])) $_SESSION[ 'correspondance' ][ 'responsable2_' ]=strtolower($_REQUEST[ 'correspondance' ][ 'responsable2_' ]);
if (isset( $_REQUEST[ 'correspondance' ][ 'administratif_' ])) $_SESSION[ 'correspondance' ][ 'administratif_' ]=strtolower($_REQUEST[ 'correspondance' ][ 'administratif_' ]);
if (isset( $_REQUEST[ 'correspondance' ][ 'administratif2_' ])) $_SESSION[ 'correspondance' ][ 'administratif2_' ]=strtolower($_REQUEST[ 'correspondance' ][ 'administratif2_' ]);
if (isset( $_REQUEST[ 'correspondance' ][ 'valid_mission_' ])) $_SESSION[ 'correspondance' ][ 'valid_mission_' ]=$_REQUEST[ 'correspondance' ][ 'valid_mission_' ];
if (isset( $_REQUEST[ 'correspondance' ][ 'valid_conge_' ])) $_SESSION[ 'correspondance' ][ 'valid_conge_' ]=$_REQUEST[ 'correspondance' ][ 'valid_conge_' ];
if (isset( $_REQUEST[ 'correspondance' ][ 'entite_depensiere_' ])) $_SESSION[ 'correspondance' ][ 'entite_depensiere_' ]=$_REQUEST[ 'correspondance' ][ 'entite_depensiere_' ];

//variable de l'edition d'une intervention
if (! isset($_SESSION[ 'edition_correpondance' ])) $_SESSION[ 'edition_correpondance' ] = 0; 
//groupe:
if (!isset( $_SESSION[ 'groupe' ])) {$_SESSION[ 'groupe_indice' ]=0;$_SESSION[ 'groupe' ]='';}
if (isset($_GET["corresp"])) {$_SESSION[ 'groupe_indice' ]=$_GET["corresp"];
$_SESSION[ 'groupe' ]=$_SESSION[ 'correspondance' ][ 'groupe' ][$_SESSION[ 'groupe_indice' ]];}

/*********************************************************************************
**********************  -G- Variables utilisateurs  ******************************
**********************************************************************************/
//chargement des utilisateurs au demarage de la page
if ($_SESSION[ 'connection' ][ 'admin' ] == 1)
if (! isset($_SESSION[ 'utilisateur' ]))
{

/**
**/
   include $chemin_connection;

   // Connecting, selecting database:
   $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
   or die('Could not connect: ' . mysqli_connect_error());
   mysqli_select_db($link,$mysql_base) or die('Could not select database');

   $query2 = 'SELECT * FROM T_UTILISATEUR ORDER BY GROUPE';
   $result2 = mysqli_query($link,$query2) or die('Erreur: ' . mysqli_error());
   $i=1;
   while ($line2 = mysqli_fetch_array($result2, MYSQL_NUM)) {
	$_SESSION[ 'utilisateur' ]['utilisateur'][$i]=$line2[0];
	//taille des groupes pour le trait bottom de l'affichage du tableau
	if (!isset($_SESSION['taille'][$line2[6]])) $_SESSION['taille'][$line2[6]]=1;
	else $_SESSION['taille'][$line2[6]]++;
	
	//if ($module_conge_charge==1)
	{
	$query3 = 'SELECT * FROM T_CONGE_SOLDE where UTILISATEUR=\''.$line2[0].'\'';
	$result3 = mysqli_query($link,$query3) or die('Erreur: ' . mysqli_error());
	$line3 = mysqli_fetch_array($result3, MYSQL_NUM);
	$_SESSION[ 'utilisateur' ]['solde_ca'][$i]=$line3[1];
	$_SESSION[ 'utilisateur' ]['solde_ca_1'][$i]=$line3[2];
	$_SESSION[ 'utilisateur' ]['solde_recup'][$i]=$line3[3];
	$_SESSION[ 'utilisateur' ]['solde_cet'][$i]=$line3[4];
	$_SESSION[ 'utilisateur' ]['quota_jours'][$i]=$line3[5];
	$_SESSION[ 'utilisateur' ]['quotite'][$i]=$line3[6];
	mysqli_free_result($result3);
	}

	$_SESSION[ 'utilisateur' ]['nom'][$i]=$line2[1];
	$_SESSION[ 'utilisateur' ]['prenom'][$i]=$line2[2];
	$_SESSION[ 'utilisateur' ]['motdepasse'][$i]=substr(md5($line2[3]),-10).'...';

	$_SESSION[ 'utilisateur' ]['groupe'][$i]=$line2[6];
	$_SESSION[ 'utilisateur' ]['status'][$i]=$line2[7];
	$_SESSION[ 'utilisateur' ]['admin'][$i]=$line2[8];
	$_SESSION[ 'utilisateur' ]['login_cas'][$i]=$line2[12];

	$_SESSION[ 'utilisateur' ]['contrat_type'][$i]=$line2[9];
	$query3 = 'SELECT DATE_FORMAT(\''.$line2[10].'\',\'%d/%m/%Y\');';
	$result3 = mysqli_query($link,$query3);
	$line3 = mysqli_fetch_array($result3, MYSQL_NUM);
	$_SESSION[ 'utilisateur' ]['contrat_debut'][$i]=$line3[0];
	mysqli_free_result($result3);
	$query3 = 'SELECT DATE_FORMAT(\''.$line2[11].'\',\'%d/%m/%Y\');';
	$result3 = mysqli_query($link,$query3);
	$line3 = mysqli_fetch_array($result3, MYSQL_NUM);
	$_SESSION[ 'utilisateur' ]['contrat_fin'][$i]=$line3[0];
	mysqli_free_result($result3);

	$i++;
   }
   $_SESSION[ 'nb_utilisateur']=$i-1;
   mysqli_free_result($result2);
   mysqli_close($link);
}

if (!isset( $_SESSION[ 'utilisateur' ][ 'utilisateur_' ])) $_SESSION[ 'utilisateur' ][ 'utilisateur_' ]='';
if (!isset( $_SESSION[ 'utilisateur' ][ 'nom_' ])) $_SESSION[ 'utilisateur' ][ 'nom_' ]='';
if (!isset( $_SESSION[ 'utilisateur' ][ 'prenom_' ])) $_SESSION[ 'utilisateur' ][ 'prenom_' ]='';
if (!isset( $_SESSION[ 'utilisateur' ][ 'motdepasse_' ])) $_SESSION[ 'utilisateur' ][ 'motdepasse_' ]='';
if (!isset( $_SESSION[ 'utilisateur' ][ 'groupe_' ])) $_SESSION[ 'utilisateur' ][ 'groupe_' ]='';
if (!isset( $_SESSION[ 'utilisateur' ][ 'status_' ])) $_SESSION[ 'utilisateur' ][ 'status_' ]=1;
if (!isset( $_SESSION[ 'utilisateur' ][ 'admin_' ])) $_SESSION[ 'utilisateur' ][ 'admin_' ]=0;
if (!isset( $_SESSION[ 'utilisateur' ][ 'login_cas_' ])) $_SESSION[ 'utilisateur' ][ 'login_cas_' ]='';
if (!isset( $_SESSION[ 'utilisateur' ][ 'contrat_type_' ])) $_SESSION[ 'utilisateur' ][ 'contrat_type_' ]=$type_contrats[0][0];
if (!isset( $_SESSION[ 'utilisateur' ][ 'contrat_debut_' ])) $_SESSION[ 'utilisateur' ][ 'contrat_debut_' ]=strftime("%d/%m/%Y");
if (!isset( $_SESSION[ 'utilisateur' ][ 'contrat_fin_' ])) $_SESSION[ 'utilisateur' ][ 'contrat_fin_' ]='';

//je commente le if car finalement lors de la creation, il faut creer un enregistrement
//dans le table T_CONGE_SOLDE au cas ou l'admin decide d'utiliser le module CONGE
//if ($module_conge_charge==1)
{
if (!isset( $_SESSION[ 'utilisateur' ][ 'solde_ca_' ])) $_SESSION[ 'utilisateur' ][ 'solde_ca_' ]=0;
if (!isset( $_SESSION[ 'utilisateur' ][ 'solde_ca_1_' ])) $_SESSION[ 'utilisateur' ][ 'solde_ca_1_' ]=0;
if (!isset( $_SESSION[ 'utilisateur' ][ 'solde_recup_' ])) $_SESSION[ 'utilisateur' ][ 'solde_recup_' ]=0;
if (!isset( $_SESSION[ 'utilisateur' ][ 'solde_cet_' ])) $_SESSION[ 'utilisateur' ][ 'solde_cet_' ]=0;
if (!isset( $_SESSION[ 'utilisateur' ][ 'quota_jours_' ])) $_SESSION[ 'utilisateur' ][ 'quota_jours_' ]=$type_contrats[1][1];
if (!isset( $_SESSION[ 'utilisateur' ][ 'quotite_' ])) $_SESSION[ 'utilisateur' ][ 'quotite_' ]=100;
}
if (isset( $_REQUEST[ 'utilisateur' ][ 'utilisateur_' ])) $_SESSION[ 'utilisateur' ][ 'utilisateur_' ]=strtolower($_REQUEST[ 'utilisateur' ][ 'utilisateur_' ]);
if (isset( $_REQUEST[ 'utilisateur' ][ 'login_cas_' ])) $_SESSION[ 'utilisateur' ][ 'login_cas_' ]=strtolower($_REQUEST[ 'utilisateur' ][ 'login_cas_' ]);
if (isset( $_REQUEST[ 'utilisateur' ][ 'nom_' ])) $_SESSION[ 'utilisateur' ][ 'nom_' ]=strtoupper($_REQUEST[ 'utilisateur' ][ 'nom_' ]);
if (isset( $_REQUEST[ 'utilisateur' ][ 'prenom_' ])) $_SESSION[ 'utilisateur' ][ 'prenom_' ]=strtoupper($_REQUEST[ 'utilisateur' ][ 'prenom_' ]);
if (isset( $_REQUEST[ 'utilisateur' ][ 'motdepasse_' ])) $_SESSION[ 'utilisateur' ][ 'motdepasse_' ]=$_REQUEST[ 'utilisateur' ][ 'motdepasse_' ];
if (isset( $_REQUEST[ 'utilisateur' ][ 'groupe_' ])) $_SESSION[ 'utilisateur' ][ 'groupe_' ]=strtoupper($_REQUEST[ 'utilisateur' ][ 'groupe_' ]);
if (isset( $_REQUEST[ 'utilisateur' ][ 'status_' ])) $_SESSION[ 'utilisateur' ][ 'status_' ]=$_REQUEST[ 'utilisateur' ][ 'status_' ];
if (isset( $_REQUEST[ 'utilisateur' ][ 'admin_' ])) $_SESSION[ 'utilisateur' ][ 'admin_' ]=$_REQUEST[ 'utilisateur' ][ 'admin_' ];


///////////////////////////////////////////////////////////////////
//choix du type de contrat
if (isset( $_REQUEST[ 'utilisateur' ][ 'contrat_type_' ]) && !isset($_REQUEST[ 'utilisateur' ][ 'nouvel' ]) )
{
$_SESSION[ 'utilisateur' ][ 'contrat_type_' ]=$_REQUEST[ 'utilisateur' ][ 'contrat_type_' ];

//if ($module_conge_charge==1)
{
//copie du quota de jour
$choix_effectue=0;
if (!isset($_REQUEST[ 'utilisateur' ][ 'editer' ]))
{
	$ind=1;
	while ($ind < sizeof($type_contrats))
	{
	if ($type_contrats[$ind][0] == $_SESSION[ 'utilisateur' ][ 'contrat_type_' ])
	{
	$_REQUEST[ 'utilisateur' ][ 'quota_jours_' ]=$type_contrats[$ind][1];
	$choix_effectue=1;
	break;
	}
	$ind++;
	}
}

if ($choix_effectue==1)
{
//calcul du nombre de jour CA restant:
$annee_actuelle=date('Y');
list($jour1, $mois1, $annee1) = explode('/', $_REQUEST['utilisateur']['contrat_debut_']); 
list($jour2, $mois2, $annee2) = explode('/', '31/12/'.date('Y'));
if(!empty($_REQUEST['utilisateur']['contrat_fin_']))
  list($jour2, $mois2, $annee2) = explode('/', $_REQUEST['utilisateur']['contrat_fin_']);
 
//test de la validité des dates
$timestamp1 = mktime(0,0,0,$mois1,$jour1,$annee1);
$timestamp2 = 0;
$timestamp3 = 0;
$calcul_possible=0;
if (checkdate($mois1,$jour1,$annee1) && $annee1==$annee_actuelle) //annee en cours = anne debut de contrat
{
	$timestamp2 = mktime(12,0,0,$mois2,$jour2,$annee2);
	if ($_REQUEST['utilisateur']['contrat_fin_']=='')
	{
	$timestamp2 = mktime(24,0,0,12,31,$annee_actuelle); 
	$calcul_possible=1;
	}
	else if ((checkdate($mois2,$jour2,$annee2)) && ($timestamp2>=$timestamp1))
	{
	if ($annee2==$annee_actuelle)//if $annee2>$annee_actuelle
	$timestamp2 = mktime(24,0,0,$mois2,$jour2,$annee_actuelle);
	else $timestamp2 = mktime(24,0,0,12,31,$annee_actuelle);
	$calcul_possible=1;
	}
}
elseif (checkdate($mois1,$jour1,$annee1) && $annee1==$annee_actuelle-1)
{
	$timestamp2 = mktime(24,0,0,12,31,$annee_actuelle-1);
	if (($_REQUEST['utilisateur']['contrat_fin_']=='') || ($annee2>$annee_actuelle)
	)
	$timestamp3 = mktime(24,0,0,12,31,$annee_actuelle);
	else $timestamp3 = mktime(24,0,0,$mois2,$jour2,$annee_actuelle);

	$calcul_possible=2;

}
$nbjour=365.;
if (isBissextile($annee1)) $nbjour=366.;
$resultat=0;
$resultat2=0;
if ($calcul_possible>0)
{
	$diff=$timestamp2-$timestamp1;
	$resultat=($diff/86400.)*($_REQUEST[ 'utilisateur' ][ 'quota_jours_' ])/$nbjour;
	$diff=$timestamp3-mktime(24,0,0,1,1,$annee_actuelle);;
	$resultat2=($diff/86400.)*($_REQUEST[ 'utilisateur' ][ 'quota_jours_' ])/$nbjour;
}
if ($calcul_possible==1)
{
$tempFl=$resultat-floor($resultat);
//mettre a jour CA
if ($tempFl<0.25) $_REQUEST[ 'utilisateur' ][ 'solde_ca_' ]=floor($resultat);
elseif ($tempFl>=0.25 && $tempFl<0.75) $_REQUEST[ 'utilisateur' ][ 'solde_ca_' ]=floor($resultat)+0.5;
elseif ($tempFl>=0.75) $_REQUEST[ 'utilisateur' ][ 'solde_ca_' ]=floor($resultat)+1;
$_REQUEST[ 'utilisateur' ][ 'solde_ca_1_' ]=0.;//Update CA-1
}
elseif ($calcul_possible==2)
{
$tempFl=$resultat2-floor($resultat2);
//mettre a jour CA
if ($tempFl<0.25) $_REQUEST[ 'utilisateur' ][ 'solde_ca_' ]=floor($resultat2);
elseif ($tempFl>=0.25 && $tempFl<0.75) $_REQUEST[ 'utilisateur' ][ 'solde_ca_' ]=floor($resultat2)+0.5;
elseif ($tempFl>=0.75) $_REQUEST[ 'utilisateur' ][ 'solde_ca_' ]=floor($resultat2)+1;

$tempFl=$resultat-floor($resultat);
//mettre a jour CA-1
if ($tempFl<0.25) $_REQUEST[ 'utilisateur' ][ 'solde_ca_1_' ]=floor($resultat);
elseif ($tempFl>=0.25 && $tempFl<0.75) $_REQUEST[ 'utilisateur' ][ 'solde_ca_1_' ]=floor($resultat)+0.5;
elseif ($tempFl>=0.75) $_REQUEST[ 'utilisateur' ][ 'solde_ca_1_' ]=floor($resultat)+1;
}
}//fin de if ($choix_effectue==1)
}//fin de if ($module_conge_charge==1)
}
//fin choix du type de contrat
///////////////////////////////////////////////////////////////////

if (isset( $_REQUEST[ 'utilisateur' ][ 'contrat_debut_' ])) $_SESSION[ 'utilisateur' ][ 'contrat_debut_' ]=$_REQUEST[ 'utilisateur' ][ 'contrat_debut_' ];
if (isset( $_REQUEST[ 'utilisateur' ][ 'contrat_fin_' ])) $_SESSION[ 'utilisateur' ][ 'contrat_fin_' ]=$_REQUEST[ 'utilisateur' ][ 'contrat_fin_' ];

//if ($module_conge_charge==1)
{
if (isset( $_REQUEST[ 'utilisateur' ][ 'solde_ca_' ])) $_SESSION[ 'utilisateur' ][ 'solde_ca_' ]=$_REQUEST[ 'utilisateur' ][ 'solde_ca_' ];
if (isset( $_REQUEST[ 'utilisateur' ][ 'solde_ca_1_' ])) $_SESSION[ 'utilisateur' ][ 'solde_ca_1_' ]=$_REQUEST[ 'utilisateur' ][ 'solde_ca_1_' ];
if (isset( $_REQUEST[ 'utilisateur' ][ 'solde_recup_' ])) $_SESSION[ 'utilisateur' ][ 'solde_recup_' ]=$_REQUEST[ 'utilisateur' ][ 'solde_recup_' ];
if (isset( $_REQUEST[ 'utilisateur' ][ 'solde_cet_' ])) $_SESSION[ 'utilisateur' ][ 'solde_cet_' ]=$_REQUEST[ 'utilisateur' ][ 'solde_cet_' ];
if (isset( $_REQUEST[ 'utilisateur' ][ 'quota_jours_' ])) $_SESSION[ 'utilisateur' ][ 'quota_jours_' ]=$_REQUEST[ 'utilisateur' ][ 'quota_jours_' ];
if (isset( $_REQUEST[ 'utilisateur' ][ 'quotite_' ])) $_SESSION[ 'utilisateur' ][ 'quotite_' ]=$_REQUEST[ 'utilisateur' ][ 'quotite_' ];
}

//variable de l'edition d'une intervention
if (! isset($_SESSION[ 'edition_utilisateur' ])) $_SESSION[ 'edition_utilisateur' ] = 0; 
//groupe:
if (!isset( $_SESSION[ 'login' ])) {$_SESSION[ 'login_indice' ]=0;$_SESSION[ 'login' ]='';}
if (isset($_GET["corresp2"])) {$_SESSION[ 'login_indice' ]=$_GET["corresp2"];
$_SESSION[ 'login' ]=$_SESSION[ 'utilisateur' ][ 'utilisateur' ][$_SESSION[ 'login_indice' ]];}

/*********************************************************************************
**********************  -H- Variables Messages 1ere connection  ******************
**********************************************************************************/
function maj_conges_non_valides($chem_conn)
{

include($chem_conn);
   
// Connecting, selecting database:
   $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
   or die('Could not connect: ' . mysqli_connect_error());
   mysqli_select_db($link,$mysql_base) or die('Could not select database');



   $query = 'SELECT ID_CONGE,UTILISATEUR,GROUPE,DEBUT_DATE,TYPE,NB_JOURS_OUVRES,INFORMER_GP,VALIDE FROM T_CONGE WHERE VALIDE=0 ORDER BY DEBUT_DATE';
   $result = mysqli_query($link,$query) or die('Erreur: ' . mysqli_error());

   $_SESSION['equipes_aprevenir']=array();
   $_SESSION['id_aprevenir']=array();
   $_SESSION['id_avalider']=array();

   $i=1;$j=1;$k=1;
   while ($line = mysqli_fetch_array($result, MYSQL_NUM)) {
	$_SESSION[ 'information' ]['id_nv'][$i]=$line[0];
	$_SESSION[ 'information' ]['utilisateur_nv'][$i]=$line[1];
	$_SESSION[ 'information' ]['type_nv'][$i]=$line[4];
	$_SESSION[ 'information' ]['nb_jours_ouvres_nv'][$i]=$line[5];
	$_SESSION[ 'information' ]['informer_gp_nv'][$i]=$line[6];

	$query2 = 'SELECT UTILISATEUR,STATUS FROM T_UTILISATEUR WHERE UTILISATEUR=\''.$line[1].'\' ';
	$result2 = mysqli_query($link,$query2);
	$line2 = mysqli_fetch_array($result2, MYSQL_NUM);
	$statut_util=$line2[1];
	mysqli_free_result($result2);

	if ($statut_util==3 || $statut_util==4 || $statut_util==6)
	$_SESSION[ 'information' ]['groupe_nv'][$i]='DIRECTION';
	else $_SESSION[ 'information' ]['groupe_nv'][$i]=$line[2];

	$query2 = 'SELECT DATE_FORMAT(\''.$line[3].'\',\'%d/%m/%Y\');';
	$result2 = mysqli_query($link,$query2);
	$line2 = mysqli_fetch_array($result2, MYSQL_NUM);
	$_SESSION[ 'information' ]['debut_nv'][$i]=$line2[0];
	mysqli_free_result($result2);

	$temps=explode("/",$_SESSION[ 'information' ]['debut_nv'][$i]);
	if (time()<mktime(0,0,0,$temps[1],$temps[0],$temps[2]))
	{
		$taille_tab=sizeof($_SESSION['equipes_aprevenir']);
		if (!in_array($_SESSION[ 'information']['groupe_nv'][$i], $_SESSION['equipes_aprevenir']))
		$_SESSION['equipes_aprevenir'][$taille_tab+1]= $_SESSION[ 'information'] ['groupe_nv'] [$i];
		$_SESSION['id_aprevenir'][$j]= $i;
		//correspondance entre numero checkbox et id conge...
		$_SESSION['checkbox_id'][$i]=$j;

		$j++;
	}
	else
	{
		$_SESSION['id_avalider'][$k]= $i;
		//correspondance entre numero checkbox et id conge...
		$_SESSION['checkbox_id2'][$i]=$k;
		 $k++;
	}
	$i++;
   }
   mysqli_free_result($result);
   $_SESSION[ 'nb_nv']=$i-1;
   $_SESSION[ 'nb_nv_standard']=$j-1;
   $_SESSION[ 'nb_nv_ddcd']=$k-1;
   mysqli_close($link);

	for ($i=1;$i<=$_SESSION[ 'nb_nv_ddcd'];$i++)
	$_SESSION[ 'avalider' ][$i]=0;

}



if (! isset($_SESSION[ 'administrer' ]) OR (isset($_GET[ 'admin_index' ]) &&$_GET[ 'admin_index' ] == 1)) $_SESSION[ 'administrer' ] = 0; 
if (isset( $_REQUEST[ 'administrer' ])) $_SESSION[ 'administrer' ]=1;

if (!isset ($_SESSION[ 'information' ]) || (isset($_GET["std"])))
{
/**
**/
   include $chemin_connection;

   // Connecting, selecting database:
   $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
   or die('Could not connect: ' . mysqli_connect_error());
   mysqli_select_db($link,$mysql_base) or die('Could not select database');

/////////////////////////utilisateurs en fin de contrat
   $query = 'SELECT NOM,PRENOM,GROUPE,CONTRAT_FIN FROM T_UTILISATEUR WHERE TO_DAYS(CONTRAT_FIN) - TO_DAYS(NOW()) <= 30 ORDER BY CONTRAT_FIN';
   $result = mysqli_query($link,$query) or die('Erreur: ' . mysqli_error());

   $i=1;
   while ($line = mysqli_fetch_array($result, MYSQL_NUM)) {
	$_SESSION[ 'information' ]['nom_ufc'][$i]=$line[0];
	$_SESSION[ 'information' ]['prenom_ufc'][$i]=$line[1];
	$_SESSION[ 'information' ]['groupe_ufc'][$i]=$line[2];

	$query2 = 'SELECT DATE_FORMAT(\''.$line[3].'\',\'%d/%m/%Y\');';
	$result2 = mysqli_query($link,$query2);
	$line2 = mysqli_fetch_array($result2, MYSQL_NUM);
	$_SESSION[ 'information' ]['contrat_fin_ufc'][$i]=$line2[0];
	mysqli_free_result($result2);

	$i++;
   }
   mysqli_free_result($result);
   $_SESSION[ 'nb_ufc']=$i-1;



   mysqli_close($link);

/////////////////////////conges non valides
maj_conges_non_valides($chemin_connection);


////////////////////////////////////////////
//les cases a cocher "standard":
   if (!isset($_SESSION[ 'aprevenir' ]))
   {
	for ($i=1;$i<=$_SESSION[ 'nb_nv_standard'];$i++)
	$_SESSION[ 'aprevenir' ][$i]=1;
   }
//les cases a cocher "date de début de congé dépassé":
   if (!isset($_SESSION[ 'avalider' ]))
   {
	for ($i=1;$i<=$_SESSION[ 'nb_nv_ddcd'];$i++)
//	$_SESSION[ 'avalider' ][$i]=1;
	$_SESSION[ 'avalider' ][$i]=0;
   }
}//fin de if (!isset ($_SESSION[ 'information' ]) || (isset($_GET["std"])))

for ($i=1;$i<=$_SESSION[ 'nb_nv_standard'];$i++)
{
   if (isset($_REQUEST[ 'aprevenir' ][$i])) $_SESSION[ 'aprevenir' ][$i] = $_REQUEST[ 'aprevenir' ][$i];
}

for ($i=1;$i<=$_SESSION[ 'nb_nv_ddcd'];$i++)
{
   if (isset($_REQUEST[ 'avalider' ][$i])) $_SESSION[ 'avalider' ][$i] = $_REQUEST[ 'avalider' ][$i];
}


//////////////////////////////////////////////////////////////////////////
$message_relancer= '';
if (isset( $_REQUEST[ 'relancer' ])) 
{
   include $chemin_connection;

   // Connecting, selecting database:
   $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
   or die('Could not connect: ' . mysqli_connect_error());
   mysqli_select_db($link,$mysql_base) or die('Could not select database');

   for ($i=1;$i<=sizeof($_SESSION['equipes_aprevenir']);$i++)
   {
	$query = 'SELECT RESPONSABLE FROM T_CORRESPONDANCE WHERE GROUPE="'.$_SESSION['equipes_aprevenir'][$i].'" ';
	$result = mysqli_query($link,$query) or die('Erreur: ' . mysqli_error());
	$line = mysqli_fetch_array($result, MYSQL_NUM);
	
	$message = "<body>Bonjour responsable ".$line[0].",<br> voici la liste des congés à valider:<br> ";
	$case_cochee=0;
	for ($j=1;$j<=sizeof($_SESSION['id_aprevenir']);$j++)
	{
		if (($_SESSION[ 'information' ]['groupe_nv'][$_SESSION['id_aprevenir'][$j]] == $_SESSION['equipes_aprevenir'][$i]))
		{
		$ind_check=$_SESSION['checkbox_id'][$_SESSION['id_aprevenir'][$j]];
		$num_conge=$_SESSION[ 'information' ]['id_nv'][$_SESSION['id_aprevenir'][$j]];
		if ($_SESSION[ 'aprevenir' ][$ind_check]==1)
		{
			$message .= "- <a href=".$chemin_mel."?dec=".$num_conge."> ".$chemin_mel."?dec=".$num_conge."</a>.<br>";
			$case_cochee=1;
		}
		}
	}
	if ($case_cochee==1)
	{
		$subject = "CONGE: Rappel";

	//demandeur de conge (quelquesoit son status)
		if ($mode_test) $TO = $mel_test;
		else $TO = $line[0].'@'.$domaine;

		$message .= "Bien cordialement,</body>";
		$message=utf8_decode($message);
		send_mail($TO, $message, $subject, $_SESSION['connection']['utilisateur'].'@'.$domaine, $_SESSION['connection']['nom']." ".$_SESSION['connection']['prenom']);
		$message_relancer= "<br>-> Mail(s) envoy&eacute;(s) au(x) responsable(s) <-";
	}
   mysqli_free_result($result);
   }

   mysqli_close($link);
}

/////////////////////////////////////////////////////////////////////////
$message_validselec= '';
if (isset( $_REQUEST[ 'validermoultes' ])) 
{
   include $chemin_connection;

   // Connecting, selecting database:
   $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
   or die('Could not connect: ' . mysqli_connect__error());
   mysqli_select_db($link,$mysql_base) or die('Could not select database');

 //  for ($i=1;$i<=sizeof($_SESSION['equipes_aprevenir']);$i++)
   {
//$message_validselec.=' '.$i;
/*	$query = 'SELECT RESPONSABLE FROM T_CORRESPONDANCE WHERE GROUPE="'.$_SESSION['equipes_aprevenir'][$i].'" ';
	$result = mysql_query($query) or die('Erreur: ' . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_NUM);
	
	$message = "<body>Bonjour responsable ".$line[0].",<br> voici la liste des congés à valider:<br> ";
*/
	$case_cochee=0;
	for ($j=1;$j<=sizeof($_SESSION['id_avalider']);$j++)
	{
//		if (($_SESSION[ 'information' ]['groupe_nv'][$_SESSION['id_aprevenir'][$j]] == $_SESSION['equipes_aprevenir'][$i]))
//		{
		$ind_check=$_SESSION['checkbox_id2'][$_SESSION['id_avalider'][$j]];
		$num_conge=$_SESSION[ 'information' ]['id_nv'][$_SESSION['id_avalider'][$j]];
		if ($_SESSION[ 'avalider' ][$ind_check]==1)
		{
//$message_validselec.=' '.$num_conge;
			//$message .= "- <a href=".$chemin_mel."?dec=".$num_conge."> ".$chemin_mel."?dec=".$num_conge."</a>.<br>";
			$case_cochee=1;
   //validation de la demande:
	$query = 'UPDATE T_CONGE SET VALIDE="1", NB_JOURS_OUVRES="'.$_SESSION["information"]["nb_jours_ouvres_nv"][$ind_check].'" WHERE ID_CONGE="'.$num_conge.'"';
   	$result = mysqli_query($link,$query) or die('Requete de modification du status: ' . mysqli_error());
   	//mysql_free_result($result);

///////////////////////////////////////////////////////////////////////////////////
//mettre a jour le solde des conges
/*   if (!$annule && $_SESSION["conge"]["type"]<3)
*/
if ($_SESSION[ 'information' ]['type_nv'][$ind_check]<3)
   {
	$query2 = 'SELECT SOLDE_CA,SOLDE_CA_1,SOLDE_CET,SOLDE_RECUP FROM T_CONGE_SOLDE WHERE UTILISATEUR=\''.$_SESSION[ 'information' ]['utilisateur_nv'][$ind_check].'\' ';
	$result2 = mysqli_query($link,$query2) or die('Query '.$query2.'| failed: ' . mysqli_error());
	if ($result2)
	{
		$line2 = mysqli_fetch_array($result2, MYSQL_NUM);
		$sca = $line2[0];
		$sca1 = $line2[1];
		$scet = $line2[2];
		$srecup = $line2[3];
		mysqli_free_result($result2);
	}
//$message_validselec.=' '. $sca .' ' .$sca1 .' '.$scet .' '.$srecup.'<br>';


	if ($_SESSION[ 'information' ]['type_nv'][$ind_check]==0)
	{
	   if ($sca1>0)
	   {
		if ($sca1-$_SESSION['information']['nb_jours_ouvres_nv'][$ind_check]<0) 
		{
	   //echo 'update ca-1';
		$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CA_1="0" WHERE UTILISATEUR="'.$_SESSION[ "information" ]["utilisateur_nv"][$ind_check].'"';
		$result = mysqli_query($link,$query) or die('Requete de modification du status: ' . mysqli_error());
		//mysql_free_result($result);
		$i_util=0;
		for ($i_util=0;$i_util<$_SESSION['nb_utilisateur'];$i_util++)
		if ($_SESSION[ 'utilisateur' ][ 'utilisateur'][$i_util]==$_SESSION[ 'information' ]['utilisateur_nv'][$ind_check]) $_SESSION[ 'utilisateur' ][ 'solde_ca_1'][$i_util]=0;
		//$_SESSION[ 'conge' ][ 'CA-1' ]=0;
		//$_SESSION[ 'solde' ][ 'CA-1' ]=0;
	   //echo 'update ca';
		$diff=$_SESSION['information']['nb_jours_ouvres_nv'][$ind_check]-$sca1;
		$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CA=SOLDE_CA-'.$diff.' WHERE UTILISATEUR="'.$_SESSION[ "information" ]["utilisateur_nv"][$ind_check].'"';
		$result = mysqli_query($link,$query) or die('Requete de modification du status: ' . mysqli_error());
		//mysql_free_result($result);
		$i_util=0;
		for ($i_util=0;$i_util<$_SESSION['nb_utilisateur'];$i_util++)
		if ($_SESSION[ 'utilisateur' ][ 'utilisateur'][$i_util]==$_SESSION[ 'information' ]['utilisateur_nv'][$ind_check]) $_SESSION[ 'utilisateur' ][ 'solde_ca'][$i_util]=$_SESSION[ 'utilisateur' ][ 'solde_ca'][$i_util]-$diff;
		//$_SESSION[ 'conge' ][ 'CA' ]=$_SESSION[ 'conge' ][ 'CA' ]-$diff;
		//$_SESSION[ 'solde' ][ 'CA' ]=$_SESSION[ 'conge' ][ 'CA' ];
		}
		else
		{
	   //echo 'update ca-1';
		$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CA_1=SOLDE_CA_1-'.$_SESSION["information"]["nb_jours_ouvres_nv"][$ind_check].' WHERE UTILISATEUR="'.$_SESSION[ "information" ]["utilisateur_nv"][$ind_check].'"';
		$result = mysqli_query($link,$query) or die('Requete de modification du status: ' . mysqli_error());
		//mysql_free_result($result);
		$i_util=0;
		for ($i_util=0;$i_util<$_SESSION['nb_utilisateur'];$i_util++)
		if ($_SESSION[ 'utilisateur' ][ 'utilisateur'][$i_util]==$_SESSION[ 'information' ]['utilisateur_nv'][$ind_check]) $_SESSION[ 'utilisateur' ][ 'solde_ca_1'][$i_util]=$_SESSION[ 'utilisateur' ][ 'solde_ca_1'][$i_util]-$_SESSION["information"]["nb_jours_ouvres_nv"][$ind_check];
		//$_SESSION[ 'conge' ][ 'CA-1' ]=$_SESSION[ 'conge' ][ 'CA-1' ]- $_SESSION["conge"]["nb_jours_ouvres_recalc"];
		//$_SESSION[ 'solde' ][ 'CA-1' ]=$_SESSION[ 'conge' ][ 'CA-1' ];
		}
	   }
	   else
	   {
	   //echo 'update ca';
		$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CA=SOLDE_CA-'.$_SESSION["information"]["nb_jours_ouvres_nv"][$ind_check].' WHERE UTILISATEUR="'.$_SESSION[ "information" ]["utilisateur_nv"][$ind_check].'"';
		$result = mysqli_query($link,$query) or die('Requete de modification du status: ' . mysqli_error());
		//mysql_free_result($result);
		$i_util=0;
		for ($i_util=0;$i_util<$_SESSION['nb_utilisateur'];$i_util++)
		if (isset($_SESSION[ 'utilisateur' ][ 'utilisateur'][$i_util]) && isset($_SESSION[ 'information' ]['utilisateur_nv'][$ind_check]) && $_SESSION[ 'utilisateur' ][ 'utilisateur'][$i_util]==$_SESSION[ 'information' ]['utilisateur_nv'][$ind_check]) $_SESSION[ 'utilisateur' ][ 'solde_ca'][$i_util]=$_SESSION[ 'utilisateur' ][ 'solde_ca'][$i_util]-$_SESSION["information"]["nb_jours_ouvres_nv"][$ind_check];
		//$_SESSION[ 'conge' ][ 'CA' ]=$_SESSION[ 'conge' ][ 'CA' ] -$_SESSION["conge"]["nb_jours_ouvres_recalc"];
		//$_SESSION[ 'solde' ][ 'CA' ]=$_SESSION[ 'conge' ][ 'CA' ];
	   }
	}
	else if ($_SESSION["conge"]["type"]==1)
	{
	//echo 'update cet';
		$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CET=SOLDE_CET-'.$_SESSION["information"]["nb_jours_ouvres_nv"][$ind_check].' WHERE UTILISATEUR="'.$_SESSION[ "information" ]["utilisateur_nv"][$ind_check].'"';
		$result = mysqli_query($link,$query) or die('Requete de modification du status: ' . mysqli_error());
		//mysql_free_result($result);
		$i_util=0;
		for ($i_util=0;$i_util<$_SESSION['nb_utilisateur'];$i_util++)
		if ($_SESSION[ 'utilisateur' ][ 'utilisateur'][$i_util]==$_SESSION[ 'information' ]['utilisateur_nv'][$ind_check]) $_SESSION[ 'utilisateur' ][ 'solde_cet'][$i_util]=$_SESSION[ 'utilisateur' ][ 'solde_cet'][$i_util]-$_SESSION["information"]["nb_jours_ouvres_nv"][$ind_check];
		//$_SESSION[ 'conge' ][ 'CET' ]=$_SESSION[ 'conge' ][ 'CET' ]-$_SESSION["conge"]["nb_jours_ouvres_recalc"];
		//$_SESSION[ 'solde' ][ 'CET' ]=$_SESSION[ 'conge' ][ 'CET' ];
	}
	else if ($_SESSION["conge"]["type"]==2)
	{
	//echo 'update recup';
		$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_RECUP=SOLDE_RECUP-'.$_SESSION["information"]["nb_jours_ouvres_nv"][$ind_check].' WHERE UTILISATEUR="'.$_SESSION[ "information" ]["utilisateur_nv"][$ind_check].'"';
		$result = mysqli_query($link,$query) or die('Requete de modification du status: ' . mysqli_error());
		//mysql_free_result($result);
		$i_util=0;
		for ($i_util=0;$i_util<$_SESSION['nb_utilisateur'];$i_util++)
		if ($_SESSION[ 'utilisateur' ][ 'utilisateur'][$i_util]==$_SESSION[ 'information' ]['utilisateur_nv'][$ind_check]) $_SESSION[ 'utilisateur' ][ 'solde_recup'][$i_util]=$_SESSION[ 'utilisateur' ][ 'solde_recup'][$i_util]-$_SESSION["information"]["nb_jours_ouvres_nv"][$ind_check];
		//$_SESSION[ 'conge' ][ 'RECUP' ]=$_SESSION[ 'conge' ][ 'RECUP' ]-$_SESSION["conge"]["nb_jours_ouvres_recalc"];
		//$_SESSION[ 'solde' ][ 'RECUP' ]=$_SESSION[ 'conge' ][ 'RECUP' ];
	}

//	$message_demande.= "<br>-> Mise &agrave; jour du solde <-";	
 /**/  }//fin de if (!$annule && $_SESSION["conge"]["type"]<3)

	$message_validselec= "-> Cong&eacute;(s) valid&eacute;(s) <-";

///////////////////////////////////////////////////////////////////////////////////////
//envoie de mail
/**/

	$subject = "CONGE: Validation demande de congé (ID=".$num_conge.")";

   //demandeur de conge (quelquesoit son status)
	if ($mode_test) $TO = $mel_test;
	else $TO = $_SESSION[ 'information' ]['utilisateur_nv'][$ind_check].'@'.$domaine;
	$message = "<body>Bonjour ".$_SESSION[ 'information' ]['utilisateur_nv'][$ind_check]." ,<br> votre demande de congé a été validée,<br> ";
	$message .= "suivez le lien <a href=".$chemin_mel."?dec=".$num_conge.">".$chemin_mel."?dec=".$num_conge."</a> pour l'afficher.</body>";
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['connection']['utilisateur'].'@'.$domaine, $_SESSION['connection']['nom']." ".$_SESSION['connection']['prenom']);

   //au responsable d'equipe ou de service du groupe selectionné pour le conge
	if ($directeur!=$_SESSION[ 'information' ]['utilisateur_nv'][$ind_check])
	{
	$pourqui='responsable';

	$query = 'SELECT RESPONSABLE FROM T_CORRESPONDANCE WHERE GROUPE="'.$_SESSION[ "information" ]["groupe_nv"][$ind_check].'"';
	$result = mysqli_query($link,$query) or die('Requete de modification du status: ' . mysqli_error());
	$line3 = mysqli_fetch_array($result, MYSQL_NUM);
	$util=$line3[0];
	mysqli_free_result($result);

//	$util=$_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']];
	if ($util==$directeur) {$util=$directeur;$pourqui='directeur';}
	if ($mode_test) $TO = $mel_test;
	else $TO = $util.'@'.$domaine;
	$message = "<body>Bonjour ".$pourqui." ".$util.",<br> ";
	$message .= "le congé <a href=".$chemin_mel."?dec=".$num_conge.">".$chemin_mel."?dec=".$num_conge."</a> émis par ".$_SESSION[ 'information' ]['utilisateur_nv'][$ind_check]." est validé.</body>";
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
	$message .= "le congé <a href=".$chemin_mel."?dec=".$num_conge.">".$chemin_mel."?dec=".$num_conge."</a> émis par ".$_SESSION[ 'information' ]['utilisateur_nv'][$ind_check]." est validé.</body>";
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['connection']['utilisateur'].'@'.$domaine, $_SESSION['connection']['nom']." ".$_SESSION['connection']['prenom']);
	}
	mysqli_free_result($result2);
/*
   //envoie aux utilisateurs du meme groupe (si case cochee)
	if ($_SESSION[ 'mailGroupe' ]==1)
	{
	$query2 = 'SELECT UTILISATEUR FROM T_UTILISATEUR WHERE GROUPE=\''.$_SESSION[ 'conge' ][ 'groupe' ].'\'';
	$result2 = mysql_query($query2);
	while ($line2 = mysql_fetch_array($result2, MYSQL_NUM))
	{
		if ($line2[0]!=$_SESSION['conge']['utilisateur'] && $line2[0]!=$_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']])
		{
		if ($mode_test) $TO = $mel_test;
		else $TO = $line2[0].'@'.$domaine;
	
//		$message = "<body>Bonjour collègue ".$line2[0].",<br> ";
		$message = "<body>Cher(e) collègue,<br> ";
		$debAMouPM="Matin";
		if ($_SESSION[conge][date_AM]==1) $debAMouPM="Aprés-midi";
		$finAMouPM="Matin";
		if ($_SESSION[conge][date_PM]==1) $finAMouPM="Aprés-midi";
		$message .= "je suis en congé du ".$_SESSION[conge][date_debut]."(".$debAMouPM.") au  ".$_SESSION[conge][date_fin]."(".$finAMouPM.").<br>Cordialement,<br>".$_SESSION[conge][prenom]."  ".$_SESSION[conge][nom]."</body>";
//		$message .= "je suis en congé du ".$_SESSION[conge][date_debut]." au  ".$_SESSION[conge][date_fin].".<br>Cordialement,<br>".$_SESSION[conge][prenom]."  ".$_SESSION[conge][nom]."</body>";
		$message=utf8_decode($message);
		send_mail($TO, $message, $subject, $_SESSION['conge']['utilisateur'].'@'.$domaine, $_SESSION['conge']['nom']." ".$_SESSION['conge']['prenom']);
		}
	}
	mysql_free_result($result2);
	}

	//$message_demande.= "<br>-> Envoi de mails <-";
  	mysql_close($link);
*/
///////////////////////////////////////////////////////////////////////////////////////

		$message_validselec.= "<br>-> Mail(s) envoy&eacute;(s) au(x) responsable(s) <-";



		}//fin if ($_SESSION[ 'avalider' ][$ind_check]==1)
		//}
	}//fin for ($j=1;$j<=sizeof($_SESSION['id_avalider']);$j++)


	if ($case_cochee==1)
	{
		maj_conges_non_valides($chemin_connection);

/*		$subject = "CONGE: Rappel";

	//demandeur de conge (quelquesoit son status)
		if ($mode_test) $TO = $mel_test;
		else $TO = $line[0].'@'.$domaine;

		$message .= "Bien cordialement,</body>";
		$message=utf8_decode($message);
		send_mail($TO, $message, $subject, $_SESSION['connection']['utilisateur'].'@'.$domaine, $_SESSION['connection']['nom']." ".$_SESSION['connection']['prenom']);
		$message_relancer= "<br>-> Mail(s) envoy&eacute;(s) au(x) responsable(s) <-";
*/	}
  // mysql_free_result($result);
/**/   }

   //mysql_close($link);
}

//fin les cases a cocher
////////////////////////////////////////////

/*********************************************************************************
****************************** -I- FONCTIONS GROUPE     **************************
**********************************************************************************/

$message_OK='';
$message_KO='';

////////////////////////////////////////////////////////////////////////////////////
//en deux temps car on passe d'abord en mode edition:
if ($_SESSION[ 'connection' ][ 'admin' ]==1)
if ( ($_SESSION[ 'action' ]=='edit') )
{
   $_SESSION['edition_correspondance']=1;
   $_SESSION[ 'correspondance' ][ 'groupe_' ]=$_SESSION[ 'groupe' ];
   $_SESSION[ 'correspondance' ][ 'responsable_' ]=$_SESSION[ 'correspondance' ][ 'responsable' ][$_SESSION[ 'groupe_indice' ]];
   $_SESSION[ 'correspondance' ][ 'responsable2_' ]=$_SESSION[ 'correspondance' ][ 'responsable2' ][$_SESSION[ 'groupe_indice' ]];
   $_SESSION[ 'correspondance' ][ 'administratif_' ]=$_SESSION[ 'correspondance' ][ 'administratif' ][$_SESSION[ 'groupe_indice' ]];
   $_SESSION[ 'correspondance' ][ 'administratif2_' ]=$_SESSION[ 'correspondance' ][ 'administratif2' ][$_SESSION[ 'groupe_indice' ]];
   $_SESSION[ 'correspondance' ][ 'valid_mission_' ]=$_SESSION[ 'correspondance' ][ 'valid_missions' ][$_SESSION[ 'groupe_indice' ]];
   $_SESSION[ 'correspondance' ][ 'valid_conge_' ]=$_SESSION[ 'correspondance' ][ 'valid_conges' ][$_SESSION[ 'groupe_indice' ]];
   $_SESSION[ 'correspondance' ][ 'entite_depensiere_' ]=$_SESSION[ 'correspondance' ][ 'entite_depensiere' ][$_SESSION[ 'groupe_indice' ]];
   $_SESSION[ 'action' ]='';
}

////////////////////////////////////////////////////////////////////////////////////
/////////////annuler l action 
if ($_SESSION[ 'connection' ][ 'admin' ]==1)
if ( isset($_REQUEST[ 'correspondance' ][ 'annul' ] ))
{
   $_SESSION['edition_correspondance']=0;
   $_SESSION[ 'correspondance' ][ 'groupe_' ]='';
   $_SESSION[ 'correspondance' ][ 'responsable_' ]='';
   $_SESSION[ 'correspondance' ][ 'responsable2_' ]='';
   $_SESSION[ 'correspondance' ][ 'administratif_' ]='';
   $_SESSION[ 'correspondance' ][ 'administratif2_' ]='';
   $_SESSION[ 'correspondance' ][ 'valid_mission_' ]=0;
   $_SESSION[ 'correspondance' ][ 'valid_conge_' ]=1;
   $_SESSION[ 'correspondance' ][ 'entite_depensiere_' ]=0;
}

////////////////////////////////////////////////////////////////////////////////////
//editer definitivement
if ($_SESSION[ 'connection' ][ 'admin' ]==1)
if ( isset($_REQUEST[ 'correspondance' ][ 'editer' ]) )
{
   $annule=0;
   if ($_SESSION[ 'correspondance' ][ 'groupe_' ]=='')
   {
	$message_KO= 'La partie "groupe" n\'est pas renseign&eacute;e: Action annul&eacute;e.';
	$annule=1;
   }
   elseif ($_SESSION[ 'correspondance' ][ 'responsable_' ]=='')
   {
	$message_KO= 'La partie "responsable" n\'est pas renseign&eacute;e: Action annul&eacute;e.';
	$annule=1;
   }
//   elseif ($_SESSION[ 'correspondance' ][ 'responsable2_' ]=='')
//   {
//	$message_KO= 'La partie "responsable des missions" n\'est pas renseign&eacute;e: Action annul&eacute;e.';
//	$annule=1;
//   }
   elseif ($_SESSION[ 'correspondance' ][ 'administratif_' ]=='')
   {
	$message_KO= 'La partie "administrateur" n\'est pas renseign&eacute;e: Action annul&eacute;e.';
	$annule=1;
   }
   if (!$annule)
   {

/**
**/
   include $chemin_connection;

// Connecting, selecting database:
   $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
   or die('Could not connect: ' . mysqli_connect_error());
   mysqli_select_db($link,$mysql_base) or die('Could not select database');

   $query = 'update T_CORRESPONDANCE set RESPONSABLE=\''.$_SESSION[ 'correspondance' ][ 'responsable_' ].'\',RESPONSABLE2=\''.$_SESSION[ 'correspondance' ][ 'responsable2_' ].'\',ADMINISTRATIF=\''.$_SESSION[ 'correspondance' ][ 'administratif_' ].'\',ADMINISTRATIF2=\''.$_SESSION[ 'correspondance' ][ 'administratif2_' ].'\',VALID_MISSIONS='.$_SESSION[ 'correspondance' ][ 'valid_mission_' ].',VALID_CONGES='.$_SESSION[ 'correspondance' ][ 'valid_conge_' ].',ENTITE_DEPENSIERE='.$_SESSION[ 'correspondance' ][ 'entite_depensiere_' ].' where GROUPE=\''.$_SESSION[ 'correspondance' ][ 'groupe_'].'\'';

   $result = mysqli_query($link,$query) or die('Requete <br>'.$query.'<br>Erreur:<br> ' . mysqli_error());
   //mysql_free_result($result);

//Informer si les utilisateurs existes dans la base
   $query = 'select * from T_UTILISATEUR where UTILISATEUR=\''.$_SESSION[ 'correspondance' ][ 'responsable_' ].'\'';
   $result = mysqli_query($link,$query);
   if (($result) && !(mysqli_fetch_array($result, MYSQL_NUM)))
   {
	$message_KO= 'L\'utilisateur "'.$_SESSION[ 'correspondance' ][ 'responsable_' ].'" n\'existe pas.';
	$annule=1;
	mysqli_free_result($result);
   }
   $query = 'select * from T_UTILISATEUR where UTILISATEUR=\''.$_SESSION[ 'correspondance' ][ 'responsable2_' ].'\'';
   $result = mysqli_query($link,$query);
   if (($result) && !(mysqli_fetch_array($result, MYSQL_NUM)) && ($_SESSION[ 'correspondance' ][ 'responsable2_' ]!=''))
   {
	if ($message_KO=='') $message_KO= 'L\'utilisateur "'.$_SESSION[ 'correspondance' ][ 'responsable2_' ].'" n\'existe pas.';
	else $message_KO= $message_KO.'<br>L\'utilisateur "'.$_SESSION[ 'correspondance' ][ 'responsable2_' ].'" n\'existe pas.';
	$annule=1;
	mysqli_free_result($result);
   }

   $query = 'select * from T_UTILISATEUR where UTILISATEUR=\''.$_SESSION[ 'correspondance' ][ 'administratif_' ].'\'';
   $result = mysqli_query($link,$query);
   if ($result && !mysqli_fetch_array($result, MYSQL_NUM))
   {
	if ($message_KO=='') $message_KO= 'L\'utilisateur "'.$_SESSION[ 'correspondance' ][ 'administratif_' ].'" n\'existe pas.';
	else $message_KO= $message_KO.'<br>L\'utilisateur "'.$_SESSION[ 'correspondance' ][ 'administratif_' ].'" n\'existe pas.';
	$annule=1;
	mysqli_free_result($result);
   }	

   if ($_SESSION[ 'correspondance' ][ 'administratif2_' ]!='')
   {
	$query = 'select * from T_UTILISATEUR where UTILISATEUR=\''.$_SESSION[ 'correspondance' ][ 'administratif2_' ].'\'';
	$result = mysqli_query($link,$query);
	if ($result && !mysqli_fetch_array($result, MYSQL_NUM))
	{
	if ($message_KO=='') $message_KO= 'L\'utilisateur "'.$_SESSION[ 'correspondance' ][ 'administratif2_' ].'" n\'existe pas.';
	else $message_KO= $message_KO.'<br>L\'utilisateur "'.$_SESSION[ 'correspondance' ][ 'administratif2_' ].'" n\'existe pas.';
	$annule=1;
	mysqli_free_result($result);
	}	
   }
  
   if (!$annule)
   {
//Mise a jour de la liste:
   $_SESSION[ 'correspondance' ][ 'responsable' ][$_SESSION[ 'groupe_indice' ]]=$_SESSION[ 'correspondance' ][ 'responsable_' ];
   $_SESSION[ 'correspondance' ][ 'responsable2' ][$_SESSION[ 'groupe_indice' ]]=$_SESSION[ 'correspondance' ][ 'responsable2_' ];
   $_SESSION[ 'correspondance' ][ 'administratif' ][$_SESSION[ 'groupe_indice' ]]=$_SESSION[ 'correspondance' ][ 'administratif_' ];
   $_SESSION[ 'correspondance' ][ 'administratif2' ][$_SESSION[ 'groupe_indice' ]]=$_SESSION[ 'correspondance' ][ 'administratif2_' ];
   $_SESSION[ 'correspondance' ][ 'valid_missions' ][$_SESSION[ 'groupe_indice' ]]=$_SESSION[ 'correspondance' ][ 'valid_mission_' ];
   $_SESSION[ 'correspondance' ][ 'valid_conges' ][$_SESSION[ 'groupe_indice' ]]=$_SESSION[ 'correspondance' ][ 'valid_conge_' ];
   $_SESSION[ 'correspondance' ][ 'entite_depensiere' ][$_SESSION[ 'groupe_indice' ]]=$_SESSION[ 'correspondance' ][ 'entite_depensiere_' ];

//Message
   $message_OK='Le groupe "'.$_SESSION[ 'correspondance' ][ 'groupe_' ].'" a &eacute;t&eacute; mis &agrave; jour.';
//////////////vidage des champs 
   $_SESSION[ 'correspondance' ][ 'groupe_' ]='';
   $_SESSION[ 'correspondance' ][ 'responsable_' ]='';
   $_SESSION[ 'correspondance' ][ 'responsable2_' ]='';
   $_SESSION[ 'correspondance' ][ 'administratif_' ]='';
   $_SESSION[ 'correspondance' ][ 'administratif2_' ]='';
   $_SESSION[ 'correspondance' ][ 'valid_mission_' ]=0;
   $_SESSION[ 'correspondance' ][ 'valid_conge_' ]=1;
   $_SESSION[ 'correspondance' ][ 'entite_depensiere_' ]=0;
    }
   mysqli_close($link);
}//fin de if !annule

$_SESSION['edition_correspondance']=0;
$_SESSION[ 'action' ]='';
}

////////////////////////////////////////////////////////////////////////////////////
//suppression d un groupe
if ($_SESSION[ 'connection' ][ 'admin' ] == 1)
if ( ($_SESSION[ 'action' ]=='suppr') )
{
$annule=0;

if (!$annule)
{

/**
**/
include $chemin_connection;

// Connecting, selecting database:
$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
or die('Could not connect: ' . mysqli_connect_error());
mysqli_select_db($link,$mysql_base) or die('Could not select database');

$query = 'delete from T_CORRESPONDANCE where GROUPE=\''.$_SESSION[ 'groupe' ].'\'';
//echo $query;

$result = mysqli_query($link,$query) or die('Requete <br>'.$query.'<br>Erreur:<br> ' . mysqli_error());
//mysql_free_result($result);

//mise a jour de la liste
$query2 = 'SELECT * FROM T_CORRESPONDANCE ORDER BY GROUPE';
$result2 = mysqli_query($link,$query2) or die('Erreur: ' . mysqli_error());
//	$_SESSION['groupe'][0]="Equipe, contrat ou service";
$i=1;
while ($line2 = mysqli_fetch_array($result2, MYSQL_NUM)) {
      $_SESSION[ 'correspondance' ]['groupe'][$i]=$line2[0];
      $_SESSION[ 'correspondance' ]['responsable'][$i]=$line2[1];
      $_SESSION[ 'correspondance' ]['responsable2'][$i]=$line2[2];
      $_SESSION[ 'correspondance' ]['administratif'][$i]=$line2[3];
      $_SESSION[ 'correspondance' ]['administratif2'][$i]=$line2[4];
      $_SESSION[ 'correspondance' ]['valid_missions'][$i]=$line2[5];
      $_SESSION[ 'correspondance' ]['valid_conges'][$i]=$line2[6];
      $_SESSION[ 'correspondance' ]['entite_depensiere'][$i]=$line2[7];
      $i++;
}
$_SESSION[ 'nb_groupe']=$i-1;
mysqli_free_result($result2);
//fin de mise a jour leiste de correspondance

mysqli_close($link);

//Message
$message_OK='Le groupe "'.$_SESSION[ 'groupe' ].'" a &eacute;t&eacute; supprim&eacute;.';

}//fin de if (!$annule)
$_SESSION[ 'action' ]='';
}

////////////////////////////////////////////////////////////////////////////////////
//nouveau groupe
if ($_SESSION[ 'connection' ][ 'admin' ]==1)
if ( isset($_REQUEST[ 'correspondance' ][ 'nouvelle' ]) )
{
$annule=0;
if ($_SESSION[ 'correspondance' ][ 'groupe_' ]=='')
{
	$message_KO= 'La partie "groupe" n\'est pas renseign&eacute;e: Action annul&eacute;e.';
	$annule=1;
}
elseif ($_SESSION[ 'correspondance' ][ 'responsable_' ]=='')
{
	$message_KO= 'La partie "responsable" n\'est pas renseign&eacute;e: Action annul&eacute;e.';
	$annule=1;
}
//elseif ($_SESSION[ 'correspondance' ][ 'responsable2_' ]=='')
//{
//	$message_KO= 'La partie "responsable missions" n\'est pas renseign&eacute;e: Action annul&eacute;e.';
//	$annule=1;
//}
elseif ($_SESSION[ 'correspondance' ][ 'administratif_' ]=='')
{
	$message_KO= 'La partie "administrateur" n\'est pas renseign&eacute;e: Action annul&eacute;e.';
	$annule=1;
}

if (!$annule)
{
include $chemin_connection;

// Connecting, selecting database:
$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
or die('Could not connect: ' . mysqli_connect_error());
mysqli_select_db($link,$mysql_base) or die('Could not select database');

//verifier que le groupe n existe pas deja dans la base
$query = 'select * from T_CORRESPONDANCE where GROUPE=\''.$_SESSION[ 'correspondance' ][ 'groupe_' ].'\'';
$result = mysqli_query($link,$query);
if (($result) && (mysqli_fetch_array($result, MYSQL_NUM))) { $message_KO= 'Le groupe "'.$_SESSION[ 'correspondance' ][ 'groupe_' ].'" existe d&eacute;j&agrave;.'; $annule=1; }
mysqli_free_result($result);

if ($message_KO=='')
{

//Informer si les utilisateurs existent dans la base
$query = 'select * from T_UTILISATEUR where UTILISATEUR=\''.$_SESSION[ 'correspondance' ][ 'responsable_' ].'\'';
$result = mysqli_query($link,$query);
if (($result) && !(mysqli_fetch_array($result, MYSQL_NUM)))
{
$message_KO= 'L\'utilisateur "'.$_SESSION[ 'correspondance' ][ 'responsable_' ].'" n\'existe pas.';
mysqli_free_result($result);
}
$query = 'select * from T_UTILISATEUR where UTILISATEUR=\''.$_SESSION[ 'correspondance' ][ 'responsable2_' ].'\'';
$result = mysqli_query($link,$query);
if (($result) && !(mysqli_fetch_array($result, MYSQL_NUM)) && ($_SESSION[ 'correspondance' ][ 'responsable2_' ]!=''))
{
if ($message_KO=='') $message_KO= 'L\'utilisateur "'.$_SESSION[ 'correspondance' ][ 'responsable2_' ].'" n\'existe pas.';
else $message_KO= $message_KO.'<br>L\'utilisateur "'.$_SESSION[ 'correspondance' ][ 'responsable2_' ].'" n\'existe pas.';
mysqli_free_result($result);
}

$query = 'select * from T_UTILISATEUR where UTILISATEUR=\''.$_SESSION[ 'correspondance' ][ 'administratif_' ].'\'';
$result = mysqli_query($link,$query);
if ($result && !mysqli_fetch_array($result, MYSQL_NUM))
{
if ($message_KO=='') $message_KO= 'L\'utilisateur "'.$_SESSION[ 'correspondance' ][ 'administratif_' ].'" n\'existe pas.';
else $message_KO= $message_KO.'<br>L\'utilisateur "'.$_SESSION[ 'correspondance' ][ 'administratif_' ].'" n\'existe pas.';
mysqli_free_result($result);
}	

if ($_SESSION[ 'correspondance' ][ 'administratif2_' ]!='')
{
$query = 'select * from T_UTILISATEUR where UTILISATEUR=\''.$_SESSION[ 'correspondance' ][ 'administratif2_' ].'\'';
$result = mysqli_query($link,$query);
if ($result && !mysqli_fetch_array($result, MYSQL_NUM))
{
if ($message_KO=='') $message_KO= 'L\'utilisateur "'.$_SESSION[ 'correspondance' ][ 'administratif2_' ].'" n\'existe pas.';
else $message_KO= $message_KO.'<br>L\'utilisateur "'.$_SESSION[ 'correspondance' ][ 'administratif2_' ].'" n\'existe pas.';
mysqli_free_result($result);
}	

}

if($message_KO=='')
{
//requete principale
$query = 'insert into T_CORRESPONDANCE(GROUPE,RESPONSABLE,RESPONSABLE2,ADMINISTRATIF,ADMINISTRATIF2,VALID_MISSIONS,VALID_CONGES,ENTITE_DEPENSIERE)  VALUES (\''.$_SESSION[ 'correspondance' ][ 'groupe_' ].'\',\''.$_SESSION[ 'correspondance' ][ 'responsable_'].'\',\''.$_SESSION[ 'correspondance' ][ 'responsable2_'].'\',\''.$_SESSION['correspondance']['administratif_'].'\',\''.$_SESSION['correspondance']['administratif2_'].'\','.$_SESSION[ 'correspondance' ]['valid_mission_'].','.$_SESSION[ 'correspondance' ]['valid_conge_'].','.$_SESSION[ 'correspondance' ]['entite_depensiere_'].')';

$result = mysqli_query($link,$query) or die('Requete <br>'.$query.'<br>Erreur:<br> ' . mysqli_error());
//mysql_free_result($result);

$message_OK='Le groupe "'.$_SESSION[ 'correspondance' ][ 'groupe_' ].'" a &eacute;t&eacute; cr&eacute;&eacute;.';
}

//////////////vidage des champs 
$_SESSION[ 'correspondance' ][ 'groupe_' ]='';
$_SESSION[ 'correspondance' ][ 'responsable_' ]='';
$_SESSION[ 'correspondance' ][ 'responsable2_' ]='';
$_SESSION[ 'correspondance' ][ 'administratif_' ]='';
$_SESSION[ 'correspondance' ][ 'administratif2_' ]='';
$_SESSION[ 'correspondance' ][ 'valid_mission_' ]=0;
$_SESSION[ 'correspondance' ][ 'valid_conge_' ]=1;
$_SESSION[ 'correspondance' ][ 'entite_depensiere_' ]=0;

//mise a jour de la liste
$query2 = 'SELECT * FROM T_CORRESPONDANCE ORDER BY GROUPE';
$result2 = mysqli_query($link,$query2) or die('Erreur: ' . mysqli_error());
$i=1;
while ($line2 = mysqli_fetch_array($result2, MYSQL_NUM)) {
      $_SESSION[ 'correspondance' ]['groupe'][$i]=$line2[0];
      $_SESSION[ 'correspondance' ]['responsable'][$i]=$line2[1];
      $_SESSION[ 'correspondance' ]['responsable2'][$i]=$line2[2];
      $_SESSION[ 'correspondance' ]['administratif'][$i]=$line2[3];
      $_SESSION[ 'correspondance' ]['administratif2'][$i]=$line2[4];
      $_SESSION[ 'correspondance' ]['valid_missions'][$i]=$line2[5];
      $_SESSION[ 'correspondance' ]['valid_conges'][$i]=$line2[6];
      $_SESSION[ 'correspondance' ]['entite_depensiere'][$i]=$line2[7];
      $i++;
}
$_SESSION[ 'nb_groupe']=$i-1;
mysqli_free_result($result2);
//fin de mise a jour liste de correspondance

}//fin du if ($message_KO=='')

mysqli_close($link);

} //fin de if !annule
}

/*********************************************************************************
****************************** -J- FONCTIONS UTILISATEURS     ********************
**********************************************************************************/
////////////////////////////////////////////////////////////////////////////////////
//en deux temps car on passe d'abord en mode edition:
if ($_SESSION[ 'connection' ][ 'admin' ]==1)
if ( ($_SESSION[ 'action' ]=='edit2') )
{
$_SESSION['edition_utilisateur']=1;

$_SESSION[ 'utilisateur' ][ 'utilisateur_' ]=$_SESSION[ 'utilisateur' ][ 'utilisateur' ][$_SESSION[ 'login_indice' ]];
$_SESSION[ 'utilisateur' ][ 'login_cas_' ]=$_SESSION[ 'utilisateur' ][ 'login_cas' ][$_SESSION[ 'login_indice' ]];
$_SESSION[ 'utilisateur' ][ 'nom_' ]=$_SESSION[ 'utilisateur' ][ 'nom' ][$_SESSION[ 'login_indice' ]];
$_SESSION[ 'utilisateur' ][ 'prenom_' ]=$_SESSION[ 'utilisateur' ][ 'prenom' ][$_SESSION[ 'login_indice' ]];
$_SESSION[ 'utilisateur' ][ 'motdepasse_' ]=$_SESSION[ 'utilisateur' ][ 'motdepasse' ][$_SESSION[ 'login_indice' ]];
$_SESSION[ 'utilisateur' ][ 'groupe_' ]=$_SESSION[ 'utilisateur' ][ 'groupe' ][$_SESSION[ 'login_indice' ]];
$_SESSION[ 'utilisateur' ][ 'status_' ]=$_SESSION[ 'utilisateur' ][ 'status' ][$_SESSION[ 'login_indice' ]];
$_SESSION[ 'utilisateur' ][ 'admin_' ]=$_SESSION[ 'utilisateur' ][ 'admin' ][$_SESSION[ 'login_indice' ]];


$_SESSION[ 'utilisateur' ][ 'contrat_debut_' ]=$_SESSION[ 'utilisateur' ][ 'contrat_debut' ][$_SESSION[ 'login_indice' ]];
$_SESSION[ 'utilisateur' ][ 'contrat_fin_' ]=$_SESSION[ 'utilisateur' ][ 'contrat_fin' ][$_SESSION[ 'login_indice' ]];
$_SESSION[ 'utilisateur' ][ 'contrat_type_' ]=$_SESSION[ 'utilisateur' ][ 'contrat_type' ][$_SESSION[ 'login_indice' ]];

//if ($module_conge_charge==1)
{
$_SESSION[ 'utilisateur' ][ 'solde_ca_' ]=$_SESSION[ 'utilisateur' ][ 'solde_ca' ][$_SESSION[ 'login_indice' ]];
$_SESSION[ 'utilisateur' ][ 'solde_ca_1_' ]=$_SESSION[ 'utilisateur' ][ 'solde_ca_1' ][$_SESSION[ 'login_indice' ]];
$_SESSION[ 'utilisateur' ][ 'solde_recup_' ]=$_SESSION[ 'utilisateur' ][ 'solde_recup' ][$_SESSION[ 'login_indice' ]];
$_SESSION[ 'utilisateur' ][ 'solde_cet_' ]=$_SESSION[ 'utilisateur' ][ 'solde_cet' ][$_SESSION[ 'login_indice' ]];
$_SESSION[ 'utilisateur' ][ 'quota_jours_' ]=$_SESSION[ 'utilisateur' ][ 'quota_jours' ][$_SESSION[ 'login_indice' ]];
$_SESSION[ 'utilisateur' ][ 'quotite_' ]=$_SESSION[ 'utilisateur' ][ 'quotite' ][$_SESSION[ 'login_indice' ]];
}

$_SESSION[ 'action' ]='';
}

////////////////////////////////////////////////////////////////////////////////////
/////////////annuler l action 
if ($_SESSION[ 'connection' ][ 'admin' ]==1)
if ( isset($_REQUEST[ 'utilisateur' ][ 'annul' ] ))
{
$_SESSION['edition_utilisateur']=0;

$_SESSION[ 'utilisateur' ][ 'utilisateur_' ]='';
$_SESSION[ 'utilisateur' ][ 'nom_' ]='';
$_SESSION[ 'utilisateur' ][ 'prenom_' ]='';
$_SESSION[ 'utilisateur' ][ 'motdepasse_' ]='';
$_SESSION[ 'utilisateur' ][ 'groupe_' ]='';
$_SESSION[ 'utilisateur' ][ 'status_' ]=1;
$_SESSION[ 'utilisateur' ][ 'admin_' ]=0;
$_SESSION[ 'utilisateur' ][ 'login_cas_' ]='';

$_SESSION[ 'utilisateur' ][ 'contrat_type_' ]=$type_contrats[0][0];
$_SESSION[ 'utilisateur' ][ 'contrat_debut_' ]=strftime("%d/%m/%Y");
$_SESSION[ 'utilisateur' ][ 'contrat_fin_' ]='';

//if ($module_conge_charge==1)
{
$_SESSION[ 'utilisateur' ][ 'solde_ca_' ]=0;
$_SESSION[ 'utilisateur' ][ 'solde_ca_1_' ]=0;
$_SESSION[ 'utilisateur' ][ 'solde_recup_' ]=0;
$_SESSION[ 'utilisateur' ][ 'solde_cet_' ]=0;
//Pour le quota jours
if(!empty($_SESSION[ 'utilisateur' ][ 'contrat_type_' ]))
{
    foreach($type_contrats as $contrat)
      if($_SESSION[ 'utilisateur' ][ 'contrat_type_' ] == $contrat[0])
	$_SESSION[ 'utilisateur' ][ 'quota_jours_' ]=$contrat[1];
}
else $_SESSION[ 'utilisateur' ][ 'quota_jours_' ]=$type_contrats[1][1]; //Sinon on initialise au nb jours du 1er contrat ([0] -> Choisir un contrat, 0)

$_SESSION[ 'utilisateur' ][ 'quotite_' ]=100;
}
}

////////////////////////////////////////////////////////////////////////////////////
//editer definitivement
if ($_SESSION[ 'connection' ][ 'admin' ]==1)
if ( isset($_REQUEST[ 'utilisateur' ][ 'editer' ]) )
{
$annule=0;
//decoupage des dates:
if(preg_match( '`^\d{1,2}/\d{1,2}/\d{4}$`' ,$_SESSION['utilisateur']['contrat_debut_']))
{
  list($jour1, $mois1, $annee1) = explode('/', $_SESSION['utilisateur']['contrat_debut_']); 
  list($jour2, $mois2, $annee2) = explode('/', '31/12/'.date('Y'));
  if(!empty($_SESSION['utilisateur']['contrat_fin_']))
    list($jour2, $mois2, $annee2) = explode('/', $_SESSION['utilisateur']['contrat_fin_']);
}

if ($_SESSION[ 'utilisateur' ][ 'nom_' ]=='')
{
	$message_KO= 'La partie "Nom" n\'est pas renseign&eacute;e: Action annul&eacute;e.';
	$annule=1;
}
elseif ($_SESSION[ 'utilisateur' ][ 'prenom_' ]=='')
{
	$message_KO= 'La partie "Pr&eacute;nom" n\'est pas renseign&eacute;e: Action annul&eacute;e.';
	$annule=1;
}
elseif ($_SESSION[ 'utilisateur' ][ 'groupe_' ]=='')
{
	$message_KO= 'La partie "groupe" n\'est pas renseign&eacute;e: Action annul&eacute;e.';
	$annule=1;
}
elseif($_SESSION['utilisateur']['contrat_debut_']!='' && !checkdate($mois1,$jour1,$annee1))
{
		$message_KO= 'Le champs "Debut contrat" est invalide.<br>Action annul&eacute;e.';
		$annule=1;
}
elseif($_SESSION['utilisateur']['contrat_fin_']!='' && !checkdate($mois2,$jour2,$annee2))
{
		$message_KO= 'Le champs "Fin contrat" est invalide.<br>Action annul&eacute;e.';
		$annule=1;
}
else //if (($module_conge_charge==1))
{
if (!is_numeric($_SESSION[ 'utilisateur' ][ 'solde_ca_' ])) {$message_KO= 'Le champs "Solde CA" est invalide.<br>Action annul&eacute;e.';$annule=1;}
elseif (!is_numeric($_SESSION[ 'utilisateur' ][ 'solde_ca_1_' ])) {$message_KO= 'Le champs "Solde CA-1" est invalide.<br>Action annul&eacute;e.';$annule=1;}
elseif (!is_numeric($_SESSION[ 'utilisateur' ][ 'solde_recup_' ])) {$message_KO= 'Le champs "Solde RECUP" est invalide.<br>Action annul&eacute;e.';$annule=1;}
elseif (!is_numeric($_SESSION[ 'utilisateur' ][ 'solde_cet_' ])) {$message_KO= 'Le champs "Solde CET" est invalide.<br>Action annul&eacute;e.';$annule=1;}
elseif (!is_numeric($_SESSION[ 'utilisateur' ][ 'quota_jours_' ])) {$message_KO= 'Le champs "Quota jours" est invalide.<br>Action annul&eacute;e.';$annule=1;}
elseif (!is_numeric($_SESSION[ 'utilisateur' ][ 'quotite_' ])) {$message_KO= 'Le champs "Quotite" est invalide.<br>Action annul&eacute;e.';$annule=1;}
}
if (!$annule)
{
/**
**/
include $chemin_connection;

// Connecting, selecting database:
$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
or die('Could not connect: ' . mysqli_connect_error());
mysqli_select_db($link,$mysql_base) or die('Could not select database');

$ddeb='CONTRAT_DEBUT=NULL,';
$dfin='CONTRAT_FIN=NULL,';
if ($_SESSION['utilisateur']['contrat_debut_']!='') $ddeb='CONTRAT_DEBUT=STR_TO_DATE(\''.$_SESSION['utilisateur']['contrat_debut_'].'\',\'%d/%m/%Y\'),';
if ($_SESSION['utilisateur']['contrat_fin_']!='') $dfin='CONTRAT_FIN=STR_TO_DATE(\''.$_SESSION['utilisateur']['contrat_fin_'].'\',\'%d/%m/%Y\'),';

$query = 'update T_UTILISATEUR set NOM=\''.$_SESSION[ 'utilisateur' ][ 'nom_' ].'\',PRENOM=\''.$_SESSION[ 'utilisateur' ][ 'prenom_' ].'\',GROUPE=\''.$_SESSION[ 'utilisateur' ][ 'groupe_' ].'\',STATUS='.$_SESSION[ 'utilisateur' ][ 'status_' ].',ADMIN='.$_SESSION[ 'utilisateur' ][ 'admin_' ].', LOGIN_CAS=\''.$_SESSION[ 'utilisateur' ][ 'login_cas_' ].'\','.$ddeb.''.$dfin.'CONTRAT_TYPE=\''.$_SESSION[ 'utilisateur' ][ 'contrat_type_' ].'\' where UTILISATEUR=\''.$_SESSION[ 'utilisateur' ][ 'utilisateur_'].'\'';
//echo $query;
$result = mysqli_query($link,$query) or die('Requete <br>'.$query.'<br>Erreur:<br> ' . mysqli_error());
//mysql_free_result($result);

//if ($module_conge_charge==1)
{
$query = 'update T_CONGE_SOLDE set SOLDE_CA='.$_SESSION[ 'utilisateur' ][ 'solde_ca_' ].',SOLDE_CA_1='.$_SESSION[ 'utilisateur' ][ 'solde_ca_1_' ].',SOLDE_RECUP='.$_SESSION[ 'utilisateur' ][ 'solde_recup_' ].',SOLDE_CET='.$_SESSION[ 'utilisateur' ][ 'solde_cet_' ].',QUOTA_JOURS='.$_SESSION[ 'utilisateur' ][ 'quota_jours_' ].',QUOTITE='.$_SESSION[ 'utilisateur' ][ 'quotite_' ].' where UTILISATEUR=\''.$_SESSION[ 'utilisateur' ][ 'utilisateur_'].'\'';
//echo $query;
$result = mysqli_query($link,$query) or die('Requete <br>'.$query.'<br>Erreur:<br> ' . mysqli_error());
//mysql_free_result($result);
}

//Informer si le groupe est existe dans la base
$query = 'select * from T_CORRESPONDANCE where GROUPE=\''.$_SESSION[ 'utilisateur' ][ 'groupe_' ].'\'';
$result = mysqli_query($link,$query);
if (($result) && !(mysqli_fetch_array($result, MYSQL_NUM)))
{
$message_KO= 'Le groupe "'.$_SESSION[ 'utilisateur' ][ 'groupe_' ].'" n\'existe pas.';
$annule=1;
mysqli_free_result($result);
}

//////////FIN FUNCTION////////
mysqli_close($link);
}//fin de if (!$annule)

if($message_KO=='')
{
//Mise a jour de la liste:
$_SESSION[ 'utilisateur' ][ 'nom' ][$_SESSION[ 'login_indice' ]]=$_SESSION[ 'utilisateur' ][ 'nom_' ];
$_SESSION[ 'utilisateur' ][ 'prenom' ][$_SESSION[ 'login_indice' ]]=$_SESSION[ 'utilisateur' ][ 'prenom_' ];
$_SESSION[ 'utilisateur' ][ 'groupe' ][$_SESSION[ 'login_indice' ]]=$_SESSION[ 'utilisateur' ][ 'groupe_' ];
$_SESSION[ 'utilisateur' ][ 'status' ][$_SESSION[ 'login_indice' ]]=$_SESSION[ 'utilisateur' ][ 'status_' ];
$_SESSION[ 'utilisateur' ][ 'admin' ][$_SESSION[ 'login_indice' ]]=$_SESSION[ 'utilisateur' ][ 'admin_' ];
$_SESSION[ 'utilisateur' ][ 'login_cas' ][$_SESSION[ 'login_indice' ]]=$_SESSION[ 'utilisateur' ][ 'login_cas_' ];

$_SESSION[ 'utilisateur' ][ 'contrat_type' ][$_SESSION[ 'login_indice' ]]=$_SESSION[ 'utilisateur' ][ 'contrat_type_' ];
$_SESSION[ 'utilisateur' ][ 'contrat_debut' ][$_SESSION[ 'login_indice' ]]=$_SESSION[ 'utilisateur' ][ 'contrat_debut_' ];
$_SESSION[ 'utilisateur' ][ 'contrat_fin' ][$_SESSION[ 'login_indice' ]]=$_SESSION[ 'utilisateur' ][ 'contrat_fin_' ];

//if ($module_conge_charge==1)
//{
$_SESSION[ 'utilisateur' ][ 'solde_ca' ][$_SESSION[ 'login_indice' ]]=$_SESSION[ 'utilisateur' ][ 'solde_ca_' ];
$_SESSION[ 'utilisateur' ][ 'solde_ca_1' ][$_SESSION[ 'login_indice' ]]=$_SESSION[ 'utilisateur' ][ 'solde_ca_1_' ];
$_SESSION[ 'utilisateur' ][ 'solde_recup' ][$_SESSION[ 'login_indice' ]]=$_SESSION[ 'utilisateur' ][ 'solde_recup_' ];
$_SESSION[ 'utilisateur' ][ 'solde_cet' ][$_SESSION[ 'login_indice' ]]=$_SESSION[ 'utilisateur' ][ 'solde_cet_' ];
$_SESSION[ 'utilisateur' ][ 'quota_jours' ][$_SESSION[ 'login_indice' ]]=$_SESSION[ 'utilisateur' ][ 'quota_jours_' ];
$_SESSION[ 'utilisateur' ][ 'quotite' ][$_SESSION[ 'login_indice' ]]=$_SESSION[ 'utilisateur' ][ 'quotite_' ];
//}

//Message
$message_OK='L\'utilisateur "'.$_SESSION[ 'utilisateur' ][ 'utilisateur_' ].'" a &eacute;t&eacute; mis &agrave; jour.';

//////////////vidage des champs 
$_SESSION[ 'utilisateur' ][ 'utilisateur_' ]='';
$_SESSION[ 'utilisateur' ][ 'nom_' ]='';
$_SESSION[ 'utilisateur' ][ 'prenom_' ]='';
$_SESSION[ 'utilisateur' ][ 'motdepasse_' ]='';
$_SESSION[ 'utilisateur' ][ 'groupe_' ]='';
if ($_SESSION[ 'selection_groupe' ]!=$tous_les_utilisateurs) 
$_SESSION[ 'utilisateur' ][ 'groupe_' ]=$_SESSION[ 'selection_groupe' ];
$_SESSION[ 'utilisateur' ][ 'status_' ]=1;
$_SESSION[ 'utilisateur' ][ 'admin_' ]=0;
$_SESSION[ 'utilisateur' ][ 'login_cas_' ]='';

$_SESSION[ 'utilisateur' ][ 'contrat_type_' ]=$type_contrats[0][0];
$_SESSION[ 'utilisateur' ][ 'contrat_debut_' ]=strftime("%d/%m/%Y");
$_SESSION[ 'utilisateur' ][ 'contrat_fin_' ]='';

//if ($module_conge_charge==1)
//{
$_SESSION[ 'utilisateur' ][ 'solde_ca_' ]=0;
$_SESSION[ 'utilisateur' ][ 'solde_ca_1_' ]=0;
$_SESSION[ 'utilisateur' ][ 'solde_recup_' ]=0;
$_SESSION[ 'utilisateur' ][ 'solde_cet_' ]=0;
//Pour le quota jours
if(!empty($_SESSION[ 'utilisateur' ][ 'contrat_type_' ]))
{
    foreach($type_contrats as $contrat)
      if($_SESSION[ 'utilisateur' ][ 'contrat_type_' ] == $contrat[0])
	$_SESSION[ 'utilisateur' ][ 'quota_jours_' ]=$contrat[1];
}
else $_SESSION[ 'utilisateur' ][ 'quota_jours_' ]=$type_contrats[1][1]; //Sinon on initialise au nb jours du 1er contrat ([0] -> Choisir un contrat, 0)
$_SESSION[ 'utilisateur' ][ 'quotite_' ]=100;
//}

$_SESSION['edition_utilisateur']=0;
$_SESSION[ 'action' ]='';
}
}
////////////////////////////////////////////////////////////////////////////////////
//suppression d un utilisateur
if ($_SESSION[ 'connection' ][ 'admin' ] == 1)
if ( ($_SESSION[ 'action' ]=='suppr2') )
{
$annule=0;

if (!$annule)
{
/**
**/
include $chemin_connection;

// Connecting, selecting database:
$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
or die('Could not connect: ' . mysqli_connect_error());
mysqli_select_db($link,$mysql_base) or die('Could not select database');

$query = 'delete from T_UTILISATEUR where UTILISATEUR=\''.$_SESSION[ 'login' ].'\'';
$result = mysqli_query($link,$query) or die('Requete <br>'.$query.'<br>Erreur:<br> ' . mysqli_error());
//mysql_free_result($result);

//if ($module_conge_charge==1)
{
$query = 'delete from T_CONGE_SOLDE where UTILISATEUR=\''.$_SESSION[ 'login' ].'\'';
$result = mysqli_query($link,$query) or die('Requete <br>'.$query.'<br>Erreur:<br> ' . mysqli_error());
//mysql_free_result($result);
}

//mise a jour de la liste
   $query2 = 'SELECT * FROM T_UTILISATEUR ORDER BY GROUPE';
   $result2 = mysqli_query($link,$query2) or die('Erreur: ' . mysqli_error());
   $i=1;

   unset($_SESSION['taille']);

   while ($line2 = mysqli_fetch_array($result2, MYSQL_NUM)) {
	$_SESSION[ 'utilisateur' ]['utilisateur'][$i]=$line2[0];
	//taille des groupes pour le trait bottom de l'affichage du tableau
	if (!isset($_SESSION['taille'][$line2[6]])) $_SESSION['taille'][$line2[6]]=1;
	else $_SESSION['taille'][$line2[6]]++;
	
//	if ($module_conge_charge==1) //sinon faire un join sur $query2
	{
	$query3 = 'SELECT * FROM T_CONGE_SOLDE where UTILISATEUR=\''.$line2[0].'\'';
	$result3 = mysqli_query($link,$query3) or die('Erreur: ' . mysqli_error());
	$line3 = mysqli_fetch_array($result3, MYSQL_NUM);
	$_SESSION[ 'utilisateur' ]['solde_ca'][$i]=$line3[1];
	$_SESSION[ 'utilisateur' ]['solde_ca_1'][$i]=$line3[2];
	$_SESSION[ 'utilisateur' ]['solde_recup'][$i]=$line3[3];
	$_SESSION[ 'utilisateur' ]['solde_cet'][$i]=$line3[4];
	$_SESSION[ 'utilisateur' ]['quota_jours'][$i]=$line3[5];
	$_SESSION[ 'utilisateur' ]['quotite'][$i]=$line3[6];
	mysqli_free_result($result3);
	}

	$_SESSION[ 'utilisateur' ]['nom'][$i]=$line2[1];
	$_SESSION[ 'utilisateur' ]['prenom'][$i]=$line2[2];
	$_SESSION[ 'utilisateur' ]['motdepasse'][$i]=substr(md5($line2[3]),-10).'...';

	$_SESSION[ 'utilisateur' ]['groupe'][$i]=$line2[6];
	$_SESSION[ 'utilisateur' ]['status'][$i]=$line2[7];
	$_SESSION[ 'utilisateur' ]['admin'][$i]=$line2[8];
	$_SESSION[ 'utilisateur' ]['login_cas'][$i]=$line2[12];

	$_SESSION[ 'utilisateur' ]['contrat_type'][$i]=$line2[9];

	$query3 = 'SELECT DATE_FORMAT(\''.$line2[10].'\',\'%d/%m/%Y\');';
	$result3 = mysqli_query($link,$query3);
	$line3 = mysqli_fetch_array($result3, MYSQL_NUM);
	$_SESSION[ 'utilisateur' ]['contrat_debut'][$i]=$line3[0];
	mysqli_free_result($result3);
	$query3 = 'SELECT DATE_FORMAT(\''.$line2[11].'\',\'%d/%m/%Y\');';
	$result3 = mysqli_query($link,$query3);
	$line3 = mysqli_fetch_array($result3, MYSQL_NUM);
	$_SESSION[ 'utilisateur' ]['contrat_fin'][$i]=$line3[0];
	mysqli_free_result($result3);

	$i++;
   }
   $_SESSION[ 'nb_utilisateur']=$i-1;
   mysqli_free_result($result2);

mysqli_close($link);

//Message
$message_OK='L\'utilisateur "'.$_SESSION[ 'login' ].'" a &eacute;t&eacute; supprim&eacute;.';
}// fin de if (!$annule)
$_SESSION[ 'action' ]='';
//lors de la suppression, l'url contien l'action de suppression
//alors un rafraichissement de page entraine une nouvelle suppression
//pour contourner le probleme:
//header("location:".$_SERVER[ 'PHP_SELF' ]."?sid=" . $sid."");
header("location:".$_SERVER[ 'PHP_SELF' ]);
}

////////////////////////////////////////////////////////////////////////////////////
//nouvel utilisateur ajouter
if ($_SESSION[ 'connection' ][ 'admin' ]==1)
if ( isset($_REQUEST[ 'utilisateur' ][ 'nouvel' ]) )
{
$annule=0;
//decoupage des dates:
list($jour1, $mois1, $annee1) = explode('/', $_SESSION['utilisateur']['contrat_debut_']); 
list($jour2, $mois2, $annee2) = explode('/', '31/12/'.date('Y'));
if(!empty($_SESSION['utilisateur']['contrat_fin_']))
  list($jour2, $mois2, $annee2) = explode('/', $_SESSION['utilisateur']['contrat_fin_']);
$timestamp1 = mktime(0,0,0,$mois1,$jour1,$annee1); 
$timestamp2 = mktime(12,0,0,$mois2,$jour2,$annee2);//12 heure pour compter le dernier jour

if ($_SESSION[ 'utilisateur' ][ 'utilisateur_' ]=='')
{
	$message_KO= 'La partie "Nom d\'utilisateur" n\'est pas renseign&eacute;e: Action annul&eacute;e.';
	$annule=1;
}else
if ($_SESSION[ 'utilisateur' ][ 'nom_' ]=='')
{
	$message_KO= 'La partie "Nom" n\'est pas renseign&eacute;e: Action annul&eacute;e.';
	$annule=1;
}
elseif ($_SESSION[ 'utilisateur' ][ 'prenom_' ]=='')
{
	$message_KO= 'La partie "Pr&eacute;nom" n\'est pas renseign&eacute;e: Action annul&eacute;e.';
	$annule=1;
}
elseif ($_SESSION[ 'utilisateur' ][ 'motdepasse_' ]=='')
{
	$message_KO= 'La partie "Mot de passe" n\'est pas renseign&eacute;e: Action annul&eacute;e.';
	$annule=1;
}
elseif ($_SESSION[ 'utilisateur' ][ 'groupe_' ]=='')
{
	$message_KO= 'La partie "groupe" n\'est pas renseign&eacute;e: Action annul&eacute;e.';
	$annule=1;
}
elseif($_SESSION['utilisateur']['contrat_debut_']!='' && !checkdate($mois1,$jour1,$annee1))
{
	$message_KO= 'Le champs "Debut contrat" est invalide.<br>Action annul&eacute;e.';
	$annule=1;
}
elseif($_SESSION['utilisateur']['contrat_fin_']!='' && !checkdate($mois2,$jour2,$annee2))
{
	$message_KO= 'Le champs "Fin contrat" est invalide.<br>Action annul&eacute;e.';
	$annule=1;
}
elseif ($_SESSION['utilisateur']['contrat_debut_']!='' && $_SESSION['utilisateur']['contrat_fin_']!='' && $timestamp2<$timestamp1)
{
	$message_KO= 'Le date "Fin contrat" pr&eacute;c&egrave;de la date "D&eacute;but contrat".<br>Action annul&eacute;e.';
	$annule=1;
}
elseif ($_SESSION['utilisateur']['contrat_type_']==$type_contrats[0][0])
{
	$message_KO= 'Vous devez s&eacute;lectionner un type de contrat.<br>Action annul&eacute;e.';
	$annule=1;
}
//je commente le if car finalement lors de la creation, il faut creer un enregistrement
//dans le table T_CONGE_SOLDE au cas ou l'admin decide d'utiliser le module CONGE plus tard
//elseif (($module_conge_charge==1))
elseif ((1))
{
if (!is_numeric($_SESSION[ 'utilisateur' ][ 'solde_ca_' ])) {$message_KO= 'Le champs "Solde CA" est invalide.<br>Action annul&eacute;e.';$annule=1;}
elseif (!is_numeric($_SESSION[ 'utilisateur' ][ 'solde_ca_1_' ])) {$message_KO= 'Le champs "Solde CA-1" est invalide.<br>Action annul&eacute;e.';$annule=1;}
elseif (!is_numeric($_SESSION[ 'utilisateur' ][ 'solde_recup_' ])) {$message_KO= 'Le champs "Solde RECUP" est invalide.<br>Action annul&eacute;e.';$annule=1;}
elseif (!is_numeric($_SESSION[ 'utilisateur' ][ 'solde_cet_' ])) {$message_KO= 'Le champs "Solde CET" est invalide.<br>Action annul&eacute;e.';$annule=1;}
elseif (!is_numeric($_SESSION[ 'utilisateur' ][ 'quota_jours_' ])) {$message_KO= 'Le champs "Quota jours" est invalide.<br>Action annul&eacute;e.';$annule=1;}
elseif (!is_numeric($_SESSION[ 'utilisateur' ][ 'quotite_' ])) {$message_KO= 'Le champs "Quotite" est invalide.<br>Action annul&eacute;e.';$annule=1;}
}

if (!$annule)
{
include $chemin_connection;

// Connecting, selecting database:
$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
or die('Could not connect: ' . mysqli_connect_error());
mysqli_select_db($link,$mysql_base) or die('Could not select database');

//verifier que l'utilisateur n existe pas deja dans la base
$query = 'select * from T_UTILISATEUR where UTILISATEUR=\''.$_SESSION[ 'utilisateur' ][ 'utilisateur_' ].'\'';
$result = mysqli_query($link,$query);
if (($result) && (mysqli_fetch_array($result, MYSQL_NUM))) $message_KO= 'L\'utilisateur "'.$_SESSION[ 'utilisateur' ][ 'utilisateur_' ].'" existe d&eacute;j&agrave;.';
mysqli_free_result($result);

if ($message_KO=='')
{
$ddeb='NULL';
$dfin='NULL';
if ($_SESSION['utilisateur']['contrat_debut_']!='') $ddeb='STR_TO_DATE(\''.$_SESSION['utilisateur']['contrat_debut_'].'\',\'%d/%m/%Y\')';
if ($_SESSION['utilisateur']['contrat_fin_']!='') $dfin='STR_TO_DATE(\''.$_SESSION['utilisateur']['contrat_fin_'].'\',\'%d/%m/%Y\')';

//requete principale 1
$query = 'insert into T_UTILISATEUR(UTILISATEUR,NOM,PRENOM,MOTDEPASSE,GROUPE,STATUS,ADMIN,CONTRAT_TYPE,CONTRAT_DEBUT,CONTRAT_FIN,LOGIN_CAS)  VALUES (\''.$_SESSION[ 'utilisateur' ][ 'utilisateur_' ].'\',\''.$_SESSION[ 'utilisateur' ][ 'nom_'].'\',\''.$_SESSION['utilisateur']['prenom_'].'\',\''.base64_encode($_SESSION['utilisateur' ]['motdepasse_']).'\',\''.$_SESSION['utilisateur']['groupe_'].'\','.$_SESSION[ 'utilisateur' ]['status_'].','.$_SESSION[ 'utilisateur' ]['admin_'].',\''.$_SESSION[ 'utilisateur' ]['contrat_type_'].'\','.$ddeb.','.$dfin.',\''.$_SESSION[ 'utilisateur' ]['login_cas_'].'\')';
//echo $query;
$result = mysqli_query($link,$query) or die('Requete <br>'.$query.'<br>Erreur:<br> ' . mysqli_error());
//mysql_free_result($result);

//je commente le if car finalement lors de la creation, il faut creer un enregistrement
//dans le table T_CONGE_SOLDE au cas ou l'admin decide d'utiliser le module CONGE
//if ($module_conge_charge==1) 
{
//requete principale 2
$query = 'insert into T_CONGE_SOLDE(UTILISATEUR,SOLDE_CA,SOLDE_CA_1,SOLDE_RECUP,SOLDE_CET,QUOTA_JOURS,QUOTITE)  VALUES (\''.$_SESSION[ 'utilisateur' ][ 'utilisateur_' ].'\','.$_SESSION[ 'utilisateur' ][ 'solde_ca_'].','.$_SESSION['utilisateur']['solde_ca_1_'].','.$_SESSION['utilisateur']['solde_recup_'].','.$_SESSION[ 'utilisateur' ]['solde_cet_'].','.$_SESSION[ 'utilisateur' ]['quota_jours_'].','.$_SESSION[ 'utilisateur' ]['quotite_'].')';
//echo $query;
$result = mysqli_query($link,$query) or die('Requete <br>'.$query.'<br>Erreur:<br> ' . mysqli_error());
//mysql_free_result($result);
}

//Message
$message_OK='L\'utilisateur "'.$_SESSION[ 'utilisateur' ][ 'utilisateur_' ].'" a &eacute;t&eacute; cr&eacute;&eacute;.';

//Informer si le groupe est present dans la base
$query = 'select * from T_CORRESPONDANCE where GROUPE=\''.$_SESSION[ 'utilisateur' ][ 'groupe_' ].'\'';
$result = mysqli_query($link,$query);
if (($result) && !(mysqli_fetch_array($result, MYSQL_NUM)))
{
$message_KO= 'Le groupe "'.$_SESSION[ 'utilisateur' ][ 'groupe_' ].'" n\'existe pas.';
$annule=1;
mysqli_free_result($result);
}

//////////////vidage des champs 
$_SESSION[ 'utilisateur' ][ 'utilisateur_' ]='';
$_SESSION[ 'utilisateur' ][ 'nom_' ]='';
$_SESSION[ 'utilisateur' ][ 'prenom_' ]='';
$_SESSION[ 'utilisateur' ][ 'motdepasse_' ]='';
$_SESSION[ 'utilisateur' ][ 'groupe_' ]='';
if ($_SESSION[ 'selection_groupe' ]!=$tous_les_utilisateurs) 
$_SESSION[ 'utilisateur' ][ 'groupe_' ]=$_SESSION[ 'selection_groupe' ];
$_SESSION[ 'utilisateur' ][ 'status_' ]=1;
$_SESSION[ 'utilisateur' ][ 'admin_' ]=0;
$_SESSION[ 'utilisateur' ][ 'login_cas_' ]='';

$_SESSION[ 'utilisateur' ][ 'contrat_type_' ]=$type_contrats[0][0];
$_SESSION[ 'utilisateur' ][ 'contrat_debut_' ]=strftime("%d/%m/%Y");
$_SESSION[ 'utilisateur' ][ 'contrat_fin_' ]='';

//je commente le if car finalement lors de la creation, il faut creer un enregistrement
//dans le table T_CONGE_SOLDE au cas ou l'admin decide d'utiliser le module CONGE
//if ($module_conge_charge==1)
{
$_SESSION[ 'utilisateur' ][ 'solde_ca_' ]=0;
$_SESSION[ 'utilisateur' ][ 'solde_ca_1_' ]=0;
$_SESSION[ 'utilisateur' ][ 'solde_recup_' ]=0;
$_SESSION[ 'utilisateur' ][ 'solde_cet_' ]=0;
//Pour le quota jours
if(!empty($_SESSION[ 'utilisateur' ][ 'contrat_type_' ]))
{
    foreach($type_contrats as $contrat)
      if($_SESSION[ 'utilisateur' ][ 'contrat_type_' ] == $contrat[0])
	$_SESSION[ 'utilisateur' ][ 'quota_jours_' ]=$contrat[1];
}
else $_SESSION[ 'utilisateur' ][ 'quota_jours_' ]=$type_contrats[1][1]; //Sinon on initialise au nb jours du 1er contrat ([0] -> Choisir un contrat, 0)
$_SESSION[ 'utilisateur' ][ 'quotite_' ]=100;
}

//mise a jour de la liste
$query2 = 'SELECT * FROM T_UTILISATEUR ORDER BY GROUPE';
$result2 = mysqli_query($link,$query2) or die('Erreur: ' . mysqli_error());

unset($_SESSION['taille']);

$i=1;
while ($line2 = mysqli_fetch_array($result2, MYSQL_NUM)) {
	$_SESSION[ 'utilisateur' ]['utilisateur'][$i]=$line2[0];
	//taille des groupes pour le trait bottom de l'affichage du tableau
	if (!isset($_SESSION['taille'][$line2[6]])) $_SESSION['taille'][$line2[6]]=1;
	else $_SESSION['taille'][$line2[6]]++;

//	if ($module_conge_charge==1) //sinon faire un join sur $query2
	{
	$query3 = 'SELECT * FROM T_CONGE_SOLDE where UTILISATEUR=\''.$line2[0].'\'';
	$result3 = mysqli_query($link,$query3) or die('Erreur: ' . mysqli_error());
	$line3 = mysqli_fetch_array($result3, MYSQL_NUM);
	$_SESSION[ 'utilisateur' ]['solde_ca'][$i]=$line3[1];
	$_SESSION[ 'utilisateur' ]['solde_ca_1'][$i]=$line3[2];
	$_SESSION[ 'utilisateur' ]['solde_recup'][$i]=$line3[3];
	$_SESSION[ 'utilisateur' ]['solde_cet'][$i]=$line3[4];
	$_SESSION[ 'utilisateur' ]['quota_jours'][$i]=$line3[5];
	$_SESSION[ 'utilisateur' ]['quotite'][$i]=$line3[6];
	mysqli_free_result($result3);
	}

	$_SESSION[ 'utilisateur' ]['nom'][$i]=$line2[1];
	$_SESSION[ 'utilisateur' ]['prenom'][$i]=$line2[2];
	$_SESSION[ 'utilisateur' ]['motdepasse'][$i]=substr(md5($line2[3]),-10).'...';

	$_SESSION[ 'utilisateur' ]['groupe'][$i]=$line2[6];
	$_SESSION[ 'utilisateur' ]['status'][$i]=$line2[7];
	$_SESSION[ 'utilisateur' ]['admin'][$i]=$line2[8];
	$_SESSION[ 'utilisateur' ]['login_cas'][$i]=$line2[12];

	$_SESSION[ 'utilisateur' ]['contrat_type'][$i]=$line2[9];

	$query3 = 'SELECT DATE_FORMAT(\''.$line2[10].'\',\'%d/%m/%Y\');';
	$result3 = mysqli_query($link,$query3);
	$line3 = mysqli_fetch_array($result3, MYSQL_NUM);
	$_SESSION[ 'utilisateur' ]['contrat_debut'][$i]=$line3[0];
	mysqli_free_result($result3);
	$query3 = 'SELECT DATE_FORMAT(\''.$line2[11].'\',\'%d/%m/%Y\');';
	$result3 = mysqli_query($link,$query3);
	$line3 = mysqli_fetch_array($result3, MYSQL_NUM);
	$_SESSION[ 'utilisateur' ]['contrat_fin'][$i]=$line3[0];
	mysqli_free_result($result3);

	$i++;
   }
   $_SESSION[ 'nb_utilisateur']=$i-1;
   mysqli_free_result($result2);
}//fin du if ($message_KO=='')

mysqli_close($link);
} //fin de if !annule
}

/*********************************************************************************
************************* -K- BOUTONS SPECIAUX   *********************************
**********************************************************************************/
if (isset($_REQUEST[ 'conge' ]['init_reliquat']))
{
/**
**/
   include $chemin_connection;

   $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
   mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

   $query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CA_1 ="0"';
   $result = mysqli_query($link,$query) or die('Requete d initialisation de reliquat: ' . mysqli_error());
   //mysql_free_result($result);

   for ($i=1;$i<=$_SESSION[ 'nb_utilisateur' ];$i++)
   $_SESSION[ 'utilisateur' ]['solde_ca_1'][$i]=0;

   mysqli_close($link);
}

if (isset($_REQUEST[ 'conge' ]['reatrib_CA']))
{
   include $chemin_connection;

   $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
   mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

   //check if SOLDE_CA<0
   $i=1;
   $query = 'SELECT UTILISATEUR FROM T_UTILISATEUR ORDER BY GROUPE';
   $result2 = mysqli_query($link,$query) or die('Select CA: ' . mysqli_error());
   while ($line2 = mysqli_fetch_array($result2, MYSQL_NUM)) {
	$query = 'SELECT SOLDE_CA,QUOTA_JOURS FROM T_CONGE_SOLDE WHERE UTILISATEUR="'.$line2[0].'"';
	$result3 = mysqli_query($link,$query) or die('CA restant: ' . mysqli_error());
	$line3 = mysqli_fetch_array($result3, MYSQL_NUM);
	$ca_restant=$line3[0];
	if ($ca_restant<0)
	{
	   $new_ca=$line3[0]+$line3[1];
	   $query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CA ='.$new_ca.' WHERE UTILISATEUR="'.$line2[0].'"';
	   $result = mysqli_query($link,$query) or die('Requete de reatribution du CA (CA=QUOTA): ' . mysqli_error());

	   $query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CA_1 ="0" WHERE UTILISATEUR="'.$line2[0].'"';
	   $result = mysqli_query($link,$query) or die('Requete de reatribution du CA (reverser dans CA-1): ' . mysqli_error());

	   //mysql_free_result($result);

	   $_SESSION[ 'utilisateur' ]['solde_ca'][$i]=$_SESSION[ 'utilisateur' ]['quota_jours'][$i]+$_SESSION[ 'utilisateur' ]['solde_ca'][$i];
	   $_SESSION[ 'utilisateur' ]['solde_ca_1'][$i]=0;
	}
	else
	{
	   $query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CA_1 =SOLDE_CA WHERE UTILISATEUR="'.$line2[0].'"';
	   $result = mysqli_query($link,$query) or die('Requete de reatribution du CA (reverser dans CA-1): ' . mysqli_error());

	   $query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CA =QUOTA_JOURS WHERE UTILISATEUR="'.$line2[0].'"';
	   $result = mysqli_query($link,$query) or die('Requete de reatribution du CA (CA=QUOTA): ' . mysqli_error());

	   //mysql_free_result($result);

	   $_SESSION[ 'utilisateur' ]['solde_ca_1'][$i]=$_SESSION[ 'utilisateur' ]['solde_ca'][$i];
	   $_SESSION[ 'utilisateur' ]['solde_ca'][$i]=$_SESSION[ 'utilisateur' ]['quota_jours'][$i];
	}//End of else
	$i++;
   }//End of while
   mysqli_free_result($result2);

//Problem when negative value for CA
/*
   $query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CA_1 =SOLDE_CA';
   $result = mysql_query($query) or die('Requete de reatribution du CA (reverser dans CA-1): ' . mysql_error());

   $query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CA =QUOTA_JOURS';
   $result = mysql_query($query) or die('Requete de reatribution du CA (CA=QUOTA): ' . mysql_error());

   mysql_free_result($result);

   for ($i=1;$i<=$_SESSION[ 'nb_utilisateur' ];$i++)
   {
	$_SESSION[ 'utilisateur' ]['solde_ca_1'][$i]=$_SESSION[ 'utilisateur' ]['solde_ca'][$i];
	$_SESSION[ 'utilisateur' ]['solde_ca'][$i]=$_SESSION[ 'utilisateur' ]['quota_jours'][$i];
   }
*/
   mysqli_close($link);
}


/*********************************************************************************
******************************   -L- HTML      ***********************************
**********************************************************************************/

// Charset header
header('Content-Type: text/html; charset=' . $charset);

?>
<!DOCTYPE html>
<html lang="fr">

<head>
<script  type="text/javascript" src="javascript.js"></script>
<title>Portail d'administration de phpMyLab</title>

<?php
/////////////////////////// CSS (habillage) //////////////////////////
?>
<STYLE TYPE="text/css">
/* ADMINISTRATION */
#logo {	color:#DB0000; font-weight:bold; font-size:16px; text-shadow: 1px 1px 3px white; cursor: pointer; }
/* Header de la partie administration */
#header_admin 
{ 	background: grey;
	background:-webkit-gradient(linear, left top, left bottom, from(#444444), to(#828282)); /* pour Chrome 10+ et Safari 5.1+ */
	background:-moz-linear-gradient(top,#444444,#828282); /* pour Firefox 3.6+ */
	background:-ms-linear-gradient(top,#444444,#828282); /* pour pour IE 10+ */
	background:-o-linear-gradient(top,#444444,#828282); /* pour Opera 11.10+ */
	background:linear-gradient(top,#444444,#828282); 
	-moz-border-radius: 13px 13px 0px 0px;
	-ms-border-radius: 13px 13px 0px 0px;
	-o-border-radius: 13px 13px 0px 0px;
	border-radius: 13px 13px 0px 0px;
	border-bottom: solid 5px #444444;
	box-shadow: 0px 4px 12px grey;
	width:100%; 
	padding: 20px;
	margin-bottom: 30px;
	height: 60px;
}

/* Deconnexion et Lien vers la partie utilisateur */
#header_admin a { color: #EA6833; border: none; text-decoration: none; }
#header_admin a:hover { text-decoration: underline; }

/* Footer + Version et mention CNIL */
#version { margin-left: 20px; }
#version td { color: black; font-size: 0.8em; }
#version a { text-decoration: underline; color: black; }
#version a:hover { text-decoration: underline; color: darkgrey; }
.dateHeure { text-align: right; color: #15266D; }
#footer a { color: #15266D; text-decoration: underline; }
#footer a:hover { color: white; }
#footer td:first-child { padding-left: 20px; }

#footer input[id=dateCourante]
{
	background-color: transparent;
	border: none;
	padding-left: 10px;
	font-size: 1em;
	color: #15266D;
}

#footer
{
	width: 100%;	
	background:-webkit-gradient(linear, left top, left bottom, from(#b8c4ce), to(#193F63)); /* pour Chrome 10+ et Safari 5.1+ */
	background:-moz-linear-gradient(top,#b8c4ce,#193F63); /* pour Firefox 3.6+ */
	background:-ms-linear-gradient(top,#b8c4ce,#193F63); /* pour pour IE 10+ */
	background:-o-linear-gradient(top,#b8c4ce,#193F63); /* pour Opera 11.10+ */
	background:linear-gradient(top,#b8c4ce,#193F63); /* "vous devez toujours mettre la propriété non préfixée en dernier dans le seul but d’assurer la stabilité de votre design dans les années futures" */	
	filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#b8c4ce',endColorstr='#193F63', GradientType=0); /* dégradé pour IE 9- */
	-webkit-border-radius: 0px 0px 13px 13px;
	-moz-border-radius: 0px 0px 13px 13px;
	-ms-border-radius: 0px 0px 13px 13px;
	-o-border-radius: 0px 0px 13px 13px;
	border-radius: 0px 0px 13px 13px;
	color: #595959;
	height: 60px;
	font-size: 0.85em;
	border-top: solid 3px #cacaca;
	margin-top: 20px;
}

label[for] { cursor: pointer; }

/* Class utilisées couramment */
.droite {text-align: right;}
.centrerText {text-align: center;}
.centrerBlock {margin: auto;}

/* Concerne les tableaux */
.enteteTab { background-color: black; color: white; padding: 5px 0px 5px 0px; font-weight: bold; text-align: center; border:solid gray 1px;}
.bordureGrise { border:solid gray 1px; }
.caseEnteteTab 
{
	background:-webkit-gradient(linear, left top, left bottom, from(#820013), to(#C4001D)); /* pour Chrome 10+ et Safari 5.1+ */
	background:-moz-linear-gradient(top,#820013,#C4001D); /* pour Firefox 3.6+ */
	background:-ms-linear-gradient(top,#820013,#C4001D); /* pour pour IE 10+ */
	background:-o-linear-gradient(top,#820013,#C4001D); /* pour Opera 11.10+ */
	background:linear-gradient(top,#820013,#C4001D); /* "vous devez toujours mettre la propriété non préfixée en dernier dans le seul but d’assurer la stabilité de votre design dans les années futures" */	
	filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#820013',endColorstr='#C4001D', GradientType=0); /* dégradé pour IE 9- */
	color: white; 
	padding-top: 5px; 
	padding-bottom: 5px; 
	font-weight: bold;
	text-align: center;
}

#tabGroupes
{
	border-collapse: collapse;
	margin: auto;
}

/* Page d'accueil d'administration */
#messageInfo
{
	width: 800px;
	margin: 0 auto;
	background-color: #EFF0F0;
}

#gesGrpUtil
{
	width: 100%;
	text-align: center;
	margin: auto;
}

#tabUtilisateurs
{
	border-collapse: collapse;
	margin: auto;
}

#tabUtilisateurs *
{
	font-size: 11px;
}

#tabPrincipal
{
	margin: auto;
}

body,a,p,span,td,th,input,select,textarea {
	font-family:verdana,arial,helvetica,geneva,sans-serif,serif;
	font-size:12px;
}



a.one:link {color: darkgray}
a.one:visited {color: darkgray}
a.two:link {color: #D01030}
a.two:visited {color: #D01030}
a.two:hover {background: #DDDDFF;color:#D01030}

</style>
<script>
  function verifDate(champ) //N'autorise que la saisie de chiffres et /
  {
      var lastChar = champ.value.substr(champ.value.length-1,champ.value.length);
      var retirer = 0;
      if(isNaN(lastChar)) 	var retirer = 1;
      if(lastChar == "/")	var retirer = 0;
      if(retirer == 1)	champ.value = champ.value.substr(0,champ.value.length-1);
  }

  function soumettre() //Soumet le formulaire principal ("form1")
  {
     document.getElementById("form1").submit();
  }
</script>
</head>

<body onload="clock()">
<form name="form1" method="post" id="form1" action="<?php echo $_SERVER[ 'PHP_SELF' ]; ?>">
<?php
/////////////////////////// Affiche entete //////////////////////////
   echo '<table id="header_admin"><tr><td>';
   echo '<span id="logo" onclick="window.location.href =\'administration.php?admin_index=1\';">ADMINISTRATION</span> ';//administration.php?sid='.$sid.'&admin_index
   echo '<b>Connect&eacute; en tant que '.$_SESSION[ 'connection' ][ 'prenom' ].' '.$_SESSION[ 'connection' ][ 'nom' ].' au portail d\'administration de phpMyLab</b></td>';
   echo '<td clas="droite"><a href="missions.php">Partie utilisateur</a></td>';//missions.php?sid='.$sid.'"
   echo '<td><a class="one" href="' . $_SERVER[ 'PHP_SELF' ] . '?disconnect=1" accesskey="d" title="Cliquez ici pour vous d&eacute;connecter">D&eacute;connexion</a></td></tr></table>';//$_SERVER[ 'PHP_SELF' ] . '?sid=' . $sid . '&disconnect
//formulaire pour le select module car sinon, il est appele a chaque REQUEST
//hidden pour ne pas afficher sid dans la barre
?>

<?php

///////////////////// Affichage ///////////
if ($_SESSION[ 'connection' ][ 'admin' ]==1)
{
////////////////////////////////////////////////////////////////////////////////////
if ($_SESSION[ 'administrer' ]==0)//1er affichage informations avant administration
{
	echo '<fieldset id="messageInfo">';
	echo '<legend><b>Messages d\'information</b></legend>';

	echo '<table><tr><td>';
	echo 'L\'administrateur peut effectuer certaines op&eacute;rations &agrave; partir des messages du logiciel lors de sa 1&egrave;re connection au panneau d\'administration. ';
	echo 'Pour administrer (ajouter, supprimer et modifier) les groupes et les utilisateurs, cliquer sur le bouton "Administrer" en bas de la page.';
	echo '<br>';
	echo '<br>';
	echo '* Messages : listes des utilisateurs en fin de contrat (1 mois avant?)';
	echo '<br>';

	if ($_SESSION[ 'nb_ufc']>0)
	{
	for ($i=1;$i<=$_SESSION[ 'nb_ufc'];$i++)
	{
		echo $_SESSION[ 'information' ]['nom_ufc'][$i].' '.$_SESSION[ 'information' ]['prenom_ufc'][$i];
		echo ' ('.$_SESSION[ 'information' ]['groupe_ufc'][$i].')';
		$couleur_alerte='#FF7F24';//"brown1";
		$temps=explode("/",$_SESSION[ 'information' ]['contrat_fin_ufc'][$i]);
		if (time()>=mktime(0,0,0,$temps[1],$temps[0],$temps[2])) $couleur_alerte='#FF4040';//"chocolate1";
		echo ' <FONT STYLE="color:'.$couleur_alerte.'">'.$_SESSION[ 'information' ]['contrat_fin_ufc'][$i].'</FONT></br>';
	}
	}
   if ($module_conge_charge)
   {
	maj_conges_non_valides($chemin_connection);
	echo '* Message pour initialiser le reliquat ou re-attribuer les CA (1 mois avant?)';
	echo '<br>';
	echo '* Messages : listes des conges non valid&eacute;s ou non annul&eacute;s.';
	echo '<br>';
	if ($_SESSION[ 'nb_nv']>0)
	{
	echo '<table align=center>';
	echo '<tr><td align=center colspan=2><b>Liste des cong&eacute;s non-valid&eacute;s</b></td></tr>';
	echo '<tr><td align=center>';
	if ($_SESSION[ 'nb_nv_standard']>0) echo 'Standard';
	echo '</td><td align=center>';
	if ($_SESSION[ 'nb_nv_standard']!=$_SESSION[ 'nb_nv']) echo 'Date de d&eacute;but de cong&eacute; d&eacute;pass&eacute;e';
	echo '</td></tr>';
	echo '<tr><td valign=top>';
///////////////////////////////////////////////////
//colonne Standard
	echo '<table>';
	$indice=1;
	$indice_j=1;
	for ($indice=1;$indice<=$_SESSION[ 'nb_nv'];$indice++)
	{
		$temps=explode("/",$_SESSION[ 'information' ]['debut_nv'][$indice]);
		if (time()<mktime(0,0,0,$temps[1],$temps[0],$temps[2]))
		{
		echo '<tr><td>';
		echo '[ <a class="two" href="conges.php?dec='.$_SESSION[ 'information' ]['id_nv'][$indice].'"> Cong&eacute; '.$_SESSION[ 'information' ]['id_nv'][$indice].'</a> ]';
		echo '</td><td>';//conges.php?sid='.$sid.'&dec

		echo $_SESSION[ 'information' ]['utilisateur_nv'][$indice].' ('.$_SESSION[ 'information' ]['groupe_nv'][$indice].')';
	//ajout pour afficher si le demandeur de cong&eacute; a des recup a prendre 
		$i_util=0;
		for ($i_util=0;$i_util<$_SESSION['nb_utilisateur'];$i_util++)
		{
if ((isset($_SESSION[ 'utilisateur' ][ 'utilisateur'][$i_util]) && isset($_SESSION[ 'utilisateur' ][ 'solde_recup'][$i_util]) && isset($_SESSION[ 'information' ]['utilisateur_nv'][$indice])) && ($_SESSION[ 'utilisateur' ][ 'utilisateur'][$i_util]==$_SESSION[ 'information' ]['utilisateur_nv'][$indice]) && ($_SESSION[ 'utilisateur' ][ 'solde_recup'][$i_util]>0))
echo ' r&eacute;cup='.$_SESSION[ 'utilisateur' ][ 'solde_recup'][$i_util].' ';
		}
		echo '</td><td>'.$_SESSION[ 'information' ]['debut_nv'][$indice].'</td>';
		echo '<td>';
		$checked="";// || !isset($_SESSION[ 'aprevenir' ][$indice])
		if (isset($_SESSION[ 'aprevenir' ][$indice_j]) && $_SESSION[ 'aprevenir' ][$indice_j]==1) $checked="checked";
		echo '<input type="hidden" name="aprevenir['.$indice_j.']" value="0" />';
		echo '<input type="checkbox" name="aprevenir['.$indice_j.']" value="1" '.$checked.'/>';
		$indice_j++;
		echo '</td>';
		echo '</tr>';
		}
	}
	echo '<td align=center colspan=3>';
	if ($indice_j>1) echo '<input type="submit" name="relancer" value=" Relancer les responsables de groupe " >';
	if (isset($message_relancer)) echo '<br><font color="#FF0000"><b>'.$message_relancer.'</b></font>';
	echo '</td>';
	echo '</table>';
	echo '</td>';
///////////////////////////////////////////////////
//colonne Date de fin depassee
	echo '<td valign=top>';
	echo '<table>';
	$indice=1;

	$indice_j=1;

	for ($indice=1;$indice<=$_SESSION[ 'nb_nv'];$indice++)
	{
		$temps=explode("/",$_SESSION[ 'information' ]['debut_nv'][$indice]);
		if (time()>=mktime(0,0,0,$temps[1],$temps[0],$temps[2]))
		{
		echo '<tr><td>';
		echo '[ <a class="two" href="conges.php?dec='.$_SESSION[ 'information' ]['id_nv'][$indice].'"> Cong&eacute; '.$_SESSION[ 'information' ]['id_nv'][$indice].'</a> ]';//conges.php?sid='.$sid.'&dec
		echo '</td><td>';
		echo $_SESSION[ 'information' ]['utilisateur_nv'][$indice];//.' ('.$_SESSION[ 'information' ]['groupe_nv'][$indice].')';
		echo ' '.$_SESSION[ 'information' ]['nb_jours_ouvres_nv'][$indice];
		echo 'J '.$conge_type[$_SESSION[ 'information' ]['type_nv'][$indice]];
//		echo ' '.$conge_type[$_SESSION[ 'information' ]['type_nv'][$indice]].')';
//		echo '|';
	//ajout pour afficher les soldes du demandeur de cong&eacute;	
		$i_util=0;
		for ($i_util=0;$i_util<$_SESSION['nb_utilisateur'];$i_util++)
		{
if (isset($_SESSION[ 'utilisateur' ][ 'utilisateur'][$i_util]) && isset($_SESSION[ 'information' ]['utilisateur_nv'][$indice]) && $_SESSION[ 'utilisateur' ][ 'utilisateur'][$i_util]==$_SESSION[ 'information' ]['utilisateur_nv'][$indice]) 
{
//&& ($_SESSION[ 'utilisateur' ][ 'solde_recup'][$i_util]>0))
echo '<br>CA='.$_SESSION[ 'utilisateur' ][ 'solde_ca'][$i_util];
if ($_SESSION[ 'utilisateur' ][ 'solde_ca_1'][$i_util]>0)
echo ' | CA-1='.$_SESSION[ 'utilisateur' ][ 'solde_ca_1'][$i_util];
if ($_SESSION[ 'utilisateur' ][ 'solde_recup'][$i_util]>0)
echo ' | R&eacute;cup='.$_SESSION[ 'utilisateur' ][ 'solde_recup'][$i_util];
if ($_SESSION[ 'utilisateur' ][ 'solde_cet'][$i_util]>0)
echo ' | CET='.$_SESSION[ 'utilisateur' ][ 'solde_cet'][$i_util];
}


		}
		echo '</td><td>'.$_SESSION[ 'information' ]['debut_nv'][$indice].' </td>';


		echo '<td>';
		$checked="";// || !isset($_SESSION[ 'aprevenir' ][$indice])
		if ($_SESSION[ 'avalider' ][$indice_j]==1) $checked="checked";
		echo '<input type="hidden" name="avalider['.$indice_j.']" value="0" />';
		echo '<input type="checkbox" name="avalider['.$indice_j.']" value="1" '.$checked.'/>';
		$indice_j++;
		echo '</td>';


//		echo '<td>POUET</td>'
//		echo '<td>POUET</td>'


		echo '</tr>';
		}
	}
	echo '<td align=center colspan=3>';
	if ($indice_j>1) echo '<input type="submit" name="validermoultes" value=" Valider les cong&eacute;s s&eacute;lectionn&eacute;s " >';
	if (isset($message_validselec)) echo '<br><font color="#FF0000"><b>'.$message_validselec.'</b></font>';
	echo '</td>';
	echo '</table>';
	echo '</td></tr>';
	echo '</table>';
	}//fin if ($_SESSION[ 'nb_nv']>0)
   }//fin if ($module_conge_charge)

   echo '</td></tr>';
   echo '</table>';
   echo '</fieldset><br>';
   echo '<center><input type="submit" name="administrer" value=" administrer "></center>';
}//fin de if ($_SESSION[ 'administrer' ]==0)
else
{
////////////////////////////////////////////////////////////////////////////////////
//Gestion utilisateur et groupe
////////////////////////////////////////
//tableau principal
   echo '<table id="tabPrincipal">';
   echo '<tr><td>';

////////////////////////////////////////
   if ($module_conge_charge && $_SESSION[ 'choix_gestion' ] == 1)
   echo '<table><tr><td>';
	
//tableau selection (options) de gestion groupe ou utilisateurs
   echo '<table id="gesGrpUtil" >';
   echo '<tr>';
   $checkcheck='';
   if ($_SESSION[ 'choix_gestion' ] == 0) $checkcheck='checked';
   echo '<td><input type="radio" name="choix_gestion" id="liste_groupes" value=0 '.$checkcheck.' onClick="soumettre();"/><label for="liste_groupes"><b>Liste des groupes   <b></label>';
   $checkcheck='';
   if ($_SESSION[ 'choix_gestion' ] == 1) $checkcheck='checked';
   echo '<input type="radio" name="choix_gestion" value=1 id="liste_utilisateurs" '.$checkcheck.' onClick="soumettre();"/><label for="liste_utilisateurs"><b>Liste des utilisateurs<b></label>';

   if ($_SESSION[ 'choix_gestion' ] == 1)
   {
	if ($_SESSION['edition_utilisateur']!=1)
	{
	   $griser = ''; //A quoi correspond griser????
	   echo '   (';
	   echo '<select name="selection_groupe" '.$griser.' ';
	   echo ' onChange="javascript:document.forms[0].submit()"';
	   echo '>';
	   echo '<option value="'.$tous_les_utilisateurs.'" >'.$tous_les_utilisateurs.'</option>';
	   for ($i=1;$i<=$_SESSION['nb_groupe'];$i++)
	   {
		if ($_SESSION['correspondance']['entite_depensiere'][$i]==0)
		echo '<option value="'.$_SESSION['correspondance']['groupe'][$i].'" ';
		if ($_SESSION['selection_groupe']==$_SESSION['correspondance']['groupe'][$i])  echo 'selected';
		echo '>'.$_SESSION['correspondance']['groupe'][$i].'</option>';
	   }
	   echo '</select>';
	   echo ')</td>';
	}
   }


//////////////bouton speciaux conges
   if ($module_conge_charge && $_SESSION[ 'choix_gestion' ] == 1)
   {
	echo '<td class="centrerText">';
	echo '<input type="image" align="top" src="images/imprimer.png" height=22 onClick="window.open(\'administration_imprimable.php\',\'Conges\',\'scrollbars=yes,width=725,height=600\')" name="conge[imprime]" title="Version imprimable"> ';//administration_imprimable.php?sid='.$sid.'\',
	echo '<input type="submit" name="conge[reatrib_CA]" value=" R&eacute;atrib. CA " onClick="return confirm(\'Etes-vous s&ucirc;re de vouloir r&eacute;atribuer les cong&eacute;s annuel?\');"> ';
	echo '<input type="submit" name="conge[init_reliquat]" value=" Init. reliquat " onClick="return confirm(\'Etes-vous s&ucirc;re de vouloir re-initialiser le reliquat?\');"> ';
	echo '</td>';
   }
   echo '<br>';
   echo '</tr></table>';
////////////////////////////////////////
//tableau messages
   if (($message_KO!='') || ($message_OK!=''))
   {
	echo '<center><table>';
	echo '<tr><td style="border:0px dotted black;width:25px">Message(s)</td><td style="border:0px"></td></tr>';
	echo '<tr><td colspan=2 style="border:1px dashed black;text-align:center">';
	if ($message_KO!='')echo '<span style="font-weight:bold;color:red">'.$message_KO.'</span><br>';
	if ($message_OK!='')echo '<span style="font-weight:bold;color:green">'.$message_OK.'</span>';
	echo '</td></tr>';
	echo '</table></center><br>';
	
   }
	
   if ($_SESSION[ 'choix_gestion' ] == 0)//Groupes
   {
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
	//tableau groupes
/*
	echo '<table align=center border=0 bordercolor="darkblue" cellspacing=0>';
	echo '<tr bgcolor="#E0C0D0">';
	echo '<td colspan=8 align=center class="enteteTab" style="border:solid gray 1px" >Liste des groupes</td>';
	echo '<td style="background-color:white"></td>';
	echo '</tr><tr bgcolor="#E0C0D0">';
	echo '<td style="border:solid gray 1px;width:150px" class="caseEnteteTab">Nom du groupe</td>';
	echo '<td style="border:solid gray 1px;width:80px" class="caseEnteteTab">Nom d\'utilisateur du responsable</td>';
	echo '<td style="border:solid gray 1px;width:80px" class="caseEnteteTab">Nom d\'utilisateur du responsable missions</td>';
	echo '<td style="border:solid gray 1px" class="caseEnteteTab" width="80px">Nom d\'utilisateur de l\'administratif principal</td>';
	echo '<td style="border:solid gray 1px" class="caseEnteteTab" width="80px">Nom d\'utilisateur de l\'administratif secondaire</td>';
	echo '<td style="border:solid gray 1px" class="caseEnteteTab" width="40px">Validation des missions</td>';
	echo '<td style="border:solid gray 1px" class="caseEnteteTab" width="40px">Validation des cong&eacute;s</td>';
	echo '<td style="border:solid gray 1px" class="caseEnteteTab" width="40px">Entit&eacute; d&eacute;pensi&egrave;re</td>';
	echo '<td style="background-color:white"  align=center><b>Actions</b></td>';
	echo '</tr>';
*/
	echo '<table id="tabGroupes">';
	echo '<tr>';
	echo '<td colspan=8 class="enteteTab">Liste des groupes</td>';
	echo '<td style="background-color:white"></td>';
	echo '</tr><tr>';
	echo '<td class="caseEnteteTab bordureGrise">Nom du groupe</td>';
	echo '<td class="caseEnteteTab bordureGrise">Nom d\'utilisateur du responsable</td>';
	echo '<td class="caseEnteteTab bordureGrise">Nom d\'utilisateur du responsable missions</td>';
	echo '<td class="caseEnteteTab bordureGrise">Nom d\'utilisateur de l\'administratif principal</td>';
	echo '<td class="caseEnteteTab bordureGrise">Nom d\'utilisateur de l\'administratif secondaire</td>';
	echo '<td class="caseEnteteTab bordureGrise">Validation des missions</td>';
	echo '<td class="caseEnteteTab bordureGrise">Validation des cong&eacute;s</td>';
	echo '<td class="caseEnteteTab bordureGrise">Entit&eacute; d&eacute;pensi&egrave;re</td>';
	echo '<td class="centrerText"><b>Actions</b></td>';
	echo '</tr>';


	$i=1;
	$nombre=$_SESSION[ 'nb_groupe'];
	for ($i=1 ; $i<=$nombre ; $i++)
	{
	   echo '<tr class="centrerText">';
	   $proprie='';
	   if ($i==$nombre) $proprie='border-bottom:solid gray 1px;';
	//Mode edition pour ce groupe 
	   if ((isset($_SESSION['edition_correspondance']) && $_SESSION['edition_correspondance']==1) && ($_SESSION['correspondance']['groupe'][$i]==$_SESSION[ 'groupe' ]))
	   {
		$bgcol='';
		if ($i%2==0) $bgcol='background-color:F8F8F8;';
	   //////////////////groupe
		echo '<td style="border-left:solid gray 1px;'.$bgcol.''.$proprie.'" align=center>'.$_SESSION['correspondance']['groupe'][$i].'</td>';
	   /////////////////responsable
		echo '<td style="'.$bgcol.''.$proprie.'" ><INPUT TYPE=text NAME="correspondance[responsable_]" value="'.$_SESSION[ 'correspondance' ][ 'responsable_' ].'"></td>';
	   /////////////////responsable2
		echo '<td style="'.$bgcol.''.$proprie.'" ><INPUT TYPE=text NAME="correspondance[responsable2_]" value="'.$_SESSION[ 'correspondance' ][ 'responsable2_' ].'"></td>';
	   /////////////////administratif
		echo '<td style="'.$bgcol.''.$proprie.'" ><INPUT TYPE=text NAME="correspondance[administratif_]" value="'.$_SESSION[ 'correspondance' ][ 'administratif_' ].'"></td>';
	   /////////////////administratif2
		echo '<td style="'.$bgcol.''.$proprie.'" ><INPUT TYPE=text NAME="correspondance[administratif2_]" value="'.$_SESSION[ 'correspondance' ][ 'administratif2_' ].'"></td>';
	   /////////////////valid_mission
		echo '<td align=center style="'.$bgcol.''.$proprie.'"  ><select name="correspondance[valid_mission_]"';
		echo ' onChange="soumettre();"';
		echo '>';
		for ($ii=0;$ii<2;$ii++)
		{
		   echo '<option value="'.$ii.'" ';
		   if ($_SESSION['correspondance']['valid_mission_']==$ii) echo 'selected';
		   echo '>'.$ii.'</option>';
		}
		echo '</select></td>';
	   /////////////////valid_conge
		echo '<td align=center style="'.$bgcol.''.$proprie.'"  ><select name="correspondance[valid_conge_]"';
		echo ' onChange="soumettre();"';
		echo '>';
		for ($ii=0;$ii<2;$ii++)
		{
		   echo '<option value="'.$ii.'" ';
		   if ($_SESSION['correspondance']['valid_conge_']==$ii) echo 'selected';
		   echo '>'.$ii.'</option>';
		}
		echo '</select></td>';
	   /////////////////entite_depensiere
		echo '<td align=center style="border-right:solid gray 1px;'.$bgcol.''.$proprie.'" ><select name="correspondance[entite_depensiere_]"';
		echo ' onChange="soumettre();"';
		echo '>';
		for ($ii=0;$ii<2;$ii++)
		{
		   echo '<option value="'.$ii.'" ';
		   if ($_SESSION['correspondance']['entite_depensiere_']==$ii) echo 'selected';
		   echo '>'.$ii.'</option>';
		}
		echo '</select></td>';
	   //fin edition d'un groupe
	   }
	   else //mode d'affichage normal (pas edition) d'un groupe
	   {
		$bgcol='';
		if ($i%2==0) $bgcol='background-color:F8F8F8;';
		echo '<td  style="border-left:solid gray 1px;'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['correspondance']['groupe'][$i].'</td>';
		echo '<td style="'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['correspondance']['responsable'][$i].'</td>';
		if ($_SESSION['correspondance']['responsable2'][$i]!='')
		echo '<td style="'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['correspondance']['responsable2'][$i].'</td>';
		else echo '<td style="'.$proprie.''.$bgcol.'" align=center> &nbsp; </td>';
		echo '<td style="'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['correspondance']['administratif'][$i].'</td>';
		echo '<td style="'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['correspondance']['administratif2'][$i].'&nbsp;</td>';
		echo '<td style="'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['correspondance']['valid_missions'][$i].'</td>';
		echo '<td style="'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['correspondance']['valid_conges'][$i].'</td>';
		echo '<td style="border-right:solid gray 1px;'.$proprie.''.$bgcol.'" align=center>';
	 	echo $_SESSION['correspondance']['entite_depensiere'][$i];
		echo '</td>';
	   }

/////////////////////////////////////////////////////////////
	//boutons action
	echo '<td>';
	if (!isset($_SESSION['edition_correspondance']) OR $_SESSION['edition_correspondance']==0)
	{
	echo '<center><a  title="Editer ce groupe" href="'.$_SERVER[ 'PHP_SELF' ]. '?corresp='.$i.'&actions=edit"><img border=0 align=top src="images/edit.png"></a>';//$_SERVER[ 'PHP_SELF' ]. '?sid=' . $sid.'&corresp

	echo '<a  title="Supprimer ce groupe" href="'.$_SERVER[ 'PHP_SELF' ]. '?corresp='.$i.'&actions=suppr" onClick="return confirm(\'Etes-vous s&ucirc;re de vouloir supprimer ce groupe?\');"><img border=0 align=top src="images/poubelle.png"></a></center>';//$_SERVER[ 'PHP_SELF' ]. '?sid=' . $sid.'&corresp
	}
	else if ($_SESSION['correspondance']['groupe'][$i]==$_SESSION[ 'groupe' ])
	{
	echo '<center><input type="image" src="images/enregistrer.png" onClick="return confirm(\'Etes-vous s&ucirc;re de vouloir mettre &agrave; jour ce groupe?\');Submit;" name="correspondance[editer]" title="Mettre &agrave; jour ce groupe">';
	echo '<input type="image" src="images/undo.png" onClick="Submit;" name="correspondance[annul]" title="Annuler l\'action"></center>';
	}
	echo '</td>';
	
	echo '</tr>';
	}//fin de boucle sur $i pour groupes


/////////////////////////////////////////////////////////////
//Nouveau groupe (en fin de tableau)
if (!isset($_SESSION['edition_correspondance']) OR $_SESSION['edition_correspondance']==0) 
{
	echo '<tr class="centrerText">';
//////////////////groupe
	echo '<td><INPUT TYPE=text NAME="correspondance[groupe_]" value="'.$_SESSION[ 'correspondance' ][ 'groupe_' ].'"></td>';
/////////////////responsable
	echo '<td><INPUT TYPE=text NAME="correspondance[responsable_]" value="'.$_SESSION[ 'correspondance' ][ 'responsable_' ].'"></td>';
/////////////////responsable2 missions
	echo '<td><INPUT TYPE=text NAME="correspondance[responsable2_]" value="'.$_SESSION[ 'correspondance' ][ 'responsable2_' ].'"></td>';
/////////////////administratif
	echo '<td><INPUT TYPE=text NAME="correspondance[administratif_]" value="'.$_SESSION[ 'correspondance' ][ 'administratif_' ].'"></td>';
/////////////////administratif2
	echo '<td><INPUT TYPE=text NAME="correspondance[administratif2_]" value="'.$_SESSION[ 'correspondance' ][ 'administratif2_' ].'"></td>';
/////////////////valid_mission
	echo '<td align=center ><select name="correspondance[valid_mission_]"';
	echo ' onChange="soumettre();"';
	echo '>';
	for ($i=0;$i<2;$i++)
	{
		echo '<option value="'.$i.'" ';
		if ($_SESSION['correspondance']['valid_mission_']==$i) echo 'selected';
		echo '>'.$i.'</option>';
	}
	echo '</select></td>';
/////////////////valid_conge
	echo '<td align=center ><select name="correspondance[valid_conge_]"';
	echo ' onChange="soumettre();"';
	echo '>';
	for ($i=0;$i<2;$i++)
	{
		echo '<option value="'.$i.'" ';
		if ($_SESSION['correspondance']['valid_conge_']==$i) echo 'selected';
		echo '>'.$i.'</option>';
	}
	echo '</select></td>';
/////////////////entite_depensiere
	echo '<td align=center ><select name="correspondance[entite_depensiere_]"';
	echo ' onChange="soumettre();"';
	echo '>';
	for ($i=0;$i<2;$i++)
	{
		echo '<option value="'.$i.'" ';
		if ($_SESSION['correspondance']['entite_depensiere_']==$i) echo 'selected';
		echo '>'.$i.'</option>';
	}
	echo '</select></td>';

	echo '<td><center><input type="image" src="images/ajouter.png" onClick="return confirm(\'Etes-vous s&ucirc;re de vouloir ajouter ce groupe?\');Submit;" name="correspondance[nouvelle]" title="Ajouter un groupe"></center></td>';
	echo '</tr>';
}//fin de if ($_SESSION['edition_correspondance']==0) pour nouveau groupe

	echo '</table>';
	}
	else //affichage pour utilisateurs
	{
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
	//tableau utilisateurs
	echo '<table id="tabUtilisateurs">';
	echo '<tr bgcolor="#E0C0D0">';
	echo '<td colspan=8 class="enteteTab">Liste des utilisateurs</td>';

	echo '<td style="background-color:white"></td>';
	echo '<td colspan=3 class="enteteTab">Contrats</td>';

	if ($module_conge_charge)
	{
	   echo '<td style="background-color:white"></td>';
	   echo '<td colspan=6 class="enteteTab">Cong&eacute;s</td>';
	}
	echo '</tr><tr bgcolor="#E0C0D0">';
	echo '<td class="caseEnteteTab bordureGrise">Nom d\' utilisateur</td>';
	echo '<td class="caseEnteteTab bordureGrise">Login CAS</td>';
	echo '<td class="caseEnteteTab bordureGrise">Nom</td>';
	echo '<td class="caseEnteteTab bordureGrise">Pr&eacute;nom</td>';
	echo '<td class="caseEnteteTab bordureGrise">Mot de passe</td>';
	//echo '<td style="border:solid gray 1px" align=center width="25px">SS</td>';
	//echo '<td style="border:solid gray 1px" align=center width="25px">MEL</td>';
	echo '<td class="caseEnteteTab bordureGrise">Groupe</td>';
	echo '<td class="caseEnteteTab bordureGrise">Statut</td>';
	echo '<td class="caseEnteteTab bordureGrise">Admin.</td>';

	echo '<td style="background-color:white" align=center width="10px">&nbsp;</td>';
	echo '<td class="caseEnteteTab bordureGrise">D&eacute;but</td>';
	echo '<td class="caseEnteteTab bordureGrise">Fin</td>';
	echo '<td class="caseEnteteTab bordureGrise">Type</td>';

	if ($module_conge_charge)
	{
	   echo '<td style="background-color:white" align=center width="10px">&nbsp;</td>';
	   echo '<td class="caseEnteteTab bordureGrise">Solde CA</td>';
	   echo '<td class="caseEnteteTab bordureGrise">Solde CA-1</td>';
	   echo '<td class="caseEnteteTab bordureGrise">Solde R&eacute;cup.</td>';
	   echo '<td class="caseEnteteTab bordureGrise">Solde CET</td>';
	   echo '<td class="caseEnteteTab bordureGrise">Quota jours</td>';
	   echo '<td class="caseEnteteTab bordureGrise">Quotit&eacute;</td>';
	}
	echo '<td style="background-color:white"  align=center><b>Actions</b></td>';
	echo '</tr>';

	$taille_groupe=0;
	$i=1;
	$nombre=$_SESSION[ 'nb_utilisateur'];
	for ($i=1 ; $i<=$nombre ; $i++)
	{
	   $groupes_visibles=0;
	   if ($_SESSION[ 'selection_groupe' ]==$tous_les_utilisateurs) $groupes_visibles=1;
	   elseif ($_SESSION['utilisateur']['groupe'][$i]==$_SESSION[ 'selection_groupe' ]) 	$groupes_visibles=1;
	   if ($groupes_visibles==1)
	   {
		echo '<tr class="centrerText">';
		$proprie='';
		$taille_groupe++;
		if (($i==$nombre) || ($taille_groupe==isset($_SESSION['taille'][$_SESSION[ 'selection_groupe' ]]) && $_SESSION['taille'][$_SESSION[ 'selection_groupe' ]])) $proprie='border-bottom:solid gray 1px;';
		$bgcol='';
		if ($i%2==0) $bgcol='background-color:F8F8F8;';
	   //edition d'un utilisateur
		if (($_SESSION['edition_utilisateur']==1) && ($_SESSION['utilisateur']['utilisateur'][$i]==$_SESSION[ 'login' ]))
		{
		//////////////////utilisateur
		echo '<td  style="border-left:solid gray 1px;'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['utilisateur']['utilisateur'][$i].'</td>';
		//////////////////login CAS
		echo '<td style="'.$proprie.''.$bgcol.'" align=center><INPUT TYPE=text NAME="utilisateur[login_cas_]" value="'.$_SESSION[ 'utilisateur' ][ 'login_cas_' ].'"></td>';
		/////////////////nom
		echo '<td style="'.$proprie.''.$bgcol.'"><INPUT TYPE=text NAME="utilisateur[nom_]" value="'.$_SESSION[ 'utilisateur' ][ 'nom_' ].'"></td>';
		/////////////////prenom
		echo '<td style="'.$proprie.''.$bgcol.'"><INPUT TYPE=text NAME="utilisateur[prenom_]" value="'.$_SESSION[ 'utilisateur' ][ 'prenom_' ].'"></td>';
		/////////////////mot de passe
		echo '<td style="'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['utilisateur']['motdepasse'][$i].'&nbsp;</td>';
		//2 colonnes en plus pour MEL et SS
		//echo '<td style="'.$proprie.''.$bgcol.'">&nbsp;</td>';//car ajout d'une colonne
		//echo '<td style="'.$proprie.''.$bgcol.'">&nbsp;</td>';//car ajout d'une colonne
		/////////////////groupe
		echo '<td style="'.$proprie.''.$bgcol.'"><INPUT TYPE=text NAME="utilisateur[groupe_]" value="'.$_SESSION[ 'utilisateur' ][ 'groupe_' ].'"></td>';
		/////////////////status
		echo '<td style="'.$proprie.''.$bgcol.'" align=center ><select name="utilisateur[status_]"';
		echo '>';
		for ($ii=1;$ii<=6;$ii++)
		{
		   echo '<option value="'.$ii.'" ';
		   if ($_SESSION['utilisateur']['status_']==$ii) echo 'selected';
		   echo '>'.$ii.'</option>';
		}
		echo '</select></td>';
		/////////////////admin
		echo '<td align=center  style="'.$proprie.''.$bgcol.'border-right:solid gray 1px;"><select name="utilisateur[admin_]"';
		echo '>';
		for ($ii=0;$ii<2;$ii++)
		{
		   echo '<option value="'.$ii.'" ';
		   if ($_SESSION['utilisateur']['admin_']==$ii) echo 'selected';
		   echo '>'.$ii.'</option>';
		}
		echo '</select></td>';
		/////////////////////Contrat
		echo '<td></td>';
		/////////////////Contrat debut
		echo '<td style="border-left:solid gray 1px;'.$proprie.''.$bgcol.'"><INPUT size=9  TYPE=text NAME="utilisateur[contrat_debut_]" value="'.$_SESSION[ 'utilisateur' ][ 'contrat_debut_' ].'"></td>';
		/////////////////Contrat fin
		echo '<td style="'.$proprie.''.$bgcol.'"><INPUT size=9  TYPE=text NAME="utilisateur[contrat_fin_]" value="'.$_SESSION[ 'utilisateur' ][ 'contrat_fin_' ].'"></td>';
		/////////////////Contrat type
		echo '<td style="border-right:solid gray 1px;'.$proprie.''.$bgcol.'">';
		echo '<select name="utilisateur[contrat_type_]" >';
		for ($ii=0;$ii<sizeof($type_contrats);$ii++)
		{
		   echo '<option value="'.$type_contrats[$ii][0].'" ';
		   if ($_SESSION['utilisateur']['contrat_type_']==$type_contrats[$ii][0]) { echo 'selected';}
		   echo '>'.$type_contrats[$ii][0].'</option>';
		}
		echo '</select>';
		echo '</td>';

		if ($module_conge_charge)
		{
		echo '<td></td>';
		/////////////////solde CA
		echo '<td style="border-left:solid gray 1px;'.$proprie.''.$bgcol.'"><INPUT size=2  TYPE=text NAME="utilisateur[solde_ca_]" value="'.$_SESSION[ 'utilisateur' ][ 'solde_ca_' ].'"></td>';
		/////////////////solde CA-1
		echo '<td style="'.$proprie.''.$bgcol.'"><INPUT size=2  TYPE=text NAME="utilisateur[solde_ca_1_]" value="'.$_SESSION[ 'utilisateur' ][ 'solde_ca_1_' ].'"></td>';
		/////////////////solde recup.
		echo '<td style="'.$proprie.''.$bgcol.'"><INPUT size=2  TYPE=text NAME="utilisateur[solde_recup_]" value="'.$_SESSION[ 'utilisateur' ][ 'solde_recup_' ].'"></td>';
		/////////////////solde cet
		echo '<td style="'.$proprie.''.$bgcol.'"><INPUT size=2  TYPE=text NAME="utilisateur[solde_cet_]" value="'.$_SESSION[ 'utilisateur' ][ 'solde_cet_' ].'"></td>';
		/////////////////quota jours
		echo '<td style="'.$proprie.''.$bgcol.'"><INPUT size=2  TYPE=text NAME="utilisateur[quota_jours_]" value="'.$_SESSION[ 'utilisateur' ][ 'quota_jours_' ].'"></td>';
		/////////////////quotite
		echo '<td style="border-right:solid gray 1px;'.$proprie.''.$bgcol.'"><INPUT size=2  TYPE=text NAME="utilisateur[quotite_]" value="'.$_SESSION[ 'utilisateur' ][ 'quotite_' ].'"></td>';
		}

		}//fin edition d'un utilisateur
		else
		{//affichage normal d'un utilisateur
		echo '<td  style="border-left:solid gray 1px;'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['utilisateur']['utilisateur'][$i].'</td>';
		//----Affichage d'un extrait du login CAS -----		if(!empty($_SESSION[ 'utilisateur' ][ 'login_cas_' ]))
		if(!empty($_SESSION['utilisateur']['login_cas'][$i]))
		{
			$extrait_log_cas = substr($_SESSION['utilisateur']['login_cas'][$i],0,10);
			if(strlen($_SESSION['utilisateur']['login_cas'][$i])>10)
				$extrait_log_cas .= '...';
		}
		else $extrait_log_cas = $_SESSION['utilisateur']['login_cas'][$i];
		echo '<td style="'.$proprie.''.$bgcol.'" align=center title="'.$_SESSION['utilisateur']['login_cas'][$i].'">'.$extrait_log_cas.'</td>';

		// ---------------------------------------------
		echo '<td style="'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['utilisateur']['nom'][$i].'</td>';
		echo '<td style="'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['utilisateur']['prenom'][$i].'</td>';
		echo '<td style="'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['utilisateur']['motdepasse'][$i].'&nbsp;</td>';

		//echo '<td style="'.$proprie.''.$bgcol.'" align=center>&nbsp;</td>';
		//echo '<td style="'.$proprie.''.$bgcol.'" align=center>&nbsp;</td>';

		echo '<td style="'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['utilisateur']['groupe'][$i].'</td>';
		echo '<td style="'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['utilisateur']['status'][$i].'</td>';
		echo '<td style="border-right:solid gray 1px;'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['utilisateur']['admin'][$i].'</td>';

		//contrat
		echo '<td style="" align=center>&nbsp;</td>';
		echo '<td style="border-left:solid gray 1px;'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['utilisateur']['contrat_debut'][$i].'&nbsp;</td>';
		echo '<td style="'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['utilisateur']['contrat_fin'][$i].'&nbsp;</td>';
		echo '<td style="border-right:solid gray 1px;'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['utilisateur']['contrat_type'][$i].'&nbsp;</td>';

		if ($module_conge_charge)
		{
		echo '<td style="" align=center>&nbsp;</td>';
		echo '<td style="border-left:solid gray 1px;'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['utilisateur']['solde_ca'][$i].'</td>';
		echo '<td style="'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['utilisateur']['solde_ca_1'][$i].'</td>';
		echo '<td style="'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['utilisateur']['solde_recup'][$i].'</td>';
		echo '<td style="'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['utilisateur']['solde_cet'][$i].'</td>';
		echo '<td style="'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['utilisateur']['quota_jours'][$i].'</td>';
		echo '<td style="border-right:solid gray 1px;'.$proprie.''.$bgcol.'" align=center>'.$_SESSION['utilisateur']['quotite'][$i].'</td>';
		}
		}

		//boutons action
		echo '<td>';
		if ($_SESSION['edition_utilisateur']==0)
		{
		   echo '<center><a  title="Editer cet utilisateur" href="'.$_SERVER[ 'PHP_SELF' ]. '?corresp2='.$i.'&actions=edit2"><img border=0 align=top src="images/edit.png"></a>';//$_SERVER[ 'PHP_SELF' ]. '?sid=' . $sid.'&corresp2
		   echo '<a  title="Supprimer cet utilisateur" href="'.$_SERVER[ 'PHP_SELF' ]. '?corresp2='.$i.'&actions=suppr2" onClick="return confirm(\'Etes-vous s&ucirc;re de vouloir supprimer cet utilisateur?\')"><img border=0 align=top src="images/poubelle.png"></a></center>';//$_SERVER[ 'PHP_SELF' ]. '?sid=' . $sid.'&corresp2
		}
		else if ($_SESSION['utilisateur']['utilisateur'][$i]==$_SESSION[ 'login' ])
		{
		   echo '<center><input type="image" src="images/enregistrer.png" onClick="return confirm(\'Etes-vous s&ucirc;re de vouloir mettre &agrave; jour cet utilisateur?\');Submit;" name="utilisateur[editer]" title="Mettre &agrave; jour ce groupe">';
		   echo '<input type="image" src="images/undo.png" onClick="Submit;" name="utilisateur[annul]" title="Annuler l\'action"></center>';
		}
		echo '</td>';
	
		echo '</tr>';
	   }//fin du if ($groupes_visibles)
	}//fin de boucle sur $i

/////////////////////////////////////////////////////////////
//Nouvel utilisateur (en fin de tableau)
if ($_SESSION['edition_utilisateur']==0) 
{
	echo '<tr class="centrerText">';
//////////////////utilisateur
	echo '<td><INPUT TYPE=text NAME="utilisateur[utilisateur_]" size=16 value="'.$_SESSION[ 'utilisateur' ][ 'utilisateur_' ].'"></td>';
/////////////////nom
	echo '<td><INPUT TYPE=text NAME="utilisateur[login_cas_]" size=15 value="'.$_SESSION[ 'utilisateur' ][ 'login_cas_' ].'"></td>';
/////////////////nom
	echo '<td><INPUT TYPE=text NAME="utilisateur[nom_]" size=15 value="'.$_SESSION[ 'utilisateur' ][ 'nom_' ].'"></td>';
/////////////////prenom
	echo '<td><INPUT TYPE=text NAME="utilisateur[prenom_]" size=16 value="'.$_SESSION[ 'utilisateur' ][ 'prenom_' ].'"></td>';
/////////////////mot de passe
	echo '<td><INPUT TYPE=password NAME="utilisateur[motdepasse_]" size=9 value="'.$_SESSION[ 'utilisateur' ][ 'motdepasse_' ].'"></td>';
//2 colonnes en plus pour MEL et SS
	//echo '<td></td>';//car ajout d'une colonne
	//echo '<td></td>';//car ajout d'une colonne
/////////////////groupe
	echo '<td><INPUT TYPE=text NAME="utilisateur[groupe_]" size=12 value="'.$_SESSION[ 'utilisateur' ][ 'groupe_' ].'"></td>';
/////////////////status
	echo '<td align=center ><select name="utilisateur[status_]"';
	echo '>';
	for ($i=1;$i<=6;$i++)
	{
		echo '<option value="'.$i.'" ';
		if ($_SESSION['utilisateur']['status_']==$i) echo 'selected';
		echo '>'.$i.'</option>';
	}
	echo '</select></td>';
/////////////////admin
	echo '<td align=center ><select name="utilisateur[admin_]"';
	echo '>';
	for ($i=0;$i<2;$i++)
	{
		echo '<option value="'.$i.'" ';
		if ($_SESSION['utilisateur']['admin_']==$i) echo 'selected';
		echo '>'.$i.'</option>';
	}
	echo '</select></td>';
//contrat
	echo '<td></td>';
	echo '<td><INPUT size=8 onkeyup="verifDate(this);" TYPE=text NAME="utilisateur[contrat_debut_]" value="'.$_SESSION[ 'utilisateur' ][ 'contrat_debut_' ].'"></td>';
	echo '<td><INPUT size=8 onkeyup="verifDate(this);" TYPE=text NAME="utilisateur[contrat_fin_]" value="'.$_SESSION[ 'utilisateur' ][ 'contrat_fin_' ].'"></td>';
	echo '<td>';
	echo '<select name="utilisateur[contrat_type_]" onChange="soumettre();">';
	for ($i=0;$i<sizeof($type_contrats);$i++)
	{
		echo '<option value="'.$type_contrats[$i][0].'" ';
		if ($_SESSION['utilisateur']['contrat_type_']==$type_contrats[$i][0])
		 { echo 'selected';}
		echo '>'.$type_contrats[$i][0].'</option>';
	}
	echo '</select>';
	echo '</td>';

	if ($module_conge_charge)
	{
	echo '<td></td>';
/////////////////solde CA
	echo '<td><INPUT size=2  TYPE=text NAME="utilisateur[solde_ca_]" value="'.$_SESSION[ 'utilisateur' ][ 'solde_ca_' ].'"></td>';
/////////////////solde CA-1
	echo '<td><INPUT size=2  TYPE=text NAME="utilisateur[solde_ca_1_]" value="'.$_SESSION[ 'utilisateur' ][ 'solde_ca_1_' ].'"></td>';
/////////////////solde recup.
	echo '<td><INPUT size=2  TYPE=text NAME="utilisateur[solde_recup_]" value="'.$_SESSION[ 'utilisateur' ][ 'solde_recup_' ].'"></td>';
/////////////////solde cet
	echo '<td><INPUT size=2  TYPE=text NAME="utilisateur[solde_cet_]" value="'.$_SESSION[ 'utilisateur' ][ 'solde_cet_' ].'"></td>';
/////////////////quota jours
	echo '<td><INPUT size=2  TYPE=text NAME="utilisateur[quota_jours_]" value="'.$_SESSION[ 'utilisateur' ][ 'quota_jours_' ].'"></td>';
/////////////////quotite
	echo '<td><INPUT size=2  TYPE=text NAME="utilisateur[quotite_]" value="'.$_SESSION[ 'utilisateur' ][ 'quotite_' ].'"></td>';
	}

	echo '<td><center><input type="image" src="images/ajouter.png" onClick="return confirm(\'Etes-vous s&ucirc;re de vouloir ajouter cet utilisateur?\');Submit;" name="utilisateur[nouvel]" title="Ajouter un nouvel utilisateur"></center></td>';
	echo '</tr>';
}//fin if ($_SESSION['edition_utilisateur']==0)


echo '</table>';
}//fin du else : affichage pour utilisateurs
echo '</tr></td>';
echo '</table>';

}//fin du else : if ($_SESSION[ 'choix_gestion' ] == 0)
}//fin du else : if ($_SESSION[ 'administrer' ]==0)
else //fin du if ($_SESSION[ 'connection' ][ 'admin' ]==1)
{
	echo 'Vous n\'&ecirc;tes pas autoris&eacute; &agrave; afficher cette page...';
}

?>
</table>
</form>
<form name="clock" onSubmit="0">
	<table id="footer">
		<tr>
			<td>
				<?php
					echo '<p><a href="mailto:'.$web_adress.'" title="Envoyer un mel aux Webmestres">Webmestres</a> [ <a href="'.$lien_organisme.'" target="_blank" title="Site Web '.$organisme.'">'.$organisme.'</a> ]</p>';
				?>
			</td>
			<td class="dateHeure">
				<?php
					echo ucwords(strftime("%A %d %B %Y")).'<input type="text" id="dateCourante" size="8" />';;
				?>
			</td>
		</tr>
	</table>
</form>

<div id="version">
	<table>
		<tr>
			<td>
				<?php
					echo '<p>PhpMyLab v'.$version.' &copy; 2012 - <a href="https://clrweb2.in2p3.fr/intra/phpmylab_help#CNIL" target="_blank">Mentions CNIL</a></p>';
				?>
			</td>
		</tr>
	</table>
</div>
</body>
</html>
