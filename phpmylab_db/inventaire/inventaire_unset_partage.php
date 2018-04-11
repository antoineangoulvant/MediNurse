<?php

include '../config.php';
include '../'.$chemin_connection;
if(isset($_POST['id_local']) && isset($_POST['labo_origine']) )
{
	$id_local=$_POST['id_local'];
	$labo_origine=$_POST['labo_origine'];

	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
	mysqli_select_db($link,$mysql_partage_base) or die('Selection de la base impossible.');
	
	$query2 = 'DELETE FROM T_INVENTAIRE_PARTAGE WHERE labo_origine="'.$labo_origine.'" AND id_local="'.$id_local.'"';
	$result2=mysqli_query($link,$query2) or die('Erreur :'. mysqli_error($link));

}
 
?>
