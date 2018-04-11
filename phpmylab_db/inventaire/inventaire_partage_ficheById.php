<?php
header("content-type: application/json"); 

include '../config.php';
include '../'.$chemin_connection;
include '../jquery-ui/fullcalendar/commun_function.php';


if(isset($_POST['id_partage']) )
{
	$id_partage = $_POST['id_partage'];
//$val['id_partage']=2;
//$val['origine']='LIMOS';
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
	mysqli_select_db($link,$mysql_partage_base) or die('Selection de la base impossible.');

	$fiche=array();
	$query = 'SELECT * FROM T_INVENTAIRE_PARTAGE WHERE ID_MATERIEL="'.$id_partage.'"';//.$limitbasse.','.$nbparpage;
	$result=mysqli_query($link,$query);
	
	if(!$result)	return array();

	$donnees=mysqli_fetch_array($result, MYSQL_BOTH);
	
	$fiche[ 'ID_MATERIEL' ] = $donnees[ 'ID_MATERIEL' ];
	$fiche[ 'LIBELLE' ] = $donnees[ 'LIBELLE' ];
	$fiche[ 'GROUPE' ] = $donnees[ 'GROUPE' ];
	$fiche[ 'DESCRIPTION' ] = $donnees[ 'DESCRIPTION' ];
	$fiche[ 'UTILISATION' ] = $donnees[ 'UTILISATION' ];	
	$fiche[ 'DISPONIBILITE' ] = $donnees[ 'DISPONIBILITE' ];
	$fiche[ 'PHOTO' ] = $donnees[ 'PHOTO' ];
	$fiche[ 'NOM_CONTACT' ] = $donnees[ 'NOM_CONTACT' ];
	$fiche[ 'TEL_CONTACT' ] = $donnees[ 'TEL_CONTACT' ];
	$fiche[ 'EMAIL_CONTACT' ] = $donnees[ 'EMAIL_CONTACT' ];
	$fiche[ 'id_local' ] = $donnees[ 'id_local' ];
	$fiche[ 'labo_origine' ] = $donnees[ 'labo_origine' ];
	$fiche[ 'is_partage' ] = $donnees[ 'partage' ];

	mysqli_free_result($result);
	echo json_encode($fiche);
}
?>
