<?php

//Source de ces fonctions : www.asp-php.net/scripts/asp-php/html2rgb.php

function bornes($nb,$min,$max)
{
  if ($nb<$min) $nb=$min; 
  if ($nb>$max) $nb=$max; 
  return $nb;
}

function rgb2html($tablo)
{
  // Vérification des bornes...
  for($i=0;$i<=2;$i++)
  {
    $tablo[$i]=bornes($tablo[$i],0,255);
  }
  // Le str_pad permet de remplir avec des 0
  // parce que sinon rgb2html(Array(0,255,255)) retournerai #0ffff<=manque un 0 !
  return "#" . str_pad(dechex( ($tablo[0]<<16)|($tablo[1]<<8)|$tablo[2] ), 6, "0", STR_PAD_LEFT);
}

//Associe un couleur pour chaque element du tableau
function couleurs_elements($tab_elements,$startcolor, $endcolor)
{
	$tab_retour=array();
	$taille=count($tab_elements);
	if ($taille>0)
	{
		for ($i=0;$i<$taille;$i++)
		{
			for ($j=0;$j<3;$j++) // Pour traiter le Rouge, Vert, Bleu
			{
				$buffer[$j] = $startcolor[$j] + ($i/$taille)*($endcolor[$j]-$startcolor[$j]);
			}
			$tab_retour[$i]=array($tab_elements[$i],rgb2html($buffer));
		}
	}
	return $tab_retour;
}

include($chemin_connection);
// Connecting, selecting database:
$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password);
mysqli_select_db($link,$mysql_base) or die('Could not select database');

//Sur 3ans
$anneeDebut=date("Y")-1;
$anneeFin=date("Y")+1;

//Pour les congés
if($_SESSION['affi_absences']['nom_prenom'] == 'NULL')
	$query = 'SELECT DISTINCT T_CONGE.UTILISATEUR FROM T_CONGE, T_UTILISATEUR WHERE T_UTILISATEUR.UTILISATEUR=T_CONGE.UTILISATEUR AND T_CONGE.GROUPE="'.$_SESSION['affi_absences']['groupe'].'" AND T_CONGE.DEBUT_DATE >= "'.$anneeDebut.'" AND T_CONGE.DEBUT_DATE <= "'.$anneeFin.'" AND VALIDE in(0,1)';
else $query = 'SELECT DISTINCT T_CONGE.UTILISATEUR FROM T_CONGE, T_UTILISATEUR WHERE T_UTILISATEUR.UTILISATEUR=T_CONGE.UTILISATEUR AND T_CONGE.UTILISATEUR="'.$_SESSION['affi_absences']['nom_prenom'].'" AND T_CONGE.GROUPE="'.$_SESSION['affi_absences']['groupe'].'" AND T_CONGE.DEBUT_DATE >= "'.$anneeDebut.'" AND T_CONGE.DEBUT_DATE <= "'.$anneeFin.'" AND VALIDE in(0,1)';
$result = mysqli_query($link,$query) or die('Query '.$query.'| failed: ' . mysqli_error());		

$tab_utilisateurs=array();
while($utilisateurs = mysqli_fetch_row($result))
{
	array_push($tab_utilisateurs,$utilisateurs[0]);
}

$start = Array(0,255,8); // Tableau RGB de départ : vert clair
$end = Array(0,63,2); // Tableau RGB d'arrivée : vert foncé

//Tableau pour les congés
$tab_couleurs_conges=couleurs_elements($tab_utilisateurs,$start, $end);


//Pour les missions
if($_SESSION['affi_absences']['nom_prenom'] == 'NULL')
	$query = 'SELECT DISTINCT T_MISSION.UTILISATEUR FROM T_MISSION, T_UTILISATEUR WHERE T_UTILISATEUR.UTILISATEUR=T_MISSION.UTILISATEUR AND T_MISSION.GROUPE="'.$_SESSION['affi_absences']['groupe'].'" AND T_MISSION.ALLER_DATE >= "'.$anneeDebut.'" AND T_MISSION.RETOUR_DATE <= "'.$anneeFin.'" AND VALIDE in(0,1)';
