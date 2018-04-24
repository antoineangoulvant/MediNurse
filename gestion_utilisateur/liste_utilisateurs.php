<?php
/**
 * Created by PhpStorm.
 * User: antoi
 * Date: 15/04/2018
 * Time: 10:44
 */
    try
    {
        $bdd = new PDO('mysql:host=e88487-mysql.services.easyname.eu;dbname=u139724db1;charset=utf8', 'u139724db1', 'pp5959he');
    }
    catch (Exception $e)
    {
        die('Erreur : ' . $e->getMessage());
    }

    $rep = $bdd->query('SELECT * FROM utilisateur');
?>

<!-- Example DataTables Card-->
<div class="card mb-3">
    <div class="card-header">
        <i class="fa fa-table"></i> Liste des utilisateurs</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Prénom</th>
                    <th>Nom</th>
                    <th>Service</th>
                    <th>En savoir plus</th>
                </tr>
                </thead>
                <tfoot>
                <tr>
                    <th>ID</th>
                    <th>Prénom</th>
                    <th>Nom</th>
                    <th>Service</th>
                    <th>En savoir plus</th>
                </tr>
                </tfoot>
                <tbody>
                    <?php
                        while ($donnees = $rep->fetch()){
                            echo '<tr>';
                            echo '<td>'.$donnees['id_utilisateur'].'</td>';
                            echo '<td>'.$donnees['prenom'].'</td>';
                            echo '<td>'.$donnees['nom'].'</td>';

                            $reqservice = $bdd->prepare('SELECT libelle FROM service WHERE id_service = :id');
                            $reqservice->execute(array('id' => $donnees['service']));
                            $libservice = $reqservice->fetch();
                            echo '<td>'.$libservice['libelle'].'</td>';

                            echo '<td>'.'<a href="index.php?page=utilisateur&id='.$donnees['id_utilisateur'].'" class="btn btn-sm btn-info"><span class="glyphicon glyphicon-user"></span> Fiche utilisateur</a>'.'</td>';
                            echo '</tr>';

                        }
                        $rep->closeCursor();
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
