<?php

include '../../config.php';
include '../../'.$chemin_connection;
 
$title=$_POST['title'];
$start_day=$_POST['start_day'];
$end_day=$_POST['end_day'];
$start_hour=$_POST['start_hour'];
$end_hour=$_POST['end_hour'];
$color=$_POST['backgroundColor'];   
$desc=$_POST['description'];
$lieu=$_POST['lieu'];

$idSemaine=$_POST['id_semaine_type'];
/* if($allDay=='true'){
				$allDay = 1;
			}
			else{
				$allDay = 0;
}*/

// connexion à la base de données
  try {
   $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password,$mysql_base);
   mysqli_query($link, "SET NAMES 'utf8'");
 } catch(Exception $e) {
  exit('Unable to connect to database.');
 }
$query2 = "INSERT INTO T_EVENT_REFERENCE (id_semaine_type,title, num_jour, num_jour_fin, h_start, h_end, description, lieu) VALUES ('".$idSemaine."','".$title."','".$start_day."','".$end_day."', '".$start_hour."' , '".$end_hour."', '".$desc."', '".$lieu."');" or die("Error in the consult.." . mysqli_error($link));
$result2 = $link->query($query2);
	if (!$result2) {
		printf("Error: %s\n", mysqli_error($link));
		exit();
	}
 
 mysqli_close($link);

?>

