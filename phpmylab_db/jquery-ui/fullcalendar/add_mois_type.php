<?php
include '../../config.php';
include '../../'.$chemin_connection;

$idCalendar=utf8_encode($_POST['idCalendar']);
$idSemaine=utf8_encode($_POST['idSemaine']);
$date_view=$_POST['date_view'];


 try {
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password,$mysql_base);
	mysqli_query($link, "SET NAMES 'utf8'");
 } catch(Exception $e) {
	exit('Unable to connect to database.');
 }
 
 $monday = date("Y-m-d" ,strtotime($date_view));


 $query = "SELECT h_start, h_end, num_jour, num_jour_fin, title FROM T_EVENT_REFERENCE WHERE id_semaine_type='".$idSemaine."';";
 $result = $link->query($query);
 if (!$result) {
				printf("Error: %s\n", mysqli_error($link));
				exit();
	}
	
 $tab=array();

 while($row = mysqli_fetch_array($result)) {
          
			array_push($tab, $row);   
 }


foreach($tab as $line)
{
	
	// membres dans config.php
	foreach($membres as $membre){
		$query="SELECT nom, prenom, mel FROM T_UTILISATEUR WHERE utilisateur='".$membre."'";
		$result = mysqli_query($link, $query);
		if (!$result) {
				printf("Error: %s\n", mysqli_error($link));
				exit();
		}
		$row = mysqli_fetch_array($result);
		$title=$row['prenom'];
		//mysqli_free_result($result);
		
		$next_monday = date("Y-m-d" ,strtotime($monday."+ 7 days"));
		$start = "".date("Y-m-d" ,strtotime($monday."+ ".$line['num_jour']." days"))." ".$line['h_start'];
		$end = "".date("Y-m-d" ,strtotime($next_monday."+ ".$line['num_jour_fin']." days"))." ".$line['h_end'];	
	
		$query2 = "INSERT INTO T_EVENT (id_calendar,title, start, end, background_color) VALUES ('".$idCalendar."','".$title."','".$start."','".$end."', '".$color."');" ;
		$result2 = $link->query($query2);
		if (!$result2) {
					printf("Error: %s\n", mysqli_error($link));
					exit();
		}
		$monday=$next_monday;
	}
}

//$friday = date("Y-m-d" ,strtotime($monday."+ 5 days"));
/*
// Event durant toute la semaine, non visible dans le calendrier    permet de stocker le nom des étudiants et du tuteur
try {
 $link = mysqli_connect($mysql_location,$mysql_user,$mysql_password,$mysql_base);
} catch(Exception $e) {
	exit('Unable to connect to database.');
}
$query = "INSERT INTO T_EVENT (id_calendar,title, start, end, is_visible, description) VALUES('".$idCalendar."','".$nom_etud."','".$monday."','".$friday."',false, '".$nom_tuteur."');";
$result = $link->query($query);
 if (!$result) {
				printf("Error: %s\n", mysqli_error($link));
				exit();
	}
 
*/
mysqli_close($link);

 
?>
