<?php
/**
* En tete des pages relatives aux modules
*
*
* Date de création : 12 Avril 2012<br>
* Date de dernière modification : 7 Mai 2012
* @version 3.0.0
* @author Emmanuel Delage, Cedric Gagnevin <cedric.gagnevin@laposte.net>
* @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.html
* @copyright CNRS (c) 2015
* @package phpMyLab
*/

/*********************************************************************************
******************************  PLAN     *****************************************
*********************************************************************************/
//    | -A- Fonctions
//    | -B- HTML
//    | -C- JQuery pour les dates picker
/*********************************************************************************
***********************  -A- Fontions *******************************************
**********************************************************************************/

header('Content-Type: text/html; charset='. $charset); 
?>

<?php

/**
* Retourne le nombre de notifications du module community
*
*/
function get_notifications($user)
{
	include ("config.php");
	include $chemin_connection;
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_error());
	mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

	//recuperation du timestamp de l'utilisateur
	$query='SELECT CONNEXION_COMMUNITY FROM T_UTILISATEUR WHERE UTILISATEUR="'.$user.'"';
	$result=mysqli_query($link,$query);
	$connexion = mysqli_fetch_array($result, MYSQL_BOTH);
	$timestamp=$connexion[0];
	mysqli_free_result($result);

	//Comptage du nombre de notification
	$query='SELECT COUNT(*) FROM T_PUBLICATION WHERE DATE_PUBLICATION > '.$timestamp;
	$result=mysqli_query($link,$query);
	$donnee = mysqli_fetch_array($result, MYSQL_BOTH);
	$notif=$donnee[0];
	mysqli_free_result($result);
	mysqli_close($link);
	return $notif;
}
/*********************************************************************************
***********************  -B- HTML *******************************************
**********************************************************************************/


?>

<!DOCTYPE html>
<html lang="fr">
<head>

<!--******************************************************************************
*******************  -C- JQuery pour les dates picker ****************************
********************************************************************************
<link rel="stylesheet" href="jquery-ui/css/ui-lightness/jquery-ui-1.8.18.custom.css">
<script src="jquery-ui/js/jquery-1.7.1.min.js"></script>
<script src="jquery-ui/js/lib2/jquery-ui.custom.min.js"></script>
<script type='text/javascript' src='jquery-ui/fullcalendar/fullcalendar.min.js' charset="UTF-8"></script>
<script src="jquery-ui/js/lib2/jquery-ui.custom.min.js"></script>


-->
<meta content='text/html; charset=utf-8' />
<link rel="stylesheet" href="./jquery-ui/js/cupertino/jquery-ui.min.css">
<link rel="stylesheet" type='text/css' href="./style.css">


<script src="./jquery-ui/js/1.11.2/external/jquery/jquery.js"></script>
<script src="./jquery-ui/js/1.11.2/jquery-ui.min.js"></script>

<script type='text/javascript' src='jquery-ui/js/1.11.2/moment.min.js' charset="UTF-8"></script>

<!--  color picker -->
<link rel='stylesheet' type='text/css' href='jquery-ui/js/1.11.2/jquery.simplecolorpicker.css' media='print'>
<script src="./jquery-ui/js/1.11.2/jquery.simplecolorpicker.js"></script>



<script>
jQuery(function($){
	   $.datepicker.regional['fr'] = {
	   closeText: 'Fermer',
	   prevText: '&#x3c;Prec',
	   nextText: 'Suiv&#x3e;',
	   currentText: 'Courant',
	   monthNames: ['Janvier','Fevrier','Mars','Avril','Mai','Juin',
	   'Juillet','Aout','Septembre','Octobre','Novembre','Decembre'],
	   monthNamesShort: ['Jan','Fev','Mar','Avr','Mai','Jun',
	   'Jul','Aou','Sep','Oct','Nov','Dec'],
	   dayNames: ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'],
	   dayNamesShort: ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'],
	   dayNamesMin: ['Di','Lu','Ma','Me','Je','Ve','Sa'],
	   weekHeader: 'Sm',
	   dateFormat: 'dd/mm/yy',
	   firstDay: 1,
	   isRTL: false,
	   showMonthAfterYear: false,
	   yearSuffix: ''};
	   $.datepicker.setDefaults($.datepicker.regional['fr']);
	   });


$(function() {
  var dates = $( "#date1, #date2" ).datepicker({
		   defaultDate: "+1w",
		   changeMonth: true,
		   numberOfMonths: 1,
		   showOn: "button",
		   showAnim: "drop",
		   buttonImage: "jquery-ui/css/ui-lightness/images/calendar-icon.png",
		   buttonImageOnly: true,
		   onSelect: function( selectedDate ) {
		   var option = this.id == "date1" ? "minDate" : "maxDate",
		   instance = $( this ).data( "datepicker" ),
		   date = $.datepicker.parseDate(
			instance.settings.dateFormat ||
			$.datepicker._defaults.dateFormat,
			selectedDate, instance.settings );
		   dates.not( this ).datepicker( "option", option, date );
		   }
		   });
  });

</script>

<!--******************************************************************************
*******************  -C- JQuery pour le full calendar ****************************
********************************************************************************-->

<link rel='stylesheet' type='text/css' href='jquery-ui/fullcalendar/fullcalendar.css'  />
<link rel='stylesheet' type='text/css' href='jquery-ui/fullcalendar/fullcalendar.print.css' media='print'  />

<script type='text/javascript' src='jquery-ui/fullcalendar/fullcalendar.min.js' charset="UTF-8"></script>
<script src='./jquery-ui/fullcalendar/lang-all.js' charset="UTF-8"></script>



