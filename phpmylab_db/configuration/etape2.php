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
	 * Etape 2 de la configuration de phpMyLab.
	 *
	 * Cette page permet de renseigner les variables relatives a la base de donnees utilisee pour l'application.
	 *
	 * Date de création : 17 Avril 2012<br/>
	 * Date de dernière modification : 17 Avril 2012
	 * @version 1.2.0
	 * @author Cedric Gagnevin <cedric.gagnevin@laposte.net>
	 * @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.html
	 * @copyright CNRS (c) 2015
	 * @package phpMyLab
	 */
	
	/*********************************************************************************
	 ******************************  PLAN     ****************************************
	 *********************************************************************************/
	
	//    | -A- Stockage des variables de l'etape 2 dans des variables de session
	//    | -B- HTML
	
	
/***********************************************************************************************************
*************** A- Stockage des variables de l'etape 2 dans des variables de session ***********************
***********************************************************************************************************/
	session_start();
	if(!isset($_SESSION[ 'etape2' ])) $_SESSION[ 'etape2' ] = array();
	
	// Si les pré-requis ne sont pas respectés
	if(empty($_SESSION[ 'requirements' ]) OR $_SESSION[ 'requirements' ] != 1)
	{
		echo '<p class="rouge centrer gras">La configuration requise n\'est pas respect&eacute;e ! Cliquer <a href="index.php">ici</a> pour voir ce qu\'il vous manque.';
		exit;
	}
	
	
	//Si la base est bien cree, que les champs sont bien renseignes et que l'utilisateur veut passer a l'etape suivante : on le redirige vers etape 3
	if(!empty($_SESSION[ 'etape2' ][ 'isFinished' ]) && $_SESSION[ 'etape2' ][ 'isFinished' ] == 1 && isset($_POST[ 'validEtap3' ]))
	{
		//Redirection vers l'etape 3
		header('Location: etape3.php');
		echo 'la redirection a pas fonctionnee';
	}
	
	//Si l'utilisateur revient sur l'etape 2 et qu'il modifie un des champs : on teste si les nouveaux parametres conviennent en indiquand l'etape comme non terminee
	if(isset($_SESSION[ 'etape2' ]) && (!empty($_POST[ 'serveur' ]) && isset($_SESSION[ 'etape2' ][ 'serveur' ]) && ($_SESSION[ 'etape2' ][ 'serveur' ] != $_POST[ 'serveur' ])) || (!empty($_POST[ 'user' ]) && ($_SESSION[ 'etape2' ][ 'user' ] != $_POST[ 'user' ])) || (isset($_POST[ 'pwd' ]) && ($_SESSION[ 'etape2' ][ 'pwd' ] != $_POST[ 'pwd' ])) || (!empty($_POST[ 'base' ]) && ($_SESSION[ 'etape2' ][ 'base' ] != $_POST[ 'base' ])))
	{	
		$_SESSION[ 'etape2' ][ 'isFinished' ] = 0;
	}
	
	// Si les champs sont renseignes et que l'on clique sur TESTER 
	if(!empty($_POST[ 'serveur' ]) && !empty($_POST[ 'user' ]) && isset($_POST[ 'pwd' ]) && !empty($_POST[ 'base' ]) && (isset($_POST[ 'tester' ]) OR isset($_POST[ 'remplir_base' ])) ) 
	{
		
		//TEST DE LA CONNEXION A LA BASE
		//------- Test de l'existance de la database --------
		$connect = mysqli_connect($_POST[ 'serveur' ],$_POST[ 'user' ],$_POST[ 'pwd' ]);
		if(!$connect)
			$connexion = 0;
		else $connexion = 1;
			
		if($connexion == 1)
		{
			$showdb=mysqli_query($connect,"SHOW DATABASES LIKE '".$_POST[ 'base' ]."'");
			if (!$resultsd2 = mysqli_fetch_array($showdb))
					$databaseExist = 0;
			else $databaseExist = 1;
		}
		
		//------- Chargement du script --------
		if($databaseExist == 0)
		{
			mysqli_query($connect,"CREATE DATABASE ".$_POST[ 'base' ]);
			mysqli_close($connect);
			//$db_selected = mysql_select_db($_SESSION[ 'etape2' ][ 'base' ], $connect);
			
			$link = mysqli_connect($_POST[ 'serveur' ],$_POST[ 'user' ],$_POST[ 'pwd' ],$_POST[ 'base' ]);
			
			$sql_file=file_get_contents("script.sql");
			$tablesCreated = mysqli_multi_query($link,$sql_file); //Creation des tables
			if($tablesCreated != false)
				$base_test = 1;
			mysqli_close($link);
		}
		  
		if(isset($databaseExist) && $databaseExist == 1 && isset($_POST[ 'remplir_base' ])) //La base existe déjà
		{
			$link = mysqli_connect($_POST[ 'serveur' ],$_POST[ 'user' ],$_POST[ 'pwd' ],$_POST[ 'base' ]);
			
			$sql_file=file_get_contents("script.sql");
			$tablesCreated = mysqli_multi_query($link,$sql_file); //Creation des tables
			if($tablesCreated != false)
				$base_test = 2;
			else $base_test = 0;
			mysqli_close($link);
			
		}
		
	}

	//Les champs sont renseignes et le test de la connexion a la base est OK -> etape 2
	if(!empty($base_test) && ($base_test == 1 OR $base_test == 2))
	{
		//Variable correspondant au serveur
		$_SESSION[ 'etape2' ][ 'serveur' ] = $_POST[ 'serveur' ];
		
		//Variable correspondant à l'utilisateur
		$_SESSION[ 'etape2' ][ 'user' ] = $_POST[ 'user' ];
		
		//Variable correspondant au mot de passe
		$_SESSION[ 'etape2' ][ 'pwd' ] = $_POST[ 'pwd' ];
		
		//Variable correspondant à la base de donnees
		$_SESSION[ 'etape2' ][ 'base' ] = $_POST[ 'base' ];
		
		
		//Variable indiquant que l'etape 2 a ete completee
		$_SESSION[ 'etape2' ][ 'isFinished' ] = 1;	
	}


