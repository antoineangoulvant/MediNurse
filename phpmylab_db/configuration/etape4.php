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
	 * Etape 4 de la configuration de phpMyLab.
	 *
	 * Cette page permet de renseigner les dernieres variables necessaires a la configuration de phpMyLab. 
	 *
	 * Date de création : 17 Avril 2012<br/>
	 * Date de dernière modification : 24 septembre 2015
	 * @version 1.3.0
	 * @author Cedric Gagnevin <cedric.gagnevin@laposte.net>
	 * @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.html
	 * @copyright CNRS (c) 2015
	 * @package phpMyLab
	 */
	
	/*********************************************************************************
	 ******************************  PLAN     ****************************************
	 *********************************************************************************/

	//    | -A- Initialisation de la liste des serveurs CAS
	//    | -B-  Stockage des variables de l'etape 4 dans des variables de session
	//    | -C-  HTML

	/***********************************************************************************************************
	 ************************** A- Initialisation de la liste des serveurs CAS *********************************
	 ***********************************************************************************************************/
	
	$liste_cas = array();
	//Structure d'une ligne du tableau : Identifiant -- Nom du serveur -- URL du serveur(host) -- Port -- Contexte
	array_push($liste_cas,array('0','UDA - Clermont 1','cas.u-clermont1.fr',443,'/cas')); //Ent clermont 1
	array_push($liste_cas,array('1','UBP - Clermont 2','cas.univ-bpclermont.fr',443,'/cas')); //Ent clermont 1
	array_push($liste_cas,array('2','CIRAD','sso.cirad.fr/',443,'')); //CIRAD
	array_push($liste_cas,array('3','CNRS','janus.dsi.cnrs.fr/cas/',443,'/')); //CNRS Janus
	array_push($liste_cas,array('4','INRA','idp.inra.fr',443,'/cas')); //INRA
	array_push($liste_cas,array('5','INRIA','cas.inria.fr',443,'/cas')); //INRIA
	array_push($liste_cas,array('6','INSERM','sso.inserm.fr',443,'/cas')); //INSERM
	array_push($liste_cas,array('7','Universit&eacute; Lille 1','sso-cas.univ-lille1.fr',443,'')); //Université Lille 1
	array_push($liste_cas,array('8','Paris Ouest Nanterre la D&eacute;fense','casidp.u-paris10.fr',443,'')); //POND
	array_push($liste_cas,array('9','Sorbonne nouvelle - Paris 3','cas.univ-paris3.fr',443,'/cas')); //Sorbonne - Paris 3
	array_push($liste_cas,array('10','Aix Marseille 1 Provence','ident.univ-amu.fr',443,'/cas')); //Marseille 1
	array_push($liste_cas,array('11','Autre...','',443,'/cas')); //Autre
	
	
