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
	 * Etape 3 de la configuration de phpMyLab.
	 *
	 * Cette page permet de renseigner les variables relatives aux differents modules de l'application.
	 *
	 * Date de création : 17 Avril 2012<br/>
	 * Date de dernière modification : 24 septembre 2015
	 * @version 1.3.0
	 * @author Cedric Gagnevin <cedric.gagnevin@laposte.net>, Emmanuel Delage (CNRS)
	 * @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.html
	 * @copyright CNRS (c) 2015
	 * @package phpMyLab
	 */
	
	/*********************************************************************************
	 ******************************  PLAN     ****************************************
	 *********************************************************************************/
	
	//    | -A-  Stockage des variables de l'etape 3 dans des variables de session
	//    | -A1- Stockage du choix des modules
	//	  | -A2- Stockage des types de contrats/nb jours de conges
	//    | -A3- Stockage des vehicules disponibles pour une mission
	//	  | -A4- Stockage des objets d'une mission
	//    | -A5- Stockage des liens d'aide dans mission
	//	  | -A6- Stockage des conges avec solde dans conge
	//	  | -A7- Stockage des conges sans solde dans conge
	//	  | -A8- Stockage des gestionnaires des expéditions
	//	  | -A9- Stockage des gestionnaires d'inventaire
	//	  | -A10- Stockage community
	//    | -B-  HTML
	//    | -C-  Javascript
	
	
	/***********************************************************************************************************
	 ***************** A- Stockage des variables de l'etape 3 dans des variables de session ********************
	 ***********************************************************************************************************/
	session_start();
	
	// Si les pré-requis ne sont pas respectés
	if(empty($_SESSION[ 'requirements' ]) OR $_SESSION[ 'requirements' ] != 1)
	{
		echo '<p class="rouge centrer gras">La configuration requise n\'est pas respect&eacute;e ! Cliquer <a href="index.php">ici</a> pour voir ce qu\'il vous manque.';
		exit;
	}
	
	
	if((isset($_POST[ 'missions' ]) 
	|| (isset($_POST[ 'conges' ]) && !empty($_POST[ 'type_contrat1' ]) && !empty($_POST[ 'nb_jours1' ]))
	|| (isset($_POST[ 'expeditions' ]) && !empty($_POST[ 'gestionnaire1' ]))
	|| (isset($_POST[ 'inventaire' ]) && !empty($_POST[ 'gestionnaireInventaire1' ]))
	|| (isset($_POST[ 'community' ]) && !empty($_POST[ 'gestionnaireCommunity1' ])))
	&& isset($_POST[ 'etape4' ]))
	{

	/************************************************************************************
	********************* A1- Stockage du choix des modules *****************************
	*************************************************************************************/
		//Variable correspondant aux modules choisis
		$_SESSION[ 'etape3' ][ 'modules' ] = array();
		if(isset($_POST[ 'missions' ])) $_SESSION[ 'etape3' ][ 'modules' ][ 'missions' ] = 1; else $_SESSION[ 'etape3' ][ 'modules' ][ 'missions' ] = 0;
		if(isset($_POST[ 'conges' ])) $_SESSION[ 'etape3' ][ 'modules' ][ 'conges' ] =1; else $_SESSION[ 'etape3' ][ 'modules' ][ 'conges' ] = 0;
		if(isset($_POST[ 'planning' ])) $_SESSION[ 'etape3' ][ 'modules' ][ 'planning' ] = 1; else $_SESSION[ 'etape3' ][ 'modules' ][ 'planning' ] = 0;
		if(isset($_POST[ 'expeditions' ])) $_SESSION[ 'etape3' ][ 'modules' ][ 'expeditions' ] = 1; else $_SESSION[ 'etape3' ][ 'modules' ][ 'expeditions' ] = 0;
		if(isset($_POST[ 'inventaire' ])) $_SESSION[ 'etape3' ][ 'modules' ][ 'inventaire' ] = 1; else $_SESSION[ 'etape3' ][ 'modules' ][ 'inventaire' ] = 0;
		if(isset($_POST[ 'community' ])) $_SESSION[ 'etape3' ][ 'modules' ][ 'community' ] = 1; else $_SESSION[ 'etape3' ][ 'modules' ][ 'community' ] = 0;

	/************************************************************************************
	************* A2- Stockage des types de contrats/nb jours de conges *****************
	*************************************************************************************/
		
		$nb=1;
		$type_contrat="type_contrat".$nb;
		$nb_jours="nb_jours".$nb;
		
		//Variable correspondant aux types de contrats avec le nb de jours de conges associés
		$_SESSION[ 'etape3' ][ 'types_contrats' ] = array();
		$_SESSION[ 'etape3' ][ 'types_contrats' ][ 'type' ] = array();
		$_SESSION[ 'etape3' ][ 'types_contrats' ][ 'jours' ] = array();

		while(!EMPTY($_POST[$type_contrat]) && !EMPTY($_POST[$nb_jours]))
		{
			array_push($_SESSION[ 'etape3' ][ 'types_contrats' ][ 'type' ],htmlentities($_POST[$type_contrat]));
			array_push($_SESSION[ 'etape3' ][ 'types_contrats' ][ 'jours' ],htmlentities($_POST[$nb_jours]));
			$nb=$nb+1;
			$type_contrat="type_contrat".$nb;
			$nb_jours="nb_jours".$nb;
		}
		
	/************************************************************************************
	************* A3- Stockage des vehicules disponibles pour une mission ***************
	*****************************************************************************/		
 
		$vehicules = explode(",",$_POST[ 'vehicules_dispo' ]);
		$_SESSION[ 'etape3' ][ 'vehicules'] = array();
		foreach($vehicules as $vehicule)
		{
			array_push($_SESSION[ 'etape3' ][ 'vehicules'],htmlentities(trim($vehicule)));
		}
		
		
	/************************************************************************************
	******************** A4- Stockage des objets d'une mission **************************
	*************************************************************************************/
		
		$objets = explode(",",$_POST[ 'objets_missions' ]);
		$_SESSION[ 'etape3' ][ 'objets_m'] = array();
		foreach($objets as $objet)
		{
			array_push($_SESSION[ 'etape3' ][ 'objets_m'],htmlentities(trim($objet)));
		}
	/************************************************************************************
	******************* A5- Stockage des liens d'aide dans mission **********************
	*************************************************************************************/

		// Stockage des liens de missions
		$nb=1;
		$lien="lien".$nb;
		$adr_lien="adr_lien".$nb;
		
		//Variable correspondant aux liens d'aide
		$_SESSION[ 'etape3' ][ 'liens' ] = array();
		$_SESSION[ 'etape3' ][ 'liens' ][ 'libelle' ] = array();
		$_SESSION[ 'etape3' ][ 'liens' ][ 'adresse' ] = array();
		
		while(!empty($_POST[$lien]) && !empty($_POST[$adr_lien]))
		{
			array_push($_SESSION[ 'etape3' ][ 'liens' ][ 'libelle' ],htmlentities($_POST[$lien]));
			array_push($_SESSION[ 'etape3' ][ 'liens' ][ 'adresse' ],htmlentities($_POST[$adr_lien]));
			$nb=$nb+1;
			$lien="lien".$nb;
			$adr_lien="adr_lien".$nb;
		}
		

	/************************************************************************************
	***************** A6- Stockage des conges avec solde dans conge *********************
	*************************************************************************************/
		
		$nb=1;
		$conge_type="type_conge_avec_solde".$nb;
		//Variable correspondant aux conges avec solde
		$_SESSION[ 'etape3' ][ 'conges_avec_solde' ] = array();
		
		while(!EMPTY($_POST[$conge_type]))
		{
			array_push($_SESSION[ 'etape3' ][ 'conges_avec_solde'], htmlentities($_POST[$conge_type]));
			$nb=$nb+1;
			$conge_type="type_conge_avec_solde".$nb;
		}
			
	/************************************************************************************
	***************** A7- Stockage des conges sans solde dans conge *********************
	*************************************************************************************/
		
		$conges_sans_solde = explode(",",$_POST[ 'conges_sans_solde' ]);
		$_SESSION[ 'etape3' ][ 'conges_sans_solde'] = array();
		foreach($conges_sans_solde as $conge_sans_solde)
		{
			array_push($_SESSION[ 'etape3' ][ 'conges_sans_solde'],htmlentities(trim($conge_sans_solde)));
		}
		
	/************************************************************************************
	***************** A8- Stockage des gestionnaires des expéditions ********************
	*************************************************************************************/
		
		if(isset($_POST[ 'expeditions' ]))
		{
			$nb=1;
			$gestionnaire="gestionnaire".$nb;
			//Variable correspondant aux gestionnaires des expéditions
			$_SESSION[ 'etape3' ][ 'gestionnaires_expeditions' ] = array();
			
			while(!EMPTY($_POST[$gestionnaire]))
			{
				array_push($_SESSION[ 'etape3' ][ 'gestionnaires_expeditions' ], $_POST[$gestionnaire]);
				$nb=$nb+1;
				$gestionnaire="gestionnaire".$nb;
			}
		}

	/************************************************************************************
	***************** -A9- Stockage des gestionnaires d'inventaire **********************
	*************************************************************************************/
		
		if(isset($_POST[ 'inventaire' ]))
		{
			$nb=1;
			$gestionnaire="gestionnaireInventaire".$nb;
			//Variable correspondant aux gestionnaires d'inventaire
			$_SESSION[ 'etape3' ][ 'gestionnaires_inventaire' ] = array();
			
			while(!EMPTY($_POST[$gestionnaire]))
			{
				array_push($_SESSION[ 'etape3' ][ 'gestionnaires_inventaire' ], $_POST[$gestionnaire]);
				$nb=$nb+1;
				$gestionnaire="gestionnaireInventaire".$nb;
			}

			if(isset($_POST[ 'visibilite_inventaire' ]))
				$_SESSION[ 'etape3' ][ 'visibilite_inventaire' ]=0;
			else $_SESSION[ 'etape3' ][ 'visibilite_inventaire' ]=1;
		}

		//Variable indiquant que l'étape 3 a ete completée
//		$_SESSION[ 'etape3' ][ 'isFinished' ] = 1;	
		
		//Redirection vers l'étape 4
//		header('Location: etape4.php');
//	}
	
	/************************************************************************************
	***************** -A10- Stockage des gestionnaires d'inventaire *********************
	*************************************************************************************/
		
		if(isset($_POST[ 'community' ]))
		{

		$categories = explode(",",$_POST[ 'categories_community' ]);
		$_SESSION[ 'etape3' ][ 'categories'] = array();
		foreach($categories as $categorie)
		{
			array_push($_SESSION[ 'etape3' ][ 'categories'],htmlentities(trim($categorie)));
		}


			$nb=1;
			$gestionnaire="gestionnaireCommunity".$nb;
			//Variable correspondant aux gestionnaires d'inventaire
			$_SESSION[ 'etape3' ][ 'gestionnaires_community' ] = array();
			
			while(!EMPTY($_POST[$gestionnaire]))
			{
				array_push($_SESSION[ 'etape3' ][ 'gestionnaires_community' ], $_POST[$gestionnaire]);
				$nb=$nb+1;
				$gestionnaire="gestionnaireCommunity".$nb;
			}

			if(isset($_POST[ 'visibilite_community' ]))
				$_SESSION[ 'etape3' ][ 'visibilite_community' ]=0;
			else $_SESSION[ 'etape3' ][ 'visibilite_community' ]=1;
		}




		//Variable indiquant que l'étape 3 a ete completée
		$_SESSION[ 'etape3' ][ 'isFinished' ] = 1;	
		
		//Redirection vers l'étape 4
		header('Location: etape4.php');
	}

	/***********************************************************************************************************
	 ********************************************* B- HTML *****************************************************
	 ***********************************************************************************************************/
