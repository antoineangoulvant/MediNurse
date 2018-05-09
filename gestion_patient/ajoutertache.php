<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 09/05/2018
 * Time: 15:43
 */
//Empêche l'insertion dans la bdd si il y a une erreur
$formulaire_valide = true;
//Permet l'affichage d'un message d'envoi
$envoye = false;

try
{
    $bdd = new PDO('mysql:host=e88487-mysql.services.easyname.eu;dbname=u139724db1;charset=utf8', 'u139724db1', 'pp5959he');
}
catch (Exception $e)
{
    die('Erreur : ' . $e->getMessage());
}

$req = $bdd->prepare('SELECT * FROM patient WHERE id_patient = :id');
$req->execute(array('id' => $_GET['id']));
$resultat = $req->fetch();

?>