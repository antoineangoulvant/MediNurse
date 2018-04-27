<?php
/**
 * Created by PhpStorm.
 * User: antoi
 * Date: 27/04/2018
 * Time: 01:14
 */
    try
    {
        $bdd = new PDO('mysql:host=e88487-mysql.services.easyname.eu;dbname=u139724db1;charset=utf8', 'u139724db1', 'pp5959he');
    }
    catch (Exception $e)
    {
        die('Erreur : ' . $e->getMessage());
    }

    $rep = $bdd->query('SELECT id_lit, numero_chambre, id_service, id_patient FROM lit l, chambre c WHERE l.numero_chambre = c.numero');
?>

<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h2 class="text-center">Création d'un nouveau lit</h2>
        </div>
    </div>
    <div class="row">
        <div class="offset-lg-4 col-lg-4">
            <form method="post" action="traitement_lit.php">
                 <div class="form-group">
                     <label for="chambre">Numéro de chambre<span class="obligatoire">*</span> :</label>
                     <select id="chambre" name="chambre" class="form-control">
                         <?php
                            $reqchambre = $bdd->query('SELECT numero FROM chambre');
                            while ($donnees = $reqchambre->fetch()){
                                echo '<option value="'.$donnees['numero'].'">'.$donnees['numero'].'</option>';
                            }
                        ?>
                     </select>
                </div>
                <button type="submit" class="btn btn-primary center-block" style="margin-bottom: 30px;">Ajouter</button>
            </form>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        <i class="fa fa-table"></i> Liste des lits</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                <tr>
                    <th>Numéro du lit</th>
                    <th>Chambre</th>
                    <th>Patient</th>
                    <th>Service</th>
                </tr>
                </thead>
                <tfoot>
                <tr>
                    <th>Numéro du lit</th>
                    <th>Chambre</th>
                    <th>Patient</th>
                    <th>Service</th>
                </tr>
                </tfoot>
                <tbody>
                <?php
                while ($donnees = $rep->fetch()){
                    echo '<tr>';
                    echo '<td>'.$donnees['id_lit'].'</td>';
                    echo '<td>'.$donnees['numero_chambre'].'</td>';

                    if( is_null($donnees['id_patient']) ){
                        echo '<td><i>Lit libre</i></td>';
                    }
                    else{
                        $reqpatient = $bdd->prepare('SELECT id_patient,nom,prenom FROM patient WHERE id_patient = :id');
                        $reqpatient->execute(array('id' => $donnees['id_patient']));
                        $libpatient = $reqpatient->fetch();
                        echo '<td>'.$libpatient['prenom'].' '.$libpatient['nom'].' <b>(Id : '.$libpatient['id_patient'].')</b>'.'</td>';
                    }

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