//echo 'I:'.$_SESSION[ 'etape3' ][ 'modules' ][ 'inventaire' ];
	header("Content-Type: text/html; charset=UTF-8");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<link rel="shortcut icon" type="image/x-icon" href="images/phpmylab.ico" />
<link rel="stylesheet" href="style_config.css">
<title>Etape 3 - Choix des modules</title>
<noscript>
	<div class="noscript">
		<img src="images/attention.png" />
		<p>Attention ! Le javascript est actuellement d&eacute;sactiv&eacute; sur votre navigateur. 
		Vous devez l'activer pour continuer la configuration et pour profiter de l'application de mani&egrave;re optimale.</p>
	</div>
</noscript>
</head>
<body onload="blocsMissions();blocsConges();blocsExpeditions();blocsInventaire();blocsCommunity();">
<div id="corps">
<h1>Etape 3 - Choix des modules</h1>

<p class="obligatoire" id="chp_obl">* Champs obligatoires</p>

<form action="<?php echo $_SERVER[ 'PHP_SELF' ]; ?>" method="POST" id="formPrincipal">
<table id="modules_dispo">
<tr>
<td>
<h3>Modules disponibles : </h3>
</td>
<td>
<input type=checkbox onclick="blocsMissions()" name="missions" id="missions" <?php if(isset($_SESSION[ 'etape3' ][ 'modules' ][ 'missions' ]) && $_SESSION[ 'etape3' ][ 'modules' ][ 'missions' ] == 0) echo ''; else echo 'checked'; ?> /><label for="missions">D&eacute;placements</label> <!--$modules-->
</td>
<td>
<input type=checkbox onclick="blocsConges()" name="conges" id="conges" <?php if(isset($_SESSION[ 'etape3' ][ 'modules' ][ 'conges' ]) && $_SESSION[ 'etape3' ][ 'modules' ][ 'conges' ] == 0) echo ''; else echo 'checked'; ?> /><label for="conges">Cong&eacute;</label> <!--$modules-->
</td>
<td>
<input type=checkbox name="planning" id="planning" <?php if(isset($_SESSION[ 'etape3' ][ 'modules' ][ 'planning' ]) && $_SESSION[ 'etape3' ][ 'modules' ][ 'planning' ] == 0) echo ''; else echo 'checked'; ?> /><label for="planning">Planning</label> <!--$modules-->
</td>
<td>
<input type=checkbox onclick="blocsExpeditions()" name="expeditions" id="expeditions" <?php if(isset($_SESSION[ 'etape3' ][ 'modules' ][ 'expeditions' ]) && $_SESSION[ 'etape3' ][ 'modules' ][ 'expeditions' ] == 0) echo ''; else echo 'checked'; ?> /><label for="expeditions">Exp&eacute;ditions</label> <!--$modules-->
</td>
<td>
<input type=checkbox onclick="blocsInventaire()" name="inventaire" id="inventaire" <?php if(isset($_SESSION[ 'etape3' ][ 'modules' ][ 'inventaire' ]) && $_SESSION[ 'etape3' ][ 'modules' ][ 'inventaire' ] == 0) echo ''; else echo 'checked'; ?> /><label for="inventaire">Inventaire</label> <!--$modules-->
</td>
<td>
<input type=checkbox onclick="blocsCommunity()" name="community" id="community" <?php if(isset($_SESSION[ 'etape3' ][ 'modules' ][ 'community' ]) && $_SESSION[ 'etape3' ][ 'modules' ][ 'community' ] == 0) echo ''; else echo 'checked'; ?> /><label for="community">Community</label> <!--$modules-->
</td>
</tr>
</table>

