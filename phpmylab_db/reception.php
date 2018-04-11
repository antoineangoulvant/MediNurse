<?php
//Affichage des erreurs en mode test
include 'config.php';
if($mode_test == 1)
{
	ini_set('display_errors', 1);
	error_reporting(E_ALL);
}

/**
* Page d'accueil du programme, gestion des mots de passe utilisateurs.
*
* La page d'accueil permet de s'enregistrer en tant qu'utilsateur et de choisir le module. L'utilisateur peut également gérer son mot de passe.
*
* Date de création : 10 septembre 2009<br>
* Date de dernière modification :  19 octobre 2015
* @version 3.0.0
* @author Emmanuel Delage, Cedric Gagnevin <cedric.gagnevin@laposte.net>, Benjamin Grosjean, Alexandre Claude
* @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.html
* @copyright CNRS (c) 2015
* @package phpMyLab
*/

/*********************************************************************************
******************************  PLAN     *****************************************
*********************************************************************************/
//    | -A- CAS
//    | -B- Fonctions
//    | -C- Initialisation generale (configuration et php)
//    | -D- Demande d'identifiants 
//    | -E- Gestion des mots de passes
//    | -F- Gestion des variables Correspondance
//    | -G- Gestion des modules
//    | -H- HTML


/*********************************************************************************
*****************************  -A- CAS *******************************************
**********************************************************************************/

//Authentification par CAS
if(isset($_REQUEST[ 'cas' ]))
{
	header('Location: CAS/identification.php');
}

/*********************************************************************************
***********************  -B- Fonctions *******************************************
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
  $mail_sent = mail($to, utf8_decode($subject), $msg, $headers);
 
  ini_restore('sendmail_from');
 
  return $mail_sent;
}

/*********************************************************************************
*************** -C- Initialisation generale (configuration et php) ***************
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
ini_set('session.use_cookies', '1');//La session n'est pas cookisée dans la page reception

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

// Initialize session ID
//$sid = '';
//if (isset($_REQUEST[ 'sid' ])) $sid = substr(trim(preg_replace('/[^a-f0-9]/', '', $_REQUEST[ 'sid' ])), 0, 13);
//if ($sid == '') $sid = uniqid('');

// Start PHP session
//session_id($sid);
session_id();
session_name('phpmylab');
session_start();

if (! isset($_SESSION[ 'connection' ]))
{
	$_SESSION[ 'connection' ][ 'utilisateur' ] = '';
	$_SESSION[ 'connection' ][ 'nom' ] = '';
	$_SESSION[ 'connection' ][ 'prenom' ] = '';
	$_SESSION[ 'connection' ][ 'mot_de_passe' ] = '';
	$_SESSION[ 'connection' ][ 'mot_de_passe_change' ] = '';
	$_SESSION[ 'connection' ][ 'mot_de_passe_new1' ] = '';
	$_SESSION[ 'connection' ][ 'mot_de_passe_new2' ] = '';
	$_SESSION[ 'connection' ][ 'ss' ] = '';
	$_SESSION[ 'connection' ][ 'mel' ] = '';
	$_SESSION[ 'connection' ][ 'groupe' ] = '';
	$_SESSION[ 'connection' ][ 'status' ] = 0;
 	$_SESSION[ 'connection' ][ 'admin' ] = 0;

	$_SESSION[ 'edition' ] =1;
}

if (isset($_REQUEST[ 'connection' ])) //identification
  if (is_array($_REQUEST[ 'connection' ]))
	{//'/[^a-zA-Z0-9_-]/'
	  if (isset($_REQUEST[ 'connection' ][ 'utilisateur' ])) $_SESSION[ 'connection' ][ 'utilisateur' ] = substr(trim(preg_replace('/[^a-zA-Z0-9_-]/', '', $_REQUEST[ 'connection' ][ 'utilisateur' ])), 0, 30);
	  if (isset($_REQUEST[ 'connection' ][ 'mot_de_passe' ])) $_SESSION[ 'connection' ][ 'mot_de_passe' ] = substr(trim($_REQUEST[ 'connection' ][ 'mot_de_passe' ]), 0, 30);
	  if (isset($_REQUEST[ 'connection' ][ 'mot_de_passe_change' ])) $_SESSION[ 'connection' ][ 'mot_de_passe_change' ] = substr(trim($_REQUEST[ 'connection' ][ 'mot_de_passe_change' ]), 0, 30);
	  if (isset($_REQUEST[ 'connection' ][ 'mot_de_passe_new1' ])) $_SESSION[ 'connection' ][ 'mot_de_passe_new1' ] = substr(trim($_REQUEST[ 'connection' ][ 'mot_de_passe_new1' ]), 0, 30);
	  if (isset($_REQUEST[ 'connection' ][ 'mot_de_passe_new2' ])) $_SESSION[ 'connection' ][ 'mot_de_passe_new2' ] = substr(trim($_REQUEST[ 'connection' ][ 'mot_de_passe_new2' ]), 0, 30);
	}

//La déconnection est gérée à l'interieur de chaque module

// Initialize connection
if (!isset($_REQUEST[ 'motdepasse']) && !isset($_REQUEST[ 'mdpoublie']))
	if (($_SESSION[ 'connection' ][ 'utilisateur' ] != '') && ($_SESSION[ 'connection' ][ 'mot_de_passe' ] != '') )
	{
		include $chemin_connection;

		// Connecting, selecting database:
		$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
				or die('Could not connect: ' . mysqli_connect_error());
		mysqli_select_db($link,$mysql_base) or die('Could not select database');

		$query = 'SELECT * FROM T_UTILISATEUR WHERE UTILISATEUR=\''.$_SESSION[ 'connection' ][ 'utilisateur' ].'\'';
		$result = mysqli_query($link,$query) or die('Connection Mysql ; Query failed: ' . mysqli_error());
		if ($result)
		{
			$line = mysqli_fetch_array($result, MYSQL_NUM);
			if ($line[3]==base64_encode($_SESSION[ 'connection' ][ 'mot_de_passe' ]))
			{
				$_SESSION[ 'connection' ][ 'utilisateur' ] = $line[0];
				$_SESSION[ 'connection' ][ 'nom' ] = ucwords(strtolower($line[1]));
				$_SESSION[ 'connection' ][ 'prenom' ] = ucwords(strtolower($line[2]));
				$_SESSION[ 'connection' ][ 'mot_de_passe' ] = $line[3]; 
				$_SESSION[ 'connection' ][ 'ss' ] = $line[4];
				$_SESSION[ 'connection' ][ 'mel' ] = $line[5];
				$_SESSION[ 'connection' ][ 'groupe' ] = $line[6];
				$_SESSION[ 'connection' ][ 'status' ] = $line[7];

				$_SESSION[ 'connection' ][ 'admin' ] = $line[8];

				//init responsable_groupe ici:
				for ($i=1;$i<=$_SESSION[ 'nb_groupe'];$i++)
					if ($_SESSION[ 'connection' ][ 'groupe' ]==$_SESSION[ 'correspondance' ]['groupe'][$i])
					{
						$_SESSION[ 'responsable_groupe']=$_SESSION[ 'correspondance' ]['responsable'][$i];
						$_SESSION[ 'responsable2_groupe']=$_SESSION[ 'correspondance' ]['responsable2'][$i];
						break;
					}
			}
			else 
			{
				session_destroy();
				session_write_close();
				die('Echec de l\'identification... Cliquez <a href="reception.php">ici</a> pour revenir vers la page d\'authentification');
			}
		}
		mysqli_free_result($result);
		mysqli_close($link);
	}

/*****************************************************************************
***********************  -D- Demande d'identifiants **************************
******************************************************************************/
if (! isset($_SESSION[ 'inscription' ])) unset($_SESSION[ 'inscription' ]);
if (isset($_GET["inscription"])) $_SESSION["inscription"]=array();