// 	foreach($liste_cas as $serveur_cas)
// 	{
// 		echo $serveur_cas[1].'('.$serveur_cas[0].') : '.$serveur_cas[2].' -- '.$serveur_cas[3].' -- '.$serveur_cas[4].'<br>';
// 	}

	/***********************************************************************************************************
	 *************** B- Stockage des variables de l'etape 4 dans des variables de session **********************
	 ***********************************************************************************************************/
	session_start();
	if(!isset($_SESSION[ 'etape4' ])) $_SESSION[ 'etape4' ] = array();

	// Si les pré-requis ne sont pas respectés
	if(empty($_SESSION[ 'requirements' ]) OR $_SESSION[ 'requirements' ] != 1)
	{
		echo '<p class="rouge centrer gras">La configuration requise n\'est pas respect&eacute;e ! Cliquer <a href="index.php">ici</a> pour voir ce qu\'il vous manque.';
		exit;
	}
	
	
	
	if(!empty($_POST[ 'domaine' ]) && !empty($_POST[ 'mail_web' ]) && !empty($_POST[ 'annee_debut' ]) && !empty($_POST[ 'annee_fin' ]) && isset($_POST[ 'terminer' ]))
	{
		if((!empty($_POST[ 'mode' ]) && $_POST[ 'mode' ] == 'test' && !empty($_POST[ 'mel_test' ])) || (!empty($_POST[ 'mode' ]) && $_POST[ 'mode' ] == 'production'))
		{
			if((isset($_POST[ 'cas' ]) && !empty($_POST[ 'cas_host' ]) && !empty($_POST[ 'cas_port' ]) && !empty($_POST[ 'url_reception' ])) || !isset($_POST[ 'cas' ]) )
			{
				//Variable correspondant au domaine d'envoi d'email
				$_SESSION[ 'etape4' ][ 'domaine' ] = htmlentities($_POST[ 'domaine' ]);
	
				//Variable correspondant a l'adresse email du webmaster
				$_SESSION[ 'etape4' ][ 'mail_web' ] = htmlentities($_POST[ 'mail_web' ]);
	
				//Variables correspondant aux annees de debut et de fin de calendriers
				$_SESSION[ 'etape4' ][ 'annee_debut' ] = $_POST[ 'annee_debut' ];
				$_SESSION[ 'etape4' ][ 'annee_fin' ] = $_POST[ 'annee_fin' ];
				
				//Variable correspondant au mode (test/production) + adresse mail de test 
				if($_POST[ 'mode' ] == 'test')// test
				{
					$_SESSION[ 'etape4' ][ 'mode' ] = 'test';
					$_SESSION[ 'etape4' ][ 'mel_test' ] = htmlentities($_POST[ 'mel_test' ]);
				}
				else 
				{
					if(isset($_SESSION[ 'etape4' ][ 'mel_test' ]))
						unset($_SESSION[ 'etape4' ][ 'mel_test' ]);
					$_SESSION[ 'etape4' ][ 'mode' ] = 'production'; // production
				}

				//Parametres CAS
				if(isset($_POST[ 'cas' ]))
				{
					$_SESSION[ 'etape4' ][ 'cas' ] = 1;
					$_SESSION[ 'etape4' ][ 'cas_host' ] = $_POST[ 'cas_host' ];
					$_SESSION[ 'etape4' ][ 'cas_port' ] = $_POST[ 'cas_port' ];
					$_SESSION[ 'etape4' ][ 'cas_context' ] = $_POST[ 'cas_context' ];
					$_SESSION[ 'etape4' ][ 'url_reception' ] = $_POST[ 'url_reception' ];
				}
				else $_SESSION[ 'etape4' ][ 'cas' ] = 0;

				if(isset($_POST[ 'captcha' ]))
					$_SESSION[ 'etape4' ][ 'captcha' ] = 1;
				else $_SESSION[ 'etape4' ][ 'captcha' ] = 0;

				//Variable indiquant que l'étape 4 a ete completée
				$_SESSION[ 'etape4' ][ 'isFinished' ] = 1;
					
				//Variable indiquant que la configuration est bien terminée
				if($_SESSION[ 'etape1' ][ 'isFinished' ] == 1 && $_SESSION[ 'etape2' ][ 'isFinished' ] == 1 && $_SESSION[ 'etape3' ][ 'isFinished' ] == 1)
					$_SESSION[ 'isFinished' ] = 1;	
					
				//Redirection vers la page de fin de configuration
				header('Location: finConfig.php');
			}
		}
	}

	/***********************************************************************************************************
	 ******************************************** C- HTML ******************************************************
	 ***********************************************************************************************************/
	
	header("Content-Type: text/html; charset=iso-8859-1");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<link rel="shortcut icon" type="image/x-icon" href="images/phpmylab.ico" />
<link rel="stylesheet" href="style_config.css">
<title>Etape 4 - Configuration des derniers param&egrave;tres</title>
<noscript>
	<div class="noscript">
		<img src="images/attention.png" />
		<p>Attention ! Le javascript est actuellement d&eacute;sactiv&eacute; sur votre navigateur. 
		Vous devez l'activer pour continuer la configuration et pour profiter de l'application de mani&egrave;re optimale.</p>
	</div>
</noscript>


<script>

function hideMelTest()
{	
	document.getElementById('ligneMelTestLabel').innerHTML = '';
	document.getElementById('ligneMelTestInput').innerHTML = '';
}

