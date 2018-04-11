<?php

include '../../config.php';
include '../../'.$chemin_connection;
include 'session.php';
include 'commun_function.php';

/* VALUES */
$id=$_POST['id'];
//$id='173';
$title=$_POST['title'];
$start=$_POST['start'];
$end=$_POST['end'];
//$allDay=$_POST['allDay'];
$color=$_POST['backgroundColor'];
$desc=$_POST['description'];
$lieu=$_POST['lieu'];
$intervenant=$_POST['intervenant'];


$user_id=$_SESSION[ 'connection' ][ 'utilisateur' ];

if( !canUserUpdateCalendar($user_id, $id) ) {
  echo "no ways";
  return false;
}

 try {
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password,$mysql_base);
	mysqli_query($link, "SET NAMES 'utf8'");
 } catch(Exception $e) {
 exit('Impossible de se connecter à la base de données.');
 }



$sql = "UPDATE T_EVENT SET title='".$title."', start='".$start."', end='".$end."', background_color='".$color."', description='".$desc."', lieu='".$lieu."', intervenant='".$intervenant."' WHERE id_event='".$id."'";
$result2 = $link->query($sql);
	if (!$result2) {
		printf("Error: %s\n", mysqli_error($link));
		exit();
	}
//$q = $bdd->prepare($sql);
//$q->execute(array($title,$start,$end,$id));
 
?>
