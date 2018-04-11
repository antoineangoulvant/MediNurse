<?php

include '../../config.php';
include '../../'.$chemin_connection;


$name=$_POST['name'];
$idCalendar=$_POST['idCalendar'];

$query = "UPDATE T_CALENDAR SET calendar_name='".$name."' WHERE id_calendar='".$idCalendar."'";
//echo $query;
 // connection to the database
 try {
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password,$mysql_base);
	mysqli_query($link, "SET NAMES 'utf8'");
 } catch(Exception $e) {
	exit('Unable to connect to database.');
 }
 // Execute the query
 $resultat = mysqli_query($link, $query);
 if (!$resultat) {
		printf("Error: %s\n", mysqli_error($link));
		exit();
} 



?>
