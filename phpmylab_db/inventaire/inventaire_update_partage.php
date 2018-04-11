<?php
//header("content-type: application/json"); 


include '../config.php';
include '../'.$chemin_connection;

$fiche=array();
//if(isset($_POST['id_local']) ) ; //&& isset($_POST['libelle']) && isset($_POST['utilisation']) && isset($_POST['groupe']) && isset($_POST['nom_contact']) && isset($_POST['tel_contact']) && isset($_POST['email_contact']) && isset($_POST['disponibilite']) && isset($_POST['labo_origine']) && isset($_POST['description']))
//{
	$fiche['libelle']=$_POST['libelle'];
	$fiche['id_local']=$_POST['id_local'];
	$fiche['description']=$_POST['description'];
	$fiche['groupe']=$_POST['groupe'];
	$fiche['contact']=$_POST['contact'];
	$fiche['telephone']=$_POST['telephone'];
	$fiche['email']=$_POST['email'];
	$fiche['disponibilite']=$_POST['disponibilite'];
	$fiche['utilisation']=$_POST['utilisation'];
	$fiche['photo']=$_POST['photo'];
	$fiche['labo_origine']=$_POST['labo_origine'];

	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
	mysqli_select_db($link,$mysql_partage_base) or die('Selection de la base impossible.');

	$query = 'UPDATE T_INVENTAIRE_PARTAGE SET 
			LIBELLE="'.mysqli_real_escape_string($link,$fiche['libelle']).'",
			DISPONIBILITE="'.mysqli_real_escape_string($link,$fiche['disponibilite']).'",
			UTILISATION="'.mysqli_real_escape_string($link,$fiche['utilisation']).'",
			DESCRIPTION="'.mysqli_real_escape_string($link,$fiche['description']).'",
			GROUPE="'.mysqli_real_escape_string($link,$fiche['groupe']).'",
			NOM_CONTACT="'.mysqli_real_escape_string($link,$fiche['contact']).'",
			TEL_CONTACT="'.mysqli_real_escape_string($link,$fiche['telephone']).'",
			EMAIL_CONTACT="'.mysqli_real_escape_string($link,$fiche['email']).'",
			PHOTO="'.mysqli_real_escape_string($link,$fiche['photo']).'"';
			/*if(!empty($_FILES['photo_materiel']['name'])) $query.=',
			PHOTO="'.mysqli_real_escape_string($link,$_SESSION['share']['photo']).'"';*/
			$query.=' WHERE id_local="'.$fiche['id_local'].'" AND labo_origine="'.$fiche['labo_origine'].'"';

	//$query2 = 'INSERT INTO T_INVENTAIRE_PARTAGE (LIBELLE, id_local, DESCRIPTION, GROUPE, NOM_CONTACT, TEL_CONTACT, EMAIL_CONTACT, DISPONIBILITE, UTILISATION, PHOTO, labo_origine,  partage) VALUES ("'.$fiche['libelle'].'" , "'.$fiche['id_local'].'" , "'.$fiche['description'].'","'.$fiche['groupe'].'","'.$fiche['nom_contact'].'","'.$fiche['tel_contact'].'","'.$fiche['email_contact'].'" , "'.$fiche['disponibilite'].'" , "'.$fiche['utilisation'].'" , "'.$fiche['photo'].'", "'.$fiche['labo_origine'].'", 1)';
	//echo $query;
	$result=mysqli_query($link,$query) or die('Erreur :'. mysqli_error($link));

//}

?>
