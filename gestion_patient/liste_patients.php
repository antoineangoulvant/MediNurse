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

                    echo '<td>'.'<a href="index.php?page=patient&id='.$donnees['id_patient'].'" class="btn btn-sm btn-info"><span class="glyphicon glyphicon-user"></span> Fiche patient</a>'.'</td>';
                    echo '<td>'.'<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalAdd">Ajouter tâche</button>'. '<button type="button" class="btn btn-warning" data-toggle="modal" data-target="#modalView">Voir liste</button>';
                    echo '</tr>';
                    ?>
                    <!-- Modal -->
                    <div class="modal fade" id="modalView" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">ToDoList</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <?php

                                    ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary">Ajouter</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Modal Add-->
                    <div class="modal fade" id="modalAdd" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">Ajout d'une tâche</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <?php

                                    ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary">Ajouter</button>
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
</div>
