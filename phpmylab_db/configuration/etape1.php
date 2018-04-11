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
	 * Etape 1 de la configuration de phpMyLab.
	 *
	 * Cette page permet de renseigner les variables concernant l'organisme qui utilise l'application.
	 *
	 * Date de création : 17 Avril 2012<br/>
	 * Date de dernière modification : 5 Juillet 2012
	 * @version 1.2.0
	 * @author Cedric Gagnevin <cedric.gagnevin@laposte.net>
	 * @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.html
	 * @copyright CNRS (c) 2015
	 * @package phpMyLab
	 */
	
	/*********************************************************************************
	 ******************************  PLAN     *****************************************
	 *********************************************************************************/
	
	//    | -A- Stockage des variables de l'etape 1 dans des variables de session
	//    | -B- HTML

	
	/*********************************************************************************
	 ****  -A- Stockage des variables de l'etape 1 dans des variables de session *****
	 *********************************************************************************/
	session_start();
	
	// Si les pré-requis ne sont pas respectés
	if(empty($_SESSION[ 'requirements' ]) OR $_SESSION[ 'requirements' ] != 1)
	{
		echo '<p class="rouge centrer gras">La configuration requise n\'est pas respect&eacute;e ! Cliquer <a href="index.php">ici</a> pour voir ce qu\'il vous manque.';
		exit;
	}
	 
	if(!empty($_POST[ 'nom_organisme' ]) && !empty($_POST[ 'lien_organisme' ])
	&& !empty($_POST[ 'directeur' ]) && !empty($_POST[ 'mail_gestion_labo' ]) && !empty($_POST[ 'chemin_mel' ]))
	{
		//Variable correspondant à l'organisme
		$_SESSION[ 'etape1' ][ 'organisme' ] = htmlentities($_POST[ 'nom_organisme' ]);

		//Variable correspondant au lien de l'organisme
		$_SESSION[ 'etape1' ][ 'lien_organisme' ] = htmlentities($_POST[ 'lien_organisme' ]);
		
		//Variable correspondant au nom d'utilisateur du directeur
		$_SESSION[ 'etape1' ][ 'directeur' ] = htmlentities($_POST[ 'directeur' ]);

		//Variable contenant le mail de la gestion du labo
		$_SESSION[ 'etape1' ][ 'mail_gestion_labo' ] = htmlentities($_POST[ 'mail_gestion_labo' ]);
		
		//Variable contenant le chemin des emails a envoyer
		$_SESSION[ 'etape1' ][ 'chemin_mel' ] = htmlentities($_POST[ 'chemin_mel' ]);
		
		//-------------Upload du logo-------------
		if(!empty($_FILES['logo']['name']))
		{
			$message='';
			//Verif qu'il n'y ait pas d'erreur lors de l'upload
			if($_FILES['logo']['error'] > 0) $message = '<p class="gras rouge">Erreur lors du transfert</p>';	
	
			//Controle sur la taille max
			if($_FILES['logo']['size'] > $_POST[ 'MAX_FILE_SIZE' ]) $message = '<p class="gras rouge">Erreur : le fichier est trop gros</p>';	
		
			//Controle de l'extention
			$extension_upload = strtolower(substr(strrchr($_FILES['logo']['name'],'.'),1));
			if(!in_array($extension_upload,array('jpg','jpeg','gif','png'))) $message='<p class="gras rouge">Erreur : Extension non autoris&eacute;e</p>';
	
			//Controle des dimentions
			$maxwidth=100; //Largeur max
			$maxheight=100; //Hauteur max
			$image_sizes = getimagesize($_FILES['logo']['tmp_name']);
			if($image_sizes[0] > $maxwidth OR $image_sizes[1] > $maxheight) $message = '<p class="gras rouge">Erreur : Image trop grande</p>';
	
			//Déplacement du fichier
			if($message=='')
			{
				$destination_fichier="../images/logo.png";
				$transfert_reussi=move_uploaded_file($_FILES['logo']['tmp_name'],$destination_fichier);
				if(!$transfert_reussi) $message = '<p class="gras rouge">Erreur : Echec du transfert de l\'image sur le serveur</p>';
			}
		}

		//Variable indiquant que l'étape 1 a ete completée
		if($message=='') 
		{
			$_SESSION[ 'etape1' ][ 'isFinished' ] = 1;	
	
			//Redirection vers l'étape 2
			header('Location: etape2.php');
			echo "La redirection n'a pas fonctionnee...";
		}
	}
	
	/*********************************************************************************
	 ******************************  -B- HTML ****************************************
	 *********************************************************************************/

	header("Content-Type: text/html; charset=iso-8859-1");
