<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 07/05/2018
 * Time: 22:40
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

    echo $_POST['idpatient'];
?>



<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="text-center">Lecture d'un patient</h1>
        </div>
    </div>
    <form method="post" action="gestion_patient/action_list.php">
        <div class="col-lg-4">
            <div class="form-group">
                <label for="idpat">Identifiant du patient:</label>
                <input type="number" class="form-control" id="idpatient" placeholder="Saisissez l'identifiant" name="idpatient">
                <?php if(isset($erreur['idpatient'])) echo '<div class="alert alert-danger">'.$erreur['idpatient'].'</div>'; ?>
            </div>
        </div>
</div>

</form>
</div>

