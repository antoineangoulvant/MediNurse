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

        if( empty($_POST['mdp']) ){
            $erreur['mdp'] = 'Le champs mot de passe est obligatoire';
            $formulaire_valide = false;
        }else{
            $pass = $_POST['mdp'];
        }

        //Insertion dans la base
        if( $formulaire_valide ) {
            $pass_hache = password_hash($pass, PASSWORD_DEFAULT);

            $req = $bdd->prepare('INSERT INTO utilisateur(nom,prenom,genre,datenaissance,adresse1,adresse2,codepostal,ville,pays,teldom,teltrav,telport,mail,motdepasse) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $req->execute(array($_POST['nom'], $_POST['prenom'], $_POST['genre'], $_POST['datenaissance'], $_POST['adresse'], $_POST['adressecomp'], $_POST['codepostal'], $_POST['ville'], $_POST['pays'], $_POST['teldom'], $_POST['teltrav'], $_POST['telport'], $_POST['mail'], $pass_hache));
            $envoye = true;
        }
    }
?>

<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="text-center">Page d'inscription</h1>
        </div>
    </div>
    <form method="post" action="../index.php?page=inscription">
        <div class="row margininscription">
            <div class="col-lg-2">
                <div class="form-group">
                    <label for="genre">Genre<span class="obligatoire">*</span> :</label>
                    <select id="genre" name="genre" class="form-control">
                        <option value="1">Mr</option>
                        <option value="2">Mme</option>
                    </select>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="prenom">Prénom<span class="obligatoire">*</span> :</label>
                    <input type="text" class="form-control" id="prenom" placeholder="Saisissez le prenom" name="prenom">
                    <?php if(isset($erreur['prenom'])) echo '<div class="alert alert-danger">'.$erreur['prenom'].'</div>'; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="nom">Nom<span class="obligatoire">*</span> :</label>
                    <input type="text" class="form-control" id="nom" placeholder="Saisissez le nom" name="nom">
                    <?php if(isset($erreur['nom'])) echo '<div class="alert alert-danger">'.$erreur['nom'].'</div>'; ?>
                </div>
            </div>
            <div class="col-lg-2">
                <label for="datenaissance">Date de naissance<span class="obligatoire">*</span> :</label>
                <input type="date" class="form-control" id="datenaissance" name="datenaissance">
                <?php if(isset($erreur['datenaissance'])) echo '<div class="alert alert-danger">'.$erreur['datenaissance'].'</div>'; ?>
            </div>
        </div>
        <div class="row margininscription">
            <div class="col-lg-8">
                <label for="mail">Adresse mail<span class="obligatoire">*</span> :</label>
                <input type="email" class="form-control" id="mail" placeholder="Saisissez l'adresse mail" name="mail">
                <?php if(isset($erreur['mail'])) echo '<div class="alert alert-danger">'.$erreur['mail'].'</div>'; ?>
            </div>
            <div class="col-lg-4">
                <label for="mdp">Mot de passe<span class="obligatoire">*</span> :</label>
                <input type="password" class="form-control" id="mdp" placeholder="Saisissez le mot de passe" name="mdp">
                <?php if(isset($erreur['mdp'])) echo '<div class="alert alert-danger">'.$erreur['mdp'].'</div>'; ?>
            </div>
        </div>
        <div class="row margininscription">
            <div class="col-lg-6">
                <label for="adresse">Adresse<span class="obligatoire">*</span> :</label>
                <input type="text" class="form-control" id="adresse" placeholder="Saisissez l'adresse" name="adresse">
                <?php if(isset($erreur['adresse'])) echo '<div class="alert alert-danger">'.$erreur['adresse'].'</div>'; ?>
            </div>
            <div class="col-lg-6">
                <label for="adressecomp">Adresse complémentaire :</label>
                <input type="text" class="form-control" id="adressecomp" placeholder="Saisissez l'adresse complémentaire" name="adressecomp">
                <?php if(isset($erreur['adressecomp'])) echo '<div class="alert alert-danger">'.$erreur['adressecomp'].'</div>'; ?>
            </div>
        </div>
        <div class="row margininscription">
            <div class="col-lg-4">
                <label for="codepostal">Code postal<span class="obligatoire">*</span> :</label>
                <input type="text" class="form-control" id="codepostal" placeholder="Saisissez le code postal" name="codepostal">
                <?php if(isset($erreur['codepostal'])) echo '<div class="alert alert-danger">'.$erreur['codepostal'].'</div>'; ?>
            </div>
            <div class="col-lg-4">
                <label for="ville">Ville<span class="obligatoire">*</span> :</label>
                <input type="text" class="form-control" id="ville" placeholder="Saisissez la ville" name="ville">
                <?php if(isset($erreur['ville'])) echo '<div class="alert alert-danger">'.$erreur['ville'].'</div>'; ?>
            </div>
            <div class="col-lg-4">
                <label for="pays">Pays<span class="obligatoire">*</span> :</label>
                <input type="text" class="form-control" id="pays" value="France" name="pays">
                <?php if(isset($erreur['pays'])) echo '<div class="alert alert-danger">'.$erreur['pays'].'</div>'; ?>
            </div>
        </div>
        <div class="row margininscription">
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="teldom">Téléphone domicile :</label>
                    <input type="tel" class="form-control" id="teldom" placeholder="Saisissez le numéro" name="teldom">
                    <?php if(isset($erreur['teldom'])) echo '<div class="alert alert-danger">'.$erreur['teldom'].'</div>'; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="teltrav">Téléphone travail :</label>
                    <input type="tel" class="form-control" id="teltrav" placeholder="Saisissez le numéro" name="teltrav">
                    <?php if(isset($erreur['teltrav'])) echo '<div class="alert alert-danger">'.$erreur['teltrav'].'</div>'; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="telport">Téléphone portable<span class="obligatoire">*</span> :</label>
                    <input type="tel" class="form-control" id="telport" placeholder="Saisissez le numéro" name="telport">
                    <?php if(isset($erreur['telport'])) echo '<div class="alert alert-danger">'.$erreur['telport'].'</div>'; ?>
                </div>
            </div>
        </div>
        <?php if($envoye) echo '<div class="alert alert-success">Utilisateur enregistré !</div>'; ?>
        <button type="submit" class="btn btn-primary center-block" style="margin-bottom: 30px;">Enregistrer</button>
    </form>
</div>
