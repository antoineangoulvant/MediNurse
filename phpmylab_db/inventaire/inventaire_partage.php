<?php
header("content-type: application/json"); 

include '../config.php';
include '../'.$chemin_connection;
include '../jquery-ui/fullcalendar/commun_function.php';


if(isset($_POST['where']) && isset($_POST['limite']) && isset($_POST['nb']))
{
	$val=array('where' => $_POST['where'],
			   'limite' => $_POST['limite'],
			   'nb' => $_POST['nb']);

	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
	mysqli_select_db($link,$mysql_partage_base) or die('Selection de la base impossible.');

	//Nombre de resultats total
	$query2 = 'SELECT COUNT(*) FROM T_INVENTAIRE_PARTAGE '.$val['where'];
	$result2=mysqli_query($link,$query2);
	$donnee=mysqli_fetch_row($result2);

	$fiches=array();
	$pertinences=array();
	$fiches['nb_resultats']=$donnee[0];
	$query = 'SELECT * FROM T_INVENTAIRE_PARTAGE '.$val['where'].' ORDER BY LIBELLE LIMIT '.$_POST['limite'].' , '.$_POST['nb'];
	$result=mysqli_query($link,$query);
	
	if(!$result)	return array();

	while($donnees=mysqli_fetch_array($result, MYSQL_BOTH))
	{
		$fiche[ 'ID_MATERIEL' ] = $donnees[ 'ID_MATERIEL' ];
		$fiche[ 'LIBELLE' ] = $donnees[ 'LIBELLE' ];
		$fiche[ 'GROUPE' ] = $donnees[ 'GROUPE' ];
		$fiche[ 'DESCRIPTION' ] = $donnees[ 'DESCRIPTION' ];
		$fiche[ 'UTILISATION' ] = $donnees[ 'UTILISATION' ];	
		$fiche[ 'DISPONIBILITE' ] = $donnees[ 'DISPONIBILITE' ];
		$fiche[ 'PHOTO' ] = $donnees[ 'PHOTO' ];
		$fiche[ 'LABO_ORIGINE' ] = $donnees[ 'labo_origine' ];
	//	$fiche[ 'is_partage' ] = 1;
	//	$fiche[ 'PERTINENCE' ] = score_pertinence($donnees[ 'LIBELLE' ],$donnees[ 'DESCRIPTION' ],$tab_keywords);
	//	array_push($pertinences,score_pertinence($donnees[ 'LIBELLE' ],$donnees[ 'DESCRIPTION' ],$tab_keywords));
		array_push($fiches,$fiche);
	}
	
	//Ordonne le tableau par pertinence
	//array_multisort($pertinences,SORT_DESC,$fiches);

	mysqli_free_result($result);
	echo json_encode($fiches);
}
?>