//Passage des données de la demande d'identifiants en SESSION
if (isset($_POST["prenom"])) $_SESSION["inscription"]["prenom"]=$_POST["prenom"];
if (isset($_POST["nom"])) $_SESSION["inscription"]["nom"]=$_POST["nom"];
if (isset($_POST["login"])) $_SESSION["inscription"]["login"]=$_POST["login"];
if (isset($_POST["groupe"]) && $_POST["groupe"] != 'Choix du groupe') $_SESSION["inscription"]["groupe"]=$_POST["groupe"];
if (isset($_POST["contrat"]) && $_POST["contrat"] != 'Choix du contrat') $_SESSION["inscription"]["contrat"]=$_POST["contrat"];
if (isset($_POST["email"])) $_SESSION["inscription"]["email"]=$_POST["email"];

if(isset($_REQUEST[ 'demande_id' ]))
{
	if(!empty($_POST[ 'prenom' ]))
	{
		if(!empty($_POST[ 'nom' ]))
		{
			if($_POST[ 'groupe' ] != 'Choix du groupe')
			{
				if($_POST[ 'contrat' ] != 'Choix du contrat')
				{
					if(!empty($_POST[ 'email' ]))
					{
						if((isset($captcha) && $captcha == 1 && $_SESSION['securecode'] == strtolower($_POST['code'])) OR (isset($captcha) && $captcha == 0))
						{
							include_once $chemin_connection;
	
							$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
							or die('Could not connect: ' . mysqli_connect_error());
							mysqli_select_db($link,$mysql_base) or die('Could not select database');
							
							$query = "SELECT UTILISATEUR FROM T_UTILISATEUR WHERE ADMIN = 1";
							$result = mysqli_query($link,$query) or die('Connection Mysql ; Query failed: ' . mysqli_error());
							if ($result)
							{						
								//envoyer un mail aux admins
								$subject = "Demande identifiants";
								$TO = '';
								while($login_admin = mysqli_fetch_array($result, MYSQL_BOTH))
								{
									$TO.=$login_admin[0].'@'.$domaine.',';
								}
								$TO = substr($TO,0,-1);
								$mel = "<html><body>";
								$mel .= "Bonjour,<br>".utf8_encode($_SESSION[ 'inscription' ][ 'prenom' ])." ".utf8_encode($_SESSION[ 'inscription' ][ 'nom' ])." voudrait obtenir des identifiants pour accèder à phpMyLab.<br>Suivez le lien <a href='".$chemin_mel."'>".$chemin_mel."</a> pour aller sur phpMyLab.<br><br>";
								$mel .= "--------- Récapitulatif ---------<br><br>Prénom : ".utf8_encode($_SESSION[ 'inscription' ][ 'prenom' ])."<br>Nom : ".utf8_encode($_SESSION[ 'inscription' ][ 'nom' ])."<br>";
								if(!empty($_SESSION[ 'inscription' ][ 'login' ]))
								$mel .= "Login ".$organisme." : ".$_SESSION[ 'inscription' ][ 'login' ]."<br>";
								if(!empty($_SESSION[ 'login_cas' ]))
								$mel .= "Login CAS : ".$_SESSION[ 'login_cas' ]."<br>";
								$mel .= "Groupe : ".utf8_encode($_SESSION[ 'inscription' ][ 'groupe' ])."<br>";
								$mel .= "Type de contrat : ".utf8_encode($_SESSION[ 'inscription' ][ 'contrat' ])."<br>";
								$mel .= "</body></html>";
								
								$mel=utf8_decode($mel);
								if ($mode_test) $TO = $mel_test;
								$isSent=send_mail($TO, $mel, $subject, $_SESSION[ 'inscription' ][ 'email' ], $_SESSION[ 'inscription' ][ 'prenom' ]." ".$_SESSION[ 'inscription' ][ 'nom' ]);
								if($isSent) $messageDemande = '<p class="gras centrer vert">Demande envoy&eacute;e</p>';
								else $messageDemande = "<p class='centrer rouge gras'>Erreur lors de l'envoi de la demande...</p>";
									
							}
							mysqli_close($link);
							unset($_SESSION['securecode']);
						}
						else $messageDemande = '<p class="centrer gras rouge">Code catpcha erron&eacute;...</p>';
					}
					else $messageDemande = '<p class="centrer gras rouge">Veuillez renseigner votre email pour recevoir votre identifiant d&eacute;finitif ainsi que votre mot de passe</p>';
		
				}
				else $messageDemande = '<p class="centrer gras rouge">Veuillez renseigner le type de votre contrat</p>';
			}
			else $messageDemande = '<p class="centrer gras rouge">Veuillez renseigner votre groupe</p>';
		}
		else $messageDemande = '<p class="centrer gras rouge">Veuillez renseigner votre nom</p>';
	}
	else $messageDemande = '<p class="centrer gras rouge">Veuillez renseigner votre pr&eacute;nom</p>';
}




