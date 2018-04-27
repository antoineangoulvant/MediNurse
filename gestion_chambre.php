<?php
/**
 * Created by PhpStorm.
 * User: antoi
 * Date: 27/04/2018
 * Time: 00:27
 */

    try
    {
        $bdd = new PDO('mysql:host=e88487-mysql.services.easyname.eu;dbname=u139724db1;charset=utf8', 'u139724db1', 'pp5959he');
    }
    catch (Exception $e)
    {
        die('Erreur : ' . $e->getMessage());
    }

    $rep = $bdd->query('SELECT * FROM chambre');
?>

<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h2 class="text-center">Création d'une nouvelle chambre</h2>
        </div>
    </div>
    <form method="post" action="traitement_chambre.php">
        <div class="row">
            <div class="offset-lg-2 col-lg-4">
                <div class="form-group">
                    <label for="numero">Saisir le numéro<span class="obligatoire">*</span> :</label>
                    <input type="text" class="form-control" id="numero" name="numero" placeholder="Saisir le numéro de chambre">
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="service">Service<span class="obligatoire">*</span> :</label>
                    <select id="service" name="service" class="form-control">
                        <?php
                            $reqchambre = $bdd->query('SELECT * FROM service');
                            while ($donnees = $reqchambre->fetch()){
                                echo '<option value="'.$donnees['id_service'].'">'.$donnees['libelle'].'</option>';
                            }
                            ?>
                    </select>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary center-block" style="margin-bottom: 30px;">Ajouter</button>
    </form>
</div>

<div class="card mb-3">
    <div class="card-header">
        <i class="fa fa-table"></i> Liste des chambres</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                <tr>
                    <th>Numéro de chambre</th>
                    <th>Service</th>
                </tr>
                </thead>
                <tfoot>
                <tr>
                    <th>Numéro de chambre</th>
                    <th>Service</th>
                </tr>
                </tfoot>
                <tbody>
                <?php
                while ($donnees = $rep->fetch()){
                    echo '<tr>';
                    echo '<td>'.$donnees['numero'].'</td>';

                    $reqservice = $bdd->prepare('SELECT libelle FROM service WHERE id_service = :id');
                    $reqservice->execute(array('id' => $donnees['id_service']));
                    $libservice = $reqservice->fetch();
                    echo '<td>'.$libservice['libelle'].'</td>';
                    echo '</tr>';
                }

                $rep->closeCursor();
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



