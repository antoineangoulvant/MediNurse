<?php

//Affichage des erreurs en mode test
include 'config.php';
if($mode_test == 1 && !isset($_GET[ 'disconnect' ]))//Probleme de deconnexion avec CAS sinon
{
	ini_set('display_errors', 1);
	error_reporting(E_ALL);
}

/**
* Reseau social pour entreprise (RSE)
*
* Date de création : 9 Aout 2012<br>
* Date de dernière modification : 25 septembre 2015
* @version 1.2.2
* @author Cedric Gagnevin <cedric.gagnevin@laposte.net>, Emmanuel Delage
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
//    | -E- Maj CONNEXION_COMMUNITY
//    | -F-  Gestion des variables d'expedition
//    | -G-  Recherche d'expeditions
//    | -H-  Ajout d'une demande d'expedition
//    | -I-  Nouvelle demande d'expedition
//    | -J-  Valider une demande d'expedition
//    | -K-  Annuler une demande d'expedition
//    | -L-  Reinitialiser la saisie
//    | -M-  Choix du module
//    | -N-  HTML
//    | -N1- Profils
//    | -N2- Catégories
//    | -N3- Actualités
//    | -M- Maj CONNEXION_COMMUNITY

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
***********************  -B- Fonctions *******************************************
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
* Initialisation des variables de la demande de publication
*
*/
function init_publication()
{
	$_SESSION[ 'community' ][ 'titre_pub' ] = '';
	$_SESSION[ 'community' ][ 'categorie_pub' ] = '';
	$_SESSION[ 'community' ][ 'contenu_pub' ] = '';
	$_SESSION[ 'community' ][ 'video' ] = '';
	$_SESSION[ 'community' ][ 'fichier' ] = '';
}

/**
* Donne le timestamp de la derniere connexion de l'utilisateur à community
*
*/
function get_timestamp_community($user)
{
	include ("config.php");
	include $chemin_connection;
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_error());
	mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

	$query='SELECT CONNEXION_COMMUNITY FROM T_UTILISATEUR WHERE UTILISATEUR="'.$user.'"';
	$result=mysqli_query($link,$query);
	$connexion = mysqli_fetch_array($result, MYSQL_BOTH);
	$timestamp=$connexion[0];
	mysqli_free_result($result);
	mysqli_close($link);
	return $timestamp;
}

/**
* Obtient N publications les plus recentes
*
*/
function get_publications($limit_basse,$nombre_pub)
{
	include ("config.php");
	include $chemin_connection;
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_error());
	mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

	$query='SELECT * FROM T_PUBLICATION, T_UTILISATEUR WHERE T_PUBLICATION.UTILISATEUR=T_UTILISATEUR.UTILISATEUR ORDER BY DATE_PUBLICATION DESC LIMIT '.$limit_basse.','.$nombre_pub;
	$result=mysqli_query($link,$query);
	$publications=array();
	while($pub = mysqli_fetch_array($result, MYSQL_BOTH))
	{
		$publication=array();
		$publication[ 'ID_PUBLICATION' ]=$pub[ 'ID_PUBLICATION' ];
		$publication[ 'TITRE' ]=$pub[ 'TITRE' ];
		$publication[ 'UTILISATEUR' ]=$pub[ 'UTILISATEUR' ];
		$publication[ 'NOM' ]=$pub[ 'NOM' ];
		$publication[ 'PRENOM' ]=$pub[ 'PRENOM' ];
		$publication[ 'FICHIER' ]=$pub[ 'FICHIER' ];
		$publication[ 'CONTENU' ]=$pub[ 'CONTENU' ];
		$publication[ 'CATEGORIE' ]=$pub[ 'CATEGORIE' ];
		$publication[ 'PLUS' ]=$pub[ 'PLUS' ];
		$publication[ 'MOINS' ]=$pub[ 'MOINS' ];
		$publication[ 'DATE_PUBLICATION' ]=$pub[ 'DATE_PUBLICATION' ];
		array_push($publications,$publication);
	}	
	mysqli_free_result($result);
	mysqli_close($link);
	return $publications;
}


/**
* Obtient N publications les plus recentes par catégorie
*
*/
function get_publications_by_categ($limit_basse,$nombre_pub,$categ)
{
	include ("config.php");
	include $chemin_connection;
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_error());
	mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

	$query='SELECT * FROM T_PUBLICATION,T_UTILISATEUR WHERE T_PUBLICATION.UTILISATEUR=T_UTILISATEUR.UTILISATEUR AND CATEGORIE="'.$categ.'" ORDER BY DATE_PUBLICATION DESC LIMIT '.$limit_basse.','.$nombre_pub;
	$result=mysqli_query($link,$query);
	$publications=array();
	while($pub = mysqli_fetch_array($result, MYSQL_BOTH))
	{
		$publication=array();
		$publication[ 'ID_PUBLICATION' ]=$pub[ 'ID_PUBLICATION' ];
		$publication[ 'TITRE' ]=$pub[ 'TITRE' ];
		$publication[ 'UTILISATEUR' ]=$pub[ 'UTILISATEUR' ];
		$publication[ 'NOM' ]=$pub[ 'NOM' ];
		$publication[ 'PRENOM' ]=$pub[ 'PRENOM' ];
		$publication[ 'FICHIER' ]=$pub[ 'FICHIER' ];
		$publication[ 'CONTENU' ]=$pub[ 'CONTENU' ];
		$publication[ 'CATEGORIE' ]=$pub[ 'CATEGORIE' ];
		$publication[ 'PLUS' ]=$pub[ 'PLUS' ];
		$publication[ 'MOINS' ]=$pub[ 'MOINS' ];
		$publication[ 'DATE_PUBLICATION' ]=$pub[ 'DATE_PUBLICATION' ];
		array_push($publications,$publication);
	}	
	mysqli_free_result($result);
	mysqli_close($link);
	return $publications;
}


/**
* Obtient le nombre total de publications dans la base
*
*/
function nb_total_publications()
{
	include ("config.php");
	include $chemin_connection;
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_error());
	mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

	$query='SELECT COUNT(*) FROM T_PUBLICATION';
	$result=mysqli_query($link,$query);
	$nb = mysqli_fetch_array($result, MYSQL_BOTH);
	return $nb[0];
}

/**
* Obtient le nombre total de publications dans la base par categorie
*
*/
function nb_total_publications_by_categ($categ)
{
	include ("config.php");
	include $chemin_connection;
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_error());
	mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

	$query='SELECT COUNT(*) FROM T_PUBLICATION WHERE CATEGORIE="'.$categ.'"';
	$result=mysqli_query($link,$query);
	$nb = mysqli_fetch_array($result, MYSQL_BOTH);
	return $nb[0];
}

/**
* Retourne une date mise en forme  partir d'un timestamp
*
*/
function mise_en_forme_date($timestamp)
{	
	if(date("j-m-Y",$timestamp) == date("j-m-Y"))
		return 'Aujourd\'hui &agrave; '.date("G\hi",$timestamp);
	else return 'Le '.date("d/m/Y",$timestamp).' &agrave; '.date("H\hi",$timestamp);
}