/*********************************************************************************
***********************  -E- Gestion des mots de passes **************************
**********************************************************************************/
//Variable pour afficher la gestion par l'utilisateur des mots de passes:
if (! isset($_SESSION[ 'mdp' ])) $_SESSION[ 'mdp' ] = 0;
if (isset($_GET["mdp"])) $_SESSION["mdp"]=$_GET["mdp"];

$message='';
//Changement de mot de passe:
if (isset($_REQUEST[ 'motdepasse']))
{
if ($_SESSION[ 'connection' ][ 'utilisateur' ] == '') $message='<p class="rouge">Veuillez renseigner le champ "Nom d\'utilisateur".</p>';
elseif ($_SESSION[ 'connection' ][ 'mot_de_passe_change' ] == '') $message='<p class="rouge">Veuillez renseigner le champ "Mot de passe actuel".</p>';
elseif ($_SESSION[ 'connection' ][ 'mot_de_passe_new1' ] == '') $message='<p class="rouge">Veuillez renseigner le champ "Nouveau mot de passe".</p>';
elseif ($_SESSION[ 'connection' ][ 'mot_de_passe_new2' ] == '') $message='<p class="rouge">Veuillez renseigner le champ "Confirmer nouveau mot de passe".</p>';
elseif ($_SESSION[ 'connection' ][ 'mot_de_passe_new2' ] != $_SESSION[ 'connection' ][ 'mot_de_passe_new1' ])
{
	$message='<p class="rouge">Erreur de saisie du nouveau mot de passe.</p>';
	$_SESSION[ 'connection' ][ 'mot_de_passe_new2' ]='';
}
elseif (strlen($_SESSION[ 'connection' ][ 'mot_de_passe_new2' ])<6)
{
	$message='<p class="rouge">Le nouveau mot de passe doit comporter au moins 6 caract&egrave;res.</p>';
	$_SESSION[ 'connection' ][ 'mot_de_passe_new2' ]='';
}
elseif (strlen($_SESSION[ 'connection' ][ 'mot_de_passe_new2' ])>16)
{
	$message='<p class="rouge">Le nouveau mot de passe doit comporter au maximum 16 caract&egrave;res.</p>';
	$_SESSION[ 'connection' ][ 'mot_de_passe_new2' ]='';
}
else //mdp
{
/**
**/
   include $chemin_connection;

   $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
   or die('Could not connect: ' . mysqli_connect_error());
   mysqli_select_db($link,$mysql_base) or die('Could not select database');

   $query = 'SELECT * FROM T_UTILISATEUR WHERE UTILISATEUR=\''.$_SESSION[ 'connection' ][ 'utilisateur' ].'\'';
   $result = mysqli_query($link,$query) or die('Connection Mysql ; Query failed: ' . mysqli_error());
   if ($result)
   {
	$line = mysqli_fetch_array($result, MYSQL_NUM);
	if (!isset($line[2])) $message='<p class="rouge">Nom d\'utilisateur incorrect.</p>';
	elseif ($line[3]==base64_encode($_SESSION[ 'connection' ][ 'mot_de_passe_change' ]))
	{
	   session_destroy();
	   session_write_close();
	   $_SESSION[ 'connection' ][ 'mot_de_passe_new2' ]=base64_encode($_SESSION[ 'connection' ][ 'mot_de_passe_new2' ]);
	   $query2 = 'UPDATE T_UTILISATEUR SET MOTDEPASSE=\''.$_SESSION[ 'connection' ][ 'mot_de_passe_new2' ].'\' WHERE UTILISATEUR=\''.$_SESSION[ 'connection' ][ 'utilisateur' ].'\'';
	   $result2 = mysqli_query($link,$query2) or die('Mise a jour mot de passe ; echec de la requete: ' . mysqli_error());
	   $message='<p class="vert">Changement de mot de passe effectu&eacute;, redirection vers authentification...</p>';
	   //mysqli_free_result($result2);

	//envoyer un mail de rappel
	   $subject = "phpMyLab: Changement de mot de passe";
	   $TO = $_SESSION[ 'connection' ][ 'utilisateur' ].'@'.$domaine;
	   $mel = "<html><body>";
	   $mel .= "Bonjour,<br/>";
	   $mel .= "Votre mot de passe a été modifié, vos nouveaux paramètres de connexion sont:<br/><br/>";
	   $mel .= "Nom d'utilisateur : ".$_SESSION[ 'connection' ][ 'utilisateur' ]."<br/>";
	   $mel .= "Mot de passe : ".base64_decode($_SESSION[ 'connection' ][ 'mot_de_passe_new2' ])."<br/><br/>";
	   $mel .= "Bien cordialement,<br/>";
	   $mel .= "Les Webmestres.";
	   $mel .= "</body></html>";
	
	   $mel=utf8_decode($mel);
	   send_mail($TO, $mel, $subject, "mail@automatique.fr", "NE PAS REPONDRE");		

	   $_SESSION[ 'connection' ][ 'utilisateur' ] = '';
	   $_SESSION[ 'connection' ][ 'mot_de_passe_change' ] = '';
	   $_SESSION[ 'connection' ][ 'mot_de_passe_new1' ] = '';
	   $_SESSION[ 'connection' ][ 'mot_de_passe_new2' ] = '';
		
	   //Une fois le mot de passe changé, on redirige l'utilisateur sur la page d'authentification au bout de 3s
	   header ("Refresh: 3;URL=".$_SERVER[ 'PHP_SELF' ]);
	}
	else $message='<p class="rouge">Mot de passe actuel incorrect.</p>';
   }
   mysqli_free_result($result);
   mysqli_close($link);
}//fin du else //mdp
}//fin if (isset($_REQUEST[ 'motdepasse']))

