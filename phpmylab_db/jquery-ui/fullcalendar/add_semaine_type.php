<?php
include '../../config.php';
include '../../'.$chemin_connection;

$idCalendar=utf8_encode($_POST['idCalendar']);
$idSemaine=utf8_encode($_POST['idSemaine']);
$date_view=$_POST['date_view'];

if(isset($_POST['nom_etud']) && !empty($_POST['nom_etud'])){
		$nom_etud=$_POST['nom_etud'];
}
if(isset($_POST['nom_tuteur']) && !empty($_POST['nom_tuteur'])){
		$nom_tuteur=$_POST['nom_tuteur'];
}

 try {
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password,$mysql_base);
	mysqli_query($link, "SET NAMES 'utf8'");
 } catch(Exception $e) {
	exit('Unable to connect to database.');
 }
 
 $monday = date("Y-m-d" ,strtotime($date_view));


 $query = "SELECT h_start, h_end, num_jour, num_jour_fin, title, lieu, description, intervenant FROM T_EVENT_REFERENCE WHERE id_semaine_type='".$idSemaine."';";
 $result = $link->query($query);
 if (!$result) {
				printf("Error: %s\n", mysqli_error($link));
				exit();
	}
	
 $tab=array();

 while($row = mysqli_fetch_array($result)) {
          
			array_push($tab, $row);   
 }


foreach($tab as $row)
{
	
	$title =$row['title']; 
	
	$desc = $row['description']; 
	
	$lieu=	$row['lieu'];
    $color = '#7bd148';	
    $intervenant = $row['intervenant'];	
	if($row['num_jour']==0 && $row['num_jour_fin']==null){
		
		$start = "".$monday." ".$row['h_start']; 
		$end = "".$monday." ".$row['h_end'];
		
	}elseif($row['num_jour_fin']!=$row['num_jour']){
		
		$next_monday = date("Y-m-d" ,strtotime($monday."+ 7 days"));
		$start = "".date("Y-m-d" ,strtotime($monday."+ ".$row['num_jour']." days"))." ".$row['h_start'];
		$end = "".date("Y-m-d" ,strtotime($next_monday."+ ".$row['num_jour_fin']." days"))." ".$row['h_end'];	
		
	}else{
		
		$start = "".date("Y-m-d" ,strtotime($monday."+ ".$row['num_jour']." days"))." ".$row['h_start'];
		$end = "".date("Y-m-d" ,strtotime($monday."+ ".$row['num_jour']." days"))." ".$row['h_end'];
			
	}
	$query2 = "INSERT INTO T_EVENT (id_calendar,title, start, end, description, background_color, intervenant, lieu) VALUES ('".$idCalendar."','".$title."','".$start."','".$end."' , '".$desc."', '".$color."', '".$intervenant."' ,'".$lieu."');" ;
    $result2 = $link->query($query2);
	if (!$result2) {
				printf("Error: %s\n", mysqli_error($link));
				exit();
	}
}

$friday = date("Y-m-d" ,strtotime($monday."+ 5 days"));
$query = "INSERT INTO T_EVENT (id_calendar,title, start, end, is_visible, description) VALUES('".$idCalendar."','".$nom_etud."','".$monday."','".$friday."',false, '".$nom_tuteur."');";
$result = $link->query($query);
 if (!$result) {
				printf("Error: %s\n", mysqli_error($link));
				exit();
	}
 

mysqli_close($link);

 
?>
