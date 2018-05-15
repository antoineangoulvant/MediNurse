<?php

    $title=$_POST['title'];
    $start=$_POST['start'];
    $end=$_POST['end'];

    // connexion à la base de données
    try
    {
        $bdd = new PDO('mysql:host=e88487-mysql.services.easyname.eu;dbname=u139724db1;charset=utf8', 'u139724db1', 'pp5959he');
    }
    catch (Exception $e)
    {
        die('Erreur : ' . $e->getMessage());
    }

    $sql = "INSERT INTO evenement (title, start, end) VALUES (:title, :start, :end)";
    $q = $bdd->prepare($sql);
    $q->execute(array(':title'=>$title, ':start'=>$start, ':end'=>$end));
?>