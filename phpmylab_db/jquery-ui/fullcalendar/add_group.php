<?php
header("content-type: application/json"); 

include '../../config.php';
include '../../'.$chemin_connection;


$group=$_POST['group'];
$idCalendar=$_POST['idCalendar'];
//$canModify=$_GET['canModify'];
$canModify=false;




$query = "SELECT utilisateur FROM T_UTILISATEUR WHERE groupe='".$group."'";
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

$users_tab=array();
while($row = mysqli_fetch_array($resultat))
{	
		array_push($users_tab, $row['utilisateur']);
}
//print_r($users_tab);
mysqli_free_result($resultat);

foreach($users_tab as $user)
{	
	$query2 = "REPLACE INTO T_CALENDAR_AUTORISATIONS VALUES('".$user."' , '".$idCalendar."', '".$canModify."')";
	$resultat2 = mysqli_query($link, $query2);
	if (!$resultat2) {
		printf("Error: %s\n", mysqli_error($link));
		exit();
	}	 
}

mysqli_close($link);

?>
