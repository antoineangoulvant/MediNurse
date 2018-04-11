<?php

//Affichage des erreurs en mode test
include 'config.php';
if($mode_test == 1 && !isset($_GET[ 'disconnect' ]))//Probleme de deconnexion avec CAS sinon
{
	ini_set('display_errors', 1);
	error_reporting(E_ALL);
}

/**
* Inventaire du matériel existant
*
* Date de création : 17 Juillet 2012<br>
* Date de dernière modification : 25 septembre 2015
* @version 2.1.0
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
//    | -E-  Gestion des variables Recherche
//    | -F-  Gestion des variables de share
//    | -G-  Creation d'une fiche
//    | -H-  Faire l'inventaire
//    | -I-  Modification d'une fiche
//    | -J-  Suppression d'une fiche
//    | -K-  Choix du module
//    | -L-  HTML
//    | -L1- Popup de confirmation d'une action 
//    | -L2- Nouvelle fiche / Modification d'une fiche
//    | -L3- Inventaire
//    | -L4- Consultation d'une fiche
//    | -M1- Barre de recherche
//    | -M2- Résultats de recherche

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
* Initialisation des variables de la creation de fiche d'un materiel
*/
function init_fiche()
{
	$_SESSION[ 'share' ][ 'id_materiel' ] = '';
	$_SESSION[ 'share' ][ 'libelle' ] = '';
	$_SESSION[ 'share' ][ 'disponibilite' ] = '';

	$_SESSION[ 'share' ][ 'utilisation' ] = '';
	$_SESSION[ 'share' ][ 'description' ] = '';
	$_SESSION[ 'share' ][ 'groupe' ] = '';
	$_SESSION[ 'share' ][ 'contact' ] = '';
	$_SESSION[ 'share' ][ 'email' ] = '';
	$_SESSION[ 'share' ][ 'telephone' ] = '';
	$_SESSION[ 'share' ][ 'photo' ] = '';
}

/**
* Retourne un score de pertinence de la fiche en fonction des mos clés
*
* @param string Fiche d'un matériel
* @param Tableau de mots clés
* @return Score
*/
function score_pertinence($libelle,$description,$mots_cles)
{
	$points_libelle=2;
	$points_description=1;
	$score=0;

	$tab_mots_libelle=explode(" ",strtoupper($libelle));
	$tab_mots_description=explode(" ",strtoupper($description));
	
	foreach($mots_cles as $mot)
	{
		if(in_array($mot,$tab_mots_libelle))
			$score=$score+$points_libelle;
		if(in_array($mot,$tab_mots_description))
			$score=$score+$points_description;
	}
	return $score;
}


/**
* Retourne un tableau contenant les fiches repondant aux mots clés
*
* @param string Chaine de mots clés
* @return Tableau de fiches
*/
function recherche_fiches($mots_cles,$groupe,$limitbasse,$nbparpage)
{
	include 'config.php';
	include($chemin_connection);

	//Split de la chaine de mots clés
	//$tab_keywords=explode(" ",strtoupper(trim(mysql_real_escape_string(htmlentities($mots_cles)))));
	//Split de la chaine de mots clés
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
	mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

	$tab_keywords=explode(" ",strtoupper(trim(mysqli_real_escape_string($link,htmlentities($mots_cles)))));
	
	//Suppression des mots parasites 
	$parasites=array("le","la","les","un","une","des","de","ou");
	$i=0;
	while(isset($tab_keywords[$i]))
	{
		if(in_array($tab_keywords[$i],$parasites))
			unset($tab_keywords[$i]);
		$i++;
	}
	
	//Construction du WHERE de la requete
	$where= 'WHERE ';

	if($groupe != $organisme)
		$where.='GROUPE="'.$groupe.'" AND ';
	
	$where.='(';

	foreach($tab_keywords as $keyword)
	{
		$where.='LIBELLE LIKE "%'.$keyword.'%" OR DESCRIPTION LIKE "%'.$keyword.'%" OR ';
	}

	
	if(substr($where,-3) == 'OR ')
	{
		$where=substr($where,0,-3);
		$where.=')';
	}

	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_error($link));
	mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

	//Nombre de resultats total
	$query2 = 'SELECT COUNT(*) FROM T_INVENTAIRE '.$where;
	$result2=mysqli_query($link,$query2);
	$donnee=mysqli_fetch_row($result2);
	$_SESSION['share']['resultats']=$donnee[0];


	$query = 'SELECT * FROM T_INVENTAIRE '.$where.' LIMIT '.$limitbasse.','.$nbparpage;
	$result=mysqli_query($link,$query);
	
	if(!$result)	return array();

	$fiches=array();
	$pertinences=array();
	while($donnees=mysqli_fetch_array($result, MYSQL_BOTH))
	{
		$fiche[ 'ID_MATERIEL' ] = $donnees[ 'ID_MATERIEL' ];
		$fiche[ 'LIBELLE' ] = $donnees[ 'LIBELLE' ];
		$fiche[ 'GROUPE' ] = $donnees[ 'GROUPE' ];
		$fiche[ 'DESCRIPTION' ] = $donnees[ 'DESCRIPTION' ];
		$fiche[ 'UTILISATION' ] = $donnees[ 'UTILISATION' ];	
		$fiche[ 'DISPONIBILITE' ] = $donnees[ 'DISPONIBILITE' ];
		$fiche[ 'PHOTO' ] = $donnees[ 'PHOTO' ];
		$fiche[ 'PERTINENCE' ] = score_pertinence($donnees[ 'LIBELLE' ],$donnees[ 'DESCRIPTION' ],$tab_keywords);
		array_push($pertinences,score_pertinence($donnees[ 'LIBELLE' ],$donnees[ 'DESCRIPTION' ],$tab_keywords));
		array_push($fiches,$fiche);
	}

	//Ordonne le tableau par pertinence
	array_multisort($pertinences,SORT_DESC,$fiches);

	mysqli_free_result($result);
	return $fiches;
}


/**
* Retourne un tableau contenant la fiche correspondant à l'identifiant
*
* @param string Identifiant du materiel
* @return Tableau contenant la fiche
*/
function ficheById($id_materiel)
{
	include 'config.php';
	include($chemin_connection);

	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_error($link));
	mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

	$query = 'SELECT * FROM T_INVENTAIRE WHERE ID_MATERIEL='.$id_materiel;
	$result=mysqli_query($link,$query) or die ('Erreur requete fiche');
	
	if(!$result)	return array();

	$donnees=mysqli_fetch_array($result, MYSQL_BOTH);

	$fiche[ 'ID_MATERIEL' ] = $donnees[ 'ID_MATERIEL' ];
	$fiche[ 'LIBELLE' ] = $donnees[ 'LIBELLE' ];
	$fiche[ 'DESCRIPTION' ] = $donnees[ 'DESCRIPTION' ];
	$fiche[ 'GROUPE' ] = $donnees[ 'GROUPE' ];
	$fiche[ 'NOM_CONTACT' ] = $donnees[ 'NOM_CONTACT' ];
	$fiche[ 'TEL_CONTACT' ] = $donnees[ 'TEL_CONTACT' ];
	$fiche[ 'EMAIL_CONTACT' ] = $donnees[ 'EMAIL_CONTACT' ];
	$fiche[ 'DISPONIBILITE' ] = $donnees[ 'DISPONIBILITE' ];
	$fiche[ 'UTILISATION' ] = $donnees[ 'UTILISATION' ];	
	$fiche[ 'PHOTO' ] = $donnees[ 'PHOTO' ];


	mysqli_free_result($result);
	return $fiche;
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