/***********************************************************************************************************
 ********************************************* B- HTML *****************************************************
 ***********************************************************************************************************/

	header("Content-Type: text/html; charset=iso-8859-1");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
	<link rel="shortcut icon" type="image/x-icon" href="images/phpmylab.ico" />
	<link rel="stylesheet" href="style_config.css">
	<title>Etape 2 - Configuration de la base de donnees</title>
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
		<h1>Etape 2 - Configuration de la base de donn&eacute;es MySQL</h1>

		<p class="obligatoire">* Champs obligatoires</p>

		<form action="<?php echo $_SERVER[ 'PHP_SELF' ]; ?>" method="POST">
			<table id="tab_etape2">
				<tr>
					<td>
						<label for="serveur">Serveur</label> <span class="obligatoire">*</span>
					</td>
					<td>
						<input type=text id="serveur" name="serveur" required <?php if(isset($_POST[ 'serveur' ])) echo 'value="'.$_POST[ 'serveur' ].'"'; else if(isset($_SESSION[ 'etape2' ][ 'serveur' ])) echo 'value="'.$_SESSION[ 'etape2' ][ 'serveur' ].'"'; else echo 'value="localhost"'; ?> />
					</td>
					<td class="indication">
						Emplacement de la base MySQL
					</td>
				</tr>
				<tr>	
					<td>
						<label for="user">Utilisateur</label> <span class="obligatoire">*</span>
					</td>
					<td>
						<input type=text id="user" name="user" required <?php if(isset($_POST[ 'user' ])) echo 'value="'.$_POST[ 'user' ].'"'; else if(isset($_SESSION[ 'etape2' ][ 'user' ])) echo 'value="'.$_SESSION[ 'etape2' ][ 'user' ].'"'; else echo 'value="root"'; ?> />
					</td>
					<td class="indication">
						Le nom d'utilisateur
					</td>
				</tr>
				<tr>
					<td>
						<label for="pwd">Mot de passe</label>
					</td>
					<td>
						<input type=password id="pwd" name="pwd" <?php if(isset($_POST[ 'pwd' ])) echo 'value="'.$_POST[ 'pwd' ].'"'; else if(isset($_SESSION[ 'etape2' ][ 'pwd' ])) echo 'value="'.$_SESSION[ 'etape2' ][ 'pwd' ].'"';?> />
					</td>
					<td class="indication">
						Le mot de passe
					</td>
				</tr>
				<tr>
					<td>
						<label for="base">Base</label> <span class="obligatoire">*</span>
					</td>
					<td>
						<input type=text id="base" name="base" required <?php if(isset($_POST[ 'base' ])) echo 'value="'.$_POST[ 'base' ].'"'; else if(isset($_SESSION[ 'etape2' ][ 'base' ])) echo 'value="'.$_SESSION[ 'etape2' ][ 'base' ].'"'; else echo 'value="phpmylabdb"'; ?> />
					</td>
					<td class="indication">
						Le nom de la base de donn&eacute;es
					</td>
				</tr>
			</table>
			<br/>
			<table width=100%>	
				<tr>
					<td class="centrer">
						<?php 
						    if(!isset($_SESSION[ 'etape2' ][ 'isFinished' ]) OR $_SESSION[ 'etape2' ][ 'isFinished' ] == 0 OR !isset($base_test) OR $base_test == 0)
							echo '<input type=submit value="Cr&eacute;er la base" name="tester" />';
						?>
					</td>
				</tr>
				<tr>		
					<td class="centrer">
						<?php
							if(!empty($_SESSION[ 'etape2' ][ 'isFinished' ]) && $_SESSION[ 'etape2' ][ 'isFinished' ] == 1)
							{
								if(isset($base_test) && $base_test == 1)
									echo '<p class="vert">La base de donn&eacute;es a &eacute;t&eacute; cr&eacute;&eacute;e avec succ&egrave;s !</p>';
								else if(isset($base_test) && $base_test == 2)
									echo '<p class="vert">La base de donn&eacute;es "'.$_POST[ 'base' ].'" sera utilis&eacute;e</p>';
								      else echo '<p class="vert">OK</p>';
							}
							else
							{
								if(isset($connexion) && $connexion == 0 && !empty($_POST[ 'serveur' ]) && !empty($_POST[ 'user' ]) && isset($_POST[ 'pwd' ]) && !empty($_POST[ 'base' ]))
									echo '<p class="rouge centrer">Connexion impossible</p>';
								if(isset($tablesCreated) && !$tablesCreated)
									echo '<p class="rouge centrer">Probl&egrave;me lors de la cr&eacute;ation des tables</p>';
								if(isset($databaseExist) && $databaseExist == 1 && (!isset($base_test) OR $base_test == 0))
								{
									echo '<p class="rouge">La base de donn&eacute;es "'.$_POST[ 'base' ].'" existe d&eacute;j&agrave;.<br/> Pour utiliser quand m&ecirc;me la base "'.$_POST[ 'base' ].'",
									      cliquez sur <input type="submit" name="remplir_base" value="Utiliser '.$_POST[ 'base' ].'" /> </p>';
								}						
							}
						?>
					</td>
				</tr>
			</table>

			<table class="case_bouton">
				<tr>
					<td id="etape_precedente">
						<input type=button value="Revenir &agrave; l'&eacute;tape 1" class="bouton" onclick="javascript:location.href='etape1.php'" />
					</td>
					<td id="etape_suivante">
						<input type=submit value="Passer &agrave; l'&eacute;tape 3" name="validEtap3" <?php if(!empty($_SESSION[ 'etape2' ][ 'isFinished' ]) && $_SESSION[ 'etape2' ][ 'isFinished' ] == 1) echo 'class="bouton"'; else echo 'class="bouton opacityMin"'; ?> <?php if(!isset($_SESSION[ 'etape2' ][ 'isFinished' ]) OR $_SESSION[ 'etape2' ][ 'isFinished' ] == 0) if(!isset($base_test) OR $base_test != 1 OR $base_test != 2) echo 'disabled'; ?> />
					</td>
				</tr>
			</table>
		</form>
	</div>
</body>
</html>
