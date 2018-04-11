<?php

include '../../config.php';
include '../../'.$chemin_connection;

/* VALUES */
$id=$_POST['idEvent'];


// connexion à la base de données
 try {
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password,$mysql_base);
 } catch(Exception $e) {
 exit('Impossible de se connecter à la base de données.');
 }


$sql = "DELETE FROM T_EVENT_REFERENCE WHERE id_event='".$id."'";
$result2 = $link->query($sql);
	if (!$result2) {
		printf("Error: %s\n", mysqli_error($link));
		exit();
	}

?>
