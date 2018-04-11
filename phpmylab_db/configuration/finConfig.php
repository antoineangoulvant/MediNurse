<?php

if (file_exists("config.php"))
{
	//Affichage des erreurs en mode test
	include '../config.php';
	if($mode_test == 1)
	{
		ini_set('display_errors', 1);
		error_reporting(E_ALL);
	}
}

	/**
	 * Fin de la configuration de phpMyLab.
	 *
	 * Cette page annonce la fin de la configuration de phpMyLab et
	 * permet a la personne qui installe l'application de se 
	 * rediriger vers la page reception.php
	 *
	 * Date de création : 17 Avril 2012<br/>
	 * Date de dernière modification : 30 septembre 2015
	 * @version 1.3.0
	 * @author Cedric Gagnevin <cedric.gagnevin@laposte.net>, Emmanuel Delage (CNRS)
	 * @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.html
	 * @copyright CNRS (c) 2015
	 * @package phpMyLab
	 */
	
	/*********************************************************************************
	 ******************************  PLAN     ****************************************
	 *********************************************************************************/
	
	//    | -A-  Ecriture des fichiers de configuration
	//    | -B-  Génération du .htaccess
	//    | -C-  HTML
	//    | -C1- Configuration terminee, redirection vers la page reception.php
	//    | -C2- Etape(s) non completee, redirection vers les etapes concernees



	
	session_start();
	
	// Si les pré-requis ne sont pas respectés
	if(empty($_SESSION[ 'requirements' ]) OR $_SESSION[ 'requirements' ] != 1)
	{
		echo '<p class="rouge centrer gras">La configuration requise n\'est pas respect&eacute;e ! Cliquer <a href="index.php">ici</a> pour voir ce qu\'il vous manque.';
		exit;
	}
	
	
	
	if(isset($_POST[ 'phpMyLab' ]))
	{
		session_destroy();
		//Redirection vers ../index.php
		header('Location: ../index.php');
	}
	
	if(isset($_POST[ 'aide' ]))
	{
		//session_destroy();
		//Redirection vers http://phpmylab.in2p3.fr/
		header('Location: http://phpmylab.in2p3.fr/');
	}
	
	
	/*********************************************************************************
	 ******************************  Fonction     ****************************************
	 *********************************************************************************/

	//http://dev.kanngard.net/Permalinks/ID_20050507183447.html	
	
	function strleft($s1, $s2) 
	{ 
		return substr($s1, 0, strpos($s1, $s2)); 
	}

	function selfURL()
	{ 
		$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
		$protocol = strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s;
		$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
		return $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI']; 
	}


	
	/***********************************************************************************************************
	 *********************** A-  Ecriture des fichiers de configuration ****************************************
	 ***********************************************************************************************************/
	
	if(isset($_SESSION[ 'isFinished' ]) && $_SESSION[ 'isFinished' ] == 1) 
	{
		//----------------DEBUT | Ecriture du fichier connectionPHPMYLABDB.php ----------------
			if(!$fichier = @fopen('../connectionPHPMYLABDB.php', 'w')) 
			{
				echo 'Probleme lors de l\'ecriture du fichier de connexion &agrave; la base...';
				exit;
			}
			
			fwrite($fichier,"<?php\n// Parametres generes par ".$_SERVER['PHP_SELF']." le ".date("D j F - G:i")."\n\n");
			//Ecriture de la variable correspondant au serveur
			fwrite($fichier, "//Nom du serveur\n\$mysql_location='".$_SESSION[ 'etape2' ][ 'serveur' ]."';\n");
			//Ecriture de la variable correspondant à l'utilisateur
			fwrite($fichier, "//Utilisateur\n\$mysql_user='".$_SESSION[ 'etape2' ][ 'user' ]."';\n");
			//Ecriture de la variable correspondant au mot de passe (si elle est définie)
			fwrite($fichier, "//Mot de passe\n\$mysql_password='".$_SESSION[ 'etape2' ][ 'pwd' ]."';\n");
			//Ecriture de la variable correspondant à la base de données
			fwrite($fichier, "//Nom de la base de donnees\n\$mysql_base='".$_SESSION[ 'etape2' ][ 'base' ]."';\n?>");
		
			//Fermeture du fichier
			fclose($fichier);
		
		//---------------- FIN | Ecriture du fichier connectionPHPMYLABDB.php ----------------
	
	
		//----------------DEBUT | Ecriture du fichier config.php ----------------
			if(!$fichier = @fopen('../config.php', 'w')) 
			{
				// Pose probleme pour la redirection
				echo 'Probleme lors de l\'ecriture du fichier de configuration...';
				exit;
			}
			fwrite($fichier,"<?php\n// Parametres generes par ".$_SERVER['PHP_SELF']." le ".date("D j F - G:i")."\n\n");
			
			//Chemin de la connexion a la base
			fwrite($fichier, "//Chemin de la connexion a la base\n\$chemin_connection='connectionPHPMYLABDB.php';\n");
				
			// en-tête du fichier
			fwrite($fichier, "/**\n* Ensemble des variables de configuration.\n*\n* @package phpMyLab\n*/\n\n// Version du logiciel\n\$version = trim(substr('\$Revision: 3.0.0 \$', 10, -1));\n");
			
			//Ecriture de la variable correspondant à l'organisme
			fwrite($fichier, "//organisme de rattachement (en haut et en bas de page)\n\$organisme='".$_SESSION[ 'etape1' ][ 'organisme' ]."';\n");
			
			//Ecriture de la variable contenant le lien de l'organisme
			fwrite($fichier, "//Liens du site de l'organisme\n\$lien_organisme='".$_SESSION[ 'etape1' ][ 'lien_organisme' ]."';\n");
			
			//Ecriture de la variable contenant le nom d'utilisateur du directeur de l'organisme
			fwrite($fichier, "//nom d'utilisateur du directeur\n\$directeur='".$_SESSION[ 'etape1' ][ 'directeur' ]."';\n");
			
			//Ecriture de la variable contenant le mail de la gestion du labo
			fwrite($fichier, "//mail de la liste de diffusion du service administratif\n\$mel_gestiolab='".$_SESSION[ 'etape1' ][ 'mail_gestion_labo' ]."';\n");
			
			//Ecriture des variables contenant le chemin des emails a envoyer
			fwrite($fichier, "//chemin pour le lien vers une mission dans les mails\n\$chemin_mel='".$_SESSION[ 'etape1' ][ 'chemin_mel' ]."';\n\$chemin_mel.=substr(\$_SERVER[ 'PHP_SELF' ],0,-strlen(strrchr(\$_SERVER[ 'PHP_SELF' ],'/')));\n\$chemin_mel.='/reception.php';\n");

			//Ecriture de la variable correspondant au domaine pour le serveur de mail
			fwrite($fichier, "//Domaine pour le serveur de mail (ex: toto@domaine)\n\$domaine='".$_SESSION[ 'etape4' ][ 'domaine' ]."';\n");		
			
			//Ecriture de la variable correspondant l'email des webmasters
			fwrite($fichier, "//adresse electronique des webmasters\n\$web_adress='".$_SESSION[ 'etape4' ][ 'mail_web' ]."';\n");
			
			//Ecriture de la liste des annees pour les calendriers
			$annees='array("----",';
			for($i=$_SESSION[ 'etape4' ][ 'annee_debut' ] ; $i <= $_SESSION[ 'etape4' ][ 'annee_fin' ] ; $i++)
			{
				$annees = $annees.'"'.$i.'",';
			}
			
			$annees = substr($annees,0,-1);
			$annees = $annees.');';	
			
			fwrite($fichier, "//liste des annee pour le calendrier\n\$annees=".$annees."\n");		
			fwrite($fichier, "//annee actuelle\n\$annee_en_cours=date('Y');\n");		

			//On indique si on veut utiliser le CAS
			if($_SESSION[ 'etape4' ][ 'cas' ] == 1)
				fwrite($fichier, "\$cas = 1;//Indique que l'on veut utiliser CAS\n");
			else fwrite($fichier, "\$cas = 0;//Indique que l'on ne veut pas utiliser CAS\n");

			//On indique que l'on veut utiliser un captcha
			if($_SESSION[ 'etape4' ][ 'captcha' ] == 1)
				fwrite($fichier, "\$captcha = 1;//Indique que l'on veut utiliser un captcha\n");
			else fwrite($fichier, "\$captcha = 0;//Indique que l'on ne veut pas utiliser un captcha\n");

			//Ecriture du mode : test/production
			if($_SESSION[ 'etape4' ][ 'mode' ] == 'test')
				fwrite($fichier, "//mode de test ou de production\n\$mode_test=1;//mode de test\n\$mel_test='".$_SESSION[ 'etape4' ][ 'mel_test' ]."';//en mode test tous les mails sont envoyes la.\n");	
			else fwrite($fichier, "//mode de test ou de production\n\$mode_test=0;//mode de production\n\$mel_test='';//en mode test tous les mails sont envoyes là. A renseigner si on passe en mode test.\n");

			//Modules
			$module = 'array(';
			if($_SESSION[ 'etape3' ][ 'modules' ][ 'missions' ] == 1)
				$module.= '"MISSIONS",';
			if($_SESSION[ 'etape3' ][ 'modules' ][ 'conges' ] == 1)
				$module.= '"CONGES",';
			if($_SESSION[ 'etape3' ][ 'modules' ][ 'planning' ] ==1)
				$module.= '"PLANNING",';
			if($_SESSION[ 'etape3' ][ 'modules' ][ 'expeditions' ] ==1)
				$module.= '"EXPEDITIONS",';
			if($_SESSION[ 'etape3' ][ 'modules' ][ 'inventaire' ] ==1)
				$module.= '"INVENTAIRE",';
			if($_SESSION[ 'etape3' ][ 'modules' ][ 'community' ] ==1)
				$module.= '"COMMUNITY",';
				
			if($module != 'array(')
				$module = substr($module,0,-1);
			$module .= ')';

			//Ecriture de la variable correspondant au choix des modules
			fwrite($fichier, "//liste de modules du logiciel\n//Supprimez un module de cette liste selon vos besoins\n//ne modifiez pas l'orthographe\n//Attention, le module PLANNING decoule des modules MISSIONS et/ou CONGES\n\$modules=".$module.";\n");

	
			fwrite($fichier, "\n/////////////////////////////////////////////////////////\n// Variables module MISSIONS\n");

			//Variable correspondant aux vehicules disponibles
			$i=0;
				$vehicules='array("Choisir un v&eacute;hicule",';
			while(!EMPTY($_SESSION[ 'etape3' ][ 'vehicules'][$i]))
			{
				$vehicules=$vehicules.'"'.$_SESSION[ 'etape3' ][ 'vehicules'][$i].'",';
				$i++;
			}
			
			if($vehicules != 'array(' )
				$vehicules = substr($vehicules,0,-1);
			$vehicules .= ')';
				
			fwrite($fichier, "//liste de moyens de transport\n\$vehicules=".$vehicules.";\n");

			
			//Variable correspondant aux differents objets de mission
			$i=0;
			$objets='array("Choisir un objet",';
			while(!EMPTY($_SESSION[ 'etape3' ][ 'objets'][$i]))
			{
				$objets=$objets.'"'.$_SESSION[ 'etape3' ][ 'objets'][$i].'",';
				$i++;
			}
			
			if($objets != 'array(' )
				$objets = substr($objets,0,-1);
			$objets .= ')';
				
			
			fwrite($fichier, "//liste pour champs objet pour aide comme \$vehicules\n\$objets=".$objets.";\n");
		
				
			//Ecriture des liens d'aide
			$i=0;
			while(!EMPTY($_SESSION[ 'etape3' ][ 'liens' ][ 'libelle' ][$i]) && !EMPTY($_SESSION[ 'etape3' ][ 'liens' ][ 'libelle' ][$i]))
			{
				fwrite($fichier, "\$libelle_lien".($i+1)."='".$_SESSION[ 'etape3' ][ 'liens'][ 'libelle' ][$i]."';//texte du bouton\n\$adresse_lien".($i+1)."='".$_SESSION[ 'etape3' ][ 'liens'][ 'adresse' ][$i]."';//adresse du lien\n");
				$i++;
			}

				
			fwrite($fichier, "\n/////////////////////////////////////////////////////////\n// Variables module CONGES\n");

			//Variable correspondant aux types de contrats avec le nb de jours de conges associés
			$i=0;
			$contrats='array(array("Choix du contrat",0),';
			while(!EMPTY($_SESSION[ 'etape3' ][ 'types_contrats' ][ 'type' ][$i]) && !EMPTY($_SESSION[ 'etape3' ][ 'types_contrats' ][ 'jours' ][$i]))
			{
				$contrats=$contrats.'array("'.$_SESSION[ 'etape3' ][ 'types_contrats' ][ 'type' ][$i].'",'.$_SESSION[ 'etape3' ][ 'types_contrats' ][ 'jours' ][$i].'),';
				$i++;
			}
			
			if($contrats != 'array(' )
				$contrats = substr($contrats,0,-1);
			$contrats .= ')';
		
			//Ecriture des types de contrats/nb de jours de conges associés
			fwrite($fichier, "//liste des differents type contrats avec le nombre de conge associe\n\$type_contrats=".$contrats.";");

			//Ecriture des conges avec solde
/*			$i=0;
			$conges_avec_solde='array(';
			while(!EMPTY($_SESSION[ 'etape3' ][ 'conges_avec_solde'][$i]))
			{
				$conges_avec_solde=$conges_avec_solde.'"'.$_SESSION[ 'etape3' ][ 'conges_avec_solde'][$i].'",';
				$i++;
			}
			
			if($conges_avec_solde != 'array(' )
				$conges_avec_solde = substr($conges_avec_solde,0,-1);
			$conges_avec_solde .= ')';
			fwrite($fichier, "//liste de type de conges (avec solde) non modifiable!\n\$conge_type=".$conges_avec_solde.";\n");
*/				
			fwrite($fichier, "//liste de type de conges (avec solde) non modifiable!\n");
			fwrite($fichier, "\$conge_type=array(\"Cong&eacute;s annuels\",\"Compte &eacute;pargne temps\",\"R&eacute;cup&eacute;ration\",\"Autres...\");\n");
				
				
			//Ecriture des conges sans solde
			$i=0;
			$conges_sans_solde='array(';
			while(!EMPTY($_SESSION[ 'etape3' ][ 'conges_sans_solde'][$i]))
			{
				$conges_sans_solde=$conges_sans_solde.'"'.$_SESSION[ 'etape3' ][ 'conges_sans_solde'][$i].'",';
				$i++;
			}
			
			if($conges_sans_solde != 'array(' )
				$conges_sans_solde = substr($conges_sans_solde,0,-1);
			$conges_sans_solde .= ')';
			
			fwrite($fichier, "//liste de type de conges (sans solde)\n\$conge_sans_solde=".$conges_sans_solde.";\n");

			fwrite($fichier, "\n/////////////////////////////////////////////////////////\n// Variables module EXPEDITIONS\n");

			//Ecriture des gestionnaires des expeditions
			if($_SESSION[ 'etape3' ][ 'modules' ][ 'expeditions' ] == 1)
			{
				$i=0;
				$gestionnaires='array(';
				while(!EMPTY($_SESSION[ 'etape3' ][ 'gestionnaires_expeditions' ][$i]))
				{
					$gestionnaires=$gestionnaires.'"'.$_SESSION[ 'etape3' ][ 'gestionnaires_expeditions' ][$i].'",';
					$i++;
				}
				
				if($gestionnaires != 'array(' )
					$gestionnaires = substr($gestionnaires,0,-1);
				$gestionnaires .= ')';
				
				fwrite($fichier, "//liste des gestionnaires des expeditions\n\$gestionnaires_expeditions=".$gestionnaires.";\n");
			}

			fwrite($fichier, "\n/////////////////////////////////////////////////////////\n// Variables module INVENTAIRE\n");

			//Ecriture des gestionnaires d'inventaire
			if($_SESSION[ 'etape3' ][ 'modules' ][ 'inventaire' ] == 1)
			{
				$i=0;
				$gestionnaires='array(';
				while(!EMPTY($_SESSION[ 'etape3' ][ 'gestionnaires_inventaire' ][$i]))
				{
					$gestionnaires=$gestionnaires.'"'.$_SESSION[ 'etape3' ][ 'gestionnaires_inventaire' ][$i].'",';
					$i++;
				}
				
				if($gestionnaires != 'array(' )
					$gestionnaires = substr($gestionnaires,0,-1);
				$gestionnaires .= ')';
				
				fwrite($fichier, "//liste des gestionnaires de inventaire\n\$gestionnaires_inventaire=".$gestionnaires.";\n");

				if(isset($_SESSION[ 'etape3' ][ 'visibilite_inventaire' ]) && $_SESSION[ 'etape3' ][ 'visibilite_inventaire' ]==1)
					fwrite($fichier, "\$visibilite_inventaire=1;//Tout le monde a acces a inventaire\n");
				else fwrite($fichier, "\$visibilite_inventaire=0;//Seuls les gestionnaires ont acces a inventaire\n");
			}

			fwrite($fichier, "\n/////////////////////////////////////////////////////////\n// Variables module COMMUNITY\n");

			//Variable correspondant aux Categories:
			$i=0;
			$categories='array(';
			while(!EMPTY($_SESSION[ 'etape3' ][ 'categories'][$i]))
			{
				$categories=$categories.'"'.$_SESSION[ 'etape3' ][ 'categories'][$i].'",';
				$i++;
			}
			
			if($categories != 'array(' )
				$categories = substr($categories,0,-1);
			$categories .= ')';
				
			fwrite($fichier, "//Catégories:\n\$categories_community=".$categories.";\n");
			//$categories_community = array('Hotels','Restaurants','Vacances','Annonces','Scientifique','Divers');

			//Ecriture des gestionnaires d'community
			if($_SESSION[ 'etape3' ][ 'modules' ][ 'community' ] == 1)
			{
				$i=0;
				$gestionnaires='array(';
				while(!EMPTY($_SESSION[ 'etape3' ][ 'gestionnaires_community' ][$i]))
				{
					$gestionnaires=$gestionnaires.'"'.$_SESSION[ 'etape3' ][ 'gestionnaires_community' ][$i].'",';
					$i++;
				}
				
				if($gestionnaires != 'array(' )
					$gestionnaires = substr($gestionnaires,0,-1);
				$gestionnaires .= ')';
				
				fwrite($fichier, "//liste des gestionnaires de Community\n\$gestionnaires_community=".$gestionnaires.";\n");

			}
			//$gestionnaires_community = array("gestionnaire1");

			
			//On indique que la configuration est terminee
			fwrite($fichier, "\n//Indique que la configuration est terminee\n\$configuration_terminee = 1;\n?>");
			
			//Fermeture du fichier
			fclose($fichier);

			if (file_exists("../config.php"))
			    $isWritten = 1;
	
		//---------------- FIN | Ecriture du fichier config.php ----------------

	}
	/***********************************************************************************************************
	 *********************** B-  Génération du .htaccess pour expedition_attachments/ ****************************************
	 ***********************************************************************************************************/


		//----------------DEBUT | Ecriture du fichier /expedition_attachments/.htaccess ----------------
			if(!$fichier = @fopen('../expedition_attachments/.htaccess', 'w')) 
			{
				echo 'Probleme lors de l\'ecriture du fichier expedition_attachments/.htaccess ...';
				exit;
			}
			
			$chemin=substr(selfURL(),0,-27);//27 est le nombre de caractere pour "/configuration/etape4.php"
			$chemin.='expeditions.php';
			//$chemin ='https://clrweb2.in2p3.fr/intra/phpmylab_stable/expeditions.php';

			//Ecriture de la variable correspondant au serveur
			fwrite($fichier, "Options -Indexes \nSetEnvIfNoCase Referer \"^".$chemin."\" yepa\n<Files ~ \"\.(pdf|jpg|jpeg|png)$\">\norder deny,allow\nallow from env=yepa\ndeny from all\n</Files>");


			//Fermeture du fichier
			fclose($fichier);
		
		//---------------- FIN | Ecriture du fichier /expedition_attachments/.htaccess ----------------

		//----------------DEBUT | Ecriture du fichier /inventaire_pictures/.htaccess ----------------
			if(!$fichier = @fopen('../inventaire_pictures/.htaccess', 'w')) 
			{
				echo 'Probleme lors de l\'ecriture du fichier inventaire_pictures/.htaccess ...';
				exit;
			}
			
			$chemin=substr(selfURL(),0,-27);//27 est le nombre de caractere pour "/configuration/etape4.php"
			$chemin.='inventaire.php';
			//$chemin ='https://clrweb2.in2p3.fr/intra/phpmylab_stable/expeditions.php';

			//Ecriture de la variable correspondant au serveur
			fwrite($fichier, "Options -Indexes \nSetEnvIfNoCase Referer \"^".$chemin."\" yepa\n<Files ~ \"\.(pdf|jpg|jpeg|png)$\">\norder deny,allow\nallow from env=yepa\ndeny from all\n</Files>");


			//Fermeture du fichier
			fclose($fichier);
		
		//---------------- FIN | Ecriture du fichier /inventaire_pictures/.htaccess ----------------

		//----------------DEBUT | Ecriture du fichier CAS/config_cas.php ----------------
			//On indique si on veut utiliser le CAS
			if($_SESSION[ 'etape4' ][ 'cas' ] == 1)
			{	
				if(!$fichier = @fopen('../CAS/config_cas.php', 'w')) 
				{
					echo 'Probleme lors de l\'ecriture du fichier de configuration de CAS';
					exit;
				}
				fwrite($fichier,"<?php\n// Parametres generes par ".$_SERVER['PHP_SELF']." le ".date("D j F - G:i")."\n\n");
				
				//Cas host
				fwrite($fichier, "//Configuration du serveur CAS\n\$cas_host = '".$_SESSION[ 'etape4' ][ 'cas_host' ]."';\n");

				//Cas port
				fwrite($fichier, "\$cas_port = ".$_SESSION[ 'etape4' ][ 'cas_port' ].";\n");

				//Cas context
				fwrite($fichier, "\$cas_context = '".$_SESSION[ 'etape4' ][ 'cas_context' ]."';\n");

				//Url de retour avec deconnexion CAS
				fwrite($fichier, "\$url_reception = '".$_SESSION[ 'etape4' ][ 'url_reception']."';\n?>");

				//Fermeture du fichier
				fclose($fichier);
			}
		//----------------FIN | Ecriture du fichier CAS/config_cas.php ----------------





	/***********************************************************************************************************
	 ********************************************** C-  HTML ***************************************************
	 ***********************************************************************************************************/

	header("Content-Type: text/html; charset=iso-8859-1");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<link rel="shortcut icon" type="image/x-icon" href="images/phpmylab.ico" />