/**
* Retourne le nombre de "+" d'une publication
*
*/
function get_nb_plus($id_publication)
{
	include ("config.php");
	include $chemin_connection;
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_error());
	mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

	$query='SELECT PLUS FROM T_PUBLICATION WHERE ID_PUBLICATION="'.$id_publication.'"';
	$result=mysqli_query($link,$query);

	$plus = mysqli_fetch_array($result, MYSQL_BOTH);
	mysqli_free_result($result);
	mysqli_close($link);
	
	if($plus[0] == "")	return 0;
	else return count(explode("#",$plus[0]));
	
}

/**
* Retourne le nombre de "-" d'une publication
*
*/
function get_nb_moins($id_publication)
{
	include ("config.php");
	include $chemin_connection;
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_error());
	mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

	$query='SELECT MOINS FROM T_PUBLICATION WHERE ID_PUBLICATION="'.$id_publication.'"';
	$result=mysqli_query($link,$query);

	$plus = mysqli_fetch_array($result, MYSQL_BOTH);
	mysqli_free_result($result);
	mysqli_close($link);
	
	if($plus[0] == "")	return 0;
	else return count(explode("#",$plus[0]));
	
}

/**
* Retourne un booleen indiquant si l'utilisateur a donné son avis sur une publication(+/-)
*
*/
function avis_donne($login_user,$id_publication)
{
	include ("config.php");
	include $chemin_connection;
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_error());
	mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

	$query='SELECT PLUS,MOINS FROM T_PUBLICATION WHERE ID_PUBLICATION="'.$id_publication.'"';
	$result=mysqli_query($link,$query);

	$donnees = mysqli_fetch_array($result, MYSQL_BOTH);
	mysqli_free_result($result);
	mysqli_close($link);
	
	$tab_user_plus = explode("#",$donnees[ 'PLUS' ]);
	$tab_user_moins = explode("#",$donnees[ 'MOINS' ]);	
	if(in_array($login_user,$tab_user_plus) OR in_array($login_user,$tab_user_moins))
		return 1;
	else return 0;
}

/**
* Retourne img         : Image
* 	   youtube     : Video issue de YouTube
*          dailymotion : Video issue de Dailymotion
*          inconnu     : Lien internet ne venant pas de youtube ou dailymotion
*/
function get_file_type($file)
{
	if(preg_match('/^(http|www)/',$file))//Lien internet donc video
	{
		if(preg_match('/youtube/',$file))//Video Youtube
			return 'youtube';
		elseif(preg_match('/dailymotion/',$file))//Video Dailymotion
			return 'dailymotion';
		else return 'inconnu';
	}
	else //Image
	{
		return 'image';
	}
}

/**
* Insere le player adéquat pour pouvoir lire la video
*
*/
function integrer_video($url_video)
{
	if(get_file_type($url_video) == 'youtube')//Insertion du player youtube
	{
		$pos_debut_id = strrpos($url_video,'?v=')+3; //Position du début de l'id de la video
		$pos_fin_id = strpos($url_video,'&',$pos_debut_id);//Position de fin de l'id
		if(empty($pos_fin_id)) $pos_fin_id=strlen($url_video);

		$length = $pos_fin_id-$pos_debut_id;//Longueur de l'id
		$id_video = substr($url_video,$pos_debut_id,$length);//ID

		return '<iframe width="560" height="315" src="http://www.youtube.com/embed/'.$id_video.'" frameborder="0" allowfullscreen></iframe>';
	}
	elseif(get_file_type($url_video) == 'dailymotion')//Insertion du player daylimotion
	{
		$pos_debut_id = strrpos($url_video,'video/')+6; //Position du début de l'id de la video
		$pos_fin_id = strpos($url_video,'_',$pos_debut_id);//Position de fin de l'id
		if(empty($pos_fin_id)) $pos_fin_id=strlen($url_video);
		$length = $pos_fin_id-$pos_debut_id;//Longueur de l'id
		$id_video = substr($url_video,$pos_debut_id,$length);//ID

		return '<object width="480" height="270"><param name="movie" value="http://www.dailymotion.com/swf/video/'.$id_video.'"></param><param name="allowFullScreen" value="true"></param><embed type="application/x-shockwave-flash" src="http://www.dailymotion.com/swf/video/'.$id_video.'" width="560" height="315" wmode="transparent" allowfullscreen="true"></embed></object></i>';
	}
}

/**
* Renvoie un message sur l'avis donné par l'utilisateur.
* 		+ : Vous recommandez 
*		- : Vous déconseillez
*
*/
function get_avis_donne($login_user,$id_publication)
{
	if(avis_donne($login_user,$id_publication))
	{
		include ("config.php");
		include $chemin_connection;
		$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_error());
		mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');
	
		$query='SELECT PLUS,MOINS FROM T_PUBLICATION WHERE ID_PUBLICATION="'.$id_publication.'"';
		$result=mysqli_query($link,$query);
	
		$donnees = mysqli_fetch_array($result, MYSQL_BOTH);
		mysqli_free_result($result);
		mysqli_close($link);
		
		$tab_user_plus = explode("#",$donnees[ 'PLUS' ]);
		$tab_user_moins = explode("#",$donnees[ 'MOINS' ]);	
		if(in_array($login_user,$tab_user_plus))
		{	
			return '<img src="images/recommandation.png" alt="Vous recommandez" class="bulle_avis" />';
		}
		else if(in_array($login_user,$tab_user_moins))
		{	
			return '<img src="images/deconseil.png" alt="Vous d&eacute;conseillez" class="bulle_avis" />';
		}
		else return -1;
	}
	else return -1;
}

/**
* Renvoie un tableau contenant les profils du groupe en question
*
*/
function get_profils_by_group($keywords, $groupe)
{
	include ("config.php");
	include $chemin_connection;
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_error());
	mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

	if($groupe == $organisme)
		$where='';
	else  $where = 'GROUPE = "'.$groupe.'" ';

	if(!empty($keywords))
	{
		if($where != '')
		{
			$where .= 'AND (';
			$parenthese=1;
		}

		//Split de la chaine de mots clés
		$tab_keywords=explode(" ",strtoupper(trim(mysql_real_escape_string(htmlentities($keywords)))));

		foreach($tab_keywords as $keyword)
		{
			$where.='UTILISATEUR LIKE "%'.$keyword.'%" 
				OR NOM LIKE "%'.$keyword.'%" 
				OR PRENOM LIKE "%'.$keyword.'%" OR ';
		}
	}

	if($where != '')
		$where = 'WHERE '.$where;
	if(substr($where,-3,3) == 'OR ')
		$where = substr($where,0,strlen($where)-3);
	if(isset($parenthese) && $parenthese==1)
		$where .= ')';

	$query='SELECT DISTINCT * FROM T_UTILISATEUR '.$where.' ORDER BY NOM ASC';
	$result=mysqli_query($link,$query);
	
	//echo $query;		

	//Stockage dans un tableau associatif
	$profils= array();
	while($donnee = mysqli_fetch_array($result, MYSQL_BOTH))	
	{	
		$profil=array();
		$profil[ 'UTILISATEUR' ] = $donnee[ 'UTILISATEUR' ];
		$profil[ 'NOM' ] = $donnee[ 'NOM' ];
		$profil[ 'PRENOM' ] = $donnee[ 'PRENOM' ];
		$profil[ 'GROUPE' ] = $donnee[ 'GROUPE' ];
		$profil[ 'TELEPHONE' ] = $donnee[ 'TELEPHONE' ];
		$profil[ 'EMAIL' ] = $donnee[ 'MEL' ];
		$profil[ 'BUREAU' ] = $donnee[ 'BUREAU' ];
		$profil[ 'PHOTO' ] = $donnee[ 'PHOTO' ];	
		array_push($profils,$profil);
	}

	mysqli_free_result($result);
	mysqli_close($link);
	
	return $profils;
}