?>


<!DOCTYPE html>
<html lang="fr">
<head>
	<title>Etape 1 - Informations sur l'organisme</title>
	<link rel="shortcut icon" type="image/x-icon" href="images/phpmylab.ico" />
	<link rel="stylesheet" href="style_config.css">
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
		<h1>Etape 1 - Informations sur l'organisme</h1>

		<p class="obligatoire">* Champs obligatoires</p>

		<form action="<?php echo $_SERVER[ 'PHP_SELF' ]; ?>" method="POST" enctype="multipart/form-data">
			<table id="tab_etape1">
				<tr>
					<td>
						<label for="nom_organisme">Nom de l'organisme</label> <span class="obligatoire">*</span> <!--$organisme-->
					</td>
					<td>
						<input type=text id="nom_organisme" name="nom_organisme" required <?php if(isset($_SESSION[ 'etape1' ][ 'organisme' ])) echo 'value="'.$_SESSION[ 'etape1' ][ 'organisme' ].'"'; ?> placeholder="Ma soci&eacute;t&eacute;" />
					</td>
				</tr>
				<tr>	
					<td>
						<label for="lien_organisme">Lien de l'organisme</label> <span class="obligatoire">*</span> <!--$lien_fin_adress-->
					</td>
					<td>
						<input type=text id="lien_organisme" name="lien_organisme" required <?php if(isset($_SESSION[ 'etape1' ][ 'lien_organisme' ])) echo 'value="'.$_SESSION[ 'etape1' ][ 'lien_organisme' ].'"'; ?> placeholder="http://www.masociete.fr" />
					</td>
				</tr>
				<tr>	
					<td>
						<label for="directeur">Login du directeur</label> <span class="obligatoire">*</span> <!--$directeur-->
					</td>
					<td>
						<input type=text id="directeur" name="directeur" required <?php if(isset($_SESSION[ 'etape1' ][ 'directeur' ])) echo 'value="'.$_SESSION[ 'etape1' ][ 'directeur' ].'"'; ?> />
					</td>
				</tr>
				<tr>
					<td>
						<label for="mail_gestion_labo">Email du gestionnaire</label> <span class="obligatoire">*</span> <!--$mel_gestiolab-->
					</td>
					<td>
						<input type=email id="mail_gestion_labo" name="mail_gestion_labo" required <?php if(isset($_SESSION[ 'etape1' ][ 'mail_gestion_labo' ])) echo 'value="'.$_SESSION[ 'etape1' ][ 'mail_gestion_labo' ].'"'; ?> placeholder="gestionnaire@masociete.fr" />
					</td>
					<td class="indication">
						Email du gestionnaire des d&eacute;placements/missions
					</td>
				</tr>
				<tr>	
					<td>
						<label for="chemin_mel">Adresse du serveur</label> <span class="obligatoire">*</span> <!--$chemin_mel-->
					</td>
					<td>
						<input type=text id="chemin_mel" name="chemin_mel" required <?php if(isset($_SESSION[ 'etape1' ][ 'chemin_mel' ])) echo 'value="'.$_SESSION[ 'etape1' ][ 'chemin_mel' ].'"'; ?> />
					</td>
					<td class="indication">
						https://nomserveur.fr
					</td>
				</tr>
				<tr>	
					<td>
						<label for="chemin_mel">Logo de l'organisme</label><!--$logo-->
					</td>
					<td>
						<input type=file id="logo" name="logo" />
						<input type="hidden" name="MAX_FILE_SIZE" value="102400" />
					</td>
					<td class="indication">
						Types : <b>JPG, PNG ou GIF</b><br/>
						&nbsp;&nbsp;&nbsp;Taille max : <b>100ko</b>, Dimensions max : <b>100*100</b>.
					</td>
				</tr>
				<tr>	
					<td colspan="2" class="centrer">
						<?php if(isset($message)) echo $message;?>
					</td>
				</tr>
			</table>
			<table class="case_bouton">
				<tr>
					<td id="etape_precedente">
						<input type=button value="Revenir &agrave; l'accueil" class="bouton" onclick="javascript:location.href='index.php'" />
					</td>
					<td id="etape_suivante">
						<input type=submit value="Passer &agrave; l'&eacute;tape 2" class="bouton" />
					</td>
				</tr>
			</table>
		</form>
	</div>
</body>
</html>