if (isset($_REQUEST[ 'mdpoublie']) && isset($_REQUEST[ 'mdpoublie']) )
{
	if ($_SESSION[ 'connection' ][ 'utilisateur' ] == '') 
		$message='<p class="rouge">Veuillez renseigner le champ "Nom d\'utilisateur".</p>';
	else 
	{
		/**
		 **/
		include $chemin_connection;

		$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
		or die('Could not connect: ' . mysqli_connect_error());
		mysqli_select_db($link,$mysql_base) or die('Could not select database');

		$query = 'SELECT * FROM T_UTILISATEUR WHERE UTILISATEUR=\''.$_SESSION[ 'connection' ][ 'utilisateur' ].'\'';
		$result = mysqli_query($link,$query) or die('Connection Mysql ; Query failed: ' . mysqli_error());
		if ($result)
		{
			$line = mysqli_fetch_array($result, MYSQL_NUM);
			if (!isset($line[2])) $message='<p class="rouge">Nom d\'utilisateur incorrect.</p>';
			else
			{
				//envoyer un mail de rappel
				$subject = "phpMyLab: Rappel de mot de passe";
				$TO = $_SESSION[ 'connection' ][ 'utilisateur' ].'@'.$domaine;
 
				$mel = "<html><body>";
				$mel .= "Bonjour,<br/>";
				$mel .= "vos paramètres de connexion sont:<br/><br/>";
				$mel .= "Nom d'utilisateur : ".$_SESSION[ 'connection' ][ 'utilisateur' ]."<br/>";
				$mel .= "Mot de passe : ".base64_decode($line[3])."<br/><br/>";
				$mel .= "Vous pouvez modifier votre mot de passe en cliquant sur le lien \"Gestion des mots de passe\" de la page d'accueil.<br/><br/>";
				$mel .= "Bien cordialement,<br/>";
				$mel .= "L'administrateur.";
				$mel .= "</body></html>";
	
				$mel=utf8_decode($mel);
				if(send_mail($TO, $mel, $subject, "mail@automatique.fr", "NE PAS REPONDRE"))
					$message='<p class="vert">Mot de passe envoy&eacute; par mail.</p>';	

				$_SESSION[ 'connection' ][ 'utilisateur' ] = '';
				$_SESSION[ 'connection' ][ 'mot_de_passe' ] = '';
				$_SESSION[ 'connection' ][ 'mot_de_passe_new1' ] = '';
				$_SESSION[ 'connection' ][ 'mot_de_passe_new2' ] = '';
			}
		}
		mysqli_free_result($result);
		mysqli_close($link);
	}
}//fin if (isset($_REQUEST[ 'mdpoublie']))