else $query = 'SELECT DISTINCT T_MISSION.UTILISATEUR FROM T_MISSION, T_UTILISATEUR WHERE T_UTILISATEUR.UTILISATEUR=T_MISSION.UTILISATEUR AND T_MISSION.UTILISATEUR="'.$_SESSION['affi_absences']['nom_prenom'].'" AND T_MISSION.GROUPE="'.$_SESSION['affi_absences']['groupe'].'" AND T_MISSION.ALLER_DATE >= "'.$anneeDebut.'" AND T_MISSION.RETOUR_DATE <= "'.$anneeFin.'" AND VALIDE in(0,1)';
$result = mysqli_query($link,$query) or die('Query '.$query.'| failed: ' . mysqli_error());		

$tab_utilisateurs=array();
while($utilisateurs = mysqli_fetch_row($result))
{
	array_push($tab_utilisateurs,$utilisateurs[0]);
}

$start = Array(2,237,249); // Tableau RGB de départ : bleu clair
$end = Array(40,52,127); // Tableau RGB d'arrivée : bleu foncé

//Tableau pour les missions
$tab_couleurs_missions=couleurs_elements($tab_utilisateurs,$start, $end);

/*********************************************************************************
***********************  Calcul des jours fériés (sur 3 ans) *********************
**********************************************************************************/

$joursferies=array();
for($i=0 ; $i<3 ; $i++)
{
	$annee=date("Y")+($i-1);//Pour faire Y-1 , Y et Y+1
	include "calendrier_variables.php";
	//Transformation des jours ouvrés pour que ça soit directement insérable dans la config du calendar
	//Format : Libelle du jour ferié -- Annee -- Mois -- Jour
	array_push($joursferies,array('Nouvel an',$annee,$feries[0][1]+1,$feries[0][0]+1));//Nouvel an de l'année en cours 
	array_push($joursferies,array('Paques',$annee,$feries[1][1]+1,$feries[1][0]+1));//Paques
	array_push($joursferies,array('Fete du travail',$annee,$feries[2][1]+1,$feries[2][0]+1));//Fete du travail
	array_push($joursferies,array('Victoire 1945',$annee,$feries[3][1]+1,$feries[3][0]+1));//Victoire 1945
	array_push($joursferies,array('Ascension',$annee,$feries[4][1]+1,$feries[4][0]+1));//Ascension	
	array_push($joursferies,array('Pentecote',$annee,$feries[5][1]+1,$feries[5][0]+1));//Pentecote
	array_push($joursferies,array('Fete nationale',$annee,$feries[6][1]+1,$feries[6][0]+1));//Fete nationale
	array_push($joursferies,array('Assomption',$annee,$feries[7][1]+1,$feries[7][0]+1));//Assomption
	array_push($joursferies,array('Toussaint',$annee,$feries[8][1]+1,$feries[8][0]+1));//Toussaint
	array_push($joursferies,array('Armistice 1918',$annee,$feries[9][1]+1,$feries[9][0]+1));//Armistice 1918 
	array_push($joursferies,array('Noel',$annee,$feries[10][1]+1,$feries[10][0]+1));//Noel
}

