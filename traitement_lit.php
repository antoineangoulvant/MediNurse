<?php
/**
 * Created by PhpStorm.
 * User: antoi
 * Date: 27/04/2018
 * Time: 01:26
 */

    try
    {
        $bdd = new PDO('mysql:host=e88487-mysql.services.easyname.eu;dbname=u139724db1;charset=utf8', 'u139724db1', 'pp5959he');
    }
    catch (Exception $e)
    {
        die('Erreur : ' . $e->getMessage());
    }

    $req = $bdd->prepare('INSERT INTO lit(numero_chambre) VALUES(?)');
    $req->execute(array($_POST['chambre']));

    header('Location: index.php?page=gestionlit')
?>