/**
* Renvoie un tableau le profil d'un utilisateur
*
*/
function get_profil_login($login)
{
	include ("config.php");
	include $chemin_connection;
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_error());
	mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

	$login=mysqli_real_escape_string($link,$login);

	$query='SELECT * FROM T_UTILISATEUR WHERE UTILISATEUR="'.$login.'"';
	$result=mysqli_query($link,$query);
	
	while($donnee = mysqli_fetch_array($result, MYSQL_BOTH))
	{	
		$profil=array();
		$profil[ 'UTILISATEUR' ] = $donnee[ 'UTILISATEUR' ];
		$profil[ 'NOM' ] = $donnee[ 'NOM' ];
		$profil[ 'PRENOM' ] = $donnee[ 'PRENOM' ];
		$profil[ 'GROUPE' ] = $donnee[ 'GROUPE' ];
		$profil[ 'TELEPHONE' ] = $donnee[ 'TELEPHONE' ];
		$profil[ 'EMAIL' ] = $donnee[ 'MEL' ];
		$profil[ 'BUREAU' ] = $donnee[ 'BUREAU' ];
		$profil[ 'PHOTO' ] = $donnee[ 'PHOTO' ];	
	}
	mysqli_free_result($result);
	mysqli_close($link);
	
	return $profil;
}


/**********************************************************************************
*************** -C- Initialisation generale (configuration et php) ****************
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
**************************  -D- Initialisation Session et variables **************
**********************************************************************************/

// Initialize session ID
//$sid = '';
//if (isset($_REQUEST[ 'sid' ])) $sid = substr(trim(preg_replace('/[^a-f0-9]/', '', $_REQUEST[ 'sid' ])), 0, 13);
//if ($sid == '') $sid = uniqid('');

// Start PHP session
//session_id($sid);
session_name('phpmylab');//conge?
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
********************  -F- Gestion des variables de publication  ******************
**********************************************************************************/
if (isset($_REQUEST[ 'community' ]))  if (is_array($_REQUEST[ 'community' ]))
{
	if (isset($_REQUEST[ 'community' ][ 'titre_pub' ])) $_SESSION[ 'community' ][ 'titre_pub' ] = htmlspecialchars($_REQUEST[ 'community' ][ 'titre_pub' ]);
	if (isset($_REQUEST[ 'community' ][ 'categorie_pub' ])) $_SESSION[ 'community' ][ 'categorie_pub' ] = htmlspecialchars($_REQUEST[ 'community' ][ 'categorie_pub' ]);
	if (isset($_REQUEST[ 'community' ][ 'contenu_pub' ])) $_SESSION[ 'community' ][ 'contenu_pub' ] = trim(htmlspecialchars($_REQUEST[ 'community' ][ 'contenu_pub' ]));
	if (isset($_REQUEST[ 'community' ][ 'video' ])) $_SESSION[ 'community' ][ 'video' ] = htmlspecialchars($_REQUEST[ 'community' ][ 'video' ]);

}



/*********************************************************************************
************** I - Gestion des variables de recherche de profils *****************
**********************************************************************************/

if (isset($_REQUEST[ 'community' ][ 'search_groupe' ])) $_SESSION[ 'community' ][ 'search_groupe' ] = htmlspecialchars($_REQUEST[ 'community' ][ 'search_groupe' ]);
else $_SESSION[ 'community' ][ 'search_groupe' ] = $organisme;

if (isset($_REQUEST[ 'community' ][ 'search_keywords' ])) $_SESSION[ 'community' ][ 'search_keywords' ] = htmlspecialchars(trim($_REQUEST[ 'community' ][ 'search_keywords' ]));
else $_SESSION[ 'community' ][ 'search_keywords' ] = '';

/*********************************************************************************
****************** I - Gestion des variables Community ***************************
**********************************************************************************/

if (isset($_REQUEST[ 'page' ])) $_SESSION[ 'community' ][ 'page' ] = $_REQUEST[ 'page' ];
elseif(!isset($_SESSION[ 'community' ][ 'page' ])) $_SESSION[ 'community' ][ 'page' ] = 'actualites';


/*********************************************************************************
*************  G - Gestion de la pagination pour les actualités ******************
**********************************************************************************/

if(isset($_GET[ 'npage' ]) && is_numeric($_GET[ 'npage' ])) $page_courante = $_GET[ 'npage' ];
else $page_courante=1;

$page_precedente = $page_courante-1;
$page_suivante = $page_courante+1;
$nb_par_page=10;//Constante
$limite_basse=$nb_par_page*($page_courante-1);
if(!empty($_GET[ 'categ' ]))
	$nb_total=nb_total_publications_by_categ($_GET[ 'categ' ]);
else $nb_total=nb_total_publications();
$nb_pages=ceil($nb_total/$nb_par_page);



/*********************************************************************************
***********************  H - Ajout d'une publication *****************************
**********************************************************************************/

