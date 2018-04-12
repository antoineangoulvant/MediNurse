<?php
/**
 * Created by PhpStorm.
 * User: antoi
 * Date: 21/02/2018
 * Time: 17:04
 */
    try
    {
        $bdd = new PDO('mysql:host=e88487-mysql.services.easyname.eu;dbname=u139724db1;charset=utf8', 'u139724db1', 'pp5959he');
    }
    catch (Exception $e)
    {
        die('Erreur : ' . $e->getMessage());
    }

    //$pass_hache = password_hash($_POST['pass'], PASSWORD_DEFAULT);
    if( isset($_POST['idUtilisateur']) AND isset($_POST['passwordUtilisateur'])){
        echo 'ok';
        $idUtilisateur = $_POST['idUtilisateur'];
        $passwordUtilisateur = $_POST['passwordUtilisateur'];
    }
    else{
        echo 'Veuillez saisir un identifiant et un mot de passe';
    }
    //$id_utilisateur = $_POST['input_id'];
    //$pass_hache = $_POST['input_password'];

    // Vérification des identifiants
    $req = $bdd->prepare('SELECT id FROM utilisateur WHERE id = :id AND password = :password');
    $req->execute(array(
        'id' => $idUtilisateur,
        'password' => $passwordUtilisateur));

    $resultat = $req->fetch();

    if (!$resultat)
    {
        echo 'Mauvais identifiant ou mot de passe !';
        header('Location: login.php');
    }
    else
    {
        session_start();
        $_SESSION['id'] = $resultat['id'];
        header('Location: index.php');
        echo 'Vous êtes connecté !';
    }
?>