// Evite d'avoir un message "le document a expiré dans le navigateur"
session_cache_limiter('private_no_expire, must-revalidate');

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
********************  -E- Gestion des variables Recherche ************************
**********************************************************************************/


if (isset($_REQUEST[ 'share' ][ 'mots_cles' ])) $_SESSION[ 'rech_materiel' ][ 'mots_cles' ] = trim($_REQUEST[ 'share' ][ 'mots_cles' ]);
else $_SESSION[ 'rech_materiel' ][ 'mots_cles' ] = '';

if (isset($_REQUEST[ 'rech_materiel' ][ 'groupe' ])) $_SESSION['rech_materiel']['groupe'] = trim($_REQUEST[ 'rech_materiel' ][ 'groupe' ]);
else $_SESSION['rech_materiel']['groupe']=$organisme;

//Nombre par page = 10
$_SESSION['share']['nb_par_page']=10;

if ( isset($_REQUEST[ 'rechercher_evol' ]) OR !isset($_SESSION['share']['limitebasse']))
{
	$_SESSION['share']['limitebasse']=0;
}

if ( isset($_REQUEST[ 'rechercher_suiv' ]) )
{
	if ($_SESSION['share']['limitebasse'] +$_SESSION['share']['nb_par_page'] <$_SESSION['share']['resultats'])
		$_SESSION['share']['limitebasse']+=$_SESSION['share']['nb_par_page'];
}

if ( isset($_REQUEST[ 'rechercher_prec' ]) )
{
	if ($_SESSION['share']['limitebasse']-$_SESSION['share']['nb_par_page']>=0)
	$_SESSION['share']['limitebasse']-=$_SESSION['share']['nb_par_page'];
}



/*********************************************************************************
********************  -F- Gestion des variables de share  ************************
**********************************************************************************/


if (isset($_REQUEST[ 'share' ]))  if (is_array($_REQUEST[ 'share' ]))
{
if (isset($_REQUEST[ 'share' ][ 'groupe' ])) $_SESSION[ 'share' ][ 'groupe' ] = htmlspecialchars($_REQUEST[ 'share' ][ 'groupe' ]);
if (isset($_REQUEST[ 'share' ][ 'libelle' ])) $_SESSION[ 'share' ][ 'libelle' ] = htmlspecialchars($_REQUEST[ 'share' ][ 'libelle' ]);
if (isset($_REQUEST[ 'share' ][ 'disponibilite' ])) $_SESSION[ 'share' ][ 'disponibilite' ] = htmlspecialchars($_REQUEST[ 'share' ][ 'disponibilite' ]);
if (isset($_REQUEST[ 'share' ][ 'utilisation' ])) $_SESSION[ 'share' ][ 'utilisation' ] = htmlspecialchars($_REQUEST[ 'share' ][ 'utilisation' ]);
if (isset($_REQUEST[ 'share' ][ 'utilisation' ]) && $_REQUEST[ 'share' ][ 'utilisation' ]=='Choisir') $_SESSION[ 'share' ][ 'utilisation' ] = '';
if (isset($_REQUEST[ 'share' ][ 'description' ])) $_SESSION[ 'share' ][ 'description' ] = htmlspecialchars($_REQUEST[ 'share' ][ 'description' ]);
if (isset($_REQUEST[ 'share' ][ 'email' ])) $_SESSION[ 'share' ][ 'email' ] = htmlspecialchars($_REQUEST[ 'share' ][ 'email' ]);
if (isset($_REQUEST[ 'share' ][ 'telephone' ])) $_SESSION[ 'share' ][ 'telephone' ] = htmlspecialchars($_REQUEST[ 'share' ][ 'telephone' ]);
if (isset($_REQUEST[ 'share' ][ 'contact' ])) $_SESSION[ 'share' ][ 'contact' ] = htmlspecialchars($_REQUEST[ 'share' ][ 'contact' ]); 
if (isset($_REQUEST[ 'share' ][ 'photo' ])) $_SESSION[ 'share' ][ 'photo' ] = htmlspecialchars($_REQUEST[ 'share' ][ 'photo' ]); 

if(!empty($_REQUEST[ 'share' ][ 'inventaire' ]))
{
	$_SESSION[ 'share' ][ 'inventaire' ]=$_REQUEST[ 'share' ][ 'inventaire' ];
} 
else $_SESSION[ 'share' ][ 'inventaire' ] = array();
}

if(isset($_POST[ 'share' ][ 'new_fiche' ]))
	$_SESSION[ 'share' ][ 'page' ]=1; //Création d'une fiche

if(isset($_POST[ 'share' ][ 'modifier' ]))
	$_SESSION[ 'share' ][ 'page' ]=3; //Modification d'une fiche

if(isset($_POST[ 'share' ][ 'inventaire' ]))
	$_SESSION[ 'share' ][ 'page' ]=4; //Inventaire

if(isset($_POST[ 'share' ][ 'id_modif' ]))
	$_SESSION[ 'share' ][ 'id_modif' ]=$_POST[ 'share' ][ 'id_modif' ];

if(isset($_GET[ 'id_materiel' ]))
	$_SESSION[ 'share' ][ 'page' ]=2; //Fiche d'un materiel

if(!isset($_SESSION[ 'share' ][ 'page' ]) OR isset($_REQUEST[ 'share' ][ 'annuler' ]))
	$_SESSION[ 'share' ][ 'page' ]=0; //Page recherche


if ( isset($_REQUEST[ 'rechercher_evol' ]))
{
	$_SESSION['recherche']['limitebasse']=0;
	$message_evolue= '';
	$message_evolue=rech_evol($_POST["rech_expedition"]["nom"], $_POST["rech_expedition"]["prenom"]);
}



/*********************************************************************************
************************  G - Creation d'une fiche *******************************
**********************************************************************************/

