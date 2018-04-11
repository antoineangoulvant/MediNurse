<?php
header("content-type: application/json"); 

include 'commun_function.php';
include '../../config.php';
include '../../'.$chemin_connection;
include 'session.php';


// List of events
 $json = array();
 
 
 if(isset($_GET['idCalendar']) && !empty($_GET['idCalendar']) ){
	$idCalendar=$_GET['idCalendar'];
} 
 else{
   //TODO:throw a redirection request
	 $idCalendar=1;
 }
 
if(isset($_POST['user_id']) && !empty($_POST['user_id']) ){
	$user_id=$_POST['user_id'];
} 
 else{
	 $user_id=$_SESSION[ 'connection' ][ 'utilisateur' ];
 }
 
if( !canUserReadCalendar($user_id, $idCalendar) ) {
  echo "no ways";
  return false;
}

  
// connection to the database
try {
 $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password,$mysql_base);

 } catch(Exception $e) {
  exit('Unable to connect to database.');
 }
/*
 $query = "SELECT canModifyCalendar FROM T_CALENDAR_AUTORISATIONS WHERE idCalendar_view='".$idCalendar."' AND utilisateur='".$user_id."';";
 $result = mysqli_query($link, $query);
 if (!$result) {
		printf("Error: %s\n", mysqli_error($link));
		exit();
} 
$canModify =  mysqli_fetch_row($result);
print_r($canModify);
 mysqli_free_result($result);
 */
 $query = "SELECT id_event, title, start, end, background_color,description, is_visible, lieu, intervenant FROM T_EVENT WHERE id_calendar='".$idCalendar."' ORDER BY id_event;";
 
 
 // Execute the query
 $result = mysqli_query($link, $query);
 if (!$result) {
		printf("Error: %s\n", mysqli_error($link));
		exit();
} 

$return_array = array();
 while($row = mysqli_fetch_array($result)) {
		$event_array = array();
		
		if($row['is_visible']=='0' )
		{
			//return;
			//$_POST['tuteur']= 'ok'; //$row['title'];
			//$_POST['etudiants']='Mathilde';
			continue;
		}
			
		// Add data from database to the event array
		$event_array['id'] = utf8_encode($row['id_event']);
		$event_array['title'] =utf8_encode( $row['title']);
	 /*  if( mb_detect_encoding($event_array['title'], 'UTF-8'))
	   {
			$event_array['title'] = $row['title'];
		}else{
			$event_array['title'] =utf8_encode( $row['title']);	
		}*/
		$event_array['start'] = utf8_encode($row['start']);
		$event_array['end'] = utf8_encode($row['end']);
		$event_array['borderColor'] = utf8_encode('black');			//default event border color 
		$event_array['textColor'] = utf8_encode('black');			//default event fontcolor
		if($row['lieu']==null || empty($row['lieu'])){
			$event_array['lieu']='lieu';
		}else{
			$event_array['lieu'] = utf8_encode($row['lieu']);
		}
		if($row['background_color']==null || empty($row['background_color']))
		{
			$event_array['backgroundColor'] = utf8_encode('#7bd148');      //default event color 
		}else{
			$event_array['backgroundColor'] = utf8_encode($row['background_color']);
		}
			
		if($row['description']==null || empty($row['description']))
		{   
			$event_array['description'] = 'description';
			
		}else{
			$event_array['description'] = utf8_encode($row['description']);			
		}
		if($row['intervenant']==null || empty($row['intervenant']))
		{   
			$event_array['intervenant'] = 'intervenant';
			
		}else{
			$event_array['intervenant'] = utf8_encode($row['intervenant']);			
		}
	/*  if($canModify[0]=='0')
	  {
			$event_array['editable']=false;
	  }
		if($row['allDay']=='1'){
			$event_array['allDay'] = true;
		}
		else{
			$event_array['allDay'] = false;
		}*/
	   
		// Merge the event array into the return array
		array_push($return_array, $event_array);
	 
}			

   mysqli_close($link);
      // print_r($return_array);
     // echo  json_last_error_msg();

  echo json_encode($return_array);
 

?>
