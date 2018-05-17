<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 16/05/2018
 * Time: 19:57
 */
$q = intval($_GET['q']);

try
{
    $bdd = new PDO('mysql:host=e88487-mysql.services.easyname.eu;dbname=u139724db1;charset=utf8', 'u139724db1', 'pp5959he');
}
catch (Exception $e)
{
    die('Erreur : ' . $e->getMessage());
}

$rep= $bdd->query("SELECT * FROM patient WHERE id_patient=$q");
$repActe = $bdd->query("SELECT * FROM utilisateur, acte WHERE acte.idSoignant= utilisateur.id_utilisateur HAVING acte.idPatient =$q");
$resTr = $bdd->query("SELECT * FROM traitement, medicament WHERE traitement.idMedicament=medicament.idMedicament HAVING idPatient=$q");
$repAllergie = $bdd->query("SELECT * FROM typeAllergie, allergie WHERE allergie.idAllergie = typeAllergie.idTypeA HAVING allergie.idPatient = $q");
$repAnte = $bdd->query("SELECT * FROM acte_antecedent WHERE idPatient=$q");

$reqchambre = $bdd->query("Select * from chambre, lit,service Where chambre.numero=lit.numero_chambre having id_patient=$q AND service.id_service=chambre.id_service;");


?>

<div class="container">
    <div class="row">
        <div class="col-12 .col-md-8">
            <h4>Information du patient</h4>
            <table class="table col-6">
                <thead>
                <tr>
                    <th>Prénom</th>
                    <th>Nom</th>
                    <th>Adresse</th>
                    <th>Téléphone</th>
                    <th>Portable</th>
                    <th>Mail</th>
                    <th>Date d'entrée</th>
                    <th>INS</th>
                </tr>
                </thead>
        </div>
    </div>
            <tbody>
            <?php
            while ($donnees = $rep->fetch()){
                echo '<tr>';
                echo '<td>'.$donnees['prenom'].'</td>';
                echo '<td>'.$donnees['nom'].'</td>';
                echo '<td>'.$donnees['adresse1'].'</td>';
                echo '<td>'.$donnees['teldom'].'</td>';
                echo '<td>'.$donnees['telport'].'</td>';
                echo '<td>'.$donnees['mail'].'</td>';
                echo '<td>'.$donnees['date_inscription'].'</td>';
                echo '<td>'.$donnees['ins'].'</td>';
            }
            ?>
            </tbody>
        </table>
</div>
        <div class="col-12 .col-md-8">
            <h4>Information du patient</h4>
            <table class="table col-6">
                <thead>
                <tr>
                    <th>Chambre</th>
                    <th>Lit</th>
                    <th>Service</th>
                </tr>
                </thead>
        </div>
        <tbody>
        <?php
            while ($libchambre = $reqchambre->fetch()){
                echo '<tr>';
                echo '<td>'.$libchambre['numero'].'</td>';
                echo '<td>'.$libchambre['id_lit'].'</td>';
                echo '<td>'.$libchambre['libelle'].'</td>';
            }
            ?>
        </tbody>
        </table>
</div>
        <br>
        <div class="">
            <div class=" col-md-12">
                <h4>Acte réalisé</h4>
                <table class="table">
                    <thead>
                    <tr>
                        <th>Nom Docteur</th>
                        <th>Prénom Docteur</th>
                        <th>Acte</th>
                        <th>Commentaire</th>
                        <th>Date</th>
                    </tr>
                    </thead>
            </div>
            <tbody>
            <?php
            while ($donneesAc = $repActe->fetch()){
                echo '<tr>';
                echo '<td>'.$donneesAc['nom'].'</td>';
                echo '<td>'.$donneesAc['prenom'].'</td>';
                echo '<td>'.$donneesAc['nomActe'].'</td>';
                echo '<td>'.$donneesAc['commentaire'].'</td>';
                echo '<td>'.$donneesAc['date'].'</td>';
            }
            ?>
            </tbody>
            </table>
        </div>
        <div class=" col-md-12">
            <h4>Traitement</h4>
            <table class="table">
                <thead>
                <tr>
                    <th>Médicament</th>
                    <th>Commentaire</th>
                    <th>Durée en mois</th>
                    <th>Début</th>
                </tr>
                </thead>
        </div>
        <tbody>
        <?php
            while ($donneesTr = $resTr->fetch()) {
                echo '<tr>';
                echo '<td>' . $donneesTr['nomMedicament'] . '</td>';
                echo '<td>' . $donneesTr['commentaire'] . '</td>';
                echo '<td>' . $donneesTr['duree'] . '</td>';
                echo '<td>' . $donneesTr['date_debut'] . '</td>';
            }
        ?>
        </tbody>
        </table>
    </div>
            <br>
<div class=" col-md-12">
                <h4>Allergie</h4>
                <table class="table col-6">
                    <thead>
                    <tr>
                        <th>Nom allergie</th>
                        <th>Commentaire</th>
                        <th>Gravité</th>
                    </tr>
                    </thead>
            </div>
            <tbody>
            <?php
            while ($donneesAllergie = $repAllergie->fetch()){
                echo '<tr>';
                echo '<td>'.$donneesAllergie['nom'].'</td>';
                echo '<td>'.$donneesAllergie['commentaire'].'</td>';
                echo '<td>'.$donneesAllergie['Gravité'].'</td>';
            }
            ?>
            </tbody>
            </table>

<div class="row">
    <div class="col-12 .col-md-8">
        <h4>Antécédent médicaux</h4>
        <table class="table col-6">
            <thead>
            <tr>
                <th>Nom Soigneur</th>
                <th>Prénom Soigneur</th>
                <th>Acte</th>
                <th>Commentaire</th>
                <th>Date</th>
                <th>Nom Hopital </th>
                <th>Adresse Hopital </th>
                <th>Code postal Hopital</th>
            </tr>
            </thead>
    </div>
    <tbody>
    <?php
    while ($donneesAnte = $repAnte->fetch()){
        echo '<tr>';
        echo '<td>'.$donneesAnte['nomSoigneur'].'</td>';
        echo '<td>'.$donneesAnte['prenom'].'</td>';
        echo '<td>'.$donneesAnte['nom'].'</td>';
        echo '<td>'.$donneesAnte['commentaire'].'</td>';
        echo '<td>'.$donneesAnte['date'].'</td>';
        echo '<td>'.$donneesAnte['nomHopital'].'</td>';
        echo '<td>'.$donneesAnte['adresseHopital'].'</td>';
        echo '<td>'.$donneesAnte['codePostal'].'</td>';
    }
    ?>
    </tbody>
    </table>
</div>
</div>