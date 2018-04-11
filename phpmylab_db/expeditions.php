<?php

//Affichage des erreurs en mode test
include 'config.php';
if($mode_test == 1 && !isset($_GET[ 'disconnect' ]))//Probleme de deconnexion avec CAS sinon
{
	ini_set('display_errors', 1);
	error_reporting(E_ALL);
}

/**
* Gestion des expéditions de colis
*
* La page des expéditions permet d'effectuer une demande d'envoi de colis
*
* Date de création : 5 Juillet 2012<br>
* Date de dernière modification : 19 mai 2015
* @version 3.0.0
* @author Cedric Gagnevin <cedric.gagnevin@laposte.net>
* @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.html
* @copyright CNRS (c) 2015
* @package phpMyLab
*/

/*********************************************************************************
******************************  PLAN     *****************************************
*********************************************************************************/

//    | -A-  Gestion de la déconnexion
//    | -B-  Fonctions
//    | -C-  Initialisation generale (configuration et php)
//    | -D-  Initialisation Session et variables
//    | -E-  Gestion des variables Recherche
//    | -F-  Gestion des variables d'expedition

//    | -G-  Recherche d'expeditions
//    | -H-  Ajout d'une demande d'expedition
//    | -I-  Nouvelle demande d'expedition
//    | -J-  Valider une demande d'expedition
//    | -K-  Annuler une demande d'expedition
//    | -L-  Reinitialiser la saisie
//    | -M-  Choix du module
//    | -N-  HTML
//    | -N1- Fenetre modale (popup)
//    | -N2- Affichage Recherche Expeditions
//    | -N3- Affichage des resultats de recherche evoluee
//    | -N4- Partie demande d'expedition
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
* Envoyer un mail.
*
* Formate le message à envoyer de manière à etre compatible avec la majorité des clients de messagerie et envoie le message au moyen de la fonction mail().
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
* Initialisation des variables des expeditions
*/
function init_expedition()
{
	$_SESSION[ 'id_deexp' ] = '';
	$_SESSION[ 'expedition' ][ 'id_expedition' ] = '';
	$_SESSION[ 'expedition' ][ 'groupe_impute' ] = '';
	$_SESSION[ 'expedition' ][ 'lieu' ] = '';

	$_SESSION[ 'expedition' ][ 'adresse' ] = '';
	$_SESSION[ 'expedition' ][ 'codepostal' ] = '';
	$_SESSION[ 'expedition' ][ 'ville' ] = '';
	$_SESSION[ 'expedition' ][ 'email' ] = '';
	$_SESSION[ 'expedition' ][ 'telephone' ] = '';

	$_SESSION[ 'expedition' ]['poids'] = '';
	$_SESSION[ 'expedition' ][ 'dimensions' ] = '';
	$_SESSION[ 'expedition' ][ 'valeur' ] = '';
	$_SESSION[ 'expedition' ][ 'designation' ] = '';
	$_SESSION[ 'expedition' ][ 'commentaire' ] = '';
	$_SESSION[ 'expedition' ][ 'etat' ] = 2;//Nouvelle demande

	$_SESSION[ 'expedition' ][ 'lien_tracker' ]= '';
	$_SESSION[ 'expedition' ][ 'num_tracking' ]= '';
	$_SESSION[ 'expedition' ][ 'piece_jointe' ]= '';
}



/**
* Initialisation des variables de recherche d'expeditions
*
*/
function init_rech_expedition()
{
	$_SESSION[ 'rech_expedition' ][ 'id_deexp' ] = '';
	$_SESSION[ 'rech_expedition' ][ 'nom' ] = '';
	$_SESSION[ 'rech_expedition' ][ 'prenom' ] = '';
	$_SESSION[ 'rech_expedition' ][ 'resultats' ]=0;
	$_SESSION[ 'rech_expedition' ][ 'limitebasse' ]=0;
	$_SESSION[ 'rech_expedition' ][ 'nb_par_page' ]=10;
}


/**
* Extrait une sous chaine de la longueur passée en parametre
*
* @param string Chaine de caractere
* @param int Nombre de caractere a extraire
* @return Chaine extraite
*/
function string_extract($chaine,$nbchar)
{
	if(!empty($chaine[$nbchar]))
	{
		if($chaine[$nbchar] == ' ')
			return $extract=substr($chaine,0,$nbchar).'...';
		return string_extract($chaine,$nbchar-1);
	}
	else return $chaine;
}


/**
* Retourne un tableau avec les noms et prénoms des membres d'un groupe pour un SELECT
*
* @param string chemin du fichier contenant les variables de connection de la base de données 
*/
function rech_evol($nom,$prenom)
{
   include 'config.php';
   include($chemin_connection);

   $message = '';

   $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
   mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

   //Si le nom et prenom sont reseignés : recherche stricte
   if(!empty($nom) OR !empty($prenom))
   {

	if(empty($nom))
	{
		$query = 'SELECT COUNT(*) FROM T_EXPEDITION WHERE UTILISATEUR IN(SELECT UTILISATEUR FROM T_UTILISATEUR WHERE PRENOM like "'.$prenom.'%")';
		$query2 = 'SELECT ID_EXPEDITION,NOM,PRENOM,GROUPE_IMPUTE,DESIGNATION,ETAT FROM T_EXPEDITION,T_UTILISATEUR WHERE T_EXPEDITION.UTILISATEUR=T_UTILISATEUR.UTILISATEUR AND T_EXPEDITION.UTILISATEUR IN(SELECT UTILISATEUR FROM T_UTILISATEUR WHERE PRENOM like "'.$prenom.'%") ORDER BY ID_EXPEDITION DESC LIMIT '.$_SESSION['rech_expedition']['limitebasse'].','.$_SESSION['rech_expedition']['nb_par_page'].'';
	}
	elseif(empty($prenom))
	{
		$query = 'SELECT COUNT(*) FROM T_EXPEDITION WHERE UTILISATEUR IN(SELECT UTILISATEUR FROM T_UTILISATEUR WHERE NOM like "'.$nom.'%")';
		$query2 = 'SELECT ID_EXPEDITION,NOM,PRENOM,GROUPE_IMPUTE,DESIGNATION,ETAT FROM T_EXPEDITION,T_UTILISATEUR WHERE T_EXPEDITION.UTILISATEUR=T_UTILISATEUR.UTILISATEUR AND T_EXPEDITION.UTILISATEUR IN(SELECT UTILISATEUR FROM T_UTILISATEUR WHERE NOM like "'.$nom.'%") ORDER BY ID_EXPEDITION DESC LIMIT '.$_SESSION['rech_expedition']['limitebasse'].','.$_SESSION['rech_expedition']['nb_par_page'].'';
	}
	else 
	{
		$query = 'SELECT COUNT(*) FROM T_EXPEDITION WHERE UTILISATEUR IN(SELECT UTILISATEUR FROM T_UTILISATEUR WHERE NOM="'.$nom.'" AND PRENOM="'.$prenom.'")';
		$query2 = 'SELECT ID_EXPEDITION,NOM,PRENOM,GROUPE_IMPUTE,DESIGNATION,ETAT FROM T_EXPEDITION,T_UTILISATEUR WHERE T_EXPEDITION.UTILISATEUR=T_UTILISATEUR.UTILISATEUR AND T_EXPEDITION.UTILISATEUR IN(SELECT UTILISATEUR FROM T_UTILISATEUR WHERE NOM="'.$nom.'" AND PRENOM="'.$prenom.'") ORDER BY ID_EXPEDITION DESC LIMIT '.$_SESSION['rech_expedition']['limitebasse'].','.$_SESSION['rech_expedition']['nb_par_page'].'';
	}
	$result = mysqli_query($link,$query);
	$line = mysqli_fetch_array($result, MYSQL_NUM);
	//comparaison avec le nombre actuel au cas ou modification des parametres
	// de recherche et appui sur recherche suivant ou precedente
	if ($_SESSION['rech_expedition']['resultats']!=$line[0]) $_SESSION['rech_expedition']['limitebasse']=0;
	$_SESSION['rech_expedition']['resultats']=$line[0];
	mysqli_free_result($result);
		
	if($line[0]>0)
 	{
		$result = mysqli_query($link,$query2) or die('Requete de recherche evolue des expeditions: ' . mysqli_error($link));
	
		$i=1;
		while ($line = mysqli_fetch_array($result, MYSQL_ASSOC))
		{
			//raffraichissement de l'affichage avec la demande trouvee
			$_SESSION['rech_expedition'][$i]['id_expedition']=$line["ID_EXPEDITION"];
			$_SESSION['rech_expedition'][$i]['nom']=ucwords(strtolower($line["NOM"]));
			$_SESSION['rech_expedition'][$i]['prenom']=ucwords(strtolower($line["PRENOM"]));
			$_SESSION['rech_expedition'][$i]['groupe_impute']=$line["GROUPE_IMPUTE"];
			$_SESSION['rech_expedition'][$i]['designation']=$line["DESIGNATION"];
			$_SESSION['rech_expedition'][$i]['etat']=$line["ETAT"];
			$i++;
		}	
		mysqli_free_result($result);
	}
	else 
	{
		$message = '<p class="gras rouge">Aucun r&eacute;sultat correspondant &agrave; la recherche</p>';
	}
	
   }

   return $message;
}

