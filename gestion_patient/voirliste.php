<?php
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

$req = $bdd->prepare('SELECT * FROM liste WHERE idPatient = :id');
$req->execute(array('id' => $_GET['id']));
$resultat = $req->fetch();
?>

<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="text-center">Patient n°<?php echo $resultat['idPatient']; ?></h1>
        </div>
    </div>
    <form method="post" action="index.php?page=voirliste&id=<?php echo $_GET['id']; ?>">

        <button type="button" class="btn btn-primary center-block" data-toggle="modal" data-target="#ajouter">
            Ajouter tache
        </button>
</div>
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
            $idPat = $_GET['id'];
            $rep = $bdd->query("SELECT * FROM liste Where idPatient=$idPat");
            while ($donnees = $rep->fetch()) {
                echo '<tr>';
                echo '<td>' . $donnees['idTache'];
                echo '<td>' . $donnees['nom'];
                echo '<td>' . $donnees['commentaire'];
                echo $donnees['statut'];
                echo '<td>' ?>
                <input name="statut" type="checkbox" <?php
                if ($donnees['statut'] = 0) {
                    echo " checked";
                } else {
                    echo "";
                } ?> >
                <form method="post" action="">
                    <input type="hidden" name="id" value="<?php echo $donnees['idTache']; ?>">
                    <button type="delete" name="delete" class="btn btn-danger">Supprimer</button>
                </form>
                    <div class="modal fade" id="ajouter" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form method="post" action="">
                                        <input type="hidden" name="id" value="<?php echo $idPat; ?>">
                                        <form method="post" action="">
                                            <div class="row margininscription">
                                                <div class="">
                                                    <div class="form-group">
                                                        <label for="genre">Nom tache<span class="obligatoire">*</span> :</label>
                                                        <input type="text" class="form-control" id="tache" placeholder="Saisissez la tache" name="tache">
                                                    </div>
                                                </div>
                                                <div class="">
                                                    <div class="form-group">
                                                        <label for="prenom">Prénom<span class="obligatoire">*</span> :</label>
                                                        <input type="text" class="form-control" id="commentaire" placeholder="Saisissez le commentaire" name="commentaire">
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="ajouter" name="ajouter" class="btn btn-danger">Ajouter</button>
                                        </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary">Save changes</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
            }
            $rep->closeCursor();
            ?>
            </tbody>
        </table>
    </div>
</div>