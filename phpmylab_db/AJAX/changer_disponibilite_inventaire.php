<?php

include '../config.php';
include '../'.$chemin_connection;

if (isset($_POST['id_materiel']))
{
	$id_materiel=$_POST['id_materiel'];
}

 try {
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password,$mysql_base);
	mysqli_query($link, "SET NAMES 'utf8'");
 } catch(Exception $e) {
		exit('Unable to connect to database.');
 }
 $query = "SELECT DISPONIBILITE, partage FROM T_INVENTAIRE WHERE ID_MATERIEL='".$id_materiel."';";
 // Execute the query
 $resultat = mysqli_query($link, $query);
 if (!$resultat) {
		printf("Error: %s\n", mysqli_error($link));
		exit();
} 
$row=mysqli_fetch_array($resultat);
if($row['DISPONIBILITE']==0)
{
		$dispo=1;
}else{
		$dispo=0;
}
if($row['partage']==1){
	$postdata = http_build_query(
		array(
			'id_local' => $id_materiel,		
			'origine' => $organisme			
		)
	);
	// Set the POST options
	$opts = array('http' => 
		array (
			'method' => 'POST',
			'header' => 'Content-type: application/x-www-form-urlencoded',
			'content' => $postdata
		)
	);
	// Create the POST context
	$context  = stream_context_create($opts);
	//print_r($context);
	$json = file_get_contents($central_mysql_location."inventaire/inventaire_set_dispo.php", false, $context);

}
$query2 = "UPDATE T_INVENTAIRE SET DISPONIBILITE='".$dispo."' WHERE ID_MATERIEL='".$id_materiel."';";//.$dispo.' WHERE ID_MATERIEL='.$id_materiel;
// Execute the query
$resultat2 = mysqli_query($link, $query2);
if (!$resultat2) {
	printf("Error: %s\n", mysqli_error($link));
	exit();

} 

?>