/*********************************************************************************
***********************  Génération de la config du calendar *********************
**********************************************************************************/
echo "
<script type='text/javascript'>
	$(document).ready(function() {
		var calendar = $('#calendar').fullCalendar({
			lang: 'fr',
			height: 600,
			header: {
				left: 'prev,next,today',
				center: 'title',
				right: 'month,basicWeek'
			},
			selectable: false,
			selectHelper: true,
			editable: false,
			events: [";

	include($chemin_connection);
	// Connecting, selecting database:
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password);
	mysqli_select_db($link,$mysql_base) or die('Could not select database');
	

	//JOURS FERIES
	$joursf=''; //Jours feries à inserer sur le planning comme evenement en rouge	

	 foreach($joursferies as $jourferie)
	 {
		
		$joursf .=	"{ 
					title: '".$jourferie[0]."',			
					start: new Date('".$jourferie[1].','.$jourferie[2].','.$jourferie[3]."'),
					end: new Date('".$jourferie[1].','.$jourferie[2].','.$jourferie[3]."'),
					
					color: '#EE3C42'
      		      		 },";
	 }

	//Affichage des evenements de l'année en cours (de janvier Y à fevrier Y+1 (compris))
	//Sur 3ans
	$anneeDebut=date("Y")-1;
	$anneeFin=date("Y")+1;

	//CONGES
	if($_SESSION['affi_absences']['nom_prenom'] == 'NULL')
		$query = 'SELECT * FROM T_CONGE, T_UTILISATEUR WHERE T_UTILISATEUR.UTILISATEUR=T_CONGE.UTILISATEUR AND T_CONGE.GROUPE="'.$_SESSION['affi_absences']['groupe'].'" AND T_CONGE.DEBUT_DATE >= "'.$anneeDebut.'" AND T_CONGE.DEBUT_DATE <= "'.$anneeFin.'" AND VALIDE in(0,1)';
	else $query = 'SELECT * FROM T_CONGE, T_UTILISATEUR WHERE T_UTILISATEUR.UTILISATEUR=T_CONGE.UTILISATEUR AND T_CONGE.UTILISATEUR="'.$_SESSION['affi_absences']['nom_prenom'].'" AND T_CONGE.GROUPE="'.$_SESSION['affi_absences']['groupe'].'" AND T_CONGE.DEBUT_DATE >= "'.$anneeDebut.'" AND T_CONGE.DEBUT_DATE <= "'.$anneeFin.'" AND VALIDE in(0,1)';
	$result = mysqli_query($link,$query) or die('Query '.$query.'| failed: ' . mysqli_error());	

	$conges='';

	 while($donnee=mysqli_fetch_array($result,MYSQL_BOTH))
	 {
		
		$tab_debut = explode("-",$donnee[ 'DEBUT_DATE' ]);
		$debut_annee=$tab_debut[0];
		$debut_mois=$tab_debut[1]-1;
		$debut_jour=$tab_debut[2];
		if($donnee[ 'DEBUT_AM' ] == 1) {$debut_heure=14; $allDay='allDay: false,';} else {$debut_heure=8;$allDay='';}
		$tab_fin = explode("-",$donnee[ 'FIN_DATE' ]);
		$fin_annee=$tab_fin[0];
		$fin_mois=$tab_fin[1]-1;
		$fin_jour=$tab_fin[2];
		if($donnee[ 'FIN_PM' ] == 1) $fin_heure=18; else {$fin_heure=12; $allDay='allDay: false,';}
		$date_debut= $debut_annee.','.$debut_mois.','.$debut_jour.','.$debut_heure;
		$date_fin= $fin_annee.','.$fin_mois.','.$fin_jour.','.$fin_heure;
		$title = substr($donnee[ 'PRENOM' ],0,1).'. '.ucfirst(strtolower($donnee[ 'NOM' ]));


		$accordeledroit=0;
		//l'admin ($accordeledroit=1) peut afficher tous les conges: 
		$query2 = 'SELECT ADMIN FROM T_UTILISATEUR WHERE UTILISATEUR=\''.$_SESSION[ 'connection' ][ 'utilisateur' ].'\'';
		$result2 = mysqli_query($link,$query2);
		$line2 = mysqli_fetch_array($result2, MYSQL_NUM);
		if ($line2[0]==1) $accordeledroit=1;
		mysqli_free_result($result2);
		//ajout pour eviter que les statuts 1 et 2 accédent aux informations des autres usagés
		//ajout pour eviter que les statuts 3 et 4 accèdent aux informations des autres groupes
		if ( ( (($_SESSION[ 'connection' ][ 'status' ] <= 2) || ($_SESSION[ 'connection' ][ 'status']==5)) && ($_SESSION[ 'connection' ][ 'utilisateur' ]== $donnee["UTILISATEUR"]))
		|| ( (($_SESSION[ 'connection' ][ 'status' ] > 2 && $_SESSION[ 'connection' ][ 'status' ] < 5)
		|| $_SESSION[ 'connection' ][ 'status' ]==6) &&
		(($_SESSION[ 'connection' ][ 'groupe' ]== $donnee["GROUPE"]) || ($_SESSION[ 'connection' ][ 'utilisateur' ]== $donnee["UTILISATEUR"])))
		|| ($accordeledroit==1) 
		|| ($_SESSION[ 'connection' ][ 'groupe' ]=='DIRECTION') )
		{
			$url= 'conges.php?r_gui=0&dec='.$donnee['ID_CONGE'].'#DEMANDE_CONGES';
 		}
		else $url= '';
	
		//couleur
		foreach ($tab_couleurs_conges as $key => $row)
		{
			foreach($row as $cell)
			{
				if ($cell == $donnee["UTILISATEUR"])
					$rang=$key;
			}
		} 
		$color= $tab_couleurs_conges[$rang][1];
		if($donnee["VALIDE"] == 0)
			$color='#E0E0E0\', textColor: \'#268E2B';

		$conges .=	"{ 
					title: '".$title."',			
					start: new Date(".$date_debut."),
					end: new Date(".$date_fin."),
					color: '".$color."',
					url: '".$url."'
      		      		 },";
	 }
	//MISSIONS
	if($_SESSION['affi_absences']['nom_prenom'] == 'NULL')
		$query = 'SELECT * FROM T_MISSION, T_UTILISATEUR WHERE T_UTILISATEUR.UTILISATEUR=T_MISSION.UTILISATEUR AND T_MISSION.GROUPE="'.$_SESSION['affi_absences']['groupe'].'" AND T_MISSION.ALLER_DATE >= "'.$anneeDebut.'" AND T_MISSION.ALLER_DATE <= "'.$anneeFin.'" AND VALIDE in(0,1)';
	else $query = 'SELECT * FROM T_MISSION, T_UTILISATEUR WHERE T_UTILISATEUR.UTILISATEUR=T_MISSION.UTILISATEUR AND T_MISSION.UTILISATEUR="'.$_SESSION['affi_absences']['nom_prenom'].'" AND T_MISSION.GROUPE="'.$_SESSION['affi_absences']['groupe'].'" AND T_MISSION.ALLER_DATE >= "'.$anneeDebut.'" AND T_MISSION.ALLER_DATE <= "'.$anneeFin.'" AND VALIDE in(0,1)';
	$result = mysqli_query($link,$query) or die('Query '.$query.'| failed: ' . mysqli_error());	

	$missions='';

	 while($donnee=mysqli_fetch_array($result,MYSQL_BOTH))
	 {
		$tab_debut = explode("-",$donnee[ 'ALLER_DATE' ]);
		$debut_annee=$tab_debut[0];
		$debut_mois=$tab_debut[1]-1;
		$debut_jour=$tab_debut[2];
		$tab_fin = explode("-",$donnee[ 'RETOUR_DATE' ]);
		$fin_annee=$tab_fin[0];
		$fin_mois=$tab_fin[1]-1;
		$fin_jour=$tab_fin[2];
		$date_debut= $debut_annee.','.$debut_mois.','.$debut_jour.',0,0';
		$date_fin= $fin_annee.','.$fin_mois.','.$fin_jour.',0,0';
		$title = substr($donnee[ 'PRENOM' ],0,1).'. '.ucfirst(strtolower($donnee[ 'NOM' ]));



		$accordeledroit=0;
		//le chef de service qui peut regarder la mission déclarée pour
		//une équipe par un de ses ITAs 
		if ($_SESSION[ 'connection' ][ 'status' ]==4)
		{
			$query2 = 'SELECT GROUPE FROM T_UTILISATEUR WHERE UTILISATEUR=\''.$donnee["UTILISATEUR"].'\'';
			$result2 = mysqli_query($link,$query2);
			$line2 = mysqli_fetch_array($result2, MYSQL_NUM);
			if ($line2[0]==$_SESSION[ 'connection' ][ 'groupe' ]) $accordeledroit=1;
			mysqli_free_result($result2);
		}
		//le chef d'equipe peut regarder les missions pour ses lignes budgetaires.
		//le responsable2 (mission) peut aussi regarder
		if ($_SESSION[ 'connection' ][ 'status' ]==3 || $_SESSION[ 'connection' ][ 'status' ]==1)
		{
        	$query2 = 'SELECT RESPONSABLE,RESPONSABLE2 FROM T_CORRESPONDANCE WHERE GROUPE=\''.$donnee["GROUPE"].'\'';
		$result2 = mysqli_query($link,$query2);
		$line2 = mysqli_fetch_array($result2, MYSQL_NUM);
		if ($line2[0]==$_SESSION[ 'connection' ][ 'utilisateur' ]) $accordeledroit=1;
		if ($line2[1]==$_SESSION[ 'connection' ][ 'utilisateur' ]) $accordeledroit=1;
		mysqli_free_result($result2);
		}
		//ajout pour eviter que les statuts 1 et 2 accèdent aux informations des autres usagés
		//ajout pour eviter que les statuts 3 et 4 accèdent aux informations des autres groupes
		if ( (($_SESSION[ 'connection' ][ 'status' ] <= 2) && ($_SESSION[ 'connection' ][ 'utilisateur' ]== $donnee["UTILISATEUR"]))
		|| (($_SESSION[ 'connection' ][ 'status' ] > 2 && $_SESSION[ 'connection' ][ 'status' ] < 5) && (($_SESSION[ 'connection' ][ 'groupe' ]== $donnee["GROUPE"]) || ($_SESSION[ 'connection' ][ 'utilisateur' ]== $donnee["UTILISATEUR"])))
		|| ($accordeledroit==1)
		|| ($_SESSION[ 'connection' ][ 'status' ] >= 5) )
		{
			$url= 'missions.php?r_gui=0&dem='.$donnee['ID_MISSION'].'#DEM_MISSION';
 		}
		else $url = '';

		//couleur
		foreach ($tab_couleurs_missions as $key => $row)
		{
			foreach($row as $cell)
			{
				if ($cell == $donnee["UTILISATEUR"])
					$rang=$key;
			}
		} 
		$color= $tab_couleurs_missions[$rang][1];

		$query3 = 'SELECT VALID_MISSIONS FROM T_CORRESPONDANCE WHERE GROUPE=\''.$donnee["GROUPE"].'\'';
		$result3=mysqli_query($link,$query3);
		$line3 = mysqli_fetch_array($result3, MYSQL_NUM);
		if($donnee["VALIDE"] == 0 AND $line3[0] == 1)
			$color='#E0E0E0\', textColor: \'#2C3EDD';

		$missions .=	"{ 
					title: '".$title."',			
					start: new Date(".$date_debut."),
					end: new Date(".$date_fin."),
					color: '".$color."',
					url: '".$url."'
      		      		 },";
	 }
	
	$evenements=$joursf.$conges.$missions;

	if($evenements != '')
		echo substr($evenements,0,-1);
										   
	echo '	],
		eventRender: function(event, element) {
			var start_hour=event.start.format(\'HH:MM\').toString();
			start_hour=start_hour.split(\':\');
			start_hour=start_hour[0];
			if(start_hour==\'00\'){
				element.find(\'.fc-time\').hide(); 	// cacher l\'heure de début dans la vue d\'un évènement 
			}
		}

	    });
	});
</script>';

	mysqli_close($link);

?>	
