<?php
$envoye = false;
$envoyeActe= false;
$envoyerLit= false;
$envoyerTraitement = false;
$envoyeAllergie = false;
$envoyerRythme= false;
$envoyertension= false;
$envoyertemp= false;
/**
 * Created by PhpStorm.
 * User: David
 * Date: 15/04/2018
 * Time: 23:44
 */
try
{
    $bdd = new PDO('mysql:host=e88487-mysql.services.easyname.eu;dbname=u139724db1;charset=utf8', 'u139724db1', 'pp5959he');
}
catch (Exception $e)
{
    die('Erreur : ' . $e->getMessage());
}

if(isset($_POST['submitAnteHopi'])) {
    $pet = $bdd->prepare('INSERT INTO acte_antecedent(idPatient, nomSoigneur, prenom, nom, commentaire, date, nomHopital, adresseHopital, codePostal, ville, pays) VALUES(?, ?, ?,  ?, ?, ?, ?, ?, ?, ?, ?)');
    $id = $_GET['id'];
    $pet->execute(array($id, $_POST['nomSoigneur'], $_POST['prenomSoigneur'], $_POST['nomActeAnte'], $_POST['commentaireActe'], $_POST['dateAntecedent'], $_POST['antecedentHopitalNom'], $_POST['adresseHopital'], $_POST['codepostaHopital'], $_POST['villeHopital'], $_POST['paysHopital']));
    $envoye=true;
}

if (isset($_POST['submitActe'])){
    $reqActe =$bdd->prepare('INSERT INTO acte(idPatient, idSoignant, nomActe, commentaire, date) VALUES (? , ? ,? ,? ,?)');
    $id = $_GET['id'];
    $reqActe->execute(array($id, $_POST['doctorActe'], $_POST['nomActe'], $_POST['commentaireActeNew'], $_POST['dateActe']));
    $envoyeActe = true;
}

if (isset($_POST['submitLit'])){
    $resLit = $bdd->prepare('UPDATE lit SET id_patient=? WHERE id_lit=?');
    $id = $_GET['id'];
    $resLit->execute(array($id, $_POST['affectationLit']));
    $envoyerLit=true;
}

if (isset($_POST['submitTraitement'])){
    $resTrai = $bdd->prepare('INSERT INTO traitement(idPatient, idMedicament, commentaire, duree, date_debut) VALUES (? , ? ,? , ?,?)');
    $id = $_GET['id'];
    $resTrai->execute(array($id, $_POST['idtraitement'], $_POST['commentaireTraitement'],$_POST['dureeTraitement'], $_POST['dateTraitement']));
    $envoyerTraitement=true;
}

if (isset($_POST['submitAllergie'])){
    $resA = $bdd->prepare('INSERT INTO allergie(idType, Gravité, idPatient) VALUES (?,?,?)');
    $id=$_GET['id'];
    $resA->execute(array($_POST['idtraitement'], $_POST['graviteAllergie'],$id));
    $envoyeAllergie=true;
}


        if (isset($_POST['submitRythme'])){
            $resRy = $bdd->prepare('INSERT INTO rythmeC(idPatient, valeur, date) VALUES (? , ?,?)');
            $id = $_GET['id'];
            $date= date("Y-m-d H:i:s");
            $resRy->execute(array($id, $_POST['valRythme'],$date));
            $envoyerRythme=true;
        }
if (isset($_POST['submitTemp'])){
    $resTemp = $bdd->prepare('INSERT INTO temperature(idPatient, valeur, date) VALUES (? , ?,?)');
    $id = $_GET['id'];
    $date= date("Y-m-d H:i:s");
    $resTemp->execute(array($id, $_POST['valTemp'],$date));
    $envoyertemp=true;
}
if (isset($_POST['submitTens'])){
    $restension = $bdd->prepare('INSERT INTO tension(idPatient, valeur, date) VALUES (? , ?,?)');
    $id = $_GET['id'];
    $date= date("Y-m-d H:i:s");
    $restension->execute(array($id, $_POST['valTens'],$date));
    $envoyertension=true;
}