<p class="rouge centrer gras" id="planningSeul">Le module Planning ne peut exister tout seul !</p> <!-- Rempli par JS si planning seul -->

<!-- si missions cochée -->
<fieldset id="bloc_vehicules">
<legend>Liste des v&eacute;hicules disponibles par d&eacute;faut</legend>
<table id="typesVehicules">
<tr>
<td>	
<textarea cols="30" rows="8" name="vehicules_dispo">
<?php
	$liste_vehicules = '';
	if(!empty($_SESSION[ 'etape3' ][ 'vehicules']))
	{
		foreach($_SESSION[ 'etape3' ][ 'vehicules'] as $vehicule)
		$liste_vehicules.=$vehicule.',';
		$liste_vehicules=substr($liste_vehicules,0,-1);
		echo $liste_vehicules;
	}
	else
echo 'Voiture de location,
Train,
Avion,
V&eacute;hicule personnel';
?>
</textarea>
<td class="indication">
<p>V&eacute;hicules de l'organisme mis &agrave; disposition du personnel pour les missions/d&eacute;placements.<p>
<p class="indication gras">S&eacute;parer les v&eacute;hicules par des ","</p>
</td>
</tr>
</table>
</fieldset>

<fieldset id="bloc_objets">
<legend>Liste des objets de missions/d&eacute;placements par d&eacute;faut</legend>

