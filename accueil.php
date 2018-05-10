<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 11/05/2018
 * Time: 01:09
 */
?>
            <div class="container">
                <div class="row">
                    <div class="col-12 .col-md-8">
                        <h4>Patient pris en charge</h4>
                        Il y a actuellement <?php echo $donnes['nbPatient']; ?> patients dans l'hopital
                        <table class="table col-6">
                            <thead>
                            <tr>
                                <th>Prénom</th>
                                <th>Nom</th>
                            </tr>
                            </thead>
                    </div>
                    <tbody>
                    <?php
                    while ($people = $rap->fetch()){
                        echo '<tr>';
                        echo '<td>'.$people['prenom'].'</td>';
                        echo '<td>'.$people['nom'].'</td>';
                    }
                    ?>
                    </tbody>
                    </table>
                </div>
                    <div class="col-6 col-md-4">
                        <h4>Docteur présent à l'hopital</h4>
                        Il y a actuellement <?php echo $doc['nbDocteur']; ?> docteur(s)
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Prénom</th>
                                <th>Nom</th>
                            </tr>
                            </thead>
                    </div>
                    <tbody>
                    <?php
                    while ($docDatas = $docAll->fetch()){
                        echo '<tr>';
                        echo '<td>'.$docDatas['prenom'].'</td>';
                        echo '<td>'.$docDatas['nom'].'</td>';
                    }
                    ?>
                    </tbody>
                    </table>
                    </div>
            <div class="col-12 col-md-8">
                <h4>Personnel soignant à l'hopital</h4>
                Il y a actuellement <?php echo $nbAS['nbPS']; ?> personnel soignant à l'hopital
                <table class="table">
                    <thead>
                    <tr>
                        <th>Prénom</th>
                        <th>Nom</th>
                    </tr>
                    </thead>
            </div>
            <tbody>
            <?php
            while ($dataPSALL = $psAll->fetch()){
                echo '<tr>';
                echo '<td>'.$dataPSALL['prenom'].'</td>';
                echo '<td>'.$dataPSALL['nom'].'</td>';
            }
            ?>
            </tbody>
            </table>
        </div>
                </div>
            </div>
        </div>