<?php
	/////////////////////////// JAVASCRIPT //////////////////////////
?>
<!----->
<script  type="text/javascript" src="./javascript.js"></script>

<?php
	/////////////////////////// script CSS (habillage) //////////////////////////
?>
<link rel=stylesheet type="text/css" href="style.css">
<link rel="shortcut icon" type="image/x-icon" href="images/phpmylab.ico" />
<title>
<?php
	$pageCourante = substr(strrchr($_SERVER['PHP_SELF'], "/"), 1);
	if($pageCourante == 'missions.php') {$libelle_module = 'Portail de gestion des missions | ';echo $libelle_module;}
	elseif($pageCourante == 'conges.php') {$libelle_module = 'Portail de gestion des cong&eacute;s | ';echo $libelle_module;}
	elseif($pageCourante == 'planning.php' OR $pageCourante == 'planning_global.php') {$libelle_module = 'Portail des absences | ';echo $libelle_module;}
	elseif($pageCourante == 'expeditions.php') {$libelle_module = 'Portail de gestion des colis | ';echo $libelle_module;}
	elseif($pageCourante == 'inventaire.php') {$libelle_module = 'Portail de partage de mat&eacute;riel | ';echo $libelle_module;}
	elseif($pageCourante == 'community.php') {$libelle_module = 'R&eacute;seau social interne | ';echo $libelle_module;}
	echo $organisme; 
	?> 
</title>
</head>

<?php
	//Pour faire appel a la fonction qui calcule le nb jours ouvrés dans conges
	if($pageCourante == 'conges.php')
		echo '<body onLoad="clock();appelCalcNbJoursOuvres();">';
	else echo '<body onLoad="clock()">';
?>
<div id="module">

<?php
	/////////////////////////// Affiche entete connecté //////////////////////////
	echo '<div id="header_module">';
	echo '<table>
			<tr>
				<td class="header_module_gauche">';
	if($pageCourante == 'missions.php')
		echo '<span id="MISSIONS">MISSIONS';
	elseif($pageCourante == 'conges.php')
		echo '<span id="CONGES">CONGES';
	elseif($pageCourante == 'planning.php' OR $pageCourante == 'planning_global.php')
		echo '<span id="PLANNING">PLANNING';
	elseif($pageCourante == 'expeditions.php')
		echo '<span id="EXPEDITIONS">EXPEDITIONS';
	elseif($pageCourante == 'inventaire.php')
		echo '<span id="SHARE"><img src="images/share.png" alt="INVENTAIRE"  height=14/>';
	elseif($pageCourante == 'community.php')
		echo '<span id="COMMUNITY">COMMUNITY';
	echo			'</span> - '.$libelle_module.$organisme.'
				</td>
				<td class="header_module_droit">
					<p>Connect&eacute; en tant que <strong>'.$_SESSION[ 'connection' ][ 'prenom' ].' '.$_SESSION[ 'connection' ][ 'nom' ].'</strong></p>
				</td>
			</tr>
			<tr>
				<td class="header_module_gauche">';		

				if ($_SESSION[ 'connection' ][ 'admin' ]==1)
					echo '<a id="lien_admin" href="administration.php"  target="">Administration</a>';//administration.php?sid=' . $sid . '"
	
	echo	   '</td>
				<td id="deconnexion" class="header_module_droit">
						<a href="' . $_SERVER[ 'PHP_SELF' ] . '?disconnect=1" title="Cliquez ici pour vous d&eacute;connecter">
						<img src="images/logout.png" height=40 />
						<span>D&eacute;connexion</span>
					</a>
				</td>
			</tr>
		  </table>';
	
	echo	'<div >
				<table id="barreOnglets">
					<tr>';

					$width=100/count($modules);
					$widthOnglet= ' style="width: '.$width.'%" ';
					for ($i=0;$i<sizeof($modules);$i++)
					{
						if($modules[$i]=="INVENTAIRE" AND $visibilite_inventaire == 0 AND !in_array($_SESSION[ 'connection' ][ 'utilisateur' ],$gestionnaires_inventaire))
						{
							//Ne pas afficher l'onglet
						}
						elseif($modules[$i]=="COMMUNITY")
						{
							$selected="";
							$onglet = strtolower($modules[$i]).'.php';
							if($pageCourante == $onglet)
								$id='id="ongletSelectionne"';
							else $id='id="COMMUNITY_ONGLET"';
							echo '<td onclick="javascript:document.location.href=\''.$_SERVER[ 'PHP_SELF' ].'?choix_module='.$modules[$i].'\'" '.$id.$widthOnglet.'>'.$modules[$i];//sid='.$sid.'&

							if(get_notifications($_SESSION[ 'connection' ][ 'utilisateur' ]) > 0)
								echo '<span id="notifications">'.get_notifications($_SESSION[ 'connection' ][ 'utilisateur' ]).'</span>';
							echo '</td>';
						}
						else 
						{
							$onglet = strtolower($modules[$i]).'.php';
							if($pageCourante == $onglet)
								echo '<td onclick="javascript:document.location.href=\''.$_SERVER[ 'PHP_SELF' ].'?choix_module='.$modules[$i].'\'" id="ongletSelectionne" '.$widthOnglet.'>'.$modules[$i].'</td>';//sid='.$sid.'&
							else echo '<td onclick="javascript:document.location.href=\''.$_SERVER[ 'PHP_SELF' ].'?choix_module='.$modules[$i].'\'" id="'.$modules[$i].'_ONGLET" '.$widthOnglet.'>'.$modules[$i].'</td>';//sid='.$sid.'&
						}
					}
	echo			'</tr>
				</table>
	
			</div>
		</div>';

?>