<!-- Pre-remplissage des champs pour les labo -->
<!--
<input type=checkbox onclick="document.getElementById('formPrincipal').submit()" name="preremplissage" id="preremplissage" <?php if(isset($_POST[ 'preremplissage' ])) echo 'checked'; ?> /><label for="preremplissage">Pr&eacute;-remplissage pour les laboratoires de recherche.</label>
-->
<table id="objetsMissions">
<tr>	
<td>
<textarea cols="30" rows="10" name="objets_missions" >
<?php
/*
if(isset($_POST[ 'preremplissage' ])) 
echo 'R&eacute;union,
Colloque,
S&eacute;minaire,
Workshop,
Congr&egrave;s,
Ecole th&eacute;matique,
Formation permanente,
Jury de concours,
Jury de th&egrave;se,
Concours interne,
Maintenance,
Shift'; 
else 
{*/
	$liste_objets = '';
	if(!empty($_SESSION[ 'etape3' ][ 'objets_m']))
	{
		foreach($_SESSION[ 'etape3' ][ 'objets_m'] as $objet)
			$liste_objets.=$objet.',';
		$liste_objets=substr($liste_objets,0,-1);
		echo $liste_objets;
	}
	else
	echo 'R&eacute;union,
Colloque,
Workshop,
Congr&egrave;s,
Formation,
Maintenance'; 
//}
?>
</textarea>

</td>
<td class="indication">
<p>Motifs d'une mission/d&eacute;placement.</p>
<p class="indication gras">S&eacute;parer les objets par des ","</p>
</td>
</tr>


</table>
</fieldset>

<fieldset id="bloc_liens">
<legend>Liens divers optionnels (6 maximum)</legend>
<table id="liensAide">
<tr>	
<td>
<label for="lien1">Libelle du lien</label>
</td>
<td>
<input type=text id="lien1" name="lien1" <?php if(isset($_SESSION[ 'etape3' ][ 'liens' ][ 'libelle' ][0]))  echo 'value="'.$_SESSION[ 'etape3' ][ 'liens' ][ 'libelle' ][0].'"'; ?> />
</td>
<td>
<label for="adr_lien1">Adresse du lien</label>
</td>
<td>
<input type=text id="adr_lien1" name="adr_lien1" <?php if(isset($_SESSION[ 'etape3' ][ 'liens' ][ 'adresse' ][0]))  echo 'value="'.$_SESSION[ 'etape3' ][ 'liens' ][ 'adresse' ][0].'"'; ?> />
<img src="images/btn_plus.png" alt="+" onclick="ajoutLien()" class="btn_plus" />
</td>
<td class="indication">
Liens disponibles dans le module Missions/D&eacute;placements</td>
</tr>
<tr>	


<?php
$i=1;
while(isset($_SESSION[ 'etape3' ][ 'liens' ][ 'libelle' ][$i]) && isset($_SESSION[ 'etape3' ][ 'liens' ][ 'adresse' ][$i]))
{
echo '<tr>	
<td>
<label for="lien'.($i+1).'">Libelle du lien</label>
</td>
<td>
<input type=text id="lien'.($i+1).'" name="lien'.($i+1).'" ';
if(isset($_SESSION[ 'etape3' ][ 'liens' ][ 'libelle' ][$i])) echo 'value="'.$_SESSION[ 'etape3' ][ 'liens' ][ 'libelle' ][$i].'"'; echo ' /></td>';

echo '<td>
<label for="adr_lien'.($i+1).'">Adresse du lien</label>
</td>
<td>
<input type=text id="adr_lien'.($i+1).'" name="adr_lien'.($i+1).'" ';
if(isset($_SESSION[ 'etape3' ][ 'liens' ][ 'adresse' ][$i])) echo 'value="'.$_SESSION[ 'etape3' ][ 'liens' ][ 'adresse' ][$i].'"'; echo ' />';

echo '	</td>
</tr>';

$i++;
}
echo '<input type="hidden" id="nbDeLiens" name="nbDeLiens" value="'.$i.'" />';
?>
</table>
</fieldset>
<!-- FIN si missions cochée -->

<!-- si conges cochée -->

<fieldset id="bloc_contrats">
<legend>Contrats disponibles</legend>
<table id="typesContrats">
<tr>	
<td>
<label for="type_contrat1">Type de contrat</label> <span class="obligatoire">*</span>
</td>
<td>
<input type=text id="type_contrat1" name="type_contrat1" required <?php if(isset($_SESSION[ 'etape3' ][ 'types_contrats' ][ 'type' ][0])) echo 'value="'.$_SESSION[ 'etape3' ][ 'types_contrats' ][ 'type' ][0].'"'; ?> /></td>
<td class="paddingL30">
<label for="nb_conges1">Nombre de jours de cong&eacute;s associ&eacute;s</label> <span class="obligatoire">*</span>
</td>
<td>
<input type=text id="nb_jours1" name="nb_jours1" required <?php if(isset($_SESSION[ 'etape3' ][ 'types_contrats' ][ 'jours' ][0])) echo 'value="'.$_SESSION[ 'etape3' ][ 'types_contrats' ][ 'jours' ][0].'"'; ?> />
<img src="images/btn_plus.png" alt="+" onclick="ajoutContrat()" class="btn_plus" />
</td>
<td class="indication">
<u>ex:</u> "cadre" , "32"
</td>
</tr>

