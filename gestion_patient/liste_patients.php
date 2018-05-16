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

$rep = $bdd->query('SELECT * FROM patient');
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
<!-- Example DataTables Card-->
<div class="card mb-3">
    <div class="card-header">
        <i class="fa fa-table"></i> Liste des patients</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Prénom</th>
                    <th>Nom</th>
                    <th>Service</th>
                    <th>Chambre</th>
                    <th>En savoir plus</th>
                    <th>ToDoList</th>
                </tr>
                </thead>
                <tfoot>
                <tr>
                    <th>ID</th>
                    <th>Prénom</th>
                    <th>Nom</th>
                    <th>Service</th>
                    <th>Chambre</th>
                    <th>En savoir plus</th>
                    <th>ToDoList</th>
                </tr>
                </tfoot>
                <tbody>
                <?php
                while ($donnees = $rep->fetch()){
                    echo '<tr>';
                    $toto = $donnees['id_patient'];
                    echo '<td>'.$donnees['id_patient'].'</td>';
                    echo '<td>'.$donnees['prenom'].'</td>';
                    echo '<td>'.$donnees['nom'].'</td>';

                    $reqservice = $bdd->prepare('SELECT libelle FROM service WHERE id_service = :id');
                    $reqservice->execute(array('id' => $donnees['service']));
                    $libservice = $reqservice->fetch();
                    echo '<td>'.$libservice['libelle'].'</td>';

                    $reqchambre = $bdd->prepare('SELECT numero FROM chambre c, lit l WHERE l.numero_chambre = c.numero AND l.id_patient = :id_patient');
                    $reqchambre->execute(array('id_patient' => $donnees['id_patient']));
                    $libchambre = $reqchambre->fetch();
                    echo '<td>'.$libchambre['numero'].'</td>';

                    echo '<td>'.'<a href="index.php?page=patient&id='.$donnees['id_patient'].'" class="btn btn-sm btn-info"><span class="glyphicon glyphicon-user"></span> Fiche patient</a>'
                    .'<a style="margin-left: 15px;" href="index.php?page=infomedicale&id='.$donnees['id_patient'].'" class="btn btn-sm btn-info"><span class="glyphicon glyphicon-user"></span> Informations médicales</a>'.'</td>';
                    echo '<td>'. '<a href="index.php?page=voirliste&id='.$donnees['id_patient'].'" class="btn btn-sm btn-info"><span class="glyphicon glyphicon-user"></span>Voir Liste</a>'.'</td>';
                        //'<button type="button" id="" class="btn btn-primary" data-toggle="modal" data-target="#modalAdd">Ajouter</button>'.
                        //'<button type="button" value="<?php echo $donnees[\'idTache\']; //" class="btn btn-warning modalView" data-toggle="modal" data-target="#modalView">Voir liste</button>';

                    echo '</tr>';
                    ?>
                <?php
                }
                $rep->closeCursor();
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
