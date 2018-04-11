<?php

include '../../config.php';
include '../../'.$chemin_connection;
include 'session.php';
include 'commun_function.php';
 
$title=$_POST['title'];
$start=$_POST['start'];
$end=$_POST['end'];
$color=$_POST['backgroundColor'];   
$desc=$_POST['description'];
$idCalendar=$_POST['idCalendar'];
$intervenant=$_POST['intervenant'];
/* if($allDay=='true'){
				$allDay = 1;
			}
			else{
				$allDay = 0;
}*/

$user_id=$_SESSION[ 'connection' ][ 'utilisateur' ];

if( !canUserReadCalendar($user_id, $idCalendar) ) {
  echo "no ways";
  return false;
}

if( !canUserWriteCalendar($user_id, $idCalendar) ) {
  echo "no ways";
  return false;
}

// connexion à la base de données
  try {
 $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password,$mysql_base);
 mysqli_query($link, "SET NAMES 'utf8'");
 } catch(Exception $e) {
  exit('Unable to connect to database.');
 }
$query2 = "INSERT INTO T_EVENT (id_calendar,title, start, end, description, background_color,intervenant, is_visible) VALUES ('".$idCalendar."','".$title."','".$start."','".$end."' , '".$desc."', '".$color."', '".$intervenant."', true);" or die("Error in the consult.." . mysqli_error($link));
$result2 = $link->query($query2);
	if (!$result2) {
		printf("Error: %s\n", mysqli_error($link));
		exit();
	}
 
 mysqli_close($link);

?>
