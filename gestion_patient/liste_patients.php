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
                    <th>Age</th>
                    <th>Start date</th>
                    <th>En savoir plus</th>
                </tr>
                </thead>
                <tfoot>
                <tr>
                    <th>ID</th>
                    <th>Prénom</th>
                    <th>Nom</th>
                    <th>Age</th>
                    <th>Start date</th>
                    <th>En savoir plus</th>
                </tr>
                </tfoot>
                <tbody>
                <?php
                while ($donnees = $rep->fetch()){
                    echo '<tr>';
                    echo '<td>'.$donnees['id_patient'].'</td>';
                    echo '<td>'.$donnees['prenom'].'</td>';
                    echo '<td>'.$donnees['nom'].'</td>';
                    echo '<td>'.'A voir'.'</td>';
                    echo '<td>'.'A voir'.'</td>';
                    echo '<td>'.'<a href="index.php?page=patient&id='.$donnees['id_patient'].'" class="btn btn-sm btn-info"><span class="glyphicon glyphicon-user"></span> Fiche patient</a>'.'</td>';
                    echo '</tr>';

                }

                $rep->closeCursor();
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
