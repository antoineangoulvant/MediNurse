<?php
$formulaire_valide = true;
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

if (isset($_POST['delete'])) {
    try {
        $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $id = $_POST["id"];
        $sql = "Delete from liste Where idTache=$id";
        $bdd->exec($sql);

    } catch (PDOException $e) {
        die('Erreur : ' . $e->getMessage());
    }
}

if( empty($_POST['tache']) ){
    $erreur['tache'] = 'Le champs tache est obligatoire';
    $formulaire_valide = false;
}

if(isset($_POST['submit'])) {
    $req = $bdd->prepare('INSERT INTO liste(idPatient, nom, commentaire, statut) VALUES (?,?,?,?)');
    $req->execute(array($_POST['id'], $_POST['tache'], $_POST['commentaire'], 0));
}
?>
<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="text-center">Patient n°<?php echo $resultat['idPatient']; ?></h1>
        </div>
    </div>
    <h2 method="post" action="index.php?page=voirliste&id=<?php echo $_GET['id']; ?>" </h2>
    <button type="button" class="btn btn-primary center-block" data-toggle="modal" data-target="#ajouter">Ajouter tache</button>
</div>
<div class="card-body">
    <div class="table-responsive" id="tachetab">
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
                echo '<td>' . $donnees['statut']; ?>
                <form method="post" action="">
                    <input type="hidden" name="id" value="<?php echo $donnees['idTache']; ?>">
                    <button type="delete" name="delete" class="btn btn-danger delete">Supprimer</button>
                </form>
                    <div class="modal fade" id="ajouter" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">Ajout d'un tâche</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                        <form method="post" action="" id="addtask">
                                            <input type="hidden" name="id" value="<?php echo $idPat; ?>">
                                            <div class="row margininscription">
                                                <div class="">
                                                    <div class="form-group">
                                                        <label for="genre">Nom tache<span class="obligatoire">*</span> :</label>
                                                        <input type="text" class="form-control" id="tache" placeholder="Saisissez la tache" name="tache">
                                                        <?php if(isset($erreur['tache'])) echo '<div class="alert alert-danger">'.$erreur['tache'].'</div>'; ?>
                                                    </div>
                                                </div>
                                                <div class="">
                                                    <div class="form-group">
                                                        <label for="commentaire">Commentaire<span class="obligatoire"></span> :</label>
                                                        <input type="text" class="form-control" id="commentaire" placeholder="Saisissez le commentaire" name="commentaire">
                                                    </div>
                                                </div>
                                            </div>
                                </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                <button type="submit" name="submit" class="btn btn-danger submit">Ajouter</button>
                                            </div>
                                        </form>
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

