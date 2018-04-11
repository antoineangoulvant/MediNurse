<?php
//header("content-type: application/json"); 


include '../config.php';
include '../'.$chemin_connection;

if(isset($_POST['id_local']))
{
	$id=$_POST['id_local'];
	$origine=$_POST['origine'];

	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
	mysqli_select_db($link,$mysql_partage_base) or die('Selection de la base impossible.');

	$query = "SELECT DISPONIBILITE FROM T_INVENTAIRE_PARTAGE WHERE id_local='".$id."' AND labo_origine='".$origine."';";
	// Execute the query
	 $resultat = mysqli_query($link, $query);
	 if (!$resultat) {
			printf("Error: %s\n", mysqli_error($link));
			exit();
	} 
	$row=mysqli_fetch_array($resultat);
	if($row['DISPONIBILITE']==0)
	{
		$dispo=1;
	}else{
		$dispo=0;
	}
	//Nombre de resultats total
	$query2 = "UPDATE T_INVENTAIRE_PARTAGE SET DISPONIBILITE='".$dispo."' WHERE id_local='".$id."' AND labo_origine='".$origine."';";
	$result2=mysqli_query($link,$query2) or die('Erreur :'. mysqli_error($link));

}

?>
