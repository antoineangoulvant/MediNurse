<?php
header("content-type: application/json"); 

include '../../config.php';
include '../../'.$chemin_connection;
include 'commun_function.php';

 $json = array();

 
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
$query = "SELECT title, start, end, description, is_visible FROM T_EVENT WHERE id_calendar='".$idCalendar."' AND is_visible='0' AND start BETWEEN '".$curWeek_start."' AND '".$curWeek_end."' ORDER BY id_event;";

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

$data =  array();
 while($row = mysqli_fetch_array($result)) {
		
		$data['etudiants']=$row['title'];
		$data['tuteur']=$row['description'];

	//array_push($data, $event_array);
}
//print_r($data);
  mysqli_close($link);

  //echo json_encode($data);
  echo json_encode($data);
?>