if(isset($_REQUEST[ 'community' ][ 'publier' ]))
{
	$annule=0;
	//Controle des champs obligatoires
	if(EMPTY($_SESSION[ 'community' ][ 'titre_pub' ]))
	{
		$message_demande= '<p class="rouge gras">Le titre n\'est pas renseign&eacute;</p>';
		$annule=1;
	}	
	elseif(EMPTY($_SESSION[ 'community' ][ 'categorie_pub' ]))
	{
		$message_demande= '<p class="rouge gras">La cat&eacute;gorie n\'est pas renseign&eacute;e</p>';
		$annule=1;
	}
	elseif(EMPTY($_SESSION[ 'community' ][ 'contenu_pub' ]))
	{
		$message_demande= '<p class="rouge gras">Le contenu n\'est pas renseign&eacute;</p>';
		$annule=1;
	}
	/*elseif(EMPTY($_SESSION[ 'community' ][ 'video' ]))
	{
		$message_demande= '<p class="rouge gras">URL non valide</p>';
		$annule=1;
	}*/

	if(!$annule)
	{
		include("config.php");
		include($chemin_connection);
		// Connecting, selecting database:
		$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
		or die('Could not connect: ' . mysqli_error());
		mysqli_select_db($link,$mysql_base) or die('Could not select database : '. mysqli_error());
	

		//recherche de l'indice a ajouter
		//la numerotation des (ID_) commence a 1
		$indi=1;
		$query = 'SELECT MAX(ID_PUBLICATION) FROM T_PUBLICATION';
		$result = mysqli_query($link,$query) or die('Requete de comptage des demandes pour community: ' . mysqli_error());
		if ($result)
		{
			$line = mysqli_fetch_array($result, MYSQL_NUM);
			$indi=$line[0]+1;
			mysqli_free_result($result);
		}

		if(!empty($_FILES['cmty_image']['name']))
		{
			//Upload de la piece jointe
			$MAX_FILE_SIZE = 3145728;// 3Mo
			//Verif qu'il n'y ait pas d'erreur lors de l'upload
			if($_FILES['cmty_image']['error'] > 0) echo $message_demande='Erreur lors du transfert';	
		
			//Controle sur la taille max
			if($_FILES['cmty_image']['size'] > $MAX_FILE_SIZE) echo $message_demande='Erreur : le fichier est trop gros (>3Mo)';	
		
			//Controle de l'extention
			$extension_upload = strtolower(substr(strrchr($_FILES['cmty_image']['name'],'.'),1));
			if(!in_array($extension_upload,array('jpg','jpeg','png','gif'))) echo $message_demande='Erreur : Extension non autoris&eacute;e. Autoris&eacute;es : jpg,jpeg,png,gif';
		
			//Génération du nom du fichier (chaine aleatoire)
			$nom_fichier = "";
			$chaine = "abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
			srand((double)microtime()*1000000);
			for($i=0; $i<15; $i++) 
				$nom_fichier .= $chaine[rand()%strlen($chaine)];
			$nom_fichier .= '.'.$extension_upload;
				
			$destination_fichier="community_files/".$nom_fichier;
			$transfert_reussi=move_uploaded_file($_FILES['cmty_image']['tmp_name'],$destination_fichier);
			if(!$transfert_reussi) echo $message_demande='Erreur : Echec du transfert de la piece jointe sur le serveur';
			else $_SESSION['community']['fichier']=$nom_fichier;
			
		}
		
		if(!empty($_SESSION[ 'community' ][ 'video' ]))
			$_SESSION['community']['fichier']=$_SESSION[ 'community' ][ 'video' ];

		if(!isset($_SESSION['community']['fichier']))  
			$_SESSION['community']['fichier']='';

		//insertion de la publication dans la base
		$query = 'INSERT INTO T_PUBLICATION(ID_PUBLICATION,UTILISATEUR,TITRE,FICHIER,CONTENU,CATEGORIE,DATE_PUBLICATION) 
		VALUES ('.$indi.',
			"'.mysqli_real_escape_string($link,$_SESSION['connection']['utilisateur']).'",
			"'.mysqli_real_escape_string($link,$_SESSION['community']['titre_pub']).'",
			"'.$_SESSION['community']['fichier'].'",
			"'.mysqli_real_escape_string($link,nl2br($_SESSION['community']['contenu_pub'])).'",
			"'.mysqli_real_escape_string($link,$_SESSION['community']['categorie_pub']).'",
			'.time().'
			)';
		$result = mysqli_query($link,$query) or die('Requete d\'insertion echouee.<br>Query : '.$query.'<br>Erreur :'. mysqli_error());
		mysqli_close($link);
	
		init_publication();//On reinitialise les champs
	}

}

/*********************************************************************************
***********************   J - Valider demande d'expedition ***********************
**********************************************************************************/

/*********************************************************************************
***********************  K - Annuler une demande d'expedition ********************
**********************************************************************************/

/*********************************************************************************
***********************  L - Reinitialiser la saisie *****************************
**********************************************************************************/

/*********************************************************************************
***********************  M - Choix du module *****************************
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
*******************************  -N- HTML ****************************************
**********************************************************************************/

// En tete des modules
include "en_tete.php";
?>
<script src="jquery-ui/js/1.11.2/jquery.elastic.source.js" type="text/javascript"></script>
<script>
	
	$(document).ready(function() {
		//$('#contenu_pub').elastic();
	});

	$(document).ready(function() {
		$('#btn_up').click(function() {
		$('html,body').animate({scrollTop: 0}, 'slow');
		});
		
		$(window).scroll(function(){
		if($(window).scrollTop()<500){
			$('#btn_up').fadeOut();
		}else{
			$('#btn_up').fadeIn();
		}
		});
	});

	function show_add_image()
	{
		if($("#tr_add_image").is(":visible"))
		{
			$("#tr_add_image").css("display","none");
		}
		else
		{
			$("#tr_add_video").css("display","none");
			$("#tr_add_image").css("display","block");
		}
	}

	function show_add_video()
	{
		if($("#tr_add_video").is(":visible"))
		{
			$("#tr_add_video").css("display","none");
		}
		else
		{
			$("#tr_add_image").css("display","none");
			$("#tr_add_video").css("display","block");
		}
	}


	function recommander(btn_plus)
	{
		if(btn_plus.className != 'avis_donne') //Si l'utilisateur peut donner son avis
		{
			var table_parent = btn_plus.parentNode.parentNode.parentNode.parentNode;
			var inputs=table_parent.getElementsByTagName("input");
			var id_publication = inputs[0].value;
			var imgs = table_parent.getElementsByTagName("img");
			for(var i=0; i<imgs.length; i++)
			{
				if(imgs[i].title == 'Deconseiller')
				{
					var img_moins = imgs[i];
					break;
				}
			}

			var spans = table_parent.getElementsByTagName("span");
			for(var i=0; i<spans.length; i++)
			{
				if(spans[i].className == 'nb_plus')
				{
					var nb_plus = spans[i];
					break;
				}
			}			
	
			$.ajax({ // fonction permettant de faire de l'ajax
			type: "POST", // methode de transmission des données au fichier php
			url: "AJAX/ajax_recommander_publication.php", // url du fichier php
			data: "id_publication="+id_publication+"&utilisateur=<?php echo $_SESSION['connection']['utilisateur']; ?>", // données à transmettre
			success: function(msg){ // si l'appel a bien fonctionné
				if(msg != -1)
				{
					nb_plus.innerHTML=msg;
					btn_plus.className = 'avis_donne';
					img_moins.className = 'avis_donne';

					var IMG = document.createElement ("img");
					var CLASS=document.createAttribute("class");
					CLASS.value="bulle_avis";
					IMG.setAttributeNode(CLASS);
					var SRC=document.createAttribute("src");
					SRC.value="images/recommandation.png";
					IMG.setAttributeNode(SRC);
					
					btn_plus.parentNode.appendChild(IMG);
				}
				else alert("Erreur... Veuillez essayer plus tard");	
			}
			});
		}
	}
		
	function deconseiller(btn_moins)
	{
		if(btn_moins.className != 'avis_donne') //Si l'utilisateur peut donner son avis
		{
			var table_parent = btn_moins.parentNode.parentNode.parentNode.parentNode;
			var inputs=table_parent.getElementsByTagName("input");
			var id_publication = inputs[0].value;
			var imgs = table_parent.getElementsByTagName("img");
			for(var i=0; i<imgs.length; i++)
			{
				if(imgs[i].title == 'Recommander')
				{
					var img_plus = imgs[i];
					break;
				}
			}

			var spans = table_parent.getElementsByTagName("span");
			for(var i=0; i<spans.length; i++)
			{
				if(spans[i].className == 'nb_moins')
				{
					var nb_moins = spans[i];
					break;
				}
			}
			

			$.ajax({ // fonction permettant de faire de l'ajax
			type: "POST", // methode de transmission des données au fichier php
			url: "AJAX/ajax_deconseiller_publication.php", // url du fichier php
			data: "id_publication="+id_publication+"&utilisateur=<?php echo $_SESSION['connection']['utilisateur']; ?>", // données à transmettre
			success: function(msg){ // si l'appel a bien fonctionné
				if(msg != -1)
				{
					nb_moins.innerHTML=msg;
					btn_moins.className = 'avis_donne';
					img_plus.className = 'avis_donne';
					
					var IMG = document.createElement ("img");
					var CLASS=document.createAttribute("class");
					CLASS.value="bulle_avis";
					IMG.setAttributeNode(CLASS);
					var SRC=document.createAttribute("src");
					SRC.value="images/deconseil.png";
					IMG.setAttributeNode(SRC);
					
					img_plus.parentNode.appendChild(IMG);
				}
				else alert("Erreur... Veuillez essayer plus tard");	
			}
			});
		}
	}

	function supprimerPublication(id_publication, btn_supprimer)
	{
		if(confirm("Voulez-vous vraiment supprimer cette publication?"))
		{
			$.ajax({ // fonction permettant de faire de l'ajax
			type: "POST", // methode de transmission des données au fichier php
			url: "AJAX/ajax_supprimer_publication.php", // url du fichier php
			data: "id_publication="+id_publication+"&utilisateur=<?php echo $_SESSION['connection']['utilisateur']; ?>", // données à transmettre
			success: function(msg){ // si l'appel a bien fonctionné
				if(msg == 1)
				{
					$('#publication_'+id_publication).children().animate({'backgroundColor':'#FF3F47'},500);
					$('#publication_'+id_publication).slideUp(1500);
				}
				else if(msg == 0)
				{
					//La suppression n'a pas marché (DELETE)
					alert("Erreur lors de la suppression... Veuillez essayer plus tard");
				}
				else alert("Erreur... Veuillez essayer plus tard");	
			}
			});
		}
	}


	<!-- Autocomplete -->
	$(function() {
	
	<?php
		include("config.php");
		include("".$chemin_connection);
		// Connecting, selecting database:
		$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
		or die('Could not connect: ' . mysqli_error());
		mysqli_select_db($link,$mysql_base) or die('Could not select database : '. mysqli_error());
	
		$query = 'SELECT DISTINCT NOM, PRENOM FROM T_UTILISATEUR ORDER BY NOM ASC'; 
		$result=mysqli_query($link,$query) or die ('ERREUR dans requete select nom prenom');
	
		$listeNomPrenom = 'var listeNomPrenom = [';
		
		while($donnee = mysqli_fetch_row($result)) 
		{
			$listeNomPrenom .= '"'.$donnee[0].' '.$donnee[1].'",';
		}

	
		if($listeNomPrenom != 'var listeNomPrenom = [')
			$listeNomPrenom = substr($listeNomPrenom,0,-1).'];';
	
		echo $listeNomPrenom;
		
		mysqli_free_result($result);
		mysqli_close($link);
	?>
	
		$( "#cmty_search_keywords" ).autocomplete({
			source: listeNomPrenom
		});

	});