<?php
$i=1;
while(isset($_SESSION[ 'etape3' ][ 'types_contrats' ][ 'type' ][$i]) && isset($_SESSION[ 'etape3' ][ 'types_contrats' ][ 'jours' ][$i]))
{
echo '<tr>	
<td>
<label for="type_contrat'.($i+1).'">Type de contrat</label>
</td>
<td>
<input type=text id="type_contrat'.($i+1).'" name="type_contrat'.($i+1).'" ';
if(isset($_SESSION[ 'etape3' ][ 'types_contrats' ][ 'type' ][$i])) echo 'value="'.$_SESSION[ 'etape3' ][ 'types_contrats' ][ 'type' ][$i].'"'; echo ' /></td>';

echo '<td class="paddingL30">
<label for="nb_conges'.($i+1).'">Nombre de jours de cong&eacute;s associ&eacute;s</label>
</td>
<td>
<input type=text id="nb_jours'.($i+1).'" name="nb_jours'.($i+1).'" ';
if(isset($_SESSION[ 'etape3' ][ 'types_contrats' ][ 'jours' ][$i])) echo 'value="'.$_SESSION[ 'etape3' ][ 'types_contrats' ][ 'jours' ][$i].'"'; echo ' />';

echo '	</td>
</tr>';

$i++;
}
echo '<input type="hidden" id="nbDeContrats" name="nbDeContrats" value="'.$i.'" />';

?>

</table>
</fieldset>

<fieldset id="bloc_conges">
<legend>Types de cong&eacute;s</legend>
	<table>
		<tr>
			<td>
				<table id="congesAvecSolde">
					<caption><b>Avec solde</b></caption>
					<tr>	
						<td>
							<label for="type_conge_avec_solde1" class="gras">1. </label>
							<input type=text id="type_conge_avec_solde1" name="type_conge_avec_solde1" value="Cong&eacute;s annuels" readonly/>
						</td>
					</tr>
					<tr>	
						<td>
							<label for="type_conge_avec_solde2" class="gras">2. </label>
							<input type=text id="type_conge_avec_solde2" name="type_conge_avec_solde2" value="Compte &eacute;pargne temps" readonly/>
						</td>
					</tr>
					<tr>	
						<td>
							<label for="type_conge_avec_solde3" class="gras">3. </label>
							<input type=text id="type_conge_avec_solde3" name="type_conge_avec_solde3" value="R&eacute;cup&eacute;ration" readonly/>
						</td>
					</tr>
					<tr>	
						<td id="lastTDcongesAvecSolde">
							<label for="type_conge_avec_solde4" class="gras">4. </label>
							<input type=text id="type_conge_avec_solde4" name="type_conge_avec_solde4" value="Autres..." readonly/>
							<img src="images/fleche.png" alt="---->" width="80" height="25" id="fleche"/>
						</td>
					</tr>
				</table>
				
			</td>

			<td>
				<table id="congesSansSolde">
					<caption><b>Sans solde</b></caption>
					<tr>	
						<td>
							<textarea cols="30" rows="11" name="conges_sans_solde">
<?php
	if(!empty($_SESSION[ 'etape3' ][ 'conges_sans_solde']))
	{
		$liste_conges='';
		foreach($_SESSION[ 'etape3' ][ 'conges_sans_solde'] as $conge)
			$liste_conges.=$conge.',';
		$liste_conges=substr($liste_conges,0,-1);
		echo $liste_conges;
	}
	else
echo 'Cong&eacute;s naissance,
Cong&eacute;s enfant malade,
Arr&ecirc;t de travail,
Cong&eacute;s d&eacute;m&eacute;nagement,
Mariage,
Mariage enfant,
D&eacute;c&egrave;s,
Conjoint maladie grave,
Cong&eacute;s maladie,
Activit&eacute; secondaire';
?>
							</textarea>
						</td>
					</tr>
				</table>
			</td>
			<td>
				<table id="indic"><tr><td><p class="indication gras">S&eacute;parer les types de cong&eacute; par des ","</p></td></tr></table>
			</td>
		</tr>
	</table>
<br/><br/><br/>
</fieldset>

<!-- Si Expeditions coché -->
<fieldset id="bloc_expeditions">
<legend>Gestionnaires des exp&eacute;ditions</legend>
<table id="expedition">
<tr>	
<td>
<label for="gestionnaire1">Login du 1er gestionnaire <span class="obligatoire">*</span></label>
</td>
<td>
<input type=text id="gestionnaire1" name="gestionnaire1" <?php if(isset($_SESSION[ 'etape3' ][ 'gestionnaires_expeditions' ][0]))  echo 'value="'.$_SESSION[ 'etape3' ][ 'gestionnaires_expeditions' ][0].'"'; ?> required/>
<img src="images/btn_plus.png" alt="+" onclick="ajoutGestionnaire()" class="btn_plus" />
</td>

<td class="indication">
Login du gestionnaire du module "Exp&eacute;ditions"</td>
</tr>

<?php
$i=1;
while(isset($_SESSION[ 'etape3' ][ 'gestionnaires_expeditions' ][$i]))
{
echo '<tr>	
<td>
<label for="gestionnaire'.($i+1).'">Login du '.($i+1).'&egrave;me gestionnaire</label>
</td>
<td>
<input type=text id="gestionnaire'.($i+1).'" name="gestionnaire'.($i+1).'" ';
if(isset($_SESSION[ 'etape3' ][ 'gestionnaires_expeditions' ][$i])) echo 'value="'.$_SESSION[ 'etape3' ][ 'gestionnaires_expeditions' ][$i].'"'; echo ' /></td></tr>';
$i++;
}
echo '<input type="hidden" id="nbGestionnaires" name="nbGestionnaires" value="'.$i.'" />';
?>
</table>
</fieldset>

<!-- Fin si Expeditions coché -->