function showMelTest()
{	
	document.getElementById('ligneMelTestLabel').innerHTML = '<label for="mel_test">Email de test</label> <span class="obligatoire">*</span>';
	document.getElementById('ligneMelTestInput').innerHTML = '<input type=email id="mel_test" name="mel_test" required placeholder="emailtest@masociete.com" />';
}

function blocsCAS()
{	
	if(document.getElementById('cas').checked)
	{	
		var visibilite = 'block';
		var required = 'required';
	}
	else
	{	
		var visibilite = 'none';
		var required = '';
	}
	document.getElementById('param_cas').style.display = visibilite;
	document.getElementById('cas_host').required = required;
	document.getElementById('cas_port').required = required;
	document.getElementById('url_reception').required = required;
}


</script>

</head>
<body onload="blocsCAS()">
<div id="corps">
<h1>Etape 4 - Configuration des derniers param&egrave;tres</h1>

<p class="obligatoire">* Champs obligatoires</p>

<form action="<?php echo $_SERVER[ 'PHP_SELF' ]; ?>" id="form1" method="POST">
<table id="tab_etape4">
	<tr>
		<td>
			<label for="domaine">Nom de domaine des emails</label> <span class="obligatoire">*</span>
		</td>
		<td>
			<input type=text name="domaine" id="domaine" 
			<?php 
				if(!empty($_POST[ 'domaine' ])) 
					echo 'value="'.$_POST[ 'domaine' ].'"'; 
				elseif(isset($_SESSION[ 'etape4' ][ 'domaine' ])) 
					echo 'value="'.$_SESSION[ 'etape4' ][ 'domaine' ].'"'; 
			?> 
			required placeholder="masociete.com" />
		</td>
		<td class="indication">
			exemple@<b>masociete.com</b>
		</td>
	</tr>

	<tr>	
		<td>
			<label for="mail_web">Email des webmasters</label> <span class="obligatoire">*</span>
		</td>
		<td>
			<input type=email id="mail_web" name="mail_web" 
			<?php 
				if(!empty($_POST[ 'mail_web' ])) 
					echo 'value="'.$_POST[ 'mail_web' ].'"'; 
				elseif(isset($_SESSION[ 'etape4' ][ 'mail_web' ])) 
					echo 'value="'.$_SESSION[ 'etape4' ][ 'mail_web' ].'"'; 
			?> 
			required placeholder="webmaster@masociete.com" />
		</td>
		<td class="indication">
			S&eacute;parer de ";" pour saisir plusieurs emails
		</td>
	</tr>

	<tr>
		<td>	
			<label for="annee_debut">Liste des ann&eacute;es pour les calendriers</label> <span class="obligatoire">*</span>
		</td>
		<td>
			<select name="annee_debut" id="annee_debut" required>
			<?php
				$dateActuelle = date('Y');
				$selected = 0;
				for($i=$dateActuelle+50 ; $i > $dateActuelle-20 ; $i--)
				{
					if(isset($_POST[ 'annee_debut' ]) && $i == $_POST[ 'annee_debut' ])
					{
						echo '<option value="'.$i.'" selected>'.$i.'</option>';
						$selected = 1;
					}
					elseif(isset($_SESSION[ 'etape4' ][ 'annee_debut' ]) && $i == $_SESSION[ 'etape4' ][ 'annee_debut' ])
					{
						echo '<option value="'.$i.'" selected>'.$i.'</option>';
						$selected = 1;
					}
					else
					{
						if($selected != 1 && date('Y') == $i)
							echo '<option value="'.$i.'" selected>'.$i.'</option>';
						else echo '<option value="'.$i.'">'.$i.'</option>';
					}
				}
			?>
			</select>
			&agrave; 
			<select name="annee_fin" id="annee_fin" required>
			<?php
				$dateActuelle = date('Y');
				$selected = 0;
				for($i=$dateActuelle+50 ; $i > $dateActuelle-20 ; $i--)
				{	
					if(isset($_POST[ 'annee_fin' ]) && $i == $_POST[ 'annee_fin' ])
					{
						echo '<option value="'.$i.'" selected>'.$i.'</option>';
						$selected = 1;
					}
					elseif(isset($_SESSION[ 'etape4' ][ 'annee_fin' ]) && $i == $_SESSION[ 'etape4' ][ 'annee_fin' ])
					{
						echo '<option value="'.$i.'" selected>'.$i.'</option>';
						$selected = 1;
					}
					else
					{
						if($selected != 1 && (date('Y')+10) == $i)
							echo '<option value="'.$i.'" selected>'.$i.'</option>';
						else echo '<option value="'.$i.'">'.$i.'</option>';
					}
				}
			?>
			</select>		
		</td>
	</tr>
	
	<tr>
		<td>
			<label>Choix du mode</label> <span class="obligatoire">*</span>
		</td>
		<td>
			<span onclick="showMelTest()"><input type=radio name="mode" value="test" id="test" 
			<?php 
				if(isset($_POST[ 'mode' ]) && $_POST[ 'mode' ] == 'test') 
					echo 'checked';
				elseif(isset($_SESSION[ 'etape4' ][ 'mode' ]) && $_SESSION[ 'etape4' ][ 'mode' ] == 'test') 
					echo 'checked'; 
			?> 
			/><label for="test">Mode Test</label></span>
			<span onclick="hideMelTest()"><input type=radio name="mode" value="production" id="production" 
			<?php 
				if((isset($_SESSION[ 'etape4' ][ 'mode' ]) && $_SESSION[ 'etape4' ][ 'mode' ] == 'production') || (isset($_POST[ 'mode' ]) && $_POST[ 'mode' ] == 'production') || (!isset($_SESSION[ 'etape4' ][ 'mode' ]) && !isset($_POST[ 'mode' ]))) 
					echo 'checked'; 
			?> 
			/><label for="production">Mode Production</label></span>
		</td>
	</tr>

	<?php 
		if(isset($_SESSION[ 'etape4' ][ 'mel_test' ]) OR isset($_POST[ 'mel_test' ]))
		{
			echo '<tr>
					<td id="ligneMelTestLabel"><label for="mel_test">Email de test</label> <span class="obligatoire">*</span></td>
					<td id="ligneMelTestInput"><input type=email id="mel_test" name="mel_test" required value="';
			if(isset($_POST[ 'mel_test' ]))
				echo $_POST[ 'mel_test' ];
			else echo $_SESSION[ 'etape4' ][ 'mel_test' ];
				echo '" /></td>
					<td class="indication">
						En mode test, tous les emails sont envoy&eacute;s ici
					</td>
				  </tr>';
		}
		else echo '<tr>
					  <td id="ligneMelTestLabel"></td>
					  <td id="ligneMelTestInput"></td>
				   </tr>';
	?>