</script>

<form name="form1" id="form1" method="post" action="<?php echo $_SERVER[ 'PHP_SELF' ]; ?>" enctype="multipart/form-data">

<?php
	//Menu du haut 
	$profil_class = 'cmty_top_menu';
	$categories_class = 'cmty_top_menu';
	$actualites_class = 'cmty_top_menu';

	if(isset($_SESSION[ 'community' ][ 'page' ]))
	{
		if($_SESSION[ 'community' ][ 'page' ]=='profils') $profil_class='cmty_menu_selected';
		else if($_SESSION[ 'community' ][ 'page' ]=='categories') $categories_class='cmty_menu_selected';
		else if($_SESSION[ 'community' ][ 'page' ]=='actualites') $actualites_class='cmty_menu_selected';
	}
	
	echo '<div id="cmty_top_bar">
		<table>
			<tr>
<!--				<td id="cmty_nom_module">Comm\'unity\'</td>--!>
				<td id="cmty_nom_module"></td>
				<td class="'.$actualites_class.'" onclick="window.location.href=\'community.php?page=actualites\'">Actualit&eacute;s</td>
				<td class="'.$profil_class.'" onclick="window.location.href=\'community.php?page=profils\'">Profils</td>
				<td class="'.$categories_class.'" onclick="window.location.href=\'community.php?page=categories\'">Cat&eacute;gories</td>
			</tr>
		</table>
	</div>';//les 3 community.php?sid='.$sid.'&page=
?>

<!-- Sous menu -->
<div id="cmty_sub_menu">
	<table><tr><td>
	<?php
		if(isset($_SESSION[ 'community' ][ 'page' ]))
		{
			if($_SESSION[ 'community' ][ 'page' ]=='profils' && !empty($_GET[ 'user' ]))
			{
				$p = get_profil_login($_GET[ 'user' ]);
				echo "Profil de ".ucfirst(strtolower($p[ 'PRENOM' ]))." ".$p[ 'NOM' ];
			}
			elseif($_SESSION[ 'community' ][ 'page' ]=='profils')
			{
				echo "Profils du personnel";
			} 
			elseif($_SESSION[ 'community' ][ 'page' ]=='categories') 
			{
				if(isset($_GET[ 'categ' ]))
					echo '-- '.$_GET[ 'categ' ].' --';
				else echo "Cat&eacute;gories de publications";
				
			}	
			elseif($_SESSION[ 'community' ][ 'page' ]=='actualites') 
			{
				echo "Fil d'actualit&eacute;s";	
			}	
		}
	?>
	</td></tr></table>
</div>

<!-- Volet de gauche -->
<div id="cmty_left_flap" align="justify">
	<p>L'onglet "Actualit&eacute;s" affiche les derni&egrave;res publication. Pour ins&eacute;rer une nouvelle publication,
remplissez le titre (sans accent), la cat&eacute;gorie et le contenu. Vous pouvez au choix proposer une image ou un lien vers une vid&eacute;o (Daylymotion, Youtube).</p>
	<p>L'onglet "Profil" fournit la liste des utilisateurs, cette fonctionnalit&eacute; n'est pas finalis&eacute;e.</p>
	<p>L'onglet "Cat&eacute;gories" vous permet de naviguer dans les publications par Cat&eacute;gorie.</p>
</div>

<!-- Bloc principal -->
<div id="community_bloc_principal">
<!-- Bouton de retour vers le haut -->
<div id="btn_up">
   <img alt="Retour en haut" title="Retour en haut" src="images/fleche_up_cmty.png" width="40" />
</div>
<!-- Fin bouton de retour vers le haut -->
<?php