<!-- Si Inventaire coché -->
<fieldset id="bloc_inventaire">
<legend>Gestionnaires des inventaires</legend>
<table id="inventaireTable">

<tr><td colspan=2 width="50%">
<input type=checkbox name="visibilite_inventaire" id="visibilite_inventaire" <?php if(isset($_SESSION[ 'etape3' ][ 'visibilite_inventaire' ]) && $_SESSION[ 'etape3' ][ 'visibilite_inventaire' ] == 0) echo 'checked'; else echo '';  ?> /><label for="visibilite_inventaire">Le module "Inventaire" n'est visible que par les gestionnaires</label>
</td>
<td class="indication">
Permet de r&eacute;pertorier les mat&eacute;riaux avant que tous les utilisateurs s'en servent</td>
</tr>

<tr>	
<td>
<label for="gestionnaireInventaire1">Login du 1er gestionnaire <span class="obligatoire">*</span></label>
</td>
<td>
<input type=text id="gestionnaireInventaire1" name="gestionnaireInventaire1" <?php if(isset($_SESSION[ 'etape3' ][ 'gestionnaires_inventaire' ][0]))  echo 'value="'.$_SESSION[ 'etape3' ][ 'gestionnaires_inventaire' ][0].'"'; ?> required/>
<img src="images/btn_plus.png" alt="+" onclick="ajoutGestionnaireInventaire()" class="btn_plus" />
</td>

<td class="indication">
Login du gestionnaire du module "Inventaire"</td>
</tr>


<?php
$i=1;
while(isset($_SESSION[ 'etape3' ][ 'gestionnaires_inventaire' ][$i]))
{
echo '<tr>	
<td>
<label for="gestionnaireInventaire'.($i+1).'">Login du '.($i+1).'&egrave;me gestionnaire</label>
</td>
<td>
<input type=text id="gestionnaireInventaire'.($i+1).'" name="gestionnaireInventaire'.($i+1).'" ';
if(isset($_SESSION[ 'etape3' ][ 'gestionnaires_inventaire' ][$i])) echo 'value="'.$_SESSION[ 'etape3' ][ 'gestionnaires_inventaire' ][$i].'"'; echo ' /></td></tr>';
$i++;
}
echo '<input type="hidden" id="nbGestionnairesInventaire" name="nbGestionnairesInventaire" value="'.$i.'" />';
?>
</table>
</fieldset>

<!-- Fin si Inventaire coché -->

<!-- Si Community coché -->
<fieldset id="bloc_community">
<legend>Community</legend>

<table id="communityTable">
<tr>
<td>	
<textarea cols="30" rows="8" name="categories_community">
<?php
	$liste_cate = '';
	if(!empty($_SESSION[ 'etape3' ][ 'categories_commu']))
	{
		foreach($_SESSION[ 'etape3' ][ 'categories_commu'] as $catagories)
		$liste_catagories.=$catagories.',';
		$liste_catagories=substr($liste_catagories,0,-1);
		echo $liste_catagories;
	}
	else
echo 'Hotels,
Restaurants,
Vacances,
Annonces,
Scientifique,
Divers';
?>
</textarea>
<td colspan="2" class="indication">
<p>Cat&eacute;gories du "mur" de l'organisme.<p>
<p class="indication gras">S&eacute;parer les cat&eacute;gories par des ","</p>
</td>
</tr>

<tr>	
<td>
<label for="gestionnaireCommunity1">Login du 1er gestionnaire <span class="obligatoire">*</span></label>
</td>
<td>
<input type=text id="gestionnaireCommunity1" name="gestionnaireCommunity1" <?php if(isset($_SESSION[ 'etape3' ][ 'gestionnaires_community' ][0]))  echo 'value="'.$_SESSION[ 'etape3' ][ 'gestionnaires_community' ][0].'"'; ?> required/>
<img src="images/btn_plus.png" alt="+" onclick="ajoutGestionnaireCommunity()" class="btn_plus" />
</td>

<td class="indication">
Login du gestionnaire du module "Community"</td>
</tr>

<?php
$i=1;
while(isset($_SESSION[ 'etape3' ][ 'gestionnaires_community' ][$i]))
{
echo '<tr>	
<td>
<label for="gestionnaireCommunity'.($i+1).'">Login du '.($i+1).'&egrave;me gestionnaire</label>
</td>
<td>
<input type=text id="gestionnaireCommunity'.($i+1).'" name="gestionnaire'.($i+1).'" ';
if(isset($_SESSION[ 'etape3' ][ 'gestionnaires_community' ][$i])) echo 'value="'.$_SESSION[ 'etape3' ][ 'gestionnaires_community' ][$i].'"'; echo ' /></td></tr>';
$i++;
}
echo '<input type="hidden" id="nbGestionnairesCommunity" name="nbGestionnairesCommunity" value="'.$i.'" />';
?>
</table>
</fieldset>

<!-- Fin si Community coché -->

<table class="case_bouton">
	<tr>
		<td id="etape_precedente">
			<input type=button value="Revenir &agrave; l'&eacute;tape 2" class="bouton" onclick="javascript:location.href='etape2.php'" />
		</td>
		<td id="etape_suivante">
			<input type=submit value="Passer &agrave; l'&eacute;tape 4" class="bouton" id="etape4" name="etape4" />
		</td>
	</tr>
</table>
</td>
</tr>
<!-- FIN si conges cochée -->

</form>
</div>


<?php
	/****************************************************************************
	 *********************************** C- Javascript **************************
	 ***************************************************************************/
?>