<link rel="stylesheet" href="style_config.css">
<title>La configuration de phpMyLab est termin&eacute;e</title>
<noscript>
	<div class="noscript">
		<img src="images/attention.png" />
		<p>Attention ! Le javascript est actuellement d&eacute;sactiv&eacute; sur votre navigateur. 
		Vous devez l'activer pour continuer la configuration et pour profiter de l'application de mani&egrave;re optimale.</p>
	</div>
</noscript>
</head>
<body>
	<div id="corps">
	<form action="<?php echo $_SERVER[ 'PHP_SELF' ]; ?>" method="POST">
		<?php	
			
			/***********************************************************************************************************
			**************** C1- Configuration terminee, redirection vers la page reception.php ************************
			***********************************************************************************************************/
			
			if(isset($_SESSION[ 'isFinished' ]) && $_SESSION[ 'isFinished' ] == 1 && isset($isWritten) && $isWritten == 1) // fin de la configuration
			{
				echo '<h1>La configuration de phpMyLab est termin&eacute;e !</h1><br/>
						
					  <p class="vert centrer gras">F&eacute;licitation ! La configuration de phpMyLab a &eacute;t&eacute; g&eacute;n&eacute;r&eacute;e avec succ&egrave;s.</p>
					
					<p class="gras">La configuration a &eacute;t&eacute; g&eacute;n&eacute;r&eacute;e dans les fichiers "config.php", "connectionPHPMYLABDB.php" et "CAS/config_cas.php". De plus, un ".htaccess" a cr&eacute;&eacute; dans le r&eacute;pertoire "expedition_attachments" pour prot&eacute;ger les pi&egrave;ces jointes upload&eacute;s via le module EXPEDITIONS.</p>
					
					<p class="gras">Vous pouvez modifier ces fichiers manuellement ou bien vous rendre dans "/configuration/index.php" pour reconfigurer le logiciel.</p>

					<p class="gras">Par mesure de s&eacute;curit&eacute;, il est recommand&eacute; de modifier les droits d\'acc&egrave;s du r&eacute;pertoire racine, du fichier "connectionPHPMYLABDB.php" et du r&eacute;pertoire "configuration" :</p>

					<textarea readonly cols="80" rows="10">
- [root@clrweb2 intra] chmod 755 phpmylab_db
- [root@clrweb2 intra] chmod 711 phpmylab_db/images
- [root@clrweb2 intra] chmod 755 phpmylab_db/configuration
- [root@clrweb2 intra] chmod 777 phpmylab_db/expedition_attachments
- [root@clrweb2 intra] chmod 711 phpmylab_db/CAS
- [root@clrweb2 intra] chmod 755 phpmylab_db/CAS/config_cas.php
- [root@clrweb2 intra] chmod 755 phpmylab_db/connectionPHPMYLABDB.php
- [root@clrweb2 intra] chmod 755 phpmylab_db/config.php

					</textarea>

					<p class="gras">Vous pouvez d&eacute;sormais vous connecter &agrave; phpMyLab avec le login "logindeladmin" et le mot de passe "mdp!".</p>

					  <table id="boutons_finConfig">
						<tr>
							<td id="etape_suivante">
								<input type="submit" value="Utiliser phpMyLab" name="phpMyLab" class="bouton"/>
								<input type="submit" value="Consulter l\'aide" name="aide" class="bouton"/>
							</td>
						</tr>
					  </table>';
			}
			
			/***********************************************************************************************************
			**************** C2- Etape(s) non completee, redirection vers les etapes concernees *************************
			***********************************************************************************************************/
	 
			else // Une ou plusieurs étapes n'ont pas été renseignées
			{
				echo '<h1>Erreur...</h1><br/>';
				if(!isset($_SESSION[ 'etape1' ][ 'isFinished' ]) || $_SESSION[ 'etape1' ][ 'isFinished' ] != 1)
				{
					echo '<p class="rouge">L\'&eacute;tape 1 n\'a &eacute;t&eacute; compl&eacute;t&eacute;e. Veuillez cliquer <a href="etape1.php">ici</a>
						  pour renseigner l\'&eacute;tape manquante et pour pouvoir finaliser la configuration.</p><br/>';
				}   
				
				if(!isset($_SESSION[ 'etape2' ][ 'isFinished' ]) || $_SESSION[ 'etape2' ][ 'isFinished' ] != 1)
				{
					echo '<p class="rouge">L\'&eacute;tape 2 n\'a &eacute;t&eacute; compl&eacute;t&eacute;e. Veuillez cliquer <a href="etape2.php">ici</a>
						  pour renseigner l\'&eacute;tape manquante et pour pouvoir finaliser la configuration.</p><br/>';
				}	

				if(!isset($_SESSION[ 'etape3' ][ 'isFinished' ]) || $_SESSION[ 'etape3' ][ 'isFinished' ] != 1)
				{
					echo '<p class="rouge">L\'&eacute;tape 3 n\'a &eacute;t&eacute; compl&eacute;t&eacute;e. Veuillez cliquer <a href="etape3.php">ici</a>
						  pour renseigner l\'&eacute;tape manquante et pour pouvoir finaliser la configuration.</p><br/>';
				}	

				if(!isset($_SESSION[ 'etape4' ][ 'isFinished' ]) || $_SESSION[ 'etape4' ][ 'isFinished' ] != 1)
				{
					echo '<p class="rouge">L\'&eacute;tape 4 n\'a &eacute;t&eacute; compl&eacute;t&eacute;e. Veuillez cliquer <a href="etape4.php">ici</a>
						  pour renseigner l\'&eacute;tape manquante et pour pouvoir finaliser la configuration.</p><br/>';
				}      
			}
		?>
	</form>
	</div>
</body>
</html>
