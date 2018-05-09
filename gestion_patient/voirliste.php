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

    </form>
        <?php if($envoye) echo '<div class="alert alert-success">Modifications enregistrées !</div>'; ?>
        <button type="submit" class="btn btn-primary center-block" style="margin-bottom: 30px;">Enregistrer</button>
    </form>
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
            $idPat = $_GET['id'];;
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
                <?php
            }
            $rep->closeCursor();
            ?>
            </tbody>
        </table>
    </div>
</div>