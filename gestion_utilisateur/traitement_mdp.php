<?php
/**
 * Created by PhpStorm.
 * User: antoi
 * Date: 18/05/2018
 * Time: 00:15
 */
    try
    {
        $bdd = new PDO('mysql:host=e88487-mysql.services.easyname.eu;dbname=u139724db1;charset=utf8', 'u139724db1', 'pp5959he');
    }
    catch (Exception $e)
    {
        die('Erreur : ' . $e->getMessage());
    }

    $reqmdp = $bdd->prepare('UPDATE utilisateur SET motdepasse=? WHERE id_utilisateur=?');
    $newmdp = $_POST['newmdp'];
    $pass_hache = password_hash($newmdp, PASSWORD_DEFAULT);
    $reqmdp->execute(array($pass_hache,$_POST['id']));

    header('Location: ../index.php?page=utilisateur&id='.$_POST['id']);
?>