<?php
include '../../config.php';
include '../../'.$chemin_connection;


 
 if(isset($_POST['idCalendar']) && !empty($_POST['idCalendar']) ){
	$idCalendar=$_POST['idCalendar'];
} 
 else{
	 $idCalendar=1;
 }
if(isset($_POST['curWeek_start']) && !empty($_POST['curWeek_start']) ){
	$curWeek_start=$_POST['curWeek_start'];
} 
 else{
	 $curWeek_start=null;
 }
if(isset($_POST['curWeek_end']) && !empty($_POST['curWeek_end']) ){
	$curWeek_end=$_POST['curWeek_end'];
} 
 else{
	 $curWeek_end=null;
 }
 if(isset($_POST['nom_etud']) && !empty($_POST['nom_etud']) ){
	$nom_etud=$_POST['nom_etud'];
} 
 else{
	 $nom_etud=null;
 }
 if(isset($_POST['nom_tuteur']) && !empty($_POST['nom_tuteur']) ){
	$nom_tuteur=$_POST['nom_tuteur'];
} 
 else{
	 $nom_tuteur=null;
 }
 
 
$query = "UPDATE T_EVENT SET title='".$nom_etud."', description='". $nom_tuteur."' WHERE id_calendar='".$idCalendar."' AND is_visible='0' AND start BETWEEN '".$curWeek_start."' AND '".$curWeek_end."' ORDER BY id_event;";

 // connection to the database
 try {
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password,$mysql_base);
 } catch(Exception $e) {
  exit('Unable to connect to database.');
 }
 
 // Execute the query
 $result = mysqli_query($link, $query);
 if (!$result) {
		printf("Error: %s\n", mysqli_error($link));
		exit();
} 

mysqli_close($link);

?>