<script>
var NbContrats = parseInt(document.getElementById('nbDeContrats').value)+1; //Recupere le nom de contrats existant et ajoute 1
function ajoutContrat()
{
    var TR = document.createElement("tr");
    var TD1  = document.createElement ("td");
	var LABEL = document.createElement ("label");
	var FOR=document.createAttribute("for");
	FOR.value="type_contrat"+NbContrats;
	LABEL.setAttributeNode(FOR);
	LABEL.innerHTML="Type de contrat";
    TD1.appendChild(LABEL);
	TR.appendChild(TD1);
	var TD2  = document.createElement ("td");
	var INPUT = document.createElement ("input");
	var ID=document.createAttribute("id");
	ID.value="type_contrat"+NbContrats;
	INPUT.setAttributeNode(ID);
	var NAME=document.createAttribute("name");
	NAME.value="type_contrat"+NbContrats;
	INPUT.setAttributeNode(NAME);
	var TYPE=document.createAttribute("type");
	TYPE.value="text";
	INPUT.setAttributeNode(TYPE);
	TD2.appendChild(INPUT);
    TR.appendChild(TD2);
    var TD3  = document.createElement ("td");
	var CLASS=document.createAttribute("class");
	CLASS.value="paddingL30";
	TD3.setAttributeNode(CLASS);
	var LABEL = document.createElement ("label");
	var FOR=document.createAttribute("for");
	FOR.value="nb_jours"+NbContrats;
	LABEL.setAttributeNode(FOR);
	LABEL.innerHTML="Nombre de jours de cong&eacute;s associ&eacute;s";
    TD3.appendChild(LABEL);
	TR.appendChild(TD3);
	var TD4  = document.createElement ("td");
	var INPUT = document.createElement ("input");
	var ID=document.createAttribute("id");
	ID.value="nb_jours"+NbContrats;
	INPUT.setAttributeNode(ID);
	var NAME=document.createAttribute("name");
	NAME.value="nb_jours"+NbContrats;
	INPUT.setAttributeNode(NAME);
	var TYPE=document.createAttribute("type");
	TYPE.value="text";
	INPUT.setAttributeNode(TYPE);
	TD4.appendChild(INPUT);
    TR.appendChild(TD4);
    document.getElementById ('typesContrats').appendChild(TR);
	NbContrats++;
}


var NbLiens = parseInt(document.getElementById('nbDeLiens').value)+1; //Recupere le nom de liens d'aide existant et ajoute 1
function ajoutLien()
{
	if(NbLiens <= 6)
	{
			var TR = document.createElement("tr");
			var TD1  = document.createElement ("td");
			var LABEL = document.createElement ("label");
			var FOR=document.createAttribute("for");
			FOR.value="lien"+NbLiens;
			LABEL.setAttributeNode(FOR);
			LABEL.innerHTML="Libelle du lien";
			TD1.appendChild(LABEL);
			TR.appendChild(TD1);
			var TD2  = document.createElement ("td");
			var INPUT = document.createElement ("input");
			var ID=document.createAttribute("id");
			ID.value="lien"+NbLiens;
			INPUT.setAttributeNode(ID);
			var NAME=document.createAttribute("name");
			NAME.value="lien"+NbLiens;
			INPUT.setAttributeNode(NAME);
			var TYPE=document.createAttribute("type");
			TYPE.value="text";
			INPUT.setAttributeNode(TYPE);
			TD2.appendChild(INPUT);
			TR.appendChild(TD2);
			var TD3  = document.createElement ("td");
			var LABEL = document.createElement ("label");
			var FOR=document.createAttribute("for");
			FOR.value="adr_lien"+NbLiens;
			LABEL.setAttributeNode(FOR);
			LABEL.innerHTML="Adresse du lien";
			TD3.appendChild(LABEL);
			TR.appendChild(TD3);
			var TD4  = document.createElement ("td");
			var INPUT = document.createElement ("input");
			var ID=document.createAttribute("id");
			ID.value="adr_lien"+NbLiens;
			INPUT.setAttributeNode(ID);
			var NAME=document.createAttribute("name");
			NAME.value="adr_lien"+NbLiens;
			INPUT.setAttributeNode(NAME);
			var TYPE=document.createAttribute("type");
			TYPE.value="text";
			INPUT.setAttributeNode(TYPE);
			TD4.appendChild(INPUT);
			TR.appendChild(TD4);
			document.getElementById ('liensAide').appendChild(TR);
			NbLiens++;
	}
}

var NbGestionnaires = parseInt(document.getElementById('nbGestionnaires').value)+1; 
function ajoutGestionnaire()
{
	var TR = document.createElement("tr");
	var TD1  = document.createElement ("td");
	var LABEL = document.createElement ("label");
	var FOR=document.createAttribute("for");
	FOR.value="gestionnaire"+NbGestionnaires;
	LABEL.setAttributeNode(FOR);
	LABEL.innerHTML="Login du "+NbGestionnaires+"&egrave;me gestionnaire";
	TD1.appendChild(LABEL);
	TR.appendChild(TD1);
	var TD2  = document.createElement ("td");
	var INPUT = document.createElement ("input");
	var ID=document.createAttribute("id");
	ID.value="gestionnaire"+NbGestionnaires;
	INPUT.setAttributeNode(ID);
	var NAME=document.createAttribute("name");
	NAME.value="gestionnaire"+NbGestionnaires;
	INPUT.setAttributeNode(NAME);
	var TYPE=document.createAttribute("type");
	TYPE.value="text";
	INPUT.setAttributeNode(TYPE);
	TD2.appendChild(INPUT);
	TR.appendChild(TD2);
	document.getElementById ('expedition').appendChild(TR);
	NbGestionnaires++;
}

