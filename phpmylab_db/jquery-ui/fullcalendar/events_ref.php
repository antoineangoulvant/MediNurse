<?php
header("content-type: application/json"); 

include '../../config.php';
include '../../'.$chemin_connection;
include 'commun_function.php';
include 'session.php';


// List of events
$json = array();
if (phpversion() > 5.3) {
  $monday = date("Y-m-d" ,strtotime('monday this week'));
}
else {
  $monday = date("Y-m-d" ,strtotime('last monday'));
}


if(isset($_GET['semaine_id']) && !empty($_GET['semaine_id']) ){
	$semaine_id=$_GET['semaine_id'];
} 
 else{
	 $semaine_id=null;
}

if( ! canUserReadTemplateWeek($_SESSION[ 'connection' ][ 'utilisateur' ], $semaine_id) ) {
  echo canUserReadTemplateWeek($_SESSION[ 'connection' ][ 'utilisateur' ], $semaine_id);
  echo "no ways";
  return false;  
}

try {
 $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password,$mysql_base);

 } catch(Exception $e) {
  exit('Unable to connect to database.');
 }

 $query = "SELECT id_event, title, h_start, h_end, description, num_jour, num_jour_fin, lieu, intervenant FROM T_EVENT_REFERENCE WHERE id_semaine_type='".$semaine_id."' ORDER BY id_event";
 
 // Execute the query
 $result = mysqli_query($link, $query);
 if (!$result) {
		printf("Error: %s\n", mysqli_error($link));
		exit();
} 
//print_r($result);

$return_array = array();
 while($row = mysqli_fetch_array($result)) {
		$event_array = array();
		

		// Add data from database to the event array
		$event_array['id'] = $row['id_event'];
		
		$event_array['title'] = utf8_encode($row['title']);
 
		$event_array['borderColor'] = 'black';			//default event border color 
		$event_array['textColor'] = 'black';			//default event fontcolor
		$event_array['backgroundColor'] = '#7bd148';      //default event color 
		$event_array['lieu'] = utf8_encode($row['lieu']);      
		$event_array['intervenant']=$row['intervenant'];
		if($row['description']==null || empty($row['description']))
		{   
			$event_array['description'] = 'description';
			
		}else{
			$event_array['description'] = utf8_encode($row['description']);			
		}
		if($row['num_jour']==0 && $row['num_jour_fin']==0){
	
			$event_array['start'] = "".$monday." ".$row['h_start']; 
			$event_array['end'] = "".$monday." ".$row['h_end'];
			
		}elseif($row['num_jour_fin']<$row['num_jour']){
			
			$next_monday = date("Y-m-d" ,strtotime($monday."+ 7 days"));
			$event_array['start'] = "".date("Y-m-d" ,strtotime($monday."+ ".$row['num_jour']." days"))." ".$row['h_start'];
			$event_array['end'] = "".date("Y-m-d" ,strtotime($next_monday."+ ".$row['num_jour_fin']." days"))." ".$row['h_end'];	
			
		}else{
			
			$event_array['start'] = "".date("Y-m-d" ,strtotime($monday."+ ".$row['num_jour']." days"))." ".$row['h_start'];
			$event_array['end'] = "".date("Y-m-d" ,strtotime($monday."+ ".$row['num_jour']." days"))." ".$row['h_end'];
				
		}
	//	echo $event_array['id'];
		// Merge the event array into the return array
		array_push($return_array, $event_array);
		 
}			
	//print_r($return_array);
  mysqli_close($link);
    

  echo json_encode($return_array);
 

?>
