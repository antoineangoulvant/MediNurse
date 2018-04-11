<?php
header("content-type: application/json"); 

include '../../config.php';
include '../../'.$chemin_connection;


$user=$_POST['user'];
$idCalendar=$_POST['idCalendar'];
//$canModify=$_GET['canModify'];
$canModify=false;

$query = "REPLACE INTO T_CALENDAR_AUTORISATIONS VALUES('".$user."' , '".$idCalendar."', '".$canModify."')";

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
