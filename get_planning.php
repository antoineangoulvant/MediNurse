<?php
/**
 * Created by PhpStorm.
 * User: antoi
 * Date: 14/05/2018
 * Time: 23:08
 */
// liste des événements
$json = array();
// requête qui récupère les événements
$requete = "SELECT * FROM evenement ORDER BY id";

try
{
    $bdd = new PDO('mysql:host=e88487-mysql.services.easyname.eu;dbname=u139724db1;charset=utf8', 'u139724db1', 'pp5959he');
}
catch (Exception $e)
{
    die('Erreur : ' . $e->getMessage());
}

// exécution de la requête
$resultat = $bdd->query($requete) or die(print_r($bdd->errorInfo()));

// envoi du résultat au success
echo json_encode($resultat->fetchAll(PDO::FETCH_ASSOC));
?>