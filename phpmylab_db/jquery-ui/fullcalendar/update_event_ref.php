<?php

include '../../config.php';
include '../../'.$chemin_connection;

/* VALUES */
$id=$_POST['id_event'];
$title=$_POST['title'];
$start_day=$_POST['start_day'];
$end_day=$_POST['end_day'];
$start_hour=$_POST['start_hour'];
$end_hour=$_POST['end_hour'];
$color=$_POST['backgroundColor'];   
$desc=$_POST['description'];
$lieu=$_POST['lieu'];
$intervenant=$_POST['intervenant'];

$idSemaine=$_POST['id_semaine_type'];
// connexion à la base de données
 try {
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password,$mysql_base);
	mysqli_query($link, "SET NAMES 'utf8'");
 } catch(Exception $e) {
 exit('Impossible de se connecter à la base de données.');
 }



$sql = "UPDATE T_EVENT_REFERENCE SET title='".$title."', num_jour='".$start_day."', num_jour_fin='".$end_day."', h_start='".$start_hour."', h_end='".$end_hour."',description='".$desc."', lieu='".$lieu."', intervenant='".$intervenant."' WHERE id_event='".$id."' AND id_semaine_type='".$idSemaine."'";
$result2 = $link->query($sql);
	if (!$result2) {
		printf("Error: %s\n", mysqli_error($link));
		exit();
	}
//$q = $bdd->prepare($sql);
//$q->execute(array($title,$start,$end,$id));
 
?>