/*********************************************************************************
*********************  -N11- Affichage profil ************************************
**********************************************************************************/

	if(isset($_SESSION[ 'community' ][ 'page' ]) && $_SESSION[ 'community' ][ 'page' ]=='profils' && !empty($_GET[ 'user' ]))
	{
		//Btn de retour à la recherche
		echo '<a href="community.php?page=profils" class="cmty_btn_bleu">Revenir &agrave la recherche</a>';//community.php?sid='.$sid.'&page

		$profil_user = get_profil_login($_GET[ 'user' ]);
		//Profil de l'utilisateur
		echo '<br/><br/><table id="cmty_profil">
			<tr>
				<td rowspan=5>';
		if(!empty($profil_user[ 'PHOTO' ]) && file_exists('community_files/photos_utilisateurs/'.$profil_user[ 'PHOTO' ]))
			echo 	'<img src="community_files/photos_utilisateurs/'.$profil_user[ 'PHOTO' ].'" /></td>';
		else echo '<img src="images/no_photo.gif" /></td>';	

		echo '	 	<td><h1>'.ucfirst(strtolower($profil_user[ 'PRENOM' ])).' 			'.$profil_user[ 'NOM' ].'</h1></td>
			</tr>
			<tr>
				<td>&bull; Groupe : '.$profil_user[ 'GROUPE' ].'</td>
			</tr>
			<tr>
				<td>&bull; Bureau : '.$profil_user[ 'BUREAU' ].'</td>
			</tr>
			<tr>
				<td>&bull; T&eacute;l&eacute;phone : '.$profil_user[ 'TELEPHONE' ].'</td>
			</tr>';
		if(!empty($profil_user[ 'EMAIL' ]))
			echo '<tr>
					<td>&bull; Email : <a href="mailto:'.$profil_user[ 'EMAIL' ].'">'.$profil_user[ 'EMAIL' ].'</a></td>
			      </tr>';
		else    echo '<tr>
					<td>&bull; Email : <a href="mailto:'.$profil_user[ 'UTILISATEUR' ].'@'.$domaine.'">'.$profil_user[ 'UTILISATEUR' ].'@'.$domaine.'</a></td>
			      </tr>';
			
	
		echo '</table>';
	}

/*********************************************************************************
*********************  -N12- Recherche profils ************************************
**********************************************************************************/

	elseif(isset($_SESSION[ 'community' ][ 'page' ]) && $_SESSION[ 'community' ][ 'page' ]=='profils')
	{
		init_publication();
		
		//Barre de recherche
		echo '<div id="community_search"><table><tr><td>';
		echo '<img src="images/loupe.png" /> <input type=text autofocus="autofocus" placeholder="Recherche" id="cmty_search_keywords" name=community[search_keywords] ';
		if(isset($_SESSION[ 'community' ][ 'search_keywords' ]))
			echo 'value="'.$_SESSION[ 'community' ][ 'search_keywords' ].'"';
		echo '/></td>';
		
		echo '<td><select name="community[search_groupe]" class="select_style">';
	
		for ($i=0;$i<sizeof($_SESSION['correspondance']['groupe']);$i++)
		{
			if($_SESSION['community']['search_groupe']==$_SESSION['correspondance']['groupe'][$i])
			{	
				echo '<option value="'.$_SESSION['correspondance']['groupe'][$i].'" selected>'.$_SESSION['correspondance']['groupe'][$i].'</option>';
			}
			else 
			{
				if($_SESSION['correspondance']['groupe'][$i] == 'Equipe, contrat ou service')
					echo '<option value="'.$organisme.'">'.$organisme.'</option>';
				else echo '<option value="'.$_SESSION['correspondance']['groupe'][$i].'">'.$_SESSION['correspondance']['groupe'][$i].'</option>';
			}
		}
	
		echo '</td><td><input type=submit name=community[rechercher] value="Rechercher"/></td></tr></table>';
		//Fin Barre de recherche

		
		//Affichage des profils correspondants
		$profils = get_profils_by_group($_SESSION['community']['search_keywords'],$_SESSION['community']['search_groupe']);	
		
		$nb_profils = count($profils);		

		//Nombre de resultats
		if($nb_profils>1)
			echo '<br/><em id="nb_resultats">'.$nb_profils.' profils trouv&eacute;s</em>';
		else if($nb_profils==1)
			echo '<br/><em id="nb_resultats">'.$nb_profils.' profil trouv&eacute;</em>';
		else	echo '<br/><br/><p class="rouge gras centrer">Aucun profil ne correspond &agrave; la recherche</p>';
		echo '</div>';

		//Profils trouvés
		echo '<table id="profils_community">';
		$i=1;
		foreach($profils as $profil)	
		{
			echo '<tr><td>';
	
			if(!empty($profil[ 'PHOTO' ]) && file_exists('community_files/photos_utilisateurs/'.$profil[ 'PHOTO' ]))
				echo 	'<img src="community_files/photos_utilisateurs/'.$profil[ 'PHOTO' ].'" />';
			else echo '<img src="images/no_photo.gif" />';	

			echo '<a href=\'?page=profils&user='.$profil[ 'UTILISATEUR' ].'\'">';//href=\'?sid='.$sid.'&page

			echo 	''.$profil[ 'NOM' ].' '.ucfirst(strtolower($profil[ 'PRENOM' ])).' <b> [ '.$profil[ 'GROUPE' ].' ] </b>';
			echo '</a></td></tr>';
		}
		echo '</table>';	
	}