if (isset($_REQUEST[ 'share' ]['enregistrer']))
{
	$annule=0;
	//Controle des champs obligatoires
	if(EMPTY($_SESSION[ 'share' ][ 'libelle' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Libell&eacute;" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(!in_array($_SESSION[ 'share' ][ 'disponibilite' ],array(0,1)))
	{
		$message_demande= '<p class="rouge gras">Le champ "Disponibilit&eacute;" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	/*elseif(EMPTY($_SESSION[ 'share' ][ 'utilisation' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Fr&eacute;quence d\'utilisation" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}*/	
	elseif($_SESSION[ 'share' ][ 'groupe' ] == 'Equipe, contrat ou service')
	{
		$message_demande= '<p class="rouge gras">La partie "Groupe" n\'est pas renseign&eacute;e.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}	
	elseif(EMPTY($_SESSION[ 'share' ][ 'contact' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Personne &agrave; contacter" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(EMPTY($_SESSION[ 'share' ][ 'telephone' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "T&eacute;l&eacute;phone" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(!preg_match('/^(00\d\d|0){1}\d[0-9]{8}$/',$_SESSION[ 'share' ][ 'telephone' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "T&eacute;l&eacute;phone" n\'est pas valide.<br/>Exemple international : 0033473123456<br/>Exemple France : 0473123456<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(EMPTY($_SESSION[ 'share' ][ 'email' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Email" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(!preg_match('/^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$/',$_SESSION[ 'share' ][ 'email' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Email" n\'est pas valide.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	// fin du controle des champs obligatoires
	if(!$annule)
	{
		$message_demande='';
		include $chemin_connection;
		
		$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_error($link));
		mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');
		
		//recherche de l'indice a ajouter
		//la numerotation des (ID_) commence a 1
		$indi=1;
		$query = 'SELECT MAX(ID_MATERIEL) FROM T_INVENTAIRE';
		$result = mysqli_query($link,$query) or die('Requete de comptage des fiches SHARE: ' . mysqli_error($link));
		if ($result)
		{
			$line = mysqli_fetch_array($result, MYSQL_NUM);
			$indi=$line[0]+1;
			mysqli_free_result($result);
		}
		$_SESSION['share']['id_materiel']=$indi;

		if(!empty($_FILES['photo_materiel']['name']))
		{
			//Upload de la piece jointe
			$MAX_FILE_SIZE = 5242880;// 5Mo
			//Verif qu'il n'y ait pas d'erreur lors de l'upload
			if($_FILES['photo_materiel']['error'] > 0) $message_demande .= '<p class="gras rouge">Erreur lors du transfert</p>';	
	
			//Controle sur la taille max
			if($_FILES['photo_materiel']['size'] > $MAX_FILE_SIZE) $message_demande .= '<p class="gras rouge">Erreur : le fichier est trop gros</p>';	
		
			//Controle de l'extention
			$extension_upload = strtolower(substr(strrchr($_FILES['photo_materiel']['name'],'.'),1));
			if(!in_array($extension_upload,array('jpg','jpeg','png'))) $message_demande .='<p class="gras rouge">Erreur : Extension non autoris&eacute;e<br/>Autoris&eacute;es : jpg,jpeg,png</p>';

			//Génération du nom du fichier (chaine aleatoire)
			$nom_fichier = "";
			$chaine = "abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
			
			//Nom de fichier unique
			do{
				srand((double)microtime()*1000000);
				for($i=0; $i<15; $i++) 
					$nom_fichier .= $chaine[rand()%strlen($chaine)];
				$nom_fichier .= '.'.$extension_upload;
			}while (file_exists("inventaire_pictures/".$nom_fichier));

			$_SESSION['share']['photo']=$nom_fichier;
				
			$destination_fichier="inventaire_pictures/".$nom_fichier;
			$transfert_reussi=move_uploaded_file($_FILES['photo_materiel']['tmp_name'],$destination_fichier);
			if(!$transfert_reussi) $message_demande .= '<p class="gras rouge">Erreur : Echec du transfert de la piece jointe sur le serveur</p>';
		}

		if($message_demande=='')
		{
			if(!isset($_SESSION['share']['photo'])) $_SESSION['share']['photo']='';
			//insertion de la demande d'expedition dans la base
			$query = 'INSERT INTO T_INVENTAIRE(ID_MATERIEL,LIBELLE,DISPONIBILITE,UTILISATION,DESCRIPTION,GROUPE,NOM_CONTACT,TEL_CONTACT,EMAIL_CONTACT,PHOTO) 
			VALUES ('.$indi.',
				"'.mysqli_real_escape_string($link,$_SESSION['share']['libelle']).'",
				"'.mysqli_real_escape_string($link,$_SESSION['share']['disponibilite']).'",
				"'.mysqli_real_escape_string($link,$_SESSION['share']['utilisation']).'",
				"'.mysqli_real_escape_string($link,$_SESSION['share']['description']).'",
				"'.mysqli_real_escape_string($link,$_SESSION['share']['groupe']).'",
				"'.mysqli_real_escape_string($link,$_SESSION['share']['contact']).'",
				"'.mysqli_real_escape_string($link,$_SESSION['share']['telephone']).'",
				"'.mysqli_real_escape_string($link,$_SESSION['share']['email']).'",
				"'.mysqli_real_escape_string($link,$_SESSION['share']['photo']).'"
				)';
			$result = mysqli_query($link,$query) or die('Requete d\'insertion echouee.<br>Query : '.$query.'<br>Erreur :'. mysqli_error($link));
			//mysql_free_result($result);

			$message_demande= "<span class='vert gras'>-> Fiche cr&eacute;&eacute;e <-</span>";	
		}
		
		mysqli_close($link);
		
		//on re-initialise les champs pour ne pas effectuer plusieurs fois le meme conge
		init_fiche();
		
	}
}


/*********************************************************************************
***********************  H - Faire l'inventaire **********************************
**********************************************************************************/

if (isset($_REQUEST[ 'share' ]['enregistrer_inventaire']))
{
	$i=0;
	$annule=0;

	include $chemin_connection;
	
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_error($link));
	mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');
		
	//Verification des champs
	while($i<10)
	{
		if(!empty($_SESSION[ 'share' ][ 'inventaire' ][$i][ 'libelle' ]))
		{
			//Controle des champs obligatoires
			if(EMPTY($_SESSION[ 'share' ][ 'inventaire' ][$i][ 'libelle' ]))
			{
				$message_demande= '<p class="rouge gras">Le champ "Libell&eacute;" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
				$annule=1;
			}
			elseif(!in_array($_SESSION[ 'share' ][ 'inventaire' ][$i][ 'disponibilite' ],array(0,1)))
			{
				$message_demande= '<p class="rouge gras">Le champ "Disponibilit&eacute;" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
				$annule=1;
			}
			/*elseif(EMPTY($_SESSION[ 'share' ][ 'inventaire' ][$i][ 'utilisation' ]))
			{
				$message_demande= '<p class="rouge gras">Le champ "Fr&eacute;quence d\'utilisation" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
				$annule=1;
			}*/	
			elseif($_SESSION[ 'share' ][ 'inventaire' ][$i][ 'groupe' ] == 'Equipe, contrat ou service')
			{
				$message_demande= '<p class="rouge gras">La partie "Groupe" n\'est pas renseign&eacute;e.<br/>Action annul&eacute;e</p>';
				$annule=1;
			}	
			elseif(EMPTY($_SESSION[ 'share' ][ 'inventaire' ][$i][ 'contact' ]))
			{
				$message_demande= '<p class="rouge gras">Le champ "Personne &agrave; contacter" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
				$annule=1;
			}
			elseif(EMPTY($_SESSION[ 'share' ][ 'inventaire' ][$i][ 'telephone' ]))
			{
				$message_demande= '<p class="rouge gras">Le champ "T&eacute;l&eacute;phone" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
				$annule=1;
			}
			elseif(!preg_match('/^(00\d\d|0){1}\d[0-9]{8}$/',$_SESSION[ 'share' ][ 'inventaire' ][$i][ 'telephone' ]))
			{
				$message_demande= '<p class="rouge gras">Le champ "T&eacute;l&eacute;phone" n\'est pas valide.<br/>Exemple international : 0033473123456<br/>Exemple France : 0473123456<br/>Action annul&eacute;e</p>';
				$annule=1;
			}
			elseif(EMPTY($_SESSION[ 'share' ][ 'inventaire' ][$i][ 'email' ]))
			{
				$message_demande= '<p class="rouge gras">Le champ "Email" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
				$annule=1;
			}
			elseif(!preg_match('/^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$/',$_SESSION[ 'share' ][ 'inventaire' ][$i][ 'email' ]))
			{
				$message_demande= '<p class="rouge gras">Le champ "Email" n\'est pas valide.<br/>Action annul&eacute;e</p>';
				$annule=1;
			}
		}
		$i++;		
	}
	
	if(!$annule)
	{	
		
		for($i=0;$i<10;$i++)
		{
			if(!empty($_SESSION[ 'share' ][ 'inventaire' ][$i][ 'libelle' ]))
			{
				$message_demande='';

				//recherche de l'indice a ajouter
				//la numerotation des (ID_) commence a 1
				$indi=1;
				$query = 'SELECT MAX(ID_MATERIEL) FROM T_INVENTAIRE';
				$result = mysqli_query($link,$query) or die('Requete de comptage des fiches SHARE: ' . mysqli_error($link));
				if ($result)
				{
					$line = mysqli_fetch_array($result, MYSQL_NUM);
					$indi=$line[0]+1;
					mysqli_free_result($result);
				}
				if($_SESSION[ 'share' ][ 'inventaire' ][$i]['utilisation']=='Choisir')
					$_SESSION[ 'share' ][ 'inventaire' ][$i]['utilisation']='';
		
				//insertion de la demande d'expedition dans la base
				$query = 'INSERT INTO T_INVENTAIRE(ID_MATERIEL,LIBELLE,DISPONIBILITE,UTILISATION,DESCRIPTION,GROUPE,NOM_CONTACT,TEL_CONTACT,EMAIL_CONTACT) 
				VALUES ('.$indi.',
					"'.mysqli_real_escape_string($link,$_SESSION[ 'share' ][ 'inventaire' ][$i]['libelle']).'",
					"'.mysqli_real_escape_string($link,$_SESSION[ 'share' ][ 'inventaire' ][$i]['disponibilite']).'",
					"'.mysqli_real_escape_string($link,$_SESSION[ 'share' ][ 'inventaire' ][$i]['utilisation']).'",
					"'.mysqli_real_escape_string($link,$_SESSION[ 'share' ][ 'inventaire' ][$i]['description']).'",
					"'.mysqli_real_escape_string($link,$_SESSION[ 'share' ][ 'inventaire' ][$i]['groupe']).'",
					"'.mysqli_real_escape_string($link,$_SESSION[ 'share' ][ 'inventaire' ][$i]['contact']).'",
					"'.mysqli_real_escape_string($link,$_SESSION[ 'share' ][ 'inventaire' ][$i]['telephone']).'",
					"'.mysqli_real_escape_string($link,$_SESSION[ 'share' ][ 'inventaire' ][$i]['email']).'"
					)';
				$result = mysqli_query($link,$query) or die('Requete d\'insertion echouee.<br>Query : '.$query.'<br>Erreur :'. mysqli_error($link));
				//mysql_free_result($result);
			}
		}
		//on re-initialise les champs pour ne pas effectuer plusieurs fois le meme conge
		init_fiche();
		$_SESSION[ 'share' ][ 'inventaire' ]=array();
		mysqli_close($link);
		$_SESSION['share']['popup_message']='<p class="centrer vert gras">Fiches cr&eacute;&eacute;es</p>';
		$message_demande= "<span class='vert gras'>-> Fiches cr&eacute;&eacute;es <-</span>";	
	}			
}
/*********************************************************************************
*****************  I - Modification d'une fiche **********************************
**********************************************************************************/

if (isset($_REQUEST[ 'share' ]['enregistrer_modif']) && $_SESSION[ 'share' ][ 'page' ]==3)
{
	$annule=0;
	$message_demande='';
	//Controle des champs obligatoires
	if(EMPTY($_SESSION[ 'share' ][ 'libelle' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Libell&eacute;" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(!in_array($_SESSION[ 'share' ][ 'disponibilite' ],array(0,1)))
	{
		$message_demande= '<p class="rouge gras">Le champ "Disponibilit&eacute;" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	/*elseif(EMPTY($_SESSION[ 'share' ][ 'utilisation' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Fr&eacute;quence d\'utilisation" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}*/	
	elseif($_SESSION[ 'share' ][ 'groupe' ] == 'Equipe, contrat ou service')
	{
		$message_demande= '<p class="rouge gras">La partie "Groupe" n\'est pas renseign&eacute;e.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}	
	elseif(EMPTY($_SESSION[ 'share' ][ 'contact' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Personne &agrave; contacter" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(EMPTY($_SESSION[ 'share' ][ 'telephone' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "T&eacute;l&eacute;phone" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(!preg_match('/^(00\d\d|0){1}\d[0-9]{8}$/',$_SESSION[ 'share' ][ 'telephone' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "T&eacute;l&eacute;phone" n\'est pas valide.<br/>Exemple international : 0033473123456<br/>Exemple France : 0473123456<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(EMPTY($_SESSION[ 'share' ][ 'email' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Email" n\'est pas renseign&eacute;.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	elseif(!preg_match('/^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$/',$_SESSION[ 'share' ][ 'email' ]))
	{
		$message_demande= '<p class="rouge gras">Le champ "Email" n\'est pas valide.<br/>Action annul&eacute;e</p>';
		$annule=1;
	}
	// fin du controle des champs obligatoires
	if(!$annule)
	{
		if(!empty($_FILES['photo_materiel']['name']))
		{
			//Upload de la piece jointe
			$MAX_FILE_SIZE = 5242880;// 5Mo
			//Verif qu'il n'y ait pas d'erreur lors de l'upload
			if($_FILES['photo_materiel']['error'] > 0) $message_demande .= '<p class="gras rouge">Erreur lors du transfert</p>';	
	
			//Controle sur la taille max
			if($_FILES['photo_materiel']['size'] > $MAX_FILE_SIZE) $message_demande .= '<p class="gras rouge">Erreur : le fichier est trop gros</p>';	
		
			//Controle de l'extention
			$extension_upload = strtolower(substr(strrchr($_FILES['photo_materiel']['name'],'.'),1));
			if(!in_array($extension_upload,array('jpg','jpeg','png'))) $message_demande .='<p class="gras rouge">Erreur : Extension non autoris&eacute;e<br/>Autoris&eacute;es : jpg,jpeg,png</p>';

			//Génération du nom du fichier (chaine aleatoire)
			$nom_fichier = "";
			$chaine = "abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
			srand((double)microtime()*1000000);
			for($i=0; $i<15; $i++) 
				$nom_fichier .= $chaine[rand()%strlen($chaine)];
			$nom_fichier .= '.'.$extension_upload;

			$_SESSION['share']['photo']=$nom_fichier;
				
			$destination_fichier="inventaire_pictures/".$nom_fichier;
			$transfert_reussi=move_uploaded_file($_FILES['photo_materiel']['tmp_name'],$destination_fichier);
			if(!$transfert_reussi) $message_demande .= '<p class="gras rouge">Erreur : Echec du transfert de la piece jointe sur le serveur</p>';
		
			//On supprime l'ancienne photo
			$chemin_photo="inventaire_pictures/".$_SESSION['share']['ancienne_photo'];
			if (file_exists($chemin_photo) && $_SESSION['share']['ancienne_photo'] != '')
				unlink($chemin_photo);

			$_SESSION['share']['ancienne_photo']='';//On vide la variable
		}



		include $chemin_connection;
		
		$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_error($link));
		mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');
		
		//modification de la fiche
		$query = 'UPDATE T_INVENTAIRE SET 
			LIBELLE="'.mysqli_real_escape_string($link,$_SESSION['share']['libelle']).'",
			DISPONIBILITE="'.mysqli_real_escape_string($link,$_SESSION['share']['disponibilite']).'",
			UTILISATION="'.mysqli_real_escape_string($link,$_SESSION['share']['utilisation']).'",
			DESCRIPTION="'.mysqli_real_escape_string($link,$_SESSION['share']['description']).'",
			GROUPE="'.mysqli_real_escape_string($link,$_SESSION['share']['groupe']).'",
			NOM_CONTACT="'.mysqli_real_escape_string($link,$_SESSION['share']['contact']).'",
			TEL_CONTACT="'.mysqli_real_escape_string($link,$_SESSION['share']['telephone']).'",
			EMAIL_CONTACT="'.mysqli_real_escape_string($link,$_SESSION['share']['email']).'"';
			if(!empty($_FILES['photo_materiel']['name'])) $query.=',
			PHOTO="'.mysqli_real_escape_string($link,$_SESSION['share']['photo']).'"';
			$query.='WHERE ID_MATERIEL='.$_SESSION['share']['id_modif'];

		$result = mysqli_query($link,$query) or die('Requete de modification fiche echouee.<br>Query : '.$query.'<br>Erreur :'. mysqli_error($link));
		//mysql_free_result($result);
		
		$_SESSION['share']['id_modif']='';
		$message_demande= "<span class='vert gras'>-> Fiche modifi&eacute;e <-</span>";	
		
		mysqli_close($link);
		
		//on re-initialise les champs pour ne pas effectuer la meme action
		init_fiche();

		$_SESSION['share']['popup_message']='<p class="centrer vert gras">Fiche modifi&eacute;e</p>';
		
		$_SESSION['share']['page']=0; //On envoie vers la page de recherche
		
	}
}


/*********************************************************************************
*****************  J - Suppression d'une fiche ***********************************
**********************************************************************************/

if (isset($_REQUEST[ 'share' ]['supprimer']))
{
	include $chemin_connection;
	
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_error($link));
	mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');
		
	$query2 = 'SELECT PHOTO FROM T_INVENTAIRE WHERE ID_MATERIEL='.$_SESSION['share']['id_modif'];
	$result2 = mysqli_query($link,$query2) or die('Requete selection photo(suppr).<br>Query : '.$query.'<br>Erreur :'. mysqli_error($link));
	$photo=mysqli_fetch_row($result2);

	//On supprime la photo associée
	$chemin_photo="inventaire_pictures/".$photo[0];
	if (file_exists($chemin_photo) && $photo[0] != '')
		unlink($chemin_photo);

	//modification de la fiche
	$query = 'DELETE FROM T_INVENTAIRE WHERE ID_MATERIEL='.$_SESSION['share']['id_modif'];
	$result = mysqli_query($link,$query) or die('Requete de suppression fiche echouee.<br>Query : '.$query.'<br>Erreur :'. mysqli_error($link));

	
	mysqli_close($link);

	$_SESSION['share']['popup_message']='<p class="centrer vert gras">Fiche supprim&eacute;e</p>';
	
	$_SESSION['share']['page']=0; //On envoie vers la page de recherche
}

/*********************************************************************************
***********************  K - Choix du module *************************************
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
		header('location:'.$chemin_module.'?sid=' . $sid);
	}
}
}



/*********************************************************************************
*******************************  -L- HTML ****************************************
**********************************************************************************/

// En tete des modules
include "en_tete.php";
?>

<form name="form1" id="form1" method="post" action="<?php echo $_SERVER[ 'PHP_SELF' ]; ?>" enctype="multipart/form-data">
<input type="hidden" name="sid" id="sid" value="<?php echo $sid; ?>" />

<?php
/*********************************************************************************
****************  L1 - popup de confirmation d'une action ************************
**********************************************************************************/
if(!empty($_SESSION['share']['popup_message']))
{
	echo '<script>
			$(function(){
				var popupWidth = \'300px\',
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
		echo 	$_SESSION['share']['popup_message'];
		echo '</div>';	

		//Suppression de la variable de session
		unset($_SESSION['share']['popup_message']);
}


/*********************************************************************************
****************  L2 - Nouvelle fiche / Modification d'une fiche *****************
**********************************************************************************/

if($_SESSION[ 'share' ][ 'page' ] == 1 OR $_SESSION[ 'share' ][ 'page' ] == 3)
{
	//On charge les anciennes variables pour modification
	if($_SESSION[ 'share' ][ 'page' ] == 3 && !empty($_SESSION[ 'share' ][ 'id_modif' ]))
	{
		$fiche_a_modifier=ficheById($_SESSION[ 'share' ][ 'id_modif' ]);
		$_SESSION[ 'share' ][ 'libelle' ]=$fiche_a_modifier[ 'LIBELLE' ];
		$_SESSION[ 'share' ][ 'disponibilite' ]=$fiche_a_modifier[ 'DISPONIBILITE' ];
		$_SESSION[ 'share' ][ 'utilisation' ]=$fiche_a_modifier[ 'UTILISATION' ];
		$_SESSION[ 'share' ][ 'groupe' ]=$fiche_a_modifier[ 'GROUPE' ];
		$_SESSION[ 'share' ][ 'ancienne_photo' ]=$fiche_a_modifier[ 'PHOTO' ];
		$_SESSION[ 'share' ][ 'description' ]=$fiche_a_modifier[ 'DESCRIPTION' ];
		$_SESSION[ 'share' ][ 'contact' ]=$fiche_a_modifier[ 'NOM_CONTACT' ];
		$_SESSION[ 'share' ][ 'telephone' ]=$fiche_a_modifier[ 'TEL_CONTACT' ];
		$_SESSION[ 'share' ][ 'email' ]=$fiche_a_modifier[ 'EMAIL_CONTACT' ];

	}

	//Bouton "Revenir à la recherche" (soumet le formulaire et renvoie par defaut sur la recherche)
	echo '<input type=submit value="Revenir &agrave la recherche" name="share[annuler]" id="btn_retour_recherche" class="btn_bleu" />';	

	if($_SESSION[ 'share' ][ 'page' ] == 3)
		echo '<h1 class="centrer h1_share">Modification de la fiche n&deg;'.$_SESSION[ 'share' ][ 'id_modif' ].'</h1>';
	else echo '<h1 class="centrer h1_share">Nouvelle fiche mat&eacute;riel</h1>';

	echo '<p class="centrer gras rouge">* Champs obligatoires</p>';

	echo '<fieldset><legend>Informations sur le mat&eacute;riel</legend>';
	echo '<table id="new_fiche" class="centrerBloc">';
	echo '<tr>
		<td><label for="materiel_libelle">Libell&eacute; du mat&eacute;riel <span class="rouge gras">*</span></label></td>
	     	<td><input type=text id="materiel_libelle" name="share[libelle]" ';
	if(isset($_SESSION[ 'share' ][ 'libelle' ]))
		echo 'value="'.$_SESSION[ 'share' ][ 'libelle' ].'" ';
	echo '/></td>
	      </tr>';

	echo '<tr>
		<td><label for="materiel_dispo">Disponibilit&eacute;</label></td>
		<td><input type=radio id="materiel_dispo" name="share[disponibilite]" value="1" ';	
		if(isset($_SESSION[ 'share' ][ 'disponibilite' ]) AND $_SESSION[ 'share' ][ 'disponibilite' ] == 1)
			echo 'checked ';
	echo ' /><label for="materiel_dispo">Oui</label>&nbsp;&nbsp;&nbsp;';

	echo '<input type=radio id="materiel_non_dispo" name="share[disponibilite]" value="0" ';	
		if(isset($_SESSION[ 'share' ][ 'disponibilite' ]) AND $_SESSION[ 'share' ][ 'disponibilite' ] == 0)
			echo 'checked ';
	echo ' /><label for="materiel_non_dispo">Non</label></td>';

	echo '<tr>
		<td><label for="materiel_utilisation">Fr&eacute;quence d\'utilisation</label></td>';

	echo '<td><select name="share[utilisation]" id="materiel_utilisation">';
		$choix_frequence=array("Choisir","Souvent","Occasionnellement","Rarement","Jamais");
	      	for ($i=0;$i<sizeof($choix_frequence);$i++)
		{
			if ($_SESSION['share']['utilisation']==$choix_frequence[$i])
			{	
				echo '<option value="'.$choix_frequence[$i].'" selected>'.$choix_frequence[$i].'</option>';
			}
			else echo '<option value="'.$choix_frequence[$i].'">'.$choix_frequence[$i].'</option>';
		}

	echo '</select></td></tr>';

	echo '<tr>
		<td><br/><label for="materiel_description">Description</label></td><td></td>
	      </tr>
	      <tr>
	      	<td colspan=2>
<textarea id="materiel_description" name="share[description]" cols=38 rows=5>';
if(isset($_SESSION[ 'share' ][ 'description' ]))
echo $_SESSION[ 'share' ][ 'description' ];
echo '</textarea>
		</td>
	     </tr>';
	
	if($_SESSION[ 'share' ][ 'page' ] == 3)
	{
		echo '<tr><td colspan=2 class="centrer"><img id="miniature_photo" src="inventaire_pictures/'.$_SESSION[ 'share' ][ 'ancienne_photo' ].'" />';
	}

	echo '<tr>
	<td><label for="photo_materiel">Photo du mat&eacute;riel</label></td>	
	<td><input type=file name="photo_materiel" /></td>
	</tr>';

	echo '    </table>
		</fieldset>';

	echo '<fieldset><legend>Contact</legend><table class="centrerBloc">';
	echo '<tr>	
		<td><label for="materiel_groupe">Groupe <span class="rouge gras">*</span></label></td>';
	echo '  <td><select id="materiel_groupe" name="share[groupe]" >';

	for ($i=0;$i<sizeof($_SESSION['correspondance']['groupe']);$i++)
	{
		if($_SESSION['share']['groupe']==$_SESSION['correspondance']['groupe'][$i])
		{	
			echo '<option value="'.$_SESSION['correspondance']['groupe'][$i].'" selected>'.$_SESSION['correspondance']['groupe'][$i].'</option>';
		}
		else echo '<option value="'.$_SESSION['correspondance']['groupe'][$i].'">'.$_SESSION['correspondance']['groupe'][$i].'</option>';
	}

	echo '</tr>';

	echo '<tr>
		<td><label for="materiel_contact">Personne &agrave; contacter <span class="rouge gras">*</span></label></td>
	      	<td><input type=text id="materiel_contact" name="share[contact]" ';
	if(isset($_SESSION[ 'share' ][ 'contact' ]))
		echo 'value="'.$_SESSION[ 'share' ][ 'contact' ].'" ';
	echo '/></td>
	     </tr>';

	echo '<tr>
		<td><label for="materiel_tel">T&eacute;l&eacute;phone <span class="rouge gras">*</span></label></td>
	      	<td><input type=text id="materiel_tel" name="share[telephone]" ';
	if(isset($_SESSION[ 'share' ][ 'telephone' ]))
		echo 'value="'.$_SESSION[ 'share' ][ 'telephone' ].'" ';
	echo '/></td>
	      </tr>';

	echo '<tr>
		<td><label for="materiel_email">Email <span class="rouge gras">*</span></label></td>
	      	<td><input type=text id="materiel_email" name="share[email]" ';
	if(isset($_SESSION[ 'share' ][ 'email' ]))
		echo 'value="'.$_SESSION[ 'share' ][ 'email' ].'" ';
	echo '/></td>
	      </tr>';
	echo '</table></fieldset>';
	
	if(isset($message_demande))
		echo '<table class="centrerBloc"><tr><td class="centrer gras">'.$message_demande.'</td></tr></table>';

	if($_SESSION[ 'share' ][ 'page' ] == 3)
	{
		echo '<table class="centrerBloc">
			<tr>
			    <td colspan=2 class="centrer" ><input type=submit name="share[enregistrer_modif]" value="Enregistrer les modifications"/><input type=submit name="share[annuler]" value="Annuler"/></td>
	     		</tr>
		     </table>';
	}
	else echo '<table class="centrerBloc"><tr>
		<td colspan=2 class="centrer" ><input type=submit name="share[enregistrer]" value="Enregistrer la fiche"/><input type=submit name="share[annuler]" value="Annuler"/></td>
	     </tr>
	</table><br/>';
}

/*********************************************************************************
***************************  L3 - Inventaire *************************************
**********************************************************************************/

elseif($_SESSION[ 'share' ][ 'page' ] == 4 AND $_POST[ 'share' ][ 'inventaire' ])
{
	//Bouton "Revenir à la recherche" (soumet le formulaire et renvoie par defaut sur la recherche)
	echo '<input type=submit value="Revenir &agrave la recherche" name="share[annuler]" id="btn_retour_recherche" class="btn_bleu" />';	

	echo '<table id="share_inventaire">';
	echo '	<tr class="centrer gras">
			<td>Libell&eacute; <span class="rouge gras">*</span></td>
			<td>Dispo <span class="rouge gras">*</span></td>
			<td>Utilisation</td>
			<td>Description</td>
			<td>Groupe <span class="rouge gras">*</span></td>
			<td>Contact <span class="rouge gras">*</span></td>
			<td>&#9742 <span class="rouge gras">*</span></td>
			<td>@ <span class="rouge gras">*</span></td>
		</tr>';
	$i=0;
	while($i<10)
	{
		echo '<tr>
			<td><input name="share[inventaire]['.$i.'][libelle]" ';
		if(isset($_SESSION[ 'share' ][ 'inventaire' ][$i]['libelle']))
			echo 'value="'.$_SESSION[ 'share' ][ 'inventaire' ][$i]['libelle'].'" ';
		echo ' /></td>
			<td><select name="share[inventaire]['.$i.'][disponibilite]">';
		if(isset($_SESSION[ 'share' ][ 'inventaire' ][$i]['disponibilite']))
		{
			if($_SESSION[ 'share' ][ 'inventaire' ][$i]['disponibilite'] == 1)
				echo '<option value="1" selected>Oui</option><option value="0">Non</option>';
			else echo '<option value="1">Oui</option><option value="0" selected>Non</option>';
		}
		else echo '
				<option value="1">Oui</option>
				<option value="0">Non</option>';
		echo '</select>	
			</td>';
		$utilisations=array('Choisir','Souvent','Occasionnellement','Rarement','Jamais');
		echo '<td><select name="share[inventaire]['.$i.'][utilisation]">';
		foreach($utilisations as $u)
		{
			$selected='';
			if(isset($_SESSION[ 'share' ][ 'inventaire' ][$i]['utilisation']) && $_SESSION[ 'share' ][ 'inventaire' ][$i]['utilisation'] == $u)
				$selected='selected';
			echo '<option value="'.$u.'" '.$selected.'>'.$u.'</option>';
		
		}
		echo '</select>	
			</td><td><input name="share[inventaire]['.$i.'][description]" ';
		if(isset($_SESSION[ 'share' ][ 'inventaire' ][$i]['description']))
			echo 'value="'.$_SESSION[ 'share' ][ 'inventaire' ][$i]['description'].'" ';
		echo '/></td>';
		echo '  <td><select name="share[inventaire]['.$i.'][groupe]">';
	
		for ($j=0;$j<sizeof($_SESSION['correspondance']['groupe']);$j++)
		{
			if(isset($_SESSION[ 'share' ][ 'inventaire' ][$i]['groupe']) && $_SESSION[ 'share' ][ 'inventaire' ][$i]['groupe'] ==$_SESSION['correspondance']['groupe'][$j])
			{	
				echo '<option value="'.$_SESSION['correspondance']['groupe'][$j].'" selected>'.$_SESSION['correspondance']['groupe'][$j].'</option>';
			}
			else 
			{
				echo '<option value="'.$_SESSION['correspondance']['groupe'][$j].'">'.$_SESSION['correspondance']['groupe'][$j].'</option>';
			}
		}
		echo '</td>';
		
		echo '<td><input name="share[inventaire]['.$i.'][contact]" ';
		if(isset($_SESSION[ 'share' ][ 'inventaire' ][$i]['contact']))
			echo 'value="'.$_SESSION[ 'share' ][ 'inventaire' ][$i]['contact'].'" ';
		echo '/></td><td><input name="share[inventaire]['.$i.'][telephone]" ';
		if(isset($_SESSION[ 'share' ][ 'inventaire' ][$i]['telephone']))
			echo 'value="'.$_SESSION[ 'share' ][ 'inventaire' ][$i]['telephone'].'" ';
		echo '/></td><td><input name="share[inventaire]['.$i.'][email]" ';
		if(isset($_SESSION[ 'share' ][ 'inventaire' ][$i]['email']))
			echo 'value="'.$_SESSION[ 'share' ][ 'inventaire' ][$i]['email'].'" ';
		echo '/></td></tr>';
		$i++;
	}
	echo '</table>';
	if(isset($message_demande))
		echo '<table class="centrerBloc"><tr><td class="centrer">'.$message_demande.'</td></tr></table>';
	echo '<table class="centrerBloc"><tr><td class="centrer"><input type=submit name=share[enregistrer_inventaire] value="Enregistrer"/></td><td><input type=submit name=share[annuler] value="Annuler"/></td></tr></table><br/>';

}

/*********************************************************************************
********************  -L4- Consultation d'une fiche ******************************
**********************************************************************************/

elseif($_SESSION[ 'share' ][ 'page' ]==2 && isset($_GET[ 'id_materiel' ]))
{
	//Bouton "Revenir à la recherche" (soumet le formulaire et renvoie par defaut sur la recherche)
	echo '<input type=submit value="Revenir &agrave la recherche" id="btn_retour_recherche" class="btn_bleu" />';	

	//if($_SESSION[ 'connection' ][ 'groupe' ] == 'ADMINISTRATION')
	if(in_array($_SESSION[ 'connection' ][ 'utilisateur' ],$gestionnaires_inventaire))
	{
		//Bouton "Supprimer la fiche"
		echo '<input type=submit name=share[supprimer] id="btn_supprimer_fiche" value="Supprimer" class="btn_rouge" onClick="return confirm(\'Etes-vous s&ucirc;r(e) de vouloir supprimer cette fiche?\');"/>';	
	
		//Bouton "Modifier la fiche"
		echo '<input type=submit name=share[modifier] id="btn_modifier_fiche" value="Modifier" class="btn_gris"/>';	
	}
	
	if(!is_numeric($_GET[ 'id_materiel' ]))
	{
		echo '<p class="centrer gras rouge">Erreur lors du chargement de la fiche</p>';
	}
	else//Affichage de la fiche
	{
		include $chemin_connection;
		$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
		mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

		$id_materiel= mysqli_real_escape_string($link,htmlentities($_GET[ 'id_materiel' ]));
		$fiche=ficheById($id_materiel);
		
		echo '<div id="consultation_fiche">';
		echo '<input type=hidden name="share[id_modif]" value="'.$fiche[ 'ID_MATERIEL' ].'" />';
		echo '	<h1 class="h1_share">'.$fiche[ 'LIBELLE' ].'</h1>';
		echo '<h3>Groupe : '.$fiche[ 'GROUPE' ].'</h3>';
		
		if(!empty($fiche[ 'PHOTO' ])) echo '<div id="photo_fiche"><a href="inventaire_pictures/'.$fiche[ 'PHOTO' ].'"><img src="inventaire_pictures/'.$fiche[ 'PHOTO' ].'" /></a></div>';

		echo '<div id="contenu_fiche">';
		echo '<p>'.$fiche[ 'DESCRIPTION' ].'</p><br/>';
		
		if(!empty($fiche[ 'UTILISATION' ])) echo '<p><b>Utilisation :</b> '.$fiche[ 'UTILISATION' ].'</p>';
		if($fiche[ 'DISPONIBILITE' ] == 1) echo '<p class="gras">Etat : <span class="vert">Disponible</span></p>'; else echo '<p class="gras">Etat : <span class="rouge">Non disponible</span></p>';

		echo '<b>Contact</b>
			<ul>
				<li>'.$fiche[ 'NOM_CONTACT' ].'</li>
				<li>&#9742; : '.$fiche[ 'TEL_CONTACT' ].'</li>	
				<li>@ : '.$fiche[ 'EMAIL_CONTACT' ].'</li>
		     	</ul>';	
		echo '</div>';
		echo '</div><br/>';
	}
}
/*********************************************************************************
*************************  -M1- Barre de recherche *******************************
**********************************************************************************/
else
{	
	init_fiche();

	//On lance la recherche
	$fiches=recherche_fiches($_SESSION[ 'rech_materiel' ][ 'mots_cles' ],$_SESSION[ 'rech_materiel' ][ 'groupe' ],$_SESSION['share']['limitebasse'],$_SESSION['share']['nb_par_page']);

	//if($_SESSION[ 'connection' ][ 'groupe' ] == 'ADMINISTRATION')
	if(in_array($_SESSION[ 'connection' ][ 'utilisateur' ],$gestionnaires_inventaire))
	{
		echo '<div id="bloc_btn_administration">';
		echo '<input type=submit name="share[new_fiche]" value="Nouvelle fiche" />';
		//echo '<input type=submit name="share[inventaire]" value="Inventaire" />';
		echo '</div>';
	}
	

	echo '<br/><div id="share_search"><table><tr><td>';
	echo '<img src="images/loupe.png" /> <input type=text autofocus="autofocus" placeholder="Recherche" name=share[mots_cles] ';
	if(isset($_SESSION[ 'rech_materiel' ][ 'mots_cles' ]))
		echo 'value="'.$_SESSION[ 'rech_materiel' ][ 'mots_cles' ].'"';
	echo '/></td>';
	
	echo '<td><select name="rech_materiel[groupe]" class="select_style">';

	for ($i=0;$i<sizeof($_SESSION['correspondance']['groupe']);$i++)
	{
		if($_SESSION['rech_materiel']['groupe']==$_SESSION['correspondance']['groupe'][$i])
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

	echo '</td><td><input type=submit name=share[rechercher] value="Rechercher"/></td></tr></table>';
	if($_SESSION['share']['resultats']>1)
		echo '<br/><br/><em id="nb_resultats">'.$_SESSION['share']['resultats'].' r&eacute;sultats trouv&eacute;s</em>';
	else if($_SESSION['share']['resultats']==1)echo '<br/><br/><em id="nb_resultats">'.$_SESSION['share']['resultats'].' r&eacute;sultat trouv&eacute;</em>';
	echo '</div>';



/*********************************************************************************
************************ -M2- Resultats recherche *********************************
**********************************************************************************/
	
	if(!$fiches)
		echo '<p class="centrer gras rouge">Aucun r&eacute;sultat correspondant &agrave; la recherche</p>';
	else
	{
		//Affichage des resultats
		foreach($fiches as $fiche)
		{
			echo '<div class="fiche_materiel" onclick="window.location.href= \'inventaire.php?id_materiel='.$fiche[ 'ID_MATERIEL' ].'\'">
				<h1><img src="images/';//inventaire.php?sid='.$sid.'&id_materiel

			if($fiche[ 'DISPONIBILITE' ] == 0) echo 'point_rouge.png" title="Non disponible" alt="Non disponible - " > '; else echo 'point_vert.png" title="Disponible" alt="Disponible - " > ';
			echo 	$fiche[ 'LIBELLE' ].'</h1>';//.' - '.$fiche[ 'PERTINENCE' ].'</h1>
			echo	'<h3>('.$fiche[ 'GROUPE' ].')</h3>';
			
			if(!empty($fiche[ 'PHOTO' ]))
				echo '<img class="photo_materiel" src="inventaire_pictures/'.$fiche[ 'PHOTO' ].'" />';
			else echo '<img class="photo_materiel" src="images/no_photo.gif" alt="Pas d\'image" />';

			echo '<p>'.$fiche[ 'DESCRIPTION' ].'</p>';
			if(!empty($fiche[ 'UTILISATION' ]))
				echo '<em><u>Utilisation :</u> '.$fiche[ 'UTILISATION' ].'</em>';
			echo '</div>';
		}
	}

	$page=floor($_SESSION['share']['limitebasse']/$_SESSION['share']['nb_par_page'])+1;
	$nb_page = ceil($_SESSION['share']['resultats']/$_SESSION['share']['nb_par_page']);
	$nb_affich=$_SESSION['share']['nb_par_page'];
	if ($page==$nb_page && $_SESSION['share']['resultats'] <$_SESSION['share']['nb_par_page']*$nb_page)
	$nb_affich=$_SESSION['share']['resultats']%$_SESSION['share']['nb_par_page'];
	
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
	echo '</table><br/>';
}

//////////////PIED DE PAGE/////////////////
include "pied_page.php";
?>
