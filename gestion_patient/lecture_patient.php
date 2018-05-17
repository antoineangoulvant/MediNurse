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

if (isset($_POST['delete'])) {
    try {
        $_SESSION['id'] = $_POST['idpatient'];
        $idPat = $_SESSION['id'];
        $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $id = $_POST["id"];
        $sql = "UPDATE liste SET statut  =1 WHERE idTache=$id";
        $bdd->exec($sql);

    } catch (PDOException $e) {
        die('Erreur : ' . $e->getMessage());
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
        <div class="offset-4 col-4 col-lg-4 offset-4">
            <div class="form-group">
                <label for="idpat">Identifiant du patient:</label>
                <input type="number" class="form-control" id="idpatient" placeholder="Saisissez l'identifiant" name="idpatient">
                <?php if (isset($erreur['idpatient'])) echo '<div class="alert alert-danger">' . $erreur['idpatient'] . '</div>'; ?>
            </div>
        </div>
        <button type="submit" class="btn btn-primary center-block" style="margin-bottom: 30px;">Récupérer Liste</button>
    </form>
    <div class="card mb-3">
        <div class="card-header">
            <i class="fa fa-table"></i> Services à effectuer
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
                if (isset($_POST['idpatient'])){
                $idPat = $_POST['idpatient'];
                $rep = $bdd->query("SELECT * FROM liste Where idPatient=$idPat HAVING statut=0");
                while ($donnees = $rep->fetch()) {
                    echo '<tr>';
                    echo '<td>' . $donnees['idTache'];
                    echo '<td>' . $donnees['nom'];
                    echo '<td>' . $donnees['commentaire']; ?>
                    <td> <form method="post" action="">
                        <input type="hidden" name="idpatient" value="<?php echo $_POST['idpatient']; ?>">
                        <input type="hidden" name="id" value="<?php echo $donnees['idTache']; ?>">
                        <button name="delete" class="btn btn-danger delete">Done</button>
                    </form> </td>
                    <?php
                }
                $rep->closeCursor();
                }?>
                </tbody>
            </table>
        </div>
    </div>