/*********************************************************************************
*******************************  -N2- Catégories *********************************
**********************************************************************************/

	else if(isset($_SESSION[ 'community' ][ 'page' ]) && $_SESSION[ 'community' ][ 'page' ]=='categories') 
	{
		init_publication();

		if(!isset($_GET[ 'categ' ])) //Affichage des catégories
		{
			echo '<table id="categories_community"><tr>';
	
			$i=1;
			foreach($categories_community as $categorie)
			{	
				echo '<td><div class="categorie_community" onclick="window.location.href=\'?page=categories&categ='.$categorie.'\'">';//window.location.href=\'?sid='.$sid.'&page
				if(file_exists('images/images/icone_'.strtolower($categorie).'.png'))
					echo 	'<img src="images/icone_'.strtolower($categorie).'.png" />';
				else echo '<img src="images/no_photo.gif" />';
				echo 	'<h2>'.$categorie.' ('.nb_total_publications_by_categ($categorie).')</h2>';
				echo '</div></td>';
	
				if($i%2 == 0)
					echo '</tr><tr>';
				$i++;
			}
	
			echo '</tr></table>';		
		}
		else //Affichage des publications par categ
		{
			$categorie=$_GET[ 'categ' ];

			//Ajout d'une publication pour une catégorie
			echo '<div id="add_publication">';
			echo '<table>';
			if(isset($message_demande))
				echo '<tr class="centrer"><td colspan=5>'.$message_demande.'</td></tr>';
			echo '<tr class="centrer">';
			echo '	<td><input type=text required name=community[titre_pub] placeholder="Titre de la publication" ';
			if(isset($_SESSION[ 'community' ][ 'titre_pub' ]))
				echo 'value="'.$_SESSION[ 'community' ][ 'titre_pub' ].'" ';
			echo '/></td>';
			
			echo '<td><select name="community[categorie_pub]" required>';
			echo '	<option value="'.$categorie.'" selected>'.$categorie.'</option>';
			echo '</select</td>';
			echo '  <td><img src="images/icone_img.png" alt="Images" title="Ajouter une image &agrave; partir de votre PC" onclick="show_add_image()" /></td>
				<td><img src="images/icone_video.png" alt="Video" title="Ajouter une vid&eacute;o &agrave; partir de YouTube ou Dailymotion" onclick="show_add_video()" /></td>
				<td class="alignDroite"><input type=submit name="community[publier]" value="Publier"/></td>
				</tr>';
	
			
			echo '<tr id="tr_add_image"><td colspan=5>Ajouter une image <input type=file name="cmty_image" id="cmty_image" /></td></tr>';
	
			if(!empty($_SESSION['community']['video'])) $visible=' style="display:block"';
			else  $visible='';
			echo '<tr id="tr_add_video" '.$visible.'><td colspan=5>Ajouter une vid&eacute;o <input type=url name="community[video]" id="cmty_video" placeholder="URL YouTube/Dailymotion" ';
			if(isset($_SESSION['community']['video']))
				echo 'value="'.$_SESSION['community']['video'].'" ';
			echo '/></td></tr>';
	
			echo '<tr><td colspan=5>
<textarea required name="community[contenu_pub]" id="contenu_pub" placeholder="Saisissez le contenu de votre publication...">';
if(isset($_SESSION[ 'community' ][ 'contenu_pub' ]))
echo $_SESSION[ 'community' ][ 'contenu_pub' ];
echo '</textarea></td></tr>';
			echo '</table>';
			echo '</div>';

			
			//Affichage des publications par catégorie
			$publications=get_publications_by_categ($limite_basse,$nb_par_page,$categorie);
		
			echo '<div id="publications">';
			foreach($publications as $publication)
			{	
				echo '<div  id="publication_'.$publication[ 'ID_PUBLICATION' ].'">';
				$id_pub=$publication[ 'ID_PUBLICATION' ];
				echo '<table class="publication">';
				echo '<tr class="ligne_grise">
					<td colspan=2>';
					if(get_timestamp_community($_SESSION[ 'connection' ][ 'utilisateur' ])<$publication[ 'DATE_PUBLICATION' ])
						echo '<sup class="new">New</sup>';
	
				echo '		<input type=hidden name="publication_'.$id_pub.'" 			id="publication_'.$id_pub.'" value="'.$id_pub.'" 			class="id_publication" />
						<a href="community.php?page=profils&user='.$publication[ 'UTILISATEUR' ].'" class="auteur">'.ucfirst(strtolower($publication[ 'PRENOM' ])).' '.$publication[ 'NOM' ].'</a> - 
						<span class="titre">'.$publication[ 'TITRE' ].'</span>';//community.php?sid='.$sid.'&page
	
						//Droit de suppression pour les gestionnaires
						if(in_array($_SESSION[ 'connection' ][ 'utilisateur' ],$gestionnaires_community))
						{
							echo '<img src="images/croix_rouge.png" alt="Supprimer" title="Supprimer cette publication" class="btn_supprimer_pub" onclick="supprimerPublication('.$publication[ 'ID_PUBLICATION' ].',this)" />';
						}
				echo '	</td>
				</tr>';
				echo '<tr>
					<td colspan=2>';
				if(!empty($publication[ 'FICHIER' ]) && get_file_type($publication[ 'FICHIER' ]) == 'image') //Image
				{
					echo '<img class="fichier_pub" src="community_files/'.$publication[ 'FICHIER' ].'" />';
				}	
				else if(!empty($publication[ 'FICHIER' ]) && in_array(get_file_type($publication[ 'FICHIER' ]),array('dailymotion','youtube'))) //Video dailymotion ou youtube
				{
					echo '<table class="centrerBloc"><tr><td class="centrer">';
					echo integrer_video($publication[ 'FICHIER' ]);
					echo '</td></tr></table>';
				}
				
				
				echo '	<p>'.$publication[ 'CONTENU' ].'</p>
					</td>
				</tr>';
				echo '<tr>
					<td>
						<span class="nb_plus">'.get_nb_plus($id_pub).'</span> <img src="images/btn_plus.png" title="Recommander" alt="+" height=27 width=27 ';
						if(!avis_donne($_SESSION[ 'connection' ][ 'utilisateur' ], $publication[ 'ID_PUBLICATION' ]))
							echo 'class="cursor_pointer" onclick="recommander(this)" ';
						else  echo 'class="avis_donne" ';
						echo '/>';
						echo '<span class="nb_moins">'.get_nb_moins($id_pub).'</span>  <img src="images/btn_moins.png" title="Deconseiller" alt="-" height=27 width=27 ';
						if(!avis_donne($_SESSION[ 'connection' ][ 'utilisateur' ], $publication[ 'ID_PUBLICATION' ]))
							echo 'class="cursor_pointer" onclick="deconseiller(this)" ';
						else  echo 'class="avis_donne" ';
						echo '/>';
			
						//Bulle d'avis de l'utilisateur
						if(get_avis_donne($_SESSION[ 'connection' ][ 'utilisateur' ], $publication[ 'ID_PUBLICATION' ]) != -1)
							echo get_avis_donne($_SESSION[ 'connection' ][ 'utilisateur' ], $publication[ 'ID_PUBLICATION' ]);
	
	
				echo '	</td>
					<td class="alignDroite">
						<span class="date_categ_publ"><a href="?page=categories&categ='.$categorie.'">'.$publication[ 'CATEGORIE' ].'</a><br/><time>'.mise_en_forme_date($publication[ 'DATE_PUBLICATION' ]).'</time></span>
					</td>
				</tr>';//href="?sid='.$sid.'&page
				
				echo '</table></div>';
			}
			echo '</div>';

			if($nb_pages != 0)
			{
				//Pagination
				echo '<table id="pagination_community"><tr>
					<td class="alignGauche">';
					if($page_courante != 1)
						echo '<a href="?page=categories&categ='.$categorie.'&npage='.$page_precedente.'">&lt;&lt; Pr&eacute;c&eacute;dent</a></td>';//href="?sid='.$sid.'&page
				echo '	<td class="centrer">Page '.$page_courante.'/'.$nb_pages.'</td>
					<td class="alignDroite">';
					if($page_suivante <= $nb_pages)
						echo '<a href="?page=categories&categ='.$categorie.'&npage='.$page_suivante.'">Suivante &gt;&gt;</a>';//href="?sid='.$sid.'&page
				echo '</td>
				</tr></table>';
			}
			else 
			{
				echo '<br/><br/><h2 class="rouge gras">Aucune publication disponible</h2>';
			
			}
		}

	}