/**
* Permet d'obtenir les expéditions en attente de validation (tableau)
*
* @return Tableau contenant les expéditions en attente
*/
function expeditions_en_attente() 
{

	include("config.php");
	include($chemin_connection);
	// Connecting, selecting database:
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
	or die('Could not connect: ' . mysqli_error($link));
	mysqli_select_db($link,$mysql_base) or die('Could not select database : '. mysqli_error($link));

	$query = 'SELECT * FROM T_EXPEDITION WHERE ETAT = 0';
	$result = mysqli_query($link, $query) or die('Query '.$query.'| failed: ' . mysqli_error($link));		

	$tab_expeditions=array();

	while($donnee = mysqli_fetch_array($result, MYSQL_BOTH))
	{
		$expedition[ 'id_expedition' ] = $donnee[ 'ID_EXPEDITION' ];
		$expedition[ 'utilisateur' ] = $donnee[ 'UTILISATEUR' ];
		$expedition[ 'designation' ] = $donnee[ 'DESIGNATION' ];
		$expedition[ 'ville' ] = $donnee[ 'VILLE' ];
		array_push($tab_expeditions,$expedition);
	}

	mysqli_free_result($result);
	mysqli_close($link);
	return $tab_expeditions;
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
ini_set('url_rewriter.tags', '');

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

//contient l'id de la demande d'expedition
if (! isset($_SESSION[ 'id_deexp' ])) 
{
	$_SESSION[ 'id_deexp' ] = '';
	$_SESSION[ 'expedition' ][ 'utilisateur' ] = $_SESSION[ 'connection' ][ 'utilisateur' ];
	$_SESSION[ 'expedition' ][ 'nom' ] = $_SESSION[ 'connection' ][ 'nom' ];
	$_SESSION[ 'expedition' ][ 'prenom' ] = $_SESSION[ 'connection' ][ 'prenom' ];
	init_expedition();
}
if (isset($_REQUEST[ 'id_deexp' ])) $_SESSION[ 'id_deexp' ] = $_REQUEST[ 'id_deexp' ];

// Initialisation et variables recherche evoluee
if (! isset($_SESSION[ 'rech_expedition' ]))
{
	init_rech_expedition();
}


if (isset($_REQUEST[ 'rech_expedition' ][ 'nom' ])) $_SESSION[ 'rech_expedition' ][ 'nom' ] = $_REQUEST[ 'rech_expedition' ][ 'nom' ];
if (isset($_REQUEST[ 'rech_expedition' ][ 'prenom' ])) $_SESSION[ 'rech_expedition' ][ 'prenom' ] = $_REQUEST[ 'rech_expedition' ][ 'prenom' ];
if (isset($_REQUEST[ 'rech_expedition' ][ 'groupe_nom_prenom' ])) 
{
	$_SESSION[ 'rech_expedition' ][ 'groupe_nom_prenom' ] = $_REQUEST[ 'rech_expedition' ][ 'groupe_nom_prenom' ];
}
if (isset($_REQUEST[ 'rech_expedition' ][ 'groupe' ])) $_SESSION[ 'rech_expedition' ][ 'groupe' ] = $_REQUEST[ 'rech_expedition' ][ 'groupe' ];


/*********************************************************************************
********************  -E- Gestion des variables d'expedition  ********************
**********************************************************************************/

if (isset($_REQUEST[ 'expedition' ]))  if (is_array($_REQUEST[ 'expedition' ]))
{
if (isset($_REQUEST[ 'expedition' ][ 'groupe_impute' ])) $_SESSION[ 'expedition' ][ 'groupe_impute' ] = htmlspecialchars($_REQUEST[ 'expedition' ][ 'groupe_impute' ]);
if (isset($_REQUEST[ 'expedition' ][ 'lieu' ])) $_SESSION[ 'expedition' ][ 'lieu' ] = htmlspecialchars($_REQUEST[ 'expedition' ][ 'lieu' ]);
if (isset($_REQUEST[ 'expedition' ][ 'adresse' ])) $_SESSION[ 'expedition' ][ 'adresse' ] = htmlspecialchars($_REQUEST[ 'expedition' ][ 'adresse' ]);
if (isset($_REQUEST[ 'expedition' ][ 'codepostal' ])) $_SESSION[ 'expedition' ][ 'codepostal' ] = htmlspecialchars($_REQUEST[ 'expedition' ][ 'codepostal' ]);
if (isset($_REQUEST[ 'expedition' ][ 'ville' ])) $_SESSION[ 'expedition' ][ 'ville' ] = htmlspecialchars($_REQUEST[ 'expedition' ][ 'ville' ]);
if (isset($_REQUEST[ 'expedition' ][ 'email' ])) $_SESSION[ 'expedition' ][ 'email' ] = htmlspecialchars($_REQUEST[ 'expedition' ][ 'email' ]);
if (isset($_REQUEST[ 'expedition' ][ 'telephone' ])) $_SESSION[ 'expedition' ][ 'telephone' ] = htmlspecialchars($_REQUEST[ 'expedition' ][ 'telephone' ]);
if (isset($_REQUEST[ 'expedition' ][ 'poids' ])) $_SESSION[ 'expedition' ][ 'poids' ] = htmlspecialchars($_REQUEST[ 'expedition' ][ 'poids' ]);
if (isset($_REQUEST[ 'expedition' ][ 'dimensions' ])) $_SESSION[ 'expedition' ][ 'dimensions' ] = htmlspecialchars($_REQUEST[ 'expedition' ][ 'dimensions' ]);
if (isset($_REQUEST[ 'expedition' ][ 'valeur' ])) $_SESSION[ 'expedition' ][ 'valeur' ] = htmlspecialchars($_REQUEST[ 'expedition' ][ 'valeur' ]);
if (isset($_REQUEST[ 'expedition' ][ 'designation' ])) $_SESSION[ 'expedition' ][ 'designation' ] = htmlspecialchars($_REQUEST[ 'expedition' ][ 'designation' ]);
if (isset($_REQUEST[ 'expedition' ][ 'commentaire' ])) $_SESSION[ 'expedition' ][ 'commentaire' ] = htmlspecialchars($_REQUEST[ 'expedition' ][ 'commentaire' ]);
if (isset($_REQUEST[ 'expedition' ][ 'lien_tracker' ])) $_SESSION[ 'expedition' ][ 'lien_tracker' ] = htmlspecialchars($_REQUEST[ 'expedition' ][ 'lien_tracker' ]);
if (isset($_REQUEST[ 'expedition' ][ 'num_tracking' ])) $_SESSION[ 'expedition' ][ 'num_tracking' ] = htmlspecialchars($_REQUEST[ 'expedition' ][ 'num_tracking' ]);
}




if ( isset($_REQUEST[ 'rechercher_evol' ]))
{
	$_SESSION['recherche']['limitebasse']=0;
	$message_evolue= '';
	$message_evolue=rech_evol($_POST["rech_expedition"]["nom"], $_POST["rech_expedition"]["prenom"]);
}


///////////////// F3 - Reinitialiser page suivante et precedente la Recherche evoluee //////////
if ( isset($_REQUEST[ 'reinitialiser' ]) )
{
	init_rech_expedition();
}

if ( isset($_REQUEST[ 'rechercher_suiv' ]) )
{
	if ($_SESSION['rech_expedition']['limitebasse'] +$_SESSION['rech_expedition']['nb_par_page'] <$_SESSION['rech_expedition']['resultats'])
	$_SESSION['rech_expedition']['limitebasse']+=$_SESSION['rech_expedition']['nb_par_page'];
	rech_evol($_POST["rech_expedition"]["nom"],$_POST["rech_expedition"]["prenom"]);
}

if ( isset($_REQUEST[ 'rechercher_prec' ]) )
{
	if ($_SESSION['rech_expedition']['limitebasse']-$_SESSION['rech_expedition']['nb_par_page']>=0)
	$_SESSION['rech_expedition']['limitebasse']-=$_SESSION['rech_expedition']['nb_par_page'];
	rech_evol($_POST["rech_expedition"]["nom"],$_POST["rech_expedition"]["prenom"]);
}


/*********************************************************************************
***********************  G1 - Recherche des expéditions **************************
**********************************************************************************/

if ($_SESSION[ 'connection' ][ 'status' ] !=0)
	if ( isset($_REQUEST[ 'rechercher' ]) || isset($_GET["deexp"]))
	{
		if (isset($_GET["deexp"])) 
			$_SESSION["id_deexp"]=$_GET["deexp"];
		$annule=0;

		include $chemin_connection;

		$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
		mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

		$message_recherche= '';
   		$ind=$_SESSION["id_deexp"];
		if(isset($_POST["rech_expedition"]["nom"])) 		
			$nom_recherche=$_POST["rech_expedition"]["nom"];
		if(isset($_POST["rech_expedition"]["prenom"]))
			$prenom_recherche=$_POST["rech_expedition"]["prenom"];

		// Recherche par ID
		if(!EMPTY($ind))
		{
			$query = 'SELECT * FROM T_EXPEDITION WHERE ID_EXPEDITION='.$ind;
			$result = mysqli_query($link,$query) or die('Requete de selection d\'une expedition par ID: ' . mysqli_error($link));
			if ($result)
			{
				$line = mysqli_fetch_array($result, MYSQL_ASSOC);
				$_SESSION[ 'edition' ] =0;
				//fermer le volet si une demande a ete touvee:
				$_SESSION[ 'r_gui' ]=0;
				//raffraichissement de l'affichage avec la demande trouvee
				$_SESSION['expedition']['id_expedition']=$line["ID_EXPEDITION"];
				$_SESSION['expedition']['utilisateur']=$line["UTILISATEUR"];
				//Rechercher le NOM,PRENOM a partir de UTILISATEUR dans T_UTILISATEUR
				$query2 = 'SELECT NOM,PRENOM FROM T_UTILISATEUR WHERE UTILISATEUR=\''.$_SESSION['expedition']['utilisateur'].'\'';
				$result2 = mysqli_query($link,$query2);
				$line2 = mysqli_fetch_array($result2, MYSQL_NUM);
				$_SESSION['expedition']['nom']=ucwords(strtolower($line2[0]));
				$_SESSION['expedition']['prenom']=ucwords(strtolower($line2[1]));
				mysqli_free_result($result2);
				//Autres infos
				$_SESSION['expedition']['groupe_impute']=$line["GROUPE_IMPUTE"];
				$_SESSION['expedition']['lieu']=$line["LIEU_ENLEVEMENT"];
				$_SESSION['expedition']['adresse']=$line["ADRESSE"];
				$_SESSION['expedition']['codepostal']=$line["CODE_POSTAL"];
				$_SESSION['expedition']['ville']=$line["VILLE"];
				$_SESSION['expedition']['telephone']=$line["TELEPHONE"];
				$_SESSION['expedition']['email']=$line["EMAIL"];
				$_SESSION['expedition']['poids']=$line["POIDS"];
				$_SESSION['expedition']['dimensions']=$line["DIMENSIONS"];
				$_SESSION['expedition']['valeur']=$line["VALEUR"];
				$_SESSION['expedition']['designation']=$line["DESIGNATION"];
				$_SESSION['expedition']['commentaire']=$line["COMMENTAIRE"];	
				$_SESSION['expedition']['lien_tracker']=$line["LIEN_TRACKER"];
				$_SESSION['expedition']['num_tracking']=$line["NUMERO_TRACKING"];
				$_SESSION['expedition']['piece_jointe']=$line["PIECE_JOINTE"];
				$_SESSION['expedition']['etat']=$line["ETAT"];
			}
		}
		// Recherche par Nom et Prénom(stricte)
		elseif(!EMPTY($nom_recherche) && !EMPTY($prenom_recherche))
		{
			$message_recherche=rech_evol($nom_recherche,$prenom_recherche);

		}
		// Recherche par Nom
		elseif(!EMPTY($nom_recherche) && EMPTY($prenom_recherche))
		{
			$message_recherche=rech_evol($nom_recherche,'');

		}
		// Recherche par Prenom
		elseif(EMPTY($nom_recherche) && !EMPTY($prenom_recherche))
		{
			$message_recherche=rech_evol('',$prenom_recherche);

		}
	}


/*********************************************************************************
***********************  G4 - Ajout d'une demande d'expedition *******************
**********************************************************************************/

if (isset($_REQUEST[ 'expedition' ]['envoyer']))
{
	$annule=0;
	//Controle des champs obligatoires
	if(EMPTY($_SESSION[ 'expedition' ][ 'nom' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Nom" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(EMPTY($_SESSION[ 'expedition' ][ 'prenom' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Pr&eacute;nom" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}	
	elseif($_SESSION[ 'expedition' ][ 'groupe_impute' ] == 'Equipe, contrat ou service')
	{
		$message_demande= '<p class="rouge gras">La partie "Groupe imput&eacute;" n\'est pas renseign&eacute;e.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}	
	elseif(EMPTY($_SESSION[ 'expedition' ][ 'lieu' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Lieu d\'enl&egrave;vement du colis" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(EMPTY($_SESSION[ 'expedition' ][ 'adresse' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Adresse" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(EMPTY($_SESSION[ 'expedition' ][ 'codepostal' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Code postal" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(!preg_match('/^\d{5}$/',$_SESSION[ 'expedition' ][ 'codepostal' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Code postal" n\'est pas valide.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(EMPTY($_SESSION[ 'expedition' ][ 'ville' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Ville" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(EMPTY($_SESSION[ 'expedition' ][ 'telephone' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "T&eacute;l&eacute;phone" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(!preg_match('/^(00\d\d|0){1}\d[0-9]{8}$/',$_SESSION[ 'expedition' ][ 'telephone' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "T&eacute;l&eacute;phone" n\'est pas valide.<br/>Exemple international : 0033473123456<br/>Exemple France : 0473123456<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(EMPTY($_SESSION[ 'expedition' ][ 'email' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Email" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(!preg_match('/^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$/',$_SESSION[ 'expedition' ][ 'email' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Email" n\'est pas valide.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(EMPTY($_SESSION[ 'expedition' ][ 'poids' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Poids" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(!preg_match('/^\d+([,.]\d+)?$/',$_SESSION[ 'expedition' ][ 'poids' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Poids" n\'est pas valide.<br/> Exemple : 10.5 ou 10,5<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(EMPTY($_SESSION[ 'expedition' ][ 'dimensions' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Dimensions" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(!preg_match('/^\d+([,.]\d+)?[\*]\d+([,.]\d+)?[\*]\d+([,.]\d+)?$/',$_SESSION[ 'expedition' ][ 'dimensions' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Dimensions" n\'est pas bon format.<br/> Exemple de format : Longueur*largeur*hauteur (en cm)<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(!EMPTY($_SESSION[ 'expedition' ][ 'valeur' ]) && !preg_match('/^\d+([,.]\d\d)?$/',$_SESSION[ 'expedition' ][ 'valeur' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Valeur du bien" n\'est pas au format mon&eacute;taire.<br/> Exemple de format : 12,40 ou 12.40<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(EMPTY($_SESSION[ 'expedition' ][ 'designation' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "D&eacute;signation du mat&eacute;riel" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	// fin du controle des champs obligatoires

	if(!$annule)
	{
		include $chemin_connection;
		
		$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
		mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');
		
		//recherche de l'indice a ajouter
		//la numerotation des (ID_) commence a 1
		$indi=1;
		$query = 'SELECT MAX(ID_EXPEDITION) FROM T_EXPEDITION';
		$result = mysqli_query($link,$query) or die('Requete de comptage des demandes pour expedition: ' . mysqli_error($link));
		if ($result)
		{
			$line = mysqli_fetch_array($result, MYSQL_NUM);
			$indi=$line[0]+1;
			mysqli_free_result($result);
		}
		$_SESSION['expedition']['id_expedition']=$indi;
		
		//insertion de la demande d'expedition dans la base
		$query = 'INSERT INTO T_EXPEDITION(ID_EXPEDITION,UTILISATEUR,GROUPE_IMPUTE,LIEU_ENLEVEMENT,ADRESSE,CODE_POSTAL,VILLE,TELEPHONE,EMAIL,POIDS,DIMENSIONS,VALEUR,DESIGNATION,COMMENTAIRE,ETAT) 
		VALUES ('.$indi.',
			"'.mysqli_real_escape_string($link,$_SESSION['expedition']['utilisateur']).'",
			"'.mysqli_real_escape_string($link,$_SESSION['expedition']['groupe_impute']).'",
			"'.mysqli_real_escape_string($link,$_SESSION['expedition']['lieu']).'",
			"'.mysqli_real_escape_string($link,$_SESSION['expedition']['adresse']).'",
			"'.mysqli_real_escape_string($link,$_SESSION['expedition']['codepostal']).'",
			"'.mysqli_real_escape_string($link,$_SESSION['expedition']['ville']).'",
			"'.mysqli_real_escape_string($link,$_SESSION['expedition']['telephone']).'",
			"'.mysqli_real_escape_string($link,$_SESSION['expedition']['email']).'",
			"'.mysqli_real_escape_string($link,$_SESSION['expedition']['poids']).'",
			"'.mysqli_real_escape_string($link,$_SESSION['expedition']['dimensions']).'",
			"'.mysqli_real_escape_string($link,$_SESSION['expedition']['valeur']).'",
			"'.mysqli_real_escape_string($link,$_SESSION['expedition']['designation']).'",
			"'.mysqli_real_escape_string($link,$_SESSION['expedition']['commentaire']).'",
			0
			)';
		$result = mysqli_query($link,$query) or die('Requete d\'insertion echouee.<br>Query : '.$query.'<br>Erreur :'. mysqli_error($link));
		//mysql_free_result($result);
	
		$message_demande= "<span class='vert gras'>-> Demande ajout&eacute;e <-</span>";	
		
		//--------- Envoi d'emails ---------
		$indi=$_SESSION['expedition']['id_expedition'];//attention $ind est une vairiable globale
		$subject = "EXPEDITION : Demande n°".$indi." pour ".string_extract(html_entity_decode($_SESSION['expedition']['designation']),40).".";
	
		//demandeur de l'expedition 
		if ($mode_test) $TO = $mel_test;
		else $TO = $_SESSION['expedition']['utilisateur'].'@'.$domaine;
		$message = "<body>Bonjour ".$_SESSION['expedition']['prenom']." ".$_SESSION['expedition']['nom'].",<br> votre demande d'expédition a été effectuée.<br> ";
		$message .= "Suivez le lien <a href=".$chemin_mel."?deexp=".$indi.">".$chemin_mel."?deexp=".$indi."</a> pour l'afficher.<br><br>";
		
		
		$message .= '================================================================<br>';
		$message .= 'Demande d\'expédition n°'.$indi.' faite le '.date("d/m/Y \à H:i").'.<br>';
		$message .= '================================================================<br><br>';
		$message .= ' &bull; DEMANDEUR<br>';		
		$message .= 'Nom : '.utf8_encode($_SESSION['expedition']['nom']).'<br>';
		$message .= 'Prénom : '.utf8_encode($_SESSION['expedition']['prenom']).'<br>';
		$message .= 'Groupe imputé : '.utf8_encode($_SESSION['expedition']['groupe_impute']).'<br>';
		$message .= 'Lieu d\'enlèvement du colis : '.utf8_encode($_SESSION['expedition']['lieu']).'<br><br>';
		
		$message .= ' &bull; DESTINATAIRE<br>';		
		$message .= 'Adresse : '.utf8_encode($_SESSION['expedition']['adresse']).'<br>';
		$message .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$_SESSION['expedition']['codepostal'].', '.utf8_encode($_SESSION['expedition']['ville']).'<br>';
		$message .= 'Email : '.utf8_encode($_SESSION['expedition']['email']).'<br>';
		$message .= 'Téléphone : '.$_SESSION['expedition']['telephone'].'<br><br>';

		$message .= ' &bull; INFORMATIONS SUR LE COLIS<br>';		
		$message .= 'Poids : '.$_SESSION['expedition']['poids'].' Kg<br>';
		$message .= 'Dimensions (L*l*h) : '.$_SESSION['expedition']['dimensions'].'<br>';
		if($_SESSION['expedition']['valeur'] == '')
			$message .= 'Valeur du bien : Non communiquée.<br>';
		else $message .= 'Valeur du bien : '.$_SESSION['expedition']['valeur'].' &euro;<br>';
		$message .= 'Désignation du matériel : '.utf8_encode($_SESSION['expedition']['designation']).'<br><br>';
		$message .= '<br>================================================================<br></body>';    
		

		$message=utf8_decode($message);
		send_mail($TO, $message, $subject, $_SESSION['expedition']['utilisateur'].'@'.$domaine, $_SESSION['expedition']['nom']." ".$_SESSION['expedition']['prenom']);
	

		//au responsable du groupe imputé
		$query3 = 'SELECT UTILISATEUR,NOM,PRENOM FROM T_UTILISATEUR WHERE UTILISATEUR =(
				SELECT RESPONSABLE FROM T_CORRESPONDANCE WHERE GROUPE="'.$_SESSION['expedition']['groupe_impute'].'")';
		$result3 = mysqli_query($link,$query3) or die('Requete selection du responsable du groupe impute: ' . mysqli_error($link));
		$responsable=mysqli_fetch_array($result3,MYSQL_BOTH);
		
		if ($mode_test) $TO = $mel_test;
		else $TO = $responsable['UTILISATEUR'].'@'.$domaine;
		$message = "<body>Bonjour ".ucfirst(strtolower($responsable['PRENOM']))." ".ucfirst(strtolower($responsable['NOM'])).",<br>".$_SESSION['expedition']['prenom']." ".$_SESSION['expedition']['nom']." a effectué une demande d'expédition pour votre groupe.<br> ";
		$message .= "Suivez le lien <a href=".$chemin_mel."?deexp=".$indi.">".$chemin_mel."?deexp=".$indi."</a> pour l'afficher.<br><br>";
		
		
		$message .= '================================================================<br>';
		$message .= 'Demande d\'expédition n°'.$indi.' faite le '.date("d/m/Y \à H:i").'.<br>';
		$message .= '================================================================<br><br>';
		$message .= ' &bull; DEMANDEUR<br>';		
		$message .= 'Nom : '.utf8_encode($_SESSION['expedition']['nom']).'<br>';
		$message .= 'Prénom : '.utf8_encode($_SESSION['expedition']['prenom']).'<br>';
		$message .= 'Groupe imputé : '.utf8_encode($_SESSION['expedition']['groupe_impute']).'<br>';
		$message .= 'Lieu d\'enlèvement du colis : '.utf8_encode($_SESSION['expedition']['lieu']).'<br><br>';
		
		$message .= ' &bull; DESTINATAIRE<br>';		
		$message .= 'Adresse : '.utf8_encode($_SESSION['expedition']['adresse']).'<br>';
		$message .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$_SESSION['expedition']['codepostal'].', '.utf8_encode($_SESSION['expedition']['ville']).'<br>';
		$message .= 'Email : '.utf8_encode($_SESSION['expedition']['email']).'<br>';
		$message .= 'Téléphone : '.$_SESSION['expedition']['telephone'].'<br><br>';

		$message .= ' &bull; INFORMATIONS SUR LE COLIS<br>';		
		$message .= 'Poids : '.$_SESSION['expedition']['poids'].' Kg<br>';
		$message .= 'Dimensions (L*l*h) : '.$_SESSION['expedition']['dimensions'].'<br>';
		if($_SESSION['expedition']['valeur'] == '')
			$message .= 'Valeur du bien : Non communiquée.<br>';
		else $message .= 'Valeur du bien : '.$_SESSION['expedition']['valeur'].' &euro;<br>';
		$message .= 'Désignation du matériel : '.utf8_encode($_SESSION['expedition']['designation']).'<br><br>';
		$message .= '<br>================================================================<br></body>';    
		

		$message=utf8_decode($message);
		send_mail($TO, $message, $subject, $_SESSION['expedition']['utilisateur'].'@'.$domaine, $_SESSION['expedition']['nom']." ".$_SESSION['expedition']['prenom']);
	


		//aux gestionnaires du module EXPEDITIONS
		//NB: $gestionnaires_expeditions est renseigné dans la configuration (tableau)
		foreach($gestionnaires_expeditions as $login_gestionnaire)
		{
			//Pour ne pas envoyer 2 emails si le demandeur est un gestionnaire
			if($login_gestionnaire != $_SESSION['expedition']['utilisateur'])
			{
				if ($mode_test) $TO = $mel_test;
				else $TO = $login_gestionnaire.'@'.$domaine;
				$message = '<body>Bonjour gestionnaire '.$login_gestionnaire.',<br>';
				$message .= 'Suivez le lien <a href=".$chemin_mel."?deexp='.$indi.'">'.$chemin_mel.'?deexp='.$indi.'</a> pour afficher la nouvelle demande d\'expédition émise par '.$_SESSION['expedition']['prenom'].' '.$_SESSION['expedition']['nom'].'.<br><br>';
				
				$message .= '================================================================<br>';
				$message .= 'Demande d\'expédition n°'.$indi.' faite le '.date("d/m/Y \à H:i").'.<br>';
				$message .= '================================================================<br><br>';
				$message .= ' &bull; DEMANDEUR<br>';		
				$message .= 'Nom : '.utf8_encode($_SESSION['expedition']['nom']).'<br>';
				$message .= 'Prénom : '.utf8_encode($_SESSION['expedition']['prenom']).'<br>';
				$message .= 'Groupe imputé : '.utf8_encode($_SESSION['expedition']['groupe_impute']).'<br>';
				$message .= 'Lieu d\'enlèvement du colis : '.utf8_encode($_SESSION['expedition']['lieu']).'<br><br>';
				
				$message .= ' &bull; DESTINATAIRE<br>';		
				$message .= 'Adresse : '.utf8_encode($_SESSION['expedition']['adresse']).'<br>';
				$message .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$_SESSION['expedition']['codepostal'].', '.utf8_encode($_SESSION['expedition']['ville']).'<br>';
				$message .= 'Email : '.utf8_encode($_SESSION['expedition']['email']).'<br>';
				$message .= 'Téléphone : '.$_SESSION['expedition']['telephone'].'<br><br>';
		
				$message .= ' &bull; INFORMATIONS SUR LE COLIS<br>';		
				$message .= 'Poids : '.$_SESSION['expedition']['poids'].' Kg<br>';
				$message .= 'Dimensions (L*l*h) : '.$_SESSION['expedition']['dimensions'].'<br>';
				if($_SESSION['expedition']['valeur'] == '')
					$message .= 'Valeur du bien : Non communiquée.<br>';
				else $message .= 'Valeur du bien : '.$_SESSION['expedition']['valeur'].' &euro;<br>';
				$message .= 'Désignation du matériel : '.utf8_encode($_SESSION['expedition']['designation']).'<br><br>';
				$message .= '<br>================================================================<br></body>';    
				
		
				$message=utf8_decode($message);
				send_mail($TO, $message, $subject, $_SESSION['expedition']['utilisateur'].'@'.$domaine, $_SESSION['expedition']['nom']." ".$_SESSION['expedition']['prenom']);
			}
		}

		$message_demande.= "<br><span class='vert gras'>-> Envoi de mails <-</span>";
		
		mysqli_close($link);
		
		//on re-initialise les champs pour ne pas effectuer plusieurs fois le meme conge
		init_expedition();
		
	}
}


/*********************************************************************************
***********************  G5 - Nouvelle demande d'expedition **********************
**********************************************************************************/

if (isset($_REQUEST[ 'expedition' ]['nouvelle']))
{
	$_SESSION['edition']=1;
	$_SESSION[ 'r_gui' ]=0;//fermeture de la zone de recherche (21 avril 2009)
	$_SESSION[ 'expedition' ][ 'utilisateur' ]=$_SESSION[ 'connection' ][ 'utilisateur' ];
	$_SESSION[ 'expedition' ][ 'nom' ]=$_SESSION[ 'connection' ][ 'nom' ];
	$_SESSION[ 'expedition' ][ 'prenom' ]=$_SESSION[ 'connection' ][ 'prenom' ];

	init_expedition();
}

/*********************************************************************************
***********************   G6 - Valider demande d'expedition **********************
**********************************************************************************/
if (isset($_REQUEST[ 'expedition' ]['valider']))
{
	$annule=0;
	//Controle des champs obligatoires
	if(EMPTY($_SESSION[ 'expedition' ][ 'lien_tracker' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Lien du tracker" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(EMPTY($_SESSION[ 'expedition' ][ 'num_tracking' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Num&eacute;ro de tracking" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}	
	if(!$annule)
	{
		include $chemin_connection;
		
		$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
		mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

		$message_demande = '';
		$id_exp=$_SESSION['expedition']['id_expedition'];//ID de la demande en question

		if(!empty($_FILES['piece_jointe']['name']))
		{
			//Upload de la piece jointe
			$MAX_FILE_SIZE = 3145728;// 3Mo
			//Verif qu'il n'y ait pas d'erreur lors de l'upload
			if($_FILES['piece_jointe']['error'] > 0) $message_demande .= '<p class="gras rouge">Erreur lors du transfert</p>';	
	
			//Controle sur la taille max
			if($_FILES['piece_jointe']['size'] > $MAX_FILE_SIZE) $message_demande .= '<p class="gras rouge">Erreur : le fichier est trop gros</p>';	
		
			//Controle de l'extention
			$extension_upload = strtolower(substr(strrchr($_FILES['piece_jointe']['name'],'.'),1));
			if(!in_array($extension_upload,array('jpg','jpeg','png','pdf'))) $message_demande .='<p class="gras rouge">Erreur : Extension non autoris&eacute;e<br/>Autoris&eacute;es : jpg,jpeg,png,pdf</p>';

			//Génération du nom du fichier (chaine aleatoire)
			$nom_fichier = "";
			$chaine = "abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
			srand((double)microtime()*1000000);
			for($i=0; $i<15; $i++) 
				$nom_fichier .= $chaine[rand()%strlen($chaine)];
			$nom_fichier .= '.'.$extension_upload;

			$_SESSION['expedition']['piece_jointe']=$nom_fichier;
				
			$destination_fichier="expedition_attachments/".$nom_fichier;
			$transfert_reussi=move_uploaded_file($_FILES['piece_jointe']['tmp_name'],$destination_fichier);
			if(!$transfert_reussi) $message_demande .= '<p class="gras rouge">Erreur : Echec du transfert de la piece jointe sur le serveur</p>';
		}

		if($message_demande == '')
		{
			//Remplissage du lien de tracking,du numéro de tracking et etat=1
			$query = 'UPDATE T_EXPEDITION SET LIEN_TRACKER="'.$_SESSION['expedition']['lien_tracker'].'", NUMERO_TRACKING="'.$_SESSION['expedition']['num_tracking'].'",PIECE_JOINTE="'.$_SESSION['expedition']['piece_jointe'].'",ETAT=1 WHERE ID_EXPEDITION="'.$id_exp.'"';
			$result = mysqli_query($link,$query) or die('Requete de modification lien et numero de tracker: ' . mysqli_error($link));
		

			$message_demande .= "<span class='vert gras'>-> Demande valid&eacute;e <-</span>";	
			$_SESSION['expedition']['etat']=1;



			//--------- Envoi d'emails ---------
			$subject = "EXPEDITION : Validation n°".$id_exp." pour ".string_extract(html_entity_decode($_SESSION['expedition']['designation']),40).".";
		
			//demandeur de l'expedition 
			if ($mode_test) $TO = $mel_test;
			else $TO = $_SESSION['expedition']['utilisateur'].'@'.$domaine;
			$message = "<body>Bonjour ".$_SESSION['expedition']['prenom']." ".$_SESSION['expedition']['nom'].",<br> votre demande d'expédition a été validée.<br> ";
			$message .= "Suivez le lien <a href=".$chemin_mel."?deexp=".$id_exp.">".$chemin_mel."?deexp=".$id_exp."</a> pour l'afficher.<br><br>";
			
			
			$message .= '================================================================<br>';
			$message .= 'Demande d\'expédition n°'.$id_exp.' validée le '.date("d/m/Y \à H:i").'.<br>';
			$message .= '================================================================<br><br>';
			$message .= ' &bull; DEMANDEUR<br>';		
			$message .= 'Nom : '.utf8_encode($_SESSION['expedition']['nom']).'<br>';
			$message .= 'Prénom : '.utf8_encode($_SESSION['expedition']['prenom']).'<br>';
			$message .= 'Groupe imputé : '.utf8_encode($_SESSION['expedition']['groupe_impute']).'<br>';
			$message .= 'Lieu d\'enlèvement du colis : '.utf8_encode($_SESSION['expedition']['lieu']).'<br><br>';
			
			$message .= ' &bull; DESTINATAIRE<br>';		
			$message .= 'Adresse : '.utf8_encode($_SESSION['expedition']['adresse']).'<br>';
			$message .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$_SESSION['expedition']['codepostal'].', '.utf8_encode($_SESSION['expedition']['ville']).'<br>';
			$message .= 'Email : '.utf8_encode($_SESSION['expedition']['email']).'<br>';
			$message .= 'Téléphone : '.$_SESSION['expedition']['telephone'].'<br><br>';
	
			$message .= ' &bull; INFORMATIONS SUR LE COLIS<br>';		
			$message .= 'Poids : '.$_SESSION['expedition']['poids'].' Kg<br>';
			$message .= 'Dimensions (L*l*h) : '.$_SESSION['expedition']['dimensions'].'<br>';
			if($_SESSION['expedition']['valeur'] == '')
				$message .= 'Valeur du bien : Non communiquée.<br>';
			else $message .= 'Valeur du bien : '.$_SESSION['expedition']['valeur'].' &euro;<br>';
			$message .= 'Désignation du matériel : '.utf8_encode($_SESSION['expedition']['designation']).'<br><br>';
	
			$message .= ' &bull; SUIVI DU COLIS <br>';		
			$message .= 'Lien du tracker : '.$_SESSION['expedition']['lien_tracker'].'<br>';
			$message .= 'Numéro de tracking : '.$_SESSION['expedition']['num_tracking'].'<br>';
			if(!empty($_SESSION['expedition']['piece_jointe']))
				$message .= 'Pièce jointe : AVEC.<br>';
			else $message .= 'Pièce jointe : SANS.<br>';
			
			$message .= '<br>================================================================<br></body>';    
			
	
			$message=utf8_decode($message);
			send_mail($TO, $message, $subject, $_SESSION['connection']['utilisateur'].'@'.$domaine, $_SESSION['connection']['nom']." ".$_SESSION['connection']['prenom']);
		
			//aux gestionnaires du module EXPEDITIONS
			//NB: $gestionnaires_expeditions est renseigné dans la configuration (tableau)
			foreach($gestionnaires_expeditions as $login_gestionnaire)
			{
				//Pour ne pas envoyer 2 emails si le demandeur est un gestionnaire
				if($login_gestionnaire != $_SESSION['expedition']['utilisateur'])
				{
					if ($mode_test) $TO = $mel_test;
					else $TO = $login_gestionnaire.'@'.$domaine;
					$message = '<body>Bonjour gestionnaire '.$login_gestionnaire.',<br>';
					$message .= 'La demande n&deg; '.$id_exp.' faite par '.$_SESSION['expedition']['prenom'].' '.$_SESSION['expedition']['nom'].' a été validée. Suivez le lien <a href=".$chemin_mel."?deexp='.$id_exp.'">'.$chemin_mel.'?deexp='.$id_exp.'</a> pour l\'afficher.<br><br>';
					
					$message .= '================================================================<br>';
					$message .= 'Demande d\'expédition n°'.$id_exp.' validée le '.date("d/m/Y \à H:i").'.<br>';
					$message .= '================================================================<br><br>';
					$message .= ' &bull; DEMANDEUR<br>';		
					$message .= 'Nom : '.utf8_encode($_SESSION['expedition']['nom']).'<br>';
					$message .= 'Prénom : '.utf8_encode($_SESSION['expedition']['prenom']).'<br>';
					$message .= 'Groupe imputé : '.utf8_encode($_SESSION['expedition']['groupe_impute']).'<br>';
					$message .= 'Lieu d\'enlèvement du colis : '.utf8_encode($_SESSION['expedition']['lieu']).'<br><br>';
					
					$message .= ' &bull; DESTINATAIRE<br>';		
					$message .= 'Adresse : '.utf8_encode($_SESSION['expedition']['adresse']).'<br>';
					$message .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$_SESSION['expedition']['codepostal'].', '.utf8_encode($_SESSION['expedition']['ville']).'<br>';
					$message .= 'Email : '.utf8_encode($_SESSION['expedition']['email']).'<br>';
					$message .= 'Téléphone : '.$_SESSION['expedition']['telephone'].'<br><br>';
			
					$message .= ' &bull; INFORMATIONS SUR LE COLIS<br>';		
					$message .= 'Poids : '.$_SESSION['expedition']['poids'].' Kg<br>';
					$message .= 'Dimensions (L*l*h) : '.$_SESSION['expedition']['dimensions'].'<br>';
					if($_SESSION['expedition']['valeur'] == '')
						$message .= 'Valeur du bien : Non communiquée.<br>';
					else $message .= 'Valeur du bien : '.$_SESSION['expedition']['valeur'].' &euro;<br>';
					$message .= 'Désignation du matériel : '.utf8_encode($_SESSION['expedition']['designation']).'<br><br>';
	
					$message .= ' &bull; SUIVI DU COLIS <br>';		
					$message .= 'Lien du tracker : '.$_SESSION['expedition']['lien_tracker'].'<br>';
					$message .= 'Numéro de tracking : '.$_SESSION['expedition']['num_tracking'].'<br>';
					if(!empty($_SESSION['expedition']['piece_jointe']))
						$message .= 'Pièce jointe : AVEC.<br>';
					else $message .= 'Pièce jointe : SANS.<br>';
	
					$message .= '<br>================================================================<br></body>';    
					
			
					$message=utf8_decode($message);
					send_mail($TO, $message, $subject, $_SESSION['connection']['utilisateur'].'@'.$domaine, $_SESSION['connection']['nom']." ".$_SESSION['connection']['prenom']);
				}
			}
	
			$message_demande.= "<br><span class='vert gras'>-> Envoi de mails <-</span>";
			
			mysqli_close($link);
		
			//on re-initialise les champs pour ne pas effectuer plusieurs fois le meme conge
			init_expedition();
		}
   	}
}


/*********************************************************************************
***********************  G7 - Annuler une demande d'expedition *******************
**********************************************************************************/

if (isset($_REQUEST[ 'expedition' ]['annuler']))
{
	include $chemin_connection;
	
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
	mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');
	
	$message_demande= "";
	$ind=$_SESSION['expedition']['id_expedition'];
   
	$query = 'UPDATE T_EXPEDITION SET ETAT=-1 WHERE ID_EXPEDITION='.$ind;
	$result = mysqli_query($link,$query) or die('L782 : Requete de modification de l etat de l expedition: ' . mysqli_error($link));
	$message_demande.= "<p class='vert gras'>-> Demande annul&eacute;e <-</p>";	
	$_SESSION['expedition']['etat']=-1;
   
	//----- Envoi d'emails -------
	$subject = "EXPEDITION : Annulation demande d'expédition (ID=".$ind.")";

   	//au demandeur
	if ($mode_test) $TO = $mel_test;
	else $TO = $_SESSION['expedition']['utilisateur'].'@'.$domaine;
	if($_SESSION['connection']['utilisateur'] == $_SESSION['expedition']['utilisateur'])
		$message .= "Votre avez annulé votre demande d'expédition.<br> ";
	else $message = "<body>Bonjour ".$_SESSION['expedition']['nom']." ".$_SESSION['expedition']['prenom'].",<br> votre demande d'expédition a été annulé par ".$_SESSION['connection']['prenom']." ".$_SESSION['connection']['nom'].".<br> ";
	$message .= "Suivez le lien <a href=".$chemin_mel."?deexp=".$ind.">".$chemin_mel."?deexp=".$ind."</a> pour l'afficher.</body>";
	$message=utf8_decode($message);
	send_mail($TO, $message, $subject, $_SESSION['connection']['utilisateur'].'@'.$domaine, $_SESSION['connection']['nom']." ".$_SESSION['connection']['prenom']);
   
	//aux gestionnaires du module EXPEDITIONS
	//NB: $gestionnaires_expeditions est renseigné dans la configuration (tableau)
	foreach($gestionnaires_expeditions as $login_gestionnaire)
	{
		//Pour ne pas envoyer 2 emails si le demandeur est un gestionnaire
		if($login_gestionnaire != $_SESSION['expedition']['utilisateur'])
		{
			if ($mode_test) $TO = $mel_test;
			else $TO = $login_gestionnaire.'@'.$domaine;
			$message = '<body>Bonjour gestionnaire '.$login_gestionnaire.',<br>';
			if($_SESSION['connection']['utilisateur'] == $_SESSION['expedition']['utilisateur'])
				$message .= "Votre avez annulé votre demande d'expédition.<br> ";
			else $message .= "La demande d'expédition faite par ".$_SESSION['expedition']['prenom']." ".$_SESSION['expedition']['nom']." a été annulée par ".$_SESSION['connection']['prenom']." ".$_SESSION['connection']['nom'].".<br> ";
			$message .= "Suivez le lien <a href=".$chemin_mel."?deexp=".$ind.">".$chemin_mel."?deexp=".$ind."</a> pour l'afficher.</body>";
	
			$message=utf8_decode($message);
			send_mail($TO, $message, $subject, $_SESSION['expedition']['utilisateur'].'@'.$domaine, $_SESSION['expedition']['nom']." ".$_SESSION['expedition']['prenom']);
		}
	}

	$message_demande.= "<span class='vert gras'>-> Envoi de mails <-</span>";
	
	mysqli_close($link);
	
	//on re-initialise les champs 
	init_expedition();
}


/*********************************************************************************
***********************  H - Reinitialiser la saisie *****************************
**********************************************************************************/
if ( isset($_REQUEST[ 'expedition' ][ 'saisie' ]) )
{
	init_expedition();
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
*******************************  -J- HTML ****************************************
**********************************************************************************/

// En tete des modules
include "en_tete.php"; 
?>

<form name="form1" id="form1" method="post" action="<?php echo $_SERVER[ 'PHP_SELF' ]; ?>" enctype="multipart/form-data">

<!-- Autocomplete -->
<script type="text/javascript">

$(function() {

<?php
	include("config.php");
	include("".$chemin_connection);
	// Connecting, selecting database:
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Could not connect: ' . mysqli_connect_error());
	mysqli_select_db($link,$mysql_base) or die('Could not select database : '. mysqli_error($link));

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

            $( "#rech_expedition_nom" ).autocomplete({
                source: listeNoms
            });

            $( "#rech_expedition_prenom" ).autocomplete({
                source: listePrenoms
            });

        });
</script>


<!-- ============ -->

<?php
/*********************************************************************************
*********************  -J- Fenetre modale (popup) ********************************
**********************************************************************************/

$tab_expeditions=expeditions_en_attente();//Tableau contenant les expéditions en attente de validation
$nb_expeditions_en_attente=count($tab_expeditions);//Nombre d'expéditions en attente de validation

//Ouverture de la fenetre grace à cette fonction JQuery onClick
if(isset($_SESSION[ 'fenetre_modale_expeditions' ]) && $_SESSION[ 'fenetre_modale_expeditions' ] == 1)
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
			if($nb_expeditions_en_attente == 1)
				echo '<h2 class="centrer" id="h2_fenetreModale">Vous avez 1 demande d\'exp&eacute;dition en attente</h2>';
			else echo '<h2 class="centrer" id="h2_fenetreModale">Vous avez '.$nb_expeditions_en_attente.' demandes d\'exp&eacute;dition en attente</h2>';
			
			echo '<div id="listeDesExpeditionsEnAttente">';
			echo '<table class="centrerBloc">';
			foreach($tab_expeditions as $expedition)
			{
				echo '<tr><td> <a href="expeditions.php?deexp='.$expedition[ 'id_expedition' ].'#DEMANDE_EXPEDITIONS">n&deg; '.$expedition[ 'id_expedition' ].'</a>  |  De <strong>'.$expedition[ 'utilisateur' ].'</strong>, destination '.$expedition[ 'ville' ].' : <em>'.string_extract($expedition[ 'designation' ],50).'</em>.</td></tr>';//expeditions.php?sid='.$sid.'&deexp
			}
			echo '</table>';
			echo '</div>';
	echo '</div>';	
}

//Popup lors de l'arrivée du gestionnaire sur le module EXPEDITIONS

if(in_array($_SESSION[ 'connection' ][ 'utilisateur' ],$gestionnaires_expeditions) && $nb_expeditions_en_attente > 0)
{
	if(!isset($_SESSION[ 'fenetre_modale_expeditions' ]) OR $_SESSION[ 'fenetre_modale_expeditions' ] == 0)//N'est ouverte qu'à la connexion
	{	
		$_SESSION[ 'fenetre_modale_expeditions' ] = 1; //Fenetre ouverte
		//JQuery
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
					if($nb_expeditions_en_attente == 1)
						echo '<h2 class="centrer" id="h2_fenetreModale">Vous avez 1 demande d\'exp&eacute;dition en attente</h2>';
					else echo '<h2 class="centrer" id="h2_fenetreModale">Vous avez '.$nb_expeditions_en_attente.' demandes d\'exp&eacute;dition en attente</h2>';
					
					echo '<div id="listeDesExpeditionsEnAttente">';
					echo '<table class="centrerBloc">';
					foreach($tab_expeditions as $expedition)
					{
						echo '<tr><td> <a href="expeditions.php?deexp='.$expedition[ 'id_expedition' ].'#DEMANDE_EXPEDITIONS">n&deg; '.$expedition[ 'id_expedition' ].'</a>  |  De <strong>'.$expedition[ 'utilisateur' ].'</strong>, destination '.$expedition[ 'ville' ].' : <em>'.string_extract($expedition[ 'designation' ],50).'</em>.</td></tr>';//expeditions.php?sid='.$sid.'&deexp
					}
					echo '</table>';
					echo '</div>';
			echo '</div>';	
	}
}
/////////////////////////// J1 - Affichage Recherche Expeditions //////////////////////////
echo    '<table id="lien_recherche_expeditions"><tr><td>';
	$val_r_gui=1;
	if ($_SESSION[ 'r_gui' ]==1) $val_r_gui=0; else $val_r_gui=1;
	if ($val_r_gui==1) echo '<a href="'.$_SERVER[ 'PHP_SELF' ]. '?r_gui='.$val_r_gui.'"><< <img src="images/loupe.png" height=17 id="loupe_recherche"/> Cliquez pour afficher la recherche >></a>';//$_SERVER[ 'PHP_SELF' ]. '?sid=' . $sid.'&r_gui
	else echo '<a href="'.$_SERVER[ 'PHP_SELF' ]. '?r_gui='.$val_r_gui.'"><< Cliquez pour masquer la recherche >></a>';//$_SERVER[ 'PHP_SELF' ]. '?sid=' . $sid.
	echo '</td></tr></table>';
	
	if ($_SESSION[ 'r_gui' ]==1)
	{
		echo '<div id="recherche_expeditions">';
		echo '<table>';
		echo '<caption>Recherche</caption>';
	
		echo '<tr><td><label for="rech_expedition_identifiant">Identifiant</label></td>';
		echo '<td><INPUT size=20 TYPE=text NAME="id_deexp" id="rech_expedition_identifiant" value="';
		if(isset($_SESSION[ 'rech_expedition' ][ 'id_deexp' ]))
			echo $_SESSION[ 'rech_expedition' ][ 'id_deexp' ];
		echo '" /></td></tr>';

		echo '<tr><td><label for="rech_expedition_nom">Nom</label></td>';
		echo '<td><INPUT size=20 TYPE=text NAME="rech_expedition[nom]" id="rech_expedition_nom" value="';
		if(isset($_SESSION[ 'rech_expedition' ][ 'nom' ]))
			echo $_SESSION[ 'rech_expedition' ][ 'nom' ];
		echo '" /></td></tr>';
		echo '<tr><td><label for="rech_expedition_prenom">Pr&eacute;nom</label></td>';
		echo '<td><INPUT size=20 TYPE=text NAME="rech_expedition[prenom]" id="rech_expedition_prenom" value="';
		if(isset($_SESSION[ 'rech_expedition' ][ 'prenom' ]))
			echo $_SESSION[ 'rech_expedition' ][ 'prenom' ];
		echo '" /></td></tr>';
	
		echo '<tr><td colspan=2 class="centrer">';
		//Bouton pour voir la liste des expéditions en attente de validation
		if(in_array($_SESSION[ 'connection' ][ 'utilisateur' ],$gestionnaires_expeditions) && $nb_expeditions_en_attente > 0)
			echo '<input type=button value="Exp&eacute;ditions en attente" class="popup-light"/>';

		echo '<input type="submit" name="rechercher" value="Rechercher" /><input type="submit" name="reinitialiser" value=" R&eacute;initialiser " /></td></tr>';

		if(isset($message_recherche))
			echo '<tr><td colspan=2 class="centrer">'.$message_recherche.'</td></tr>';
		echo '</table>';
	
	

		/////////////////////////// J2 - Affichage des resultats de recherche evoluee ///////////
		if (isset($_SESSION['rech_expedition']['resultats']) && $_SESSION['rech_expedition']['resultats']>0) //si resultat de recherche evoluee
		{
			echo '<table id="res_rech_expedition">';
			echo '<caption>R&eacute;sultats de la recherche</caption>';
			echo '<tr class="enTeteTabExpeditions">';
			echo '<td>Identifiant</td>';
			echo '<td>Nom</td>';
			echo '<td>Pr&eacute;nom</td>';
			echo '<td>Groupe imput&eacute;</td>';
			echo '<td>D&eacute;signation</td>';
			echo '<td>Etat</td>';
			echo '</tr>';
			$i=1;
	
			$page=floor($_SESSION['rech_expedition']['limitebasse']/$_SESSION['rech_expedition']['nb_par_page'])+1;
			$nb_page = ceil($_SESSION['rech_expedition']['resultats']/$_SESSION['rech_expedition']['nb_par_page']);
			$nb_affich=$_SESSION['rech_expedition']['nb_par_page'];
			if ($page==$nb_page && $_SESSION['rech_expedition']['resultats'] <$_SESSION['rech_expedition']['nb_par_page']*$nb_page)
			$nb_affich=$_SESSION['rech_expedition']['resultats']%$_SESSION['rech_expedition']['nb_par_page'];
	
			for ($i=1 ; $i<=$nb_affich ; $i++)
			{
				if ($i%2==0) echo '<tr class="ligne_claire">';
					else echo '<tr class="ligne_foncee">';
	
				echo '<td><a href="'.$_SERVER[ 'PHP_SELF' ]. '?r_gui=0&deexp='.$_SESSION['rech_expedition'][$i]['id_expedition'].'#DEMANDE_EXPEDITIONS">'.$_SESSION['rech_expedition'][$i]['id_expedition'].'</a></td>';//$_SERVER[ 'PHP_SELF' ]. '?sid=' . $sid.'&r_gui
				echo '<td>'.$_SESSION['rech_expedition'][$i]['nom'].'</td>';
				echo '<td>'.$_SESSION['rech_expedition'][$i]['prenom'].'</td>';
				echo '<td>'.$_SESSION['rech_expedition'][$i]['groupe_impute'].'</td>';
				echo '<td class="alignGauche" title="'.$_SESSION['rech_expedition'][$i]['designation'].'">'.string_extract($_SESSION['rech_expedition'][$i]['designation'],50).'</td>';
				$bcol='';
				if ($_SESSION['rech_expedition'][$i]['etat']==-1) $bcol='bgcolor="#DD0000"';
				else if ($_SESSION['rech_expedition'][$i]['etat']==0) 
				{
					$bcol='bgcolor="#478AFF"';
				}
				else if ($_SESSION['rech_expedition'][$i]['etat']==1) $bcol='bgcolor="#00AA00"';
				echo '<td '.$bcol.'>'.$_SESSION['rech_expedition'][$i]['etat'].'</td>';
				echo '</tr>';
			}
			echo '</table>';
			
			echo '<table id="pagination">';
			if ($page > 1) 
				echo '<td id="precedent"><input type="submit" name="rechercher_prec" value=" Page pr&eacute;c&eacute;dente " /></td>';
			else echo '<td id="precedent"></td>';
			echo '<td id="page">page '.$page.'/'.$nb_page.'</td>';
			if ($page < $nb_page) 
				echo '<td id="suivant"><input type="submit" name="rechercher_suiv" value=" Page suivante " /></td>';
			else echo '<td id="suivant"></td>';
			echo '</table>';
			echo '</td></tr>';
			echo '</table>';
		}
		echo '</div>';
	}

/////////////////////////// J4 - Partie demande d'expedition //////////////////////////
	if(!empty($_SESSION[ 'expedition' ][ 'id_expedition' ]))
		echo '<table id="DEMANDE_EXPEDITIONS"><tr><td>Demande n&deg;'.$_SESSION[ 'expedition' ][ 'id_expedition' ].'</td></tr></table>';	
	else echo '<table id="DEMANDE_EXPEDITIONS"><tr><td>Demande</td></tr></table>';	
	///////////////////////////Bandeau correspondant à l'état de l'expédition//////////////////
	if($_SESSION[ 'expedition' ][ 'etat' ] == 2)//Nouvelle demande
		echo '<img src="images/bandeau_nouvelle_demande.png" id="bandeau_etat" alt="Nouvelle demande" />';
	elseif($_SESSION[ 'expedition' ][ 'etat' ] == -1)//Annulée
		echo '<img src="images/bandeau_-1.png" id="bandeau_etat" alt="Demande annul&eacute;e" />';
	elseif($_SESSION[ 'expedition' ][ 'etat' ] == 0)//En attente
		echo '<img src="images/bandeau_0.png" id="bandeau_etat" alt="Demande en attente" />';
	elseif($_SESSION[ 'expedition' ][ 'etat' ] == 1)//Validée
		echo '<img src="images/bandeau_1.png" id="bandeau_etat" alt="Demande valid&eacute;e" />';
	/////////////////////////// Partie concernant le demandeur //////////////////////////
	echo '<p class="centrer gras rouge correctionDecalage">* Champs obligatoires</p>';
	echo '<fieldset id="demandeur_expeditions" class="correctionDecalage"><legend>Demandeur</legend>';
	echo '<table>';
	echo '<tr><td><label for="expedition_nom">Nom <span class="gras rouge">*</span></label></td>';
	echo '<td><INPUT size=20 tabindex="30" TYPE=text NAME="expedition[nom]" id="expedition_nom" value="'.$_SESSION[ 'expedition' ][ 'nom' ].'" readonly ></td></tr>';
	echo '<tr><td><label for="expedition_prenom">Pr&eacute;nom <span class="gras rouge">*</span></label></td>';
	echo '<td><INPUT size=20 tabindex="32" TYPE=text NAME="expedition[prenom]" id="expedition_prenom" value="'.$_SESSION[ 'expedition' ][ 'prenom' ].'" readonly ></td></tr>';
	echo '<tr><td><label for="expedition_groupe_impute">Groupe imput&eacute; <span class="gras rouge">*</span></label></td>';
	echo '<td><select NAME="expedition[groupe_impute]" id="expedition_groupe_impute" required>';
	for ($i=0;$i<sizeof($_SESSION['correspondance']['groupe']);$i++)
	{
		if ($_SESSION['expedition']['groupe_impute']==$_SESSION['correspondance']['groupe'][$i])
		{	
			echo '<option value="'.$_SESSION['correspondance']['groupe'][$i].'" selected>'.$_SESSION['correspondance']['groupe'][$i].'</option>';
		}
		else echo '<option value="'.$_SESSION['correspondance']['groupe'][$i].'">'.$_SESSION['correspondance']['groupe'][$i].'</option>';
	}

	echo '<tr><td><label for="expedition_lieu">Lieu d\'enl&egrave;vement du colis <span class="gras rouge">*</span></label></td>';

	echo '<td><INPUT size=20 tabindex="34" TYPE=text NAME="expedition[lieu]" id="expedition_lieu" value="'.$_SESSION[ 'expedition' ][ 'lieu' ].'"  />';
	echo '</td></tr></table></fieldset>';

	/////////////////////////// Partie concernant le destinataire //////////////////////////
	echo '<fieldset id="destinataire_expeditions"><legend>Destinataire</legend>';
	echo '<table>';
	echo '<tr><td><label for="expedition_adresse">Adresse <span class="gras rouge">*</span></label></td>';
	echo '<td colspan=3 ><INPUT style="width:100%" tabindex="30" TYPE=text NAME="expedition[adresse]" id="expedition_adresse" value="'.$_SESSION[ 'expedition' ][ 'adresse' ].'" /></td></tr>';
	echo '<tr><td><label for="expedition_codepostal">Code postal <span class="gras rouge">*</span></label></td>';
	echo '<td><INPUT size=8 tabindex="32" TYPE=number step=10 NAME="expedition[codepostal]" id="expedition_codepostal" value="'.$_SESSION[ 'expedition' ][ 'codepostal' ].'" class="alignDroite" /></td>';
	echo '<td><label for="expedition_ville">Ville <span class="gras rouge">*</span></label></td>';
	echo '<td><INPUT class="widthMax" tabindex="34" TYPE=text NAME="expedition[ville]" id="expedition_ville" value="'.$_SESSION[ 'expedition' ][ 'ville' ].'" />';
	echo '</td></tr>';

	echo '<tr><td><label for="expedition_tel" title="Exemple international : 0033473123456 &bull; Exemple France : 0473123456">T&eacute;l&eacute;phone <span class="gras rouge">*</span></label></td>';
	echo '<td><INPUT size=20 title="Exemple international : 0033473123456 &bull; Exemple France : 0473123456" tabindex="34" TYPE=number NAME="expedition[telephone]" id="expedition_tel" value="'.$_SESSION[ 'expedition' ][ 'telephone' ].'" />';
	echo '</td>';

	echo '<td><label for="expedition_email">Email <span class="gras rouge">*</span></label></td>';
	echo '<td><INPUT class="widthMax" tabindex="34" TYPE=text NAME="expedition[email]" id="expedition_email" value="'.$_SESSION[ 'expedition' ][ 'email' ].'" />';
	echo '</td></tr></table></fieldset>';

	/////////////////////////// Partie concernant le colis //////////////////////////
	echo '<fieldset id="colis_expeditions"><legend>Colis</legend>';
	echo '<table>';
	echo '<tr><td><label for="expedition_poids">Poids (kg) <span class="gras rouge">*</span></label></td>';
	echo '<td><INPUT size=20 tabindex="30" TYPE=text NAME="expedition[poids]" id="expedition_poids" value="'.$_SESSION[ 'expedition' ][ 'poids' ].'" /></td></tr>';

	echo '<tr><td><label for="expedition_dimensions" title="Longueur*largeur*hauteur en centim&egrave;tres">Dimensions (L*l*h en cm) <span class="gras rouge">*</span></label></td>';
	echo '<td><INPUT size=20 title="Longueur*largeur*hauteur en centim&egrave;tres" tabindex="32" TYPE=text NAME="expedition[dimensions]" id="expedition_dimensions" value="'.$_SESSION[ 'expedition' ][ 'dimensions' ].'" placeholder="Longueur*largeur*hauteur"/></td></tr>';

	echo '<tr><td><label for="expedition_valeur" title="Facultatif">Valeur du bien (en &euro;)</label></td>';
	echo '<td><INPUT size=20 tabindex="34" TYPE=text NAME="expedition[valeur]" id="expedition_valeur" value="'.$_SESSION[ 'expedition' ][ 'valeur' ].'" placeholder="Facultatif"/>';
	echo '</td></tr>';

	echo '<tr><td colspan=2></td></tr><tr><td><label for="expedition_designation">D&eacute;signation du mat&eacute;riel <span class="gras rouge">*</span></label></td><td></td></tr>';
	echo '<tr><td colspan=2>
<textarea NAME="expedition[designation]" id="expedition_designation" cols=52 rows=6 >'
.$_SESSION[ 'expedition' ][ 'designation' ].'
</textarea>';
	echo '</td></tr></table></fieldset>';

	//------ Commentaire -------
	echo '<fieldset id="commentaire_expeditions"><legend>Commentaire sur la demande d\'exp&eacute;dition</legend><table>';
	echo '<tr><td>
<textarea NAME="expedition[commentaire]" id="expedition_commentaire" placeholder="Ajoutez un commentaire &agrave; votre demande d\'exp&eacute;dition">'
.$_SESSION[ 'expedition' ][ 'commentaire' ].'
</textarea>';
	echo '</td></tr></table></fieldset>';

	//--------------- Partie réservée aux gestionnaires pour validation ---------------
	if(($_SESSION[ 'expedition' ][ 'etat' ] == 0 && in_array($_SESSION[ 'connection' ][ 'utilisateur' ],$gestionnaires_expeditions)) OR $_SESSION[ 'expedition' ][ 'etat' ] == 1)
	{
		echo '<fieldset id="gestionnaires_expeditions"><legend>Suivi du colis (remplie par le gestionnaire)</legend>';
		echo '<table>';
		
		if($_SESSION[ 'expedition' ][ 'etat' ] == 1)
		{
			echo '<tr><td>Suivre le colis</td><td><input type="button" value="Go!" onclick="window.open(\''.$_SESSION[ 'expedition' ][ 'lien_tracker' ].'\')"/></td></tr>';
		}
		else 
		{
			echo '<tr><td><label for="expedition_lien_tracker">Lien du tracker <span class="gras rouge">*</span></label></td>';
			echo '<td><INPUT size=31 tabindex="30" TYPE=url NAME="expedition[lien_tracker]" id="expedition_lien_tracker" value="'.$_SESSION[ 'expedition' ][ 'lien_tracker' ].'"  /></td></tr>';
		}

		echo '<tr><td><label for="expedition_numero_tracking" >Num&eacute;ro de tracking <span class="gras rouge">*</span></label></td>';
		echo '<td><INPUT size=31 tabindex="32" TYPE=text NAME="expedition[num_tracking]" id="expedition_numero_tracking" value="'.$_SESSION[ 'expedition' ][ 'num_tracking' ].'"  class="alignDroite"/></td></tr>';

		if($_SESSION[ 'expedition' ][ 'etat' ] == 1)
		{
			echo '</table>';
			if($_SESSION[ 'expedition' ][ 'piece_jointe' ] == '')
				echo '<br/><p class="rouge gras centrer">Aucune pi&egrave;ce jointe</p>';
			else//il y a une piece jointe
			{
				$extention_piecejointe=strtolower(substr(strrchr($_SESSION[ 'expedition' ][ 'piece_jointe' ],'.'),1));
				if(in_array($extention_piecejointe,array('jpg','jpeg','png')))
					echo '<br/><p class="gras centrer">Pi&egrave;ce jointe :<br/><br/><a href="expedition_attachments/'.$_SESSION[ 'expedition' ][ 'piece_jointe' ].'"><img src="expedition_attachments/'.$_SESSION[ 'expedition' ][ 'piece_jointe' ].'" id="img_piece_jointe"/></a><br/>';
				elseif($extention_piecejointe == 'pdf')
					echo '<p class="centrer"><img src="images/trombone.png" height=23 id="trombone"/><a href="expedition_attachments/'.$_SESSION[ 'expedition' ][ 'piece_jointe' ].'">Voir la pi&egrave;ce jointe</a><br/></p>';
			}
		}
		else
		{
			echo '<tr><td><label for="expedition_piece_jointe" title="Facultatif">Pi&egrave;ce jointe</label></td>';
			echo '<td><INPUT size=20 tabindex="34" TYPE=file NAME="piece_jointe" id="expedition_piece_jointe" placeholder="Facultative"/></td></tr></table>';
		}
		echo '</fieldset>';
	}


	//--------------- Boutons ---------------
	//Nouvelle demande
	if(!isset($_SESSION[ 'id_deexp' ]) OR $_SESSION[ 'id_deexp' ] == '')
	{	
		echo '<table class="centrerBloc"><tr><td><input type="submit" class="bouton_expedition" name="expedition[envoyer]" id="expedition_envoyer" value=" Envoyer une demande d\'exp&eacute;dition " onClick="return confirm(\'Etes-vous s&ucirc;r(e) de vouloir envoyer cette demande?\');"></td>';
		echo '<td><input type="submit" class="bouton_expedition" name="expedition[saisie]" id="expedition_annuler" value=" Annuler saisie " >';
		echo '</td></tr></table>';	
	}
	//Le demandeur peut annuler sa demande si etat=0 ou editer une nouvelle demande
	else if(!empty($_SESSION[ 'id_deexp' ]) AND $_SESSION[ 'expedition' ][ 'utilisateur' ] == $_SESSION[ 'connection' ][ 'utilisateur' ] AND $_SESSION[ 'expedition' ][ 'etat' ] == 0 AND !in_array($_SESSION[ 'connection' ][ 'utilisateur' ],$gestionnaires_expeditions))
	{	
		echo '<table class="centrerBloc"><tr><td><input type="submit" class="bouton_expedition" name="expedition[annuler]" id="expedition_annuler" value=" Annuler ma demande d\'exp&eacute;dition " onClick="return confirm(\'Etes-vous s&ucirc;r(e) de vouloir annuler votre demande?\');"></td><td><input type="submit" class="bouton_expedition" name="expedition[nouvelle]" value=" Editer une nouvelle exp&eacute;dition " ></td></tr></table>';
	}
	//Le gestionnnaire peut valider, annuler une demande ou editer une nouvelle demande
	else if(!empty($_SESSION[ 'id_deexp' ]) AND in_array($_SESSION[ 'connection' ][ 'utilisateur' ],$gestionnaires_expeditions) AND $_SESSION[ 'expedition' ][ 'etat' ] == 0)
	{	
		echo '<table class="centrerBloc"><tr><td><input type="submit" class="bouton_expedition" name="expedition[valider]" id="expedition_valider" value=" Valider la demande d\'exp&eacute;dition " onClick="return confirm(\'Etes-vous s&ucirc;r(e) de vouloir valider cette demande?\');"></td><td><input type="submit" class="bouton_expedition" name="expedition[annuler]" id="expedition_annuler" value=" Annuler la demande d\'exp&eacute;dition " onClick="return confirm(\'Etes-vous s&ucirc;r(e) de vouloir annuler cette demande?\');"></td><td><input type="submit" class="bouton_expedition" name="expedition[nouvelle]" value=" Editer une nouvelle exp&eacute;dition " ></td></tr></table>';
	}
	else//Nouvelle demande d'expédition
	{	
		echo '<table class="centrerBloc"><tr><td><input type="submit" class="bouton_expedition" name="expedition[nouvelle]" value=" Editer une nouvelle exp&eacute;dition " ></td></tr></table>';
	}


	if(isset($message_demande))
		echo '<table class="centrerBloc centrer"><tr><td>'.$message_demande.'</td></tr></table>';



//////////////PIED DE PAGE/////////////////
include "pied_page.php";
?>