/*********************************************************************************
********************  -F- Gestion des variables Correspondance *******************
**********************************************************************************/
/**/
if (! isset($_SESSION[ 'correspondance' ]))
{
/**
**/
   include $chemin_connection;

   $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
   or die('Could not connect: ' . mysqli_connect_error());
   mysqli_select_db($link,$mysql_base) or die('Could not select database');

   $query2 = 'SELECT * FROM T_CORRESPONDANCE ORDER BY GROUPE';
   $result2 = mysqli_query($link,$query2) or die('Requete de selection des pieces associees a la demande: ' . mysqli_error());
   //$_SESSION['groupe'][0]="Equipe, contrat ou service";
   $_SESSION[ 'correspondance' ]['groupe'][0]="Equipe, contrat ou service";
   $_SESSION[ 'correspondance' ]['responsable'][0]=-1;
   $_SESSION[ 'correspondance' ]['responsable2'][0]=-1;
   $_SESSION[ 'correspondance' ]['administratif'][0]=-1;
   $_SESSION[ 'correspondance' ]['administratif2'][0]=-1;
   $i=1;
   while ($line2 = mysqli_fetch_array($result2, MYSQL_NUM)) {
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

   mysqli_free_result($result2);
   mysqli_close($link);
}

/*********************************************************************************
***********************  -G- Gestion des modules *********************************
**********************************************************************************/
if (!isset($_SESSION["ident_dem"])) $_SESSION["ident_dem"]=0;//demande de mission
if (!isset($_SESSION["ident_dec"])) $_SESSION["ident_dec"]=0;//demande de congés
if (!isset($_SESSION["ident_deexp"])) $_SESSION["ident_deexp"]=0;//demande d'expeditions

if (isset($_GET["dem"])) $_SESSION["ident_dem"]=$_GET["dem"];
if (isset($_GET["dec"])) $_SESSION["ident_dec"]=$_GET["dec"];
if (isset($_GET["deexp"])) $_SESSION["ident_deexp"]=$_GET["deexp"];

if (! isset($_SESSION[ 'choix_module' ])) $_SESSION[ 'choix_module' ] = $modules[0];
//ajout pour pre diriger l'utilisateur vers le module. 
if (isset($_GET["dec"])) $_SESSION[ 'choix_module' ]="CONGES";
if (isset($_GET["dem"])) $_SESSION[ 'choix_module' ]="MISSIONS";
if (isset($_GET["deexp"])) $_SESSION[ 'choix_module' ]="EXPEDITIONS";

if (isset($_REQUEST[ 'missions_bouton'])) $_SESSION[ 'choix_module' ]="MISSIONS";
if (isset($_REQUEST[ 'conges_bouton'])) $_SESSION[ 'choix_module' ]="CONGES";
if (isset($_REQUEST[ 'planning_bouton'])) $_SESSION[ 'choix_module' ]="PLANNING";
if (isset($_REQUEST[ 'expeditions_bouton'])) $_SESSION[ 'choix_module' ]="EXPEDITIONS";
//if (isset($_REQUEST[ 'expeditions_bouton'])) $_SESSION[ 'choix_module' ]="INVENTAIRE";
if (isset($_REQUEST[ 'share_bouton'])) $_SESSION[ 'choix_module' ]="SHARE";
if (isset($_REQUEST[ 'community_bouton'])) $_SESSION[ 'choix_module' ]="COMMUNITY";

if(isset($_REQUEST[ 'connexion'])) $_SESSION[ 'choix_module' ]=$_REQUEST[ 'module'];

if ($_SESSION[ 'connection' ][ 'status' ] > 0)
{
   for ($i=0;$i<sizeof($modules);$i++)
   {
	if ($_SESSION['choix_module']==$modules[$i]) 
	{
		$self=$_SERVER[ 'PHP_SELF' ];
		$chemin_module=substr($self,0,-strlen(strrchr($self,"/")));
		$chemin_module.='/'.strtolower($_SESSION['choix_module']).'.php';
		$demande='';
		if ($_SESSION["ident_dem"]!=0) $demande='?dem='.$_SESSION["ident_dem"].'#DEM_MISSION';
		if ($_SESSION["ident_dec"]!=0) $demande='?dec='.$_SESSION["ident_dec"].'#DEMANDE_CONGES';
		if ($_SESSION["ident_deexp"]!=0) $demande='?deexp='.$_SESSION["ident_deexp"].'#DEMANDE_EXPEDITIONS';
//		header('location:'.$chemin_module.'?sid=' . $sid.$demande);
		header('location:'.$chemin_module.$demande);
	}
   }
}

/*********************************************************************************
******************************  -H- HTML      ************************************
**********************************************************************************/
// Charset header
header('Content-Type: text/html; charset=' . $charset);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<title>PHPMYLAB</title>

<link rel="shortcut icon" type="image/x-icon" href="images/phpmylab.ico" />

<?php
	/////////////////////////// JAVASCRIPT //////////////////////////
?>
<script  type="text/javascript" src="javascript.js"></script>
<script  type="text/javascript" src="mdp.js"></script>
<script type="text/javascript">
	function refreshCaptcha()
	{
		// On crée ici une valeur aléatoire
		// Math.ceil récupère le nombre entier supérieur le plus proche
		// Math.random génère un nombre aléatoire entre 0 et 1
		var alea = Math.ceil( Math.random() * 1000000 );

		// En change vraiment la source de l'image forçant ainsi le
		// navigateur à recharger l'image plutot que de la charger
		// à l'aide du cache
		document.getElementById('imageCaptcha').src = "securitecode.php?" + alea;
	}
</script>

<?php
	/////////////////////////// CSS (habillage) //////////////////////////
?>
<link rel="stylesheet" href="style.css" >

</head>

<body onLoad="clock()">
<div id="conteneur">
<form name="form1" id="form1" method="post" action="<?php echo $_SERVER[ 'PHP_SELF' ];  ?>">

<?php
	/////////////// Affichage non connecte /////////////////////////////////////////
?>
<table id="header">
	<tr>
		<td>	
			<img src="images/logo.png" name="logo"/>
		</td>
		<td>
			<p><span class="gras padding">PhpMyLab</span> - Interface de gestion administrative.</p>
		</td>
		<td>
			<a href="http://phpmylab.in2p3.fr" target="_blank" title="Aide en ligne de PhpMyLab"><img src="images/aide.png" name="aide" height="30" alt="Aide"/></a>
		</td>
	</tr>
</table>
<?php
	// Check requirements
	$requirements_ok = true;
	$required_version = '4.1';
	if (version_compare(phpversion(), $required_version) < 0)
	{
		printf("<strong>PHP too old</strong>: You're running PHP %s, but <strong>PHP %s is required</strong> to run conges.php!<br/>\n", phpversion(), $required_version);
		$requirements_ok = false;
	}
	
	if (! function_exists('session_start'))
	{
		echo "<strong>PHP has no session support</strong>: Your PHP installation doesn't have the <a href=\"http://www.php.net/manual/fr/ref.session.php\">Session module</a> installed which is required to run conges.php!<br/>\n";
		$requirements_ok = false;
	}
	
	// Login form
	if ($requirements_ok)
	{
	if(isset($_SESSION["inscription"])) //Page d'inscription
		{
			session_destroy();
			session_write_close();
			echo '<h1 class="centrer">Demande d\'identifiants</h1>';
			echo '<table id="inscription">
				<tr>
					<td><label for="prenom">Pr&eacute;nom</label></td>
					<td><input type="text" name="prenom" id="prenom" ';

			if(isset($_SESSION["inscription"]["prenom"])) echo 'value="'.$_SESSION["inscription"]["prenom"].'"';
			echo ' /></td>
				</tr>
				<tr>
					<td><label for="nom">Nom</label></td>
					<td><input type="text" name="nom" id="nom" ';

			if(isset($_SESSION["inscription"]["nom"])) echo 'value="'.$_SESSION["inscription"]["nom"].'"';

			echo ' /></td>
				</tr>
				<tr>
					<td><label for="login">Login '.$organisme.'</label></td>
					<td><input type="text" name="login" id="login" placeholder="Optionnel" ';

			if(isset($_SESSION["inscription"]["login"])) echo 'value="'.$_SESSION["inscription"]["login"].'"';

			echo ' /></td>
				</tr>
				<tr>
					<td><label for="groupe">Groupe</label></td>
					<td><select name="groupe" id="groupe">
					<option value="Choix du groupe">Choix du groupe</option>';

			//On remonte tous les groupes de la base
			include_once $chemin_connection;

			$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
			or die('Could not connect: ' . mysqli_connect_error());
			mysqli_select_db($link,$mysql_base) or die('Could not select database');
			
			$query = 'SELECT GROUPE FROM T_CORRESPONDANCE ORDER BY GROUPE';
			$result = mysqli_query($link,$query) or die('Connection Mysql ; Query failed: ' . mysqli_error());

			while($groupe = mysqli_fetch_array($result, MYSQL_NUM))
			{
				if(isset($_SESSION["inscription"]["groupe"]) && $_SESSION["inscription"]["groupe"] == $groupe[0]) 
					echo '<option value="'.$groupe[0].'" selected>'.$groupe[0].'</option>';
				else echo  '<option value="'.$groupe[0].'">'.$groupe[0].'</option>';
			}
			echo '</select>';
			mysqli_close($link);

			echo '	</tr>
				<tr>
					<td><label for="contrat">Type de contrat</label></td>
					<td><select name="contrat" id="contrat">';
					foreach($type_contrats as $contrat)
					{
						if(isset($_SESSION["inscription"]["contrat"]) && $_SESSION["inscription"]["contrat"] == $contrat[0]) 
							echo '<option value="'.$contrat[0].'" selected>'.$contrat[0].'</option>';
						else echo  '<option value="'.$contrat[0].'">'.$contrat[0].'</option>';
					}
				echo ' </select>		
				</tr>
				<tr>
					<td><label for="email">Email de contact</label></td>
					<td><input type="email" name="email" id="email" placeholder="Email de contact" ';

				if(isset($_SESSION["inscription"]["email"])) echo 'value="'.$_SESSION["inscription"]["email"].'"';

				echo ' /></td>
				</tr>';
				
			if(isset($captcha) && $captcha == 1)
				echo '<tr>
					<td colspan="2" id="captcha" class="centrer" style="padding-left:20px">
						<br/><img src="securitecode.php" alt="Code de sécurité" id="imageCaptcha"/>
						<img src="images/refresh.png" title="Rafraichir l\'image" alt="Refresh" onclick="refreshCaptcha();" id="refresh" />
					</td>
				</tr>
				<tr>
					<td colspan="2" class="centrer">
						<label for="code">Code ? </label>
						<input name="code" id="code" type="text" size="7" required />
					</td>
				</tr>';

			echo '<tr>
					<td class="centrer" colspan="2">
						<br/><input type="submit" name="demande_id" value="Envoyer ma demande" />
						<br/>
						<a href="'.$_SERVER[ 'PHP_SELF' ]. '" name="retour" class="lien_jaune">Retour &agrave; l\'identification</a>
					</td>
				</tr>
			</table>';
			if(isset($messageDemande))
			      echo '<p colspan=2 class="gras centrer">'.$messageDemande.'</p>';
			
		}
		else if ($_SESSION[ 'mdp' ] == 0)
		{
			echo '<div id="authentification">
			<table>
			<tr>
			<td colspan=3 class="centrer">
				<img src="images/cle.png" height="80" name="cle"/>
				<img src="images/authentification.png" alt="Authentification" name="authentification" />
			</td>
			</tr>
			<tr>
			<td class="paddingLeft">
			<label for="connection[utilisateur]">Nom d\'utilisateur</label>
			</td>
			<td>
			<input type="text" id="connection[utilisateur]" name="connection[utilisateur]" 
			value="'.$_SESSION[ 'connection' ][ 'utilisateur' ].'" title="Entrez votre Nom d\'utilisateur" autofocus/>
			</td>
			</tr>
			<tr>
			<td class="paddingLeft">
			<label for="connection[mot_de_passe]">Mot de passe</label>
			</td>
			<td>
			<input type="password" name="connection[mot_de_passe]" id="connection[mot_de_passe]" 
			value="'.$_SESSION[ 'connection' ][ 'mot_de_passe' ].'" title="Entrez votre mot de passe" />
			</td></tr>';
			if(isset($_GET["dec"]) OR isset($_GET["dem"]) OR isset($_GET["deexp"]))
			{
				echo '<tr><td class="centrer" colspan=3>';
				if (isset($_GET["dem"]) && in_array("MISSIONS",$modules))
				{
					echo '			<a href="javascript:void(0)">
					<input type="submit" id="valider" name="missions_bouton" value="Missions"/>
					</a>';
				}
				elseif (isset($_GET["dec"]) && in_array("CONGES",$modules))
				{
					echo '			<a href="javascript:void(0)">
					<input type="submit" name="conges_bouton" value="Cong&eacute;s">
					</a>';
				}
				elseif (isset($_GET["deexp"]) && in_array("EXPEDITIONS",$modules))
				{
					echo '			<a href="javascript:void(0)">
					<input type="submit" name="expeditions_bouton" value="Exp&eacute;ditions">
					</a>';
				}
				echo '</td></tr>';
			}	
			else
			{	
					echo '<tr><td class="centrer" colspan=2 ><br/>';

					echo '<input type="submit" value="Connexion" name="connexion" id="btn_connexion"/>';

					echo '<select name="module" id="select_reception">';
					foreach($modules as $module)
					{
						if($module=="INVENTAIRE" AND $visibilite_inventaire != 1)
						{
							//Ne pas afficher l'onglet
						}
						else 
						{
						echo '<option value="'.$module.'"  >'.$module.'</option>';
						}
					}
		
					echo '</select>';	
	
					echo '</td></tr>';
				
			}

			echo '<tr>
			<td colspan=3 class="centrer gestionMdp">
			<a href="'.$_SERVER[ 'PHP_SELF' ]. '?mdp=1" class="lien_jaune" >Changer son mot de passe</a>
			<a href="'.$_SERVER[ 'PHP_SELF' ]. '?inscription=1" class="lien_jaune">S\'inscrire</a>';
			//<a href="'.$_SERVER[ 'PHP_SELF' ]. '?mdpoublie=1" onclick="document.getElementById(\'form1\').submit();">Mot de passe oubli&eacute;?</a>
			echo '<input type="submit" value="Mot de passe oubli&eacute;?" name="mdpoublie" class="lien_jaune"/>
			</td>
			</tr>';
			if(isset($cas) && $cas == 1) //Si on a choisi CAS a l'installation
				echo '<tr><td colspan=3 class="centrer"><input type="submit" value="Authentification par CAS" name="cas" /></td></tr>';
			if (isset($message) && $message!='') 
				echo '<tr><td colspan=3 class="gras centrer">'.$message.'</td></tr>';
			echo '	</table>';
			echo '</div>';
		}
		else if ($_SESSION[ 'mdp' ] == 1) //interface changer mdp
		{
			echo '<div id="authentification">';
			echo '<table id="changerMpd">';
			//champ "Nom"
			echo '<tr><td><label for="connection[utilisateur]">Nom d\'utilisateur</label>';
			echo '</td><td colspan=2>';
			echo '<input type="text" name="connection[utilisateur]" id="connection[utilisateur]" value="';
			echo $_SESSION[ 'connection' ][ 'utilisateur' ]; 
			echo '" title="Entrez votre nom d\'utilisateur" autofocus/>';
			echo '</td></tr>';
			//mettre le focus sur le champ "Nom" /!\ REMPLACE PAR AUTOFOCUS SUR LE CHAMP
			//echo '<script type="text/javascript">';
//			echo 'document.forms[ "form1" ].elements[ "connection[utilisateur]" ].focus();';
//			echo '</script>';
			//champ "Mot de passe"
			echo '<tr><td><label for="connection[mot_de_passe_change]">Mot de passe actuel</label>';
			echo '</td><td colspan=2>';
			echo '<input type="password" name="connection[mot_de_passe_change]" id="connection[mot_de_passe_change]" value="';
			echo $_SESSION[ 'connection' ][ 'mot_de_passe_change' ]; 
			echo '" title="Entrez votre mot de passe" />';
			echo '</td></tr>';
			//champ "Nouveau Mot de passe1"
			echo '<tr><td><label for="connection[mot_de_passe_new1]">Nouveau mot de passe</label>';
			echo '</td><td>';
			echo '<input type="password" name="connection[mot_de_passe_new1]" OnKeyUp="showComplexity();" id="connection_mot_de_passe_new1" value="';
			echo $_SESSION[ 'connection' ][ 'mot_de_passe_new1' ]; 
			echo '" title="Entrez le nouveau mot de passe" />';
			echo '</td><td class="indicateur">
			<span id="faible" title="S&eacute;curit&eacute; faible. Votre mot de passe est un peu trop simple, nous vous conseillons d\'en choisir un qui contienne &agrave; la fois des chiffres et des lettres.">
			faible
			</span>
			<span id="moyen" title="S&eacute;curit&eacute; suffisante. Votre mot de passe est suffisamment complexe pour emp&ecirc;cher quelqu\'un de le deviner.">
			moyen
			</span>
			<span id="fort" title="S&eacute;curit&eacute; &eacute;lev&eacute;e. Votre mot de passe est suffisamment complexe pour ne pas &ecirc;tre facilement retrouv&eacute; !">
			fort
			</span>
			</td></tr>';
			//champ "Nouveau Mot de passe2"
			echo '<tr><td><label for="connection[mot_de_passe_new2]">Confirmer nouveau mot de passe</label>';
			echo '</td><td>';
			echo '<input type="password" name="connection[mot_de_passe_new2]" onKeyUp="comparePassword();" id="connection_mot_de_passe_new2" value="';
			echo $_SESSION[ 'connection' ][ 'mot_de_passe_new2' ]; 
			echo '" title="Entrez le nouveau mot de passe" />';
			echo '</td>';
			echo '<td class="indicateur"><span id="different">diff&eacute;rent</span><span id="identique">identique</span></td></tr>'; //Indicateur different/identique
			echo '<tr><td colspan=3 class="centrer">';
			echo '<input type="submit" value=" Changer de mot de passe " name="motdepasse" title="Cliquez pour changer de mot de passe"/>';
			//echo '<br/><input type="submit" value=" Recevoir son mot de passe " name="mdpoublie"  accesskey="m" title="Cliquez pour recevoir son de mot de passe par mail" />';
			echo '<br/><a href="'.$_SERVER[ 'PHP_SELF' ]. '?mdp=0" name="retour" class="lien_jaune">Retour &agrave; l\'identification</a>';
			echo '</td></tr>';
			if ($message!='') echo '<tr><td colspan=3 class="gras centrer">'.$message.'</td></tr>';
			echo '</table>';
			echo '</div>';
		}
//		else //si affiche interface mot de passe oublie
//		{
//			echo '<div id="authentification">';
//			echo 'Mot de passe oublie';
//			echo '</div>';
//		}
	}
	echo '</form>';
	?>

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
</div>
<div id="version">
	<table>
		<tr>
			<td>
				<?php
					echo '<p>PhpMyLab v'.$version.' &copy; 2015 - <a href="http://phpmylab.in2p3.fr/cnil.php" target="_blank">Mentions CNIL</a></p>';
				?>
			</td>
		</tr>
	</table>
</div>
</body>
</html>