</table>

<fieldset>
<legend>Central Authentification Service</legend>
	<input type=checkbox onclick="blocsCAS()" name="cas" id="cas" <?php if(!empty($_SESSION[ 'etape4' ][ 'cas' ]) && $_SESSION[ 'etape4' ][ 'cas' ] == 1) echo 'checked'; elseif(isset($_POST[ 'cas' ])) echo 'checked'; else echo ''; ?> /><label for="cas">Utiliser une authentification par CAS (Beta)</label>
	<table id="param_cas">
		<tr>
			<td>
				<label for="cas_host">Choix du serveur CAS</label> <span class="obligatoire">*</span>
			</td>
			<td>
				<select name="cas_liste">
				<?php
					foreach($liste_cas as $serveur_cas)
					{
						echo '<option value="'.$serveur_cas[0].'" onclick="document.getElementById(\'form1\').submit();" ';
						if(isset($_POST[ 'cas_liste' ]) && $_POST[ 'cas_liste' ] == $serveur_cas[0]) 
							echo 'selected';
						else if(isset($_SESSION[ 'etape4' ][ 'cas_liste' ]) && $_SESSION[ 'etape4' ][ 'cas_liste' ] == $serveur_cas[1]) 
							echo 'selected';
						elseif(!isset($_POST[ 'cas_liste' ]) && $serveur_cas[0] == sizeof($liste_cas)-1)
							echo 'selected';
						echo '>'.$serveur_cas[1].'</option>';
					}
				?>
				</select>
			</td>
			<td class="indication">
				Choisir un serveur dans la liste. Pour utiliser un serveur<br/> 
				 non d&eacute;fini, s&eacute;lectionner "Autre..."
			</td>
		</tr>
		<tr><td><br/></td><td></td><td></td></tr>
		<tr>
			<td>
				<label for="cas_host">Adresse du serveur CAS</label> <span class="obligatoire">*</span>
			</td>
			<td>
				<input type=text name="cas_host" id="cas_host" 
				<?php 
					if(isset($_POST[ 'cas_liste' ]))
						 echo 'value="'.$liste_cas[$_POST[ 'cas_liste' ]][2].'"';
					elseif(isset($_SESSION[ 'etape4' ][ 'cas_host' ])) 
						echo 'value="'.$_SESSION[ 'etape4' ][ 'cas_host' ].'"'; 
				?> 
				required />
			</td>
			<td class="indication">
				cas.societe.fr
			</td>
		</tr>
		<tr>
			<td>
				<label for="cas_port">Port du serveur CAS</label> <span class="obligatoire">*</span>
			</td>
			<td>
				<input type=number name="cas_port" id="cas_port" 
				<?php 
					if(isset($_POST[ 'cas_liste' ]))
						 echo 'value="'.$liste_cas[$_POST[ 'cas_liste' ]][3].'"';
					elseif(isset($_SESSION[ 'etape4' ][ 'cas_port' ])) 
						echo 'value="'.$_SESSION[ 'etape4' ][ 'cas_port' ].'"'; 
					else echo 'value="443"';
				?> 
				required />
			</td>
			<td class="indication">
				Par d&eacute;faut 443 (https)
			</td>
		</tr>
		<tr>
			<td>
				<label for="cas_context">Contexte du serveur CAS</label>
			</td>
			<td>
				<input type=text name="cas_context" id="cas_context" 
				<?php 
					if(isset($_POST[ 'cas_liste' ]))
						 echo 'value="'.$liste_cas[$_POST[ 'cas_liste' ]][4].'"';
					elseif(isset($_SESSION[ 'etape4' ][ 'cas_context' ])) 
						echo 'value="'.$_SESSION[ 'etape4' ][ 'cas_context' ].'"'; 
					else echo 'value="/CAS"';
				?> 
				/>
			</td>
			<td class="indication">
				G&eacute;n&eacute;ralement "/cas"
			</td>
		</tr>
		<tr><td><br/></td><td></td><td></td></tr>
		<tr>
			<td>
				<label for="url_reception">URL de la page d'identification sur le serveur</label> <span class="obligatoire">*</span>
			</td>
			<td>
				<input type=text size="30" name="url_reception" id="url_reception" 
				<?php 
					if(!empty($_POST[ 'url_reception' ]))
						 echo 'value="'.$_POST[ 'url_reception' ].'"';
					else	
					{
						$chemin_reception='http://'.$_SERVER[ 'HTTP_HOST' ].$_SERVER[ 'REQUEST_URI' ];
 						$chemin_reception=substr($chemin_reception,0,-25);//25 est le nombre de caractere pour "/configuration/etape4.php"
						$chemin_reception.='/reception.php';
						echo 'value="'.$chemin_reception.'"';
					}
				?> 
				required />
			</td>
			<td class="indication">
				Page sur laquelle est redirig&eacute;e l'utilisateur apr&egrave;s d&eacute;connexion CAS<br/>
			</td>
		</tr>
	</table>
</fieldset>
<br/>
<input type=checkbox name="captcha" id="captcha" <?php if(isset($_POST[ 'captcha' ])) echo 'checked'; elseif(!empty($_SESSION[ 'etape4' ][ 'captcha' ]) && $_SESSION[ 'etape4' ][ 'captcha' ] == 1) echo 'checked'; else echo ''; ?> /><label for="captcha">Je veux prot&eacute;ger le formulaire de demande d'indentifiants par captcha (&eacute;vite le spam)</label>

<table class="case_bouton">
	<tr>
		<td id="etape_precedente">
			<input type=button value="Revenir &agrave; l'&eacute;tape 3" class="bouton" onclick="javascript:location.href='etape3.php'" />
		</td>
		<td id="etape_suivante">
			<input type=submit value="Terminer la configuration" name="terminer" class="bouton"/>
		</td>
	</tr>
</table>
</form>
</div>
</body>
</html>
