
<?php
//Empêche l'insertion dans la bdd si il y a une erreur
$formulaire_valide = true;
//Permet l'affichage d'un message d'envoi
$envoye = false;

try
{
    $bdd = new PDO('mysql:host=e88487-mysql.services.easyname.eu;dbname=u139724db1;charset=utf8', 'u139724db1', 'pp5959he');
}
catch (Exception $e)
{
    die('Erreur : ' . $e->getMessage());
}

$req = $bdd->prepare('SELECT * FROM patient WHERE id_patient = :id');
$req->execute(array('id' => $_GET['id']));
$resultat = $req->fetch();

//Test des champs et creation d'une erreur si champs non rempli
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $erreur=[];

    if( $_POST['nom'] == '' ){
        $erreur['nom'] = 'Le champs nom est obligatoire';
        $formulaire_valide = false;
    }

    if( $_POST['prenom'] == '' ){
        $erreur['prenom'] = 'Le champs prénom est obligatoire';
        $formulaire_valide = false;
    }

    if( $_POST['mail'] == '' ){
        $erreur['mail'] = 'Le champs mail est obligatoire';
        $formulaire_valide = false;
    }

    if( $_POST['datenaissance'] == '' ){
        $erreur['datenaissance'] = 'Le champs date de naissance est obligatoire';
        $formulaire_valide = false;
    }

    if( empty($_POST['adresse']) ){
        $erreur['adresse'] = 'Le champs adresse est obligatoire';
        $formulaire_valide = false;
    }

    if( empty($_POST['codepostal']) ){
        $erreur['codepostal'] = 'Le champs code postal est obligatoire';
        $formulaire_valide = false;
    }

    if( empty($_POST['ville']) ){
        $erreur['ville'] = 'Le champs ville est obligatoire';
        $formulaire_valide = false;
    }

    if( empty($_POST['pays']) ){
        $erreur['pays'] = 'Le champs pays est obligatoire';
        $formulaire_valide = false;
    }

    //Insertion dans la base
    if( $formulaire_valide ) {
        $req = $bdd->prepare('UPDATE patient SET nom=?,prenom=?,genre=?,datenaissance=?,adresse1=?,adresse2=?,codepostal=?,ville=?,pays=?,teldom=?,teltrav=?,telport=?,mail=?,service=? WHERE id_patient=?');
        $req->execute(array($_POST['nom'], $_POST['prenom'], $_POST['genre'], $_POST['datenaissance'], $_POST['adresse'], $_POST['adressecomp'], $_POST['codepostal'], $_POST['ville'], $_POST['pays'], $_POST['teldom'], $_POST['teltrav'], $_POST['telport'], $_POST['mail'], $_POST['service'], $_GET['id']));
        $envoye = true;
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="text-center">Patient n°<?php echo $resultat['id_patient']; ?></h1>
        </div>
    </div>
    <form method="post" action="index.php?page=patient&id=<?php echo $_GET['id']; ?>">
        <div class="row margininscription">
            <div class="col-lg-2">
                <div class="form-group">
                    <label for="genre">Genre<span class="obligatoire">*</span> :</label>
                    <select id="genre" name="genre" class="form-control">
                        <option<?php if($resultat['genre'] == 1) echo ' selected'; ?> value="1">Mr</option>
                        <option<?php if($resultat['genre'] == 2) echo ' selected'; ?> value="2">Mme</option>
                    </select>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="prenom">Prénom<span class="obligatoire">*</span> :</label>
                    <input type="text" class="form-control" id="prenom" value="<?php echo $resultat['prenom']; ?>" name="prenom">
                    <?php if(isset($erreur['prenom'])) echo '<div class="alert alert-danger">'.$erreur['prenom'].'</div>'; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="nom">Nom<span class="obligatoire">*</span> :</label>
                    <input type="text" class="form-control" id="nom" value="<?php echo $resultat['nom']; ?>" name="nom">
                    <?php if(isset($erreur['nom'])) echo '<div class="alert alert-danger">'.$erreur['nom'].'</div>'; ?>
                </div>
            </div>
            <div class="col-lg-2">
                <label for="datenaissance">Date de naissance<span class="obligatoire">*</span> :</label>
                <input type="date" class="form-control" id="datenaissance" value="<?php echo $resultat['datenaissance']; ?>" name="datenaissance">
                <?php if(isset($erreur['datenaissance'])) echo '<div class="alert alert-danger">'.$erreur['datenaissance'].'</div>'; ?>
            </div>
        </div>
        <div class="row margininscription">
            <div class="col-lg-8">
                <label for="mail">Adresse mail<span class="obligatoire">*</span> :</label>
                <input type="email" class="form-control" id="mail" value="<?php echo $resultat['mail']; ?>" name="mail">
                <?php if(isset($erreur['mail'])) echo '<div class="alert alert-danger">'.$erreur['mail'].'</div>'; ?>
            </div>
            <div class="col-lg-4">
                <label for="ins">Identifiant National de Santé :<span class="obligatoire">*</span> :</label>
                <input type="text" class="form-control" id="ins" value="<?php echo $resultat['ins']; ?>" name="ins">
                <?php if(isset($erreur['ins'])) echo '<div class="alert alert-danger">'.$erreur['ins'].'</div>'; ?>
            </div>
        </div>
        <div class="row margininscription">
            <div class="col-lg-6">
                <label for="adresse">Adresse<span class="obligatoire">*</span> :</label>
                <input type="text" class="form-control" id="adresse" value="<?php echo $resultat['adresse1']; ?>" name="adresse">
                <?php if(isset($erreur['adresse'])) echo '<div class="alert alert-danger">'.$erreur['adresse'].'</div>'; ?>
            </div>
            <div class="col-lg-6">
                <label for="adressecomp">Adresse complémentaire :</label>
                <input type="text" class="form-control" id="adressecomp" value="<?php echo $resultat['adresse2']; ?>" name="adressecomp">
                <?php if(isset($erreur['adressecomp'])) echo '<div class="alert alert-danger">'.$erreur['adressecomp'].'</div>'; ?>
            </div>
        </div>
        <div class="row margininscription">
            <div class="col-lg-4">
                <label for="codepostal">Code postal<span class="obligatoire">*</span> :</label>
                <input type="text" class="form-control" id="codepostal" value="<?php echo $resultat['codepostal']; ?>" name="codepostal">
                <?php if(isset($erreur['codepostal'])) echo '<div class="alert alert-danger">'.$erreur['codepostal'].'</div>'; ?>
            </div>
            <div class="col-lg-4">
                <label for="ville">Ville<span class="obligatoire">*</span> :</label>
                <input type="text" class="form-control" id="ville" value="<?php echo $resultat['ville']; ?>" name="ville">
                <?php if(isset($erreur['ville'])) echo '<div class="alert alert-danger">'.$erreur['ville'].'</div>'; ?>
            </div>
            <div class="col-lg-4">
                <label for="pays">Pays<span class="obligatoire">*</span> :</label>
                <input type="text" class="form-control" id="pays" value="<?php echo $resultat['pays']; ?>" name="pays">
                <?php if(isset($erreur['pays'])) echo '<div class="alert alert-danger">'.$erreur['pays'].'</div>'; ?>
            </div>
        </div>
        <div class="row margininscription">
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="teldom">Téléphone domicile :</label>
                    <input type="tel" class="form-control" id="teldom" value="<?php echo $resultat['teldom']; ?>" name="teldom">
                    <?php if(isset($erreur['teldom'])) echo '<div class="alert alert-danger">'.$erreur['teldom'].'</div>'; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="teltrav">Téléphone travail :</label>
                    <input type="tel" class="form-control" id="teltrav" value="<?php echo $resultat['teltrav']; ?>" name="teltrav">
                    <?php if(isset($erreur['teltrav'])) echo '<div class="alert alert-danger">'.$erreur['teltrav'].'</div>'; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="telport">Téléphone portable<span class="obligatoire">*</span> :</label>
                    <input type="tel" class="form-control" id="telport" value="<?php echo $resultat['telport']; ?>" name="telport">
                    <?php if(isset($erreur['telport'])) echo '<div class="alert alert-danger">'.$erreur['telport'].'</div>'; ?>
                </div>
            </div>
        </div>
        <div class="row margininscription">
            <div class="col-lg-3">
                <div class="form-group">
                    <label for="service">Service<span class="obligatoire">*</span> :</label>
                    <select id="service" name="service" class="form-control">
                        <?php
                        $reqservice = $bdd->query('SELECT * FROM service');
                        while ($donnees = $reqservice->fetch()){
                            $selected = '';
                            if( $resultat['service'] == $donnees['id_service'] ){
                                $selected = ' selected';
                            }
                            echo '<option'.$selected.' value="'.$donnees['id_service'].'">'.$donnees['libelle'].'</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <?php if($envoye) echo '<div class="alert alert-success">Modifications enregistrées !</div>'; ?>
        <button type="submit" class="btn btn-primary center-block" style="margin-bottom: 30px;">Enregistrer</button>
    </form>
</div>