/*********************************************************************************
*******************************  -N3- Actualités *********************************
**********************************************************************************/

	elseif(isset($_SESSION[ 'community' ][ 'page' ]) && $_SESSION[ 'community' ][ 'page' ]=='actualites') // Actualités
	{
		//Ajout d'une publication
		echo '<div id="add_publication">';
		echo '<table>';
		if(isset($message_demande))
			echo '<tr class="centrer"><td colspan=5>'.$message_demande.'</td></tr>';
		echo '<tr class="centrer">';
		echo '	<td><input type=text required name=community[titre_pub] placeholder="Titre de la publication" ';
		if(isset($_SESSION[ 'community' ][ 'titre_pub' ]))
			echo 'value="'.$_SESSION[ 'community' ][ 'titre_pub' ].'" ';
		echo '/></td>';
		
		echo '<td><select name="community[categorie_pub]" required>
				<option value="">Choisir une cat&eacute;gorie</option>';
		foreach($categories_community as $categorie)
		{
			if($categories_community==$categorie)
				echo '<option value="'.$categorie.'" selected>'.$categorie.'</option>';
			else echo '<option value="'.$categorie.'">'.$categorie.'</option>';

		}
		echo '</select></td>';
		echo '  <td><img src="images/icone_img.png" alt="Images" title="Ajouter une image &agrave; partir de votre PC" onclick="show_add_image()" /></td>
			<td><img src="images/icone_video.png" alt="Video" title="Ajouter une vid&eacute;o &agrave; partir de YouTube ou Dailymotion" onclick="show_add_video()" /></td>
			<td class="alignDroite"><input type=submit name="community[publier]" value="Publier"/></td>
			</tr>';

		
		echo '<tr id="tr_add_image"><td colspan=5>Ajouter une image <input type=file name="cmty_image" id="cmty_image" /></td></tr>';

		if(!empty($_SESSION['community']['video'])) $visible=' style="display:block"';
		else  $visible='';
		echo '<tr id="tr_add_video" '.$visible.'><td colspan=5>Ajouter une vid&eacute;o <input type=url name="community[video]" id="cmty_video" placeholder="URL YouTube/Dailymotion" ';
		if(isset($_SESSION['community']['video']))
			echo 'value="'.$_SESSION['community']['video'].'" ';
		echo '/></td></tr>';

		echo '<tr><td colspan=5>
<textarea required name="community[contenu_pub]" id="contenu_pub" placeholder="Saisissez le contenu de votre publication...">';
if(isset($_SESSION[ 'community' ][ 'contenu_pub' ]))
echo $_SESSION[ 'community' ][ 'contenu_pub' ];
echo '</textarea></td></tr>';
		echo '</table>';
		echo '</div>';


		//Affichage des publications
		$publications=get_publications($limite_basse,$nb_par_page);
		
		echo '<div id="publications">';
		foreach($publications as $publication)
		{	
			echo '<div  id="publication_'.$publication[ 'ID_PUBLICATION' ].'">';
			$id_pub=$publication[ 'ID_PUBLICATION' ];
			echo '<table class="publication">';
			echo '<tr class="ligne_grise">
				<td colspan=2>';
				if(get_timestamp_community($_SESSION[ 'connection' ][ 'utilisateur' ])<$publication[ 'DATE_PUBLICATION' ])
					echo '<sup class="new">New</sup>';

			echo '		<input type=hidden name="publication_'.$id_pub.'" 			id="publication_'.$id_pub.'" value="'.$id_pub.'" 			class="id_publication" />
					<a href="community.php?page=profils&user='.$publication[ 'UTILISATEUR' ].'" class="auteur">'.ucfirst(strtolower($publication[ 'PRENOM' ])).' ' .$publication[ 'NOM' ].'</a> - 
					<span class="titre">'.$publication[ 'TITRE' ].'</span>';//community.php?sid='.$sid.'&page

					//Droit de suppression pour les gestionnaires
					if(in_array($_SESSION[ 'connection' ][ 'utilisateur' ],$gestionnaires_community))
					{
						echo '<img src="images/croix_rouge.png" alt="Supprimer" title="Supprimer cette publication" class="btn_supprimer_pub" onclick="supprimerPublication('.$publication[ 'ID_PUBLICATION' ].',this)" />';
					}
			echo '	</td>
			      </tr>';
			echo '<tr>
				<td colspan=2>';
			if(!empty($publication[ 'FICHIER' ]) && get_file_type($publication[ 'FICHIER' ]) == 'image') //Image
			{
				echo '<img class="fichier_pub" src="community_files/'.$publication[ 'FICHIER' ].'" />';
			}	
			else if(!empty($publication[ 'FICHIER' ]) && in_array(get_file_type($publication[ 'FICHIER' ]),array('dailymotion','youtube'))) //Video dailymotion ou youtube
			{
				echo '<table class="centrerBloc"><tr><td class="centrer">';
				echo integrer_video($publication[ 'FICHIER' ]);
				echo '</td></tr></table>';
			}
			
			
			echo '	<p>'.$publication[ 'CONTENU' ].'</p>
				</td>
			      </tr>';
			echo '<tr>
				<td>
					<span class="nb_plus">'.get_nb_plus($id_pub).'</span> <img src="images/btn_plus.png" title="Recommander" alt="+" height=27 width=27 ';
					if(!avis_donne($_SESSION[ 'connection' ][ 'utilisateur' ], $publication[ 'ID_PUBLICATION' ]))
						echo 'class="cursor_pointer" onclick="recommander(this)" ';
					else  echo 'class="avis_donne" ';
					echo '/>';
					echo '<span class="nb_moins">'.get_nb_moins($id_pub).'</span>  <img src="images/btn_moins.png" title="Deconseiller" alt="-" height=27 width=27 ';
					if(!avis_donne($_SESSION[ 'connection' ][ 'utilisateur' ], $publication[ 'ID_PUBLICATION' ]))
						echo 'class="cursor_pointer" onclick="deconseiller(this)" ';
					else  echo 'class="avis_donne" ';
					echo '/>';
		
					//Bulle d'avis de l'utilisateur
					if(get_avis_donne($_SESSION[ 'connection' ][ 'utilisateur' ], $publication[ 'ID_PUBLICATION' ]) != -1)
						echo get_avis_donne($_SESSION[ 'connection' ][ 'utilisateur' ], $publication[ 'ID_PUBLICATION' ]);


			echo '	</td>
				<td class="alignDroite">
					<span class="date_categ_publ"><a href="?page=categories&categ='.$publication[ 'CATEGORIE' ].'">'.$publication[ 'CATEGORIE' ].'</a><br/><time>'.mise_en_forme_date($publication[ 'DATE_PUBLICATION' ]).'</time></span>
				</td>
			     </tr>';//href="?sid='.$sid.'&page=categories
			
			echo '</table></div>';
		}
		echo '</div>';

		//Pagination
		echo '<table id="pagination_community"><tr>
			<td class="alignGauche">';
			if($page_courante != 1)
				echo '<a href="?npage='.$page_precedente.'">&lt;&lt; Pr&eacute;c&eacute;dent</a></td>';//href="?sid='.$sid.'&npage
		echo '	<td class="centrer">Page '.$page_courante.'/'.$nb_pages.'</td>
			<td class="alignDroite">';
			if($page_suivante <= $nb_pages)
				echo '<a href="?npage='.$page_suivante.'">Suivante &gt;&gt;</a>';//href="?sid='.$sid.'&npage
		echo '</td>
		     </tr></table>';
	}
?>	
</div>

<?php
/*********************************************************************************
********************  -M- Maj CONNEXION_COMMUNITY ********************************
**********************************************************************************/

//Permet d'afficher les notifications et les publications nouvelles

include ("config.php");
include $chemin_connection;
$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_error());
mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

$query='UPDATE T_UTILISATEUR SET CONNEXION_COMMUNITY='.time().' WHERE UTILISATEUR="'.$_SESSION[ 'connection' ][ 'utilisateur' ].'"';
$result=mysqli_query($link,$query);
mysqli_close($link);
?>


<?php
//////////////PIED DE PAGE/////////////////
include "pied_page.php";
?>