?>
<div class="container">
    <div class="row">
    <nav>
        <div class="nav nav-tabs" id="nav-tab" role="tablist">
            <a class="nav-item nav-link active" id="nav-contact-tab" data-toggle="tab" href="#nav-lit" role="tab" aria-controls="nav-contact" aria-selected="false">Lit</a>
            <a class="nav-item nav-link" id="nav-home-tab" data-toggle="tab" href="#nav-acte" role="tab" aria-controls="nav-home" aria-selected="true">Acte hospitalier</a>
            <a class="nav-item nav-link" id="nav-home-tab" data-toggle="tab" href="#nav-antecedentHospi" role="tab" aria-controls="nav-home" aria-selected="true">Antécédent hospitalier</a>
            <a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#nav-traitement" role="tab" aria-controls="nav-profile" aria-selected="false">Traitement</a>
            <a class="nav-item nav-link" id="nav-contact-tab" data-toggle="tab" href="#nav-allergie" role="tab" aria-controls="nav-contact" aria-selected="false">Allergie</a>
            <a class="nav-item nav-link" id="nav-contact-tab" data-toggle="tab" href="#nav-rythme" role="tab" aria-controls="nav-contact" aria-selected="false">Rythme cardiaque</a>
            <a class="nav-item nav-link" id="nav-contact-tab" data-toggle="tab" href="#nav-tension" role="tab" aria-controls="nav-contact" aria-selected="false">Tension</a>
            <a class="nav-item nav-link" id="nav-contact-tab" data-toggle="tab" href="#nav-temp" role="tab" aria-controls="nav-contact" aria-selected="false">Température</a>
        </div>
    </nav>
    </div>
    <div class="tab-content" id="nav-tabContent">
        <div class="tab-pane fade show active" id="nav-lit" role="tabpanel" aria-labelledby="nav-profile-tab">
            <br>
            <h3 class="text-center"> Affectation d'un lit</h3>
            <form method="post" action="index.php?page=infomedicale&id=<?php echo $_GET['id']; ?>">
                <div class="row margininscription">
                    <div class="col-lg-12">
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <label class="input-group-text" for="inputGroupSelect01">Affectation lit</label>
                            </div>
                            <select class="custom-select" name="affectationLit" id="inputGroupSelect01">
                                <?php
                                $rep= $bdd->query("SELECT * FROM lit WHERE id_patient IS NULL ");
                                while ($donnees = $rep->fetch()){ ?>
                                    <option value="<?php echo $donnees['id_lit'] ?>"> Lit n°<?php echo $donnees['id_lit']; ?> Chambre n°<?php echo $donnees['numero_chambre']; ?> </option>
                                    <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <?php if($envoyerLit) echo '<div class="alert alert-success">Information enregistré !</div>'; ?>
                <button type="submit" name="submitLit" class="btn btn-primary center-block" style="margin-bottom: 30px;">Enregistrer</button>
            </form>
        </div>
        <div class="tab-pane fade show " id="nav-acte" role="tabpanel" aria-labelledby="nav-profile-tab">
            <br>
            <h3 class="text-center"> Saisie d'une prestation </h3>
                <form method="post" action="index.php?page=infomedicale&id=<?php echo $_GET['id']; ?>">
                    <div class="row margininscription">
                        <div class="col-lg-8">
                            <label for="nomActe">Nom de l'acte</label>
                            <input type="text" class="form-control" id="nomActe"  placeholder="Entrer le nom de l'acte médical" name="nomActe">
                        </div>
                        <div class="col-lg-4">
                            <label for="dateActe">Date de l'acte :</label>
                            <input type="date" class="form-control" id="dateActe" name="dateActe">
                        </div>
                    </div>
                    <div class="row margininscription">
                        <div class="col-lg-12">
                            <label for="commentaireActeNew">Commentaire :</label>
                            <input type="text" class="form-control" id="commentaireActeNew" placeholder="Saisissez un commentaire concernant l'acte réalisé" name="commentaireActeNew">
                        </div>
                    </div>
                    <div class="row margininscription">
                        <div class="col-lg-12">
                            <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <label class="input-group-text" for="inputGroupSelect01">Docteur</label>
                            </div>
                            <select class="custom-select" name="doctorActe"id="inputGroupSelect01">
                                <?php
                                $rep= $bdd->query("SELECT * FROM utilisateur WHERE role=1");
                                    while ($donnees = $rep->fetch()){ ?>
                                        <option value="<?php echo $donnees['id_utilisateur'] ?>"><?php echo $donnees['nom'] ." ". $donnees['prenom'] ?> </option>
                                        <?php
                                    }
                                ?>
                            </select>
                        </div>
                        </div>
                    </div>
                    <?php if($envoyeActe) echo '<div class="alert alert-success">Information enregistré !</div>'; ?>
                    <button type="submit" name="submitActe" class="btn btn-primary center-block" style="margin-bottom: 30px;">Enregistrer</button>
                </form>
        </div>
        <div class="tab-pane fade " id="nav-antecedentHospi" role="tabpanel" aria-labelledby="nav-home-tab">
            <br>
            <h3 class="text-center">Historique Hospitalier</h3>
            <form method="post" action="index.php?page=infomedicale&id=<?php echo $_GET['id']; ?>">
                <div class="row margininscription">
                    <div class="col-lg-6">
                        <label for="antecedentHopitalNom">Nom de l'hopital</label>
                        <input type="text" class="form-control" id="antecedentHopitalNom"  placeholder="Entrer le nom de l'hopital anciennement prestataire" name="antecedentHopitalNom">
                    </div>
                    <div class="col-lg-6">
                        <label for="nomActeAnte">Nom acte :</label>
                        <input type="text" class="form-control" id="nomActeAnte" placeholder="Saisissez l'acte" name="nomActeAnte">
                    </div>
                </div>
                <div class="row margininscription">
                    <div class="col-lg-12">
                        <label for="commentaireActe">Commentaire :</label>
                        <input type="text" class="form-control" id="commentaireActe" placeholder="Saisissez un commentaire concernant l'acte" name="commentaireActe">
                    </div>
                </div>
                <div class="row margininscription">
                    <div class="col-lg-6">
                        <label for="nomSoigneur">Nom Docteur :</label>
                        <input type="text" class="form-control" id="nomSoigneur" placeholder="Saisissez le nom du praticien" name="nomSoigneur">
                    </div>
                    <div class="col-lg-6">
                        <label for="prenomSoigneur">Prénom Docteur :</label>
                        <input type="text" class="form-control" id="prenomSoigneur" placeholder="Saisissez le prénom du praticien" name="prenomSoigneur">
                    </div>
                </div>
                <div class="row margininscription">
                    <div class="col-lg-8">
                        <label for="adresseHopital">Adresse de l'hopital :</label>
                        <input type="text" class="form-control" id="adresseHopital" placeholder="Saisissez l'adresse de l'hopital" name="adresseHopital">
                    </div>
                    <div class="col-lg-4">
                        <label for="dateAntecedent">Date de l'antécédent :</label>
                        <input type="date" class="form-control" id="dateAntecedent" name="dateAntecedent">
                    </div>
                </div>
                <div class="row margininscription">
                    <div class="col-lg-4">
                        <label for="codepostaHopital">Code postal :</label>
                        <input type="text" class="form-control" id="codepostaHopital" placeholder="Saisissez le code postal de l'hopital prestataire" name="codepostaHopital">
                    </div>
                    <div class="col-lg-4">
                        <label for="villeHopital">Ville :</label>
                        <input type="text" class="form-control" id="villeHopital" placeholder="Saisissez la ville de l'hopital prestataire" name="villeHopital">
                    </div>
                    <div class="col-lg-4">
                        <label for="paysHopital">Pays :</label>
                        <input type="text" class="form-control" id="paysHopital" value="France" name="paysHopital">
                    </div>
                </div>
                <?php if($envoye) echo '<div class="alert alert-success">Information enregistré !</div>'; ?>
                <button type="submit" name="submitAnteHopi" class="btn btn-primary center-block" style="margin-bottom: 30px;">Enregistrer</button>
            </form>
        </div>
        <div class="tab-pane fade" id="nav-traitement" role="tabpanel" aria-labelledby="nav-profile-tab">
            <br>
            <h3 class="text-center">Traitement à prescrire</h3>
            <form method="post" action="index.php?page=infomedicale&id=<?php echo $_GET['id']; ?>">
                <div class="row margininscription">
                    <div class="col-lg-12">
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <label class="input-group-text" for="inputGroupSelect01">Medicament</label>
                            </div>
                            <select class="custom-select" name="idtraitement" id="inputGroupSelect01">
                                <?php
                                $rep= $bdd->query("SELECT * FROM medicament");
                                while ($donnees = $rep->fetch()){ ?>
                                    <option value="<?php echo $donnees['idMedicament'] ?>"><?php echo $donnees['nomMedicament']; ?> </option>
                                    <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row margininscription">
                    <div class="col-lg-12">
                        <label for="commentaireTraitement">Commentaire concernant le traitement (Ex : Matin Midi Soir)</label>
                        <input type="text" class="form-control" id="commentaireTraitement"  placeholder="Commentaire concernant le traitement" name="commentaireTraitement">
                    </div>
                </div>
                <div class="row margininscription">
                    <div class="form-group col-lg-8">
                        <label for="exampleFormControlSelect2">Consigne pour le traitement</label>
                        <select multiple class="form-control" name="dureeTraitement" id="dureeTraitement">
                            <option>1</option>
                            <option>2</option>
                            <option>3</option>
                            <option>4</option>
                            <option>5</option>
                            <option>6</option>
                            <option>7</option>
                            <option>8</option>
                            <option>9</option>
                            <option>10</option>
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label for="dateTraitement">Début du traitement:</label>
                        <input type="date" class="form-control" id="dateTraitement" name="dateTraitement">
                    </div>
                </div>
                <?php if($envoyerTraitement) echo '<div class="alert alert-success">Information enregistré !</div>'; ?>
                <button type="submit" name="submitTraitement" class="btn btn-primary center-block" style="margin-bottom: 30px;">Enregistrer</button>
            </form>
        </div>
        <div class="tab-pane fade" id="nav-allergie" role="tabpanel" aria-labelledby="nav-contact-tab">
            <br>
            <h3 class="text-center">Allergie</h3>
            <form method="post" action="index.php?page=infomedicale&id=<?php echo $_GET['id']; ?>">
                <div class="row margininscription">
                    <div class="col-lg-12">
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <label class="input-group-text" for="inputGroupSelect01">Medicament</label>
                            </div>
                            <select class="custom-select" name="idtraitement" id="inputGroupSelect01">
                                <?php
                                $rep= $bdd->query("SELECT * FROM typeAllergie");
                                while ($donnees = $rep->fetch()){ ?>
                                    <option value="<?php echo $donnees['idTypeA'] ?>"><?php echo $donnees['nom'] ." ". $donnees['commentaire'] ?> </option>
                                    <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row margininscription">
                    <div class="col-lg-12">
                        <label for=graviteAllergie>Gravité à noter de + à +++++</label>
                        <input type="text" class="form-control" id="graviteAllergie"  placeholder="Gravité concernant l'allergie" name="graviteAllergie">
                    </div>
                </div>
                <?php if($envoyeAllergie) echo '<div class="alert alert-success">Information enregistré !</div>'; ?>
                <button type="submit" name="submitAllergie" class="btn btn-primary center-block" style="margin-bottom: 30px;">Enregistrer</button>
            </form>
        </div>


        <div class="tab-pane fade" id="nav-rythme" role="tabpanel" aria-labelledby="nav-contact-tab">
            <br>
            <h3 class="text-center">Saisie rythme</h3>
            <form method="post" action="index.php?page=infomedicale&id=<?php echo $_GET['id']; ?>">
                <div class="col-lg-12">
                    <label for="valRythme">Rythme cardiaque du jour</label>
                    <input type="text" class="form-control" id="valRythme"  placeholder="Rythme cardiaque du jour" name="valRythme">
                </div>
                <br>
                <button type="submit" name="submitRythme" class="btn btn-primary center-block" style="margin-bottom: 30px;">Enregistrer</button>
                <?php if($envoyerRythme) echo '<div class="alert alert-success">Information enregistré !</div>'; ?>
            </form>
            <br>
            <h3>Historique</h3>
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                <tr>
                    <th>Valeur Rythme cardiaque</th>
                    <th>date</th>
                </tr>
                </thead>
                <tfoot>
                <tr>
                    <th>Valeur rythme cardiaque</th>
                    <th>date</th>
                </tr>
                </tfoot>
                <tbody>
                <?php
                $id=$_GET['id'];
                $repC = $bdd->query("SELECT * FROM rythmeC WHERE idPatient=$id ORDER BY date DESC");
                    while ($test = $repC->fetch()) {
                        echo '<tr>';
                        echo '<td>' . $test['valeur'] . '</td>';
                        echo '<td>' . $test['date'] . '</td>';
                    }
                    ?>
                </tbody>
            </table>
        </div>


            <div class="tab-pane fade" id="nav-tension" role="tabpanel" aria-labelledby="nav-contact-tab">
                <br>
                <h3 class="text-center">Saisie tension</h3>
                <form method="post" action="index.php?page=infomedicale&id=<?php echo $_GET['id']; ?>">
                    <div class="col-lg-12">
                        <label for="valTens">Saisie tension du jour</label>
                        <input type="text" class="form-control" id="valTens"  placeholder="Saisie tension du jour" name="valTens">
                    </div>
                    <br>
                    <button type="submit" name="submitTens" class="btn btn-primary center-block" style="margin-bottom: 30px;">Enregistrer</button>
                    <?php if($envoyertension) echo '<div class="alert alert-success">Information enregistré !</div>'; ?>
                </form>
                <br>
                <h3>Historique</h3>
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th>Valeur Tension</th>
                        <th>date</th>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr>
                        <th>Valeur Tension</th>
                        <th>date</th>
                    </tr>
                    </tfoot>
                    <tbody>
                    <?php
                    $id=$_GET['id'];
                    $repC = $bdd->query("SELECT * FROM tension WHERE idPatient=$id ORDER BY date DESC");
                    while ($test = $repC->fetch()) {
                        echo '<tr>';
                        echo '<td>' . $test['valeur'] . '</td>';
                        echo '<td>' . $test['date'] . '</td>';
                    }
                    ?>
                    </tbody>
                </table>
            </div>


            <div class="tab-pane fade" id="nav-temp" role="tabpanel" aria-labelledby="nav-contact-tab">
                <br>
                <h3 class="text-center">Saisie température</h3>
                <form method="post" action="index.php?page=infomedicale&id=<?php echo $_GET['id']; ?>">
                    <div class="col-lg-12">
                        <label for="valTemp">Température du jour</label>
                        <input type="text" class="form-control" id="valTemp"  placeholder="Saisie température" name="valTemp">
                    </div>
                    <br>
                    <button type="submit" name="submitTemp" class="btn btn-primary center-block" style="margin-bottom: 30px;">Enregistrer</button>
                    <?php if($envoyertemp) echo '<div class="alert alert-success">Information enregistré !</div>'; ?>
                </form>
                <br>
                <h3>Historique</h3>
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th>Valeur Tension</th>
                        <th>date</th>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr>
                        <th>Valeur Tension</th>
                        <th>date</th>
                    </tr>
                    </tfoot>
                    <tbody>
                    <?php
                    $id=$_GET['id'];
                    $repC = $bdd->query("SELECT * FROM temperature WHERE idPatient=$id ORDER BY date DESC");
                    while ($test = $repC->fetch()) {
                        echo '<tr>';
                        echo '<td>' . $test['valeur'] . '</td>';
                        echo '<td>' . $test['date'] . '</td>';
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
</div>