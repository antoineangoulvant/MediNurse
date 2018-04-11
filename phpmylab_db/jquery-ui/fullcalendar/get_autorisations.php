<?php

header("content-type: application/json"); 

include '../../config.php';
include '../../'.$chemin_connection;
include 'commun_function.php';


 
 if(isset($_POST['idCalendar']) && !empty($_POST['idCalendar']) ){
	$idCalendar=$_POST['idCalendar'];
} 
 else{
	 $idCalendar=1;
 }
 
if(isset($_POST['user']) && !empty($_POST['user']) ){
	$user=$_POST['user'];
} 
 else{
	 $user=null;
 } 


try {
 $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password,$mysql_base);

 } catch(Exception $e) {
  exit('Unable to connect to database.');
 }

 $query = "SELECT utilisateur, id_calendar_view, can_modify_calendar, calendar_name FROM T_CALENDAR_AUTORISATIONS, T_CALENDAR WHERE id_calendar_view=id_calendar AND id_calendar_view='".$idCalendar."' AND utilisateur='".$user."';";
 $result = mysqli_query($link, $query);
 if (!$result) {
		printf("Error: %s\n", mysqli_error($link));
		exit();
} 
$return_array = array();
while($row = mysqli_fetch_array($result)) {
	  $calendar= array();
	  $calendar['user']=utf8_encode($row['utilisateur']);
	  $calendar['idCalendar']=utf8_encode($row['id_calendar_view']);
	  $calendar['calendarName']=utf8_encode($row['calendar_name']);
	  if($row['can_modify_calendar']=='0'){
				$calendar['canModify'] = false;
	  }
	  else{
				$calendar['canModify']= true;
	  }
	  array_push($return_array, $calendar);

}

mysqli_free_result($result);


mysqli_close($link);
echo json_encode($return_array);

?>