var NbGestionnairesInventaire = parseInt(document.getElementById('nbGestionnairesInventaire').value)+1; 
function ajoutGestionnaireInventaire()
{
	var TR = document.createElement("tr");
	var TD1  = document.createElement ("td");
	var LABEL = document.createElement ("label");
	var FOR=document.createAttribute("for");
	FOR.value="gestionnaireInventaire"+NbGestionnairesInventaire;
	LABEL.setAttributeNode(FOR);
	LABEL.innerHTML="Login du "+NbGestionnairesInventaire+"&egrave;me gestionnaire";
	TD1.appendChild(LABEL);
	TR.appendChild(TD1);
	var TD2  = document.createElement ("td");
	var INPUT = document.createElement ("input");
	var ID=document.createAttribute("id");
	ID.value="gestionnaireInventaire"+NbGestionnairesInventaire;
	INPUT.setAttributeNode(ID);
	var NAME=document.createAttribute("name");
	NAME.value="gestionnaireInventaire"+NbGestionnairesInventaire;
	INPUT.setAttributeNode(NAME);
	var TYPE=document.createAttribute("type");
	TYPE.value="text";
	INPUT.setAttributeNode(TYPE);
	TD2.appendChild(INPUT);
	TR.appendChild(TD2);
	document.getElementById ('inventaireTable').appendChild(TR);
	NbGestionnairesInventaire++;
}

var NbGestionnairesCommunity = parseInt(document.getElementById('nbGestionnairesCommunity').value)+1; 
function ajoutGestionnaireCommunity()
{
	var TR = document.createElement("tr");
	var TD1  = document.createElement ("td");
	var LABEL = document.createElement ("label");
	var FOR=document.createAttribute("for");
	FOR.value="gestionnaireCommunity"+NbGestionnairesCommunity;
	LABEL.setAttributeNode(FOR);
	LABEL.innerHTML="Login du "+NbGestionnairesCommunity+"&egrave;me gestionnaire";
	TD1.appendChild(LABEL);
	TR.appendChild(TD1);
	var TD2  = document.createElement ("td");
	var INPUT = document.createElement ("input");
	var ID=document.createAttribute("id");
	ID.value="gestionnaireCommunity"+NbGestionnairesCommunity;
	INPUT.setAttributeNode(ID);
	var NAME=document.createAttribute("name");
	NAME.value="gestionnaireCommunity"+NbGestionnairesCommunity;
	INPUT.setAttributeNode(NAME);
	var TYPE=document.createAttribute("type");
	TYPE.value="text";
	INPUT.setAttributeNode(TYPE);
	TD2.appendChild(INPUT);
	TR.appendChild(TD2);
	document.getElementById ('communityTable').appendChild(TR);
	NbGestionnairesCommunity++;
}
//////////////////////////////////////////////////////////////////////////////////

function blocsMissions()
{
	if(!document.getElementById('missions').checked && !document.getElementById('conges').checked && document.getElementById('planning').checked)
	{
		document.getElementById('planningSeul').style.display = "block";
		document.getElementById('etape4').disabled = "disabled";
		document.getElementById('etape4').style.opacity = "0.5";
		
	}
	else 
	{
		document.getElementById('planningSeul').style.display = "none";
		document.getElementById('etape4').disabled = "";
		document.getElementById('etape4').style.opacity = "1";
	}
	
	if(document.getElementById('missions').checked)
		var visibilite = 'block';
	else var visibilite = 'none';
	
	document.getElementById('bloc_vehicules').style.display = visibilite;
	document.getElementById('bloc_objets').style.display = visibilite;
	document.getElementById('bloc_liens').style.display = visibilite;
}

function blocsConges()
{
	if(!document.getElementById('missions').checked && !document.getElementById('conges').checked && document.getElementById('planning').checked)
	{
		document.getElementById('planningSeul').style.display = "block";
		document.getElementById('etape4').disabled = "disabled";
		document.getElementById('etape4').style.opacity = "0.5";
		
	}
	else 
	{
		document.getElementById('planningSeul').style.display = "none";
		document.getElementById('etape4').disabled = "";
		document.getElementById('etape4').style.opacity = "1";
	}
	
	if(document.getElementById('conges').checked)
	{
		var visibilite = 'block';
		var required = 'required';
	}
	else	
	{
		var visibilite = 'none';
		var required = '';
	}
	document.getElementById('bloc_conges').style.display = visibilite;
	document.getElementById('bloc_contrats').style.display = visibilite;
	//document.getElementById('chp_obl').style.display = visibilite;
	document.getElementById('type_contrat1').required = required;
	document.getElementById('nb_jours1').required = required;

}

function blocsExpeditions()
{
	if(document.getElementById('expeditions').checked)
	{
		var visibilite = 'block';
		var required = 'required';
	}
	else	
	{
		var visibilite = 'none';
		var required = '';
	}
	document.getElementById('bloc_expeditions').style.display = visibilite;
	document.getElementById('gestionnaire1').required = required;
}

function blocsInventaire()
{
	if(document.getElementById('inventaire').checked)
	{
		var visibilite = 'block';
		var required = 'required';
	}
	else	
	{
		var visibilite = 'none';
		var required = '';
	}
	document.getElementById('bloc_inventaire').style.display = visibilite;
	document.getElementById('gestionnaireInventaire1').required = required;
}

function blocsCommunity()
{
	if(document.getElementById('community').checked)
	{
		var visibilite = 'block';
		var required = 'required';
	}
	else	
	{
		var visibilite = 'none';
		var required = '';
	}
	document.getElementById('bloc_community').style.display = visibilite;
	document.getElementById('gestionnaireCommunity1').required = required;
}
</script>

</body>
</html>
