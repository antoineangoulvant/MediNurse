<?php

try
{
    $bdd = new PDO('mysql:host=e88487-mysql.services.easyname.eu;dbname=u139724db1;charset=utf8', 'u139724db1', 'pp5959he');
}
catch (Exception $e)
{
    die('Erreur : ' . $e->getMessage());
}

//Test des champs et creation d'une erreur si champs non rempli
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $erreur=[];

    if( $_POST['idpatient'] == '' ){
        $erreur['idpatient'] = 'Le champs Identifiant est obligatoire';
        $formulaire_valide = false;
    }
}
?>
<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="text-center">Lecture d'un patient</h1>
        </div>
    </div>
    <form method="post" action="">
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="idpat">Identifiant du patient:</label>
                    <input type="number" class="form-control" id="idpatient" placeholder="Saisissez l'identifiant" name="idpatient">
                    <?php if(isset($erreur['idpatient'])) echo '<div class="alert alert-danger">'.$erreur['idpatient'].'</div>'; ?>
                </div>
            </div>

        <button type="submit" class="btn btn-primary center-block" style="margin-bottom: 30px;">Récupérer Liste</button>
    </form>
    <div class="card mb-3">
        <div class="card-header">
            <i class="fa fa-table"></i>Service à effectuer</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Commentaire</th>
                        <th>Statut</th>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Commentaire</th>
                        <th>Statut</th>
                    </tr>
                    </tfoot>
                    <tbody>
                        <?php
                        $idPat = $_POST['idpatient'];
                        $rep = $bdd->query("SELECT * FROM liste Where idPatient=$idPat");
                        while ($donnees = $rep->fetch()){
                            echo '<tr>';
                            echo '<td>'.$donnees['idTache'];
                            echo '<td>'.$donnees['nom'];
                            echo '<td>'.$donnees['commentaire'];
                            echo $donnees['statut'];
                            echo '<td>'?>
                            <input name="statut" type="checkbox" <?php
                            if($donnees['statut'] = 0){
                                echo " checked";
                            }else{
                                echo "";
                            }?> >
                            <form method="post" action="">
                        <button type="delete" class="btn btn-danger" value="<?php echo $donnees['idTache'];?>">Supprimer</button>
                            </form>
                        <?php
                        }
                        $rep->closeCursor();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
