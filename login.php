<?php
/**
 * Created by PhpStorm.
 * User: antoi
 * Date: 21/02/2018
 * Time: 15:15
 */

    try
    {
        $bdd = new PDO('mysql:host=e88487-mysql.services.easyname.eu;dbname=u139724db1;charset=utf8', 'u139724db1', 'pp5959he');
    }
    catch (Exception $e)
    {
        die('Erreur : ' . $e->getMessage());
    }

    $erreur = '';

    //$pass_hache = password_hash($_POST['pass'], PASSWORD_DEFAULT);
    if($_SERVER["REQUEST_METHOD"] == "POST") {
        if( $_POST['idUtilisateur'] != '' AND $_POST['passwordUtilisateur'] != ''){
            $id = $_POST['idUtilisateur'];
            $pass = $_POST['passwordUtilisateur'];

            //  Récupération de l'utilisateur et de son pass hashé
            $req = $bdd->prepare('SELECT id_utilisateur, motdepasse FROM utilisateur WHERE id_utilisateur = :id');
            $req->execute(array('id' => $id));
            $resultat = $req->fetch();

            $isPasswordCorrect = password_verify($_POST['passwordUtilisateur'], $resultat['motdepasse']);

            if ($isPasswordCorrect) {
                session_start();
                $_SESSION['id'] = $id;
                echo 'Vous êtes connecté !';
                header('Location: index.php');
            }
            else {
                echo 'Mauvais identifiant ou mot de passe !';
            }
        }
        else{
            $erreur = 'Veuillez saisir votre identifiant et un mot de passe';
        }
    }
?>
<!DOCTYPE html>
<html lang="fr">
    <title>MediNurse - Connexion</title>

    <?php include 'head.php' ?>

    <body class="bg-dark">
        <div class="container">
            <div class="card card-login mx-auto mt-5">
                <div class="card-header text-center"><img src="res/logo.png" class="img-fluid" alt="Responsive image"></div>
                <div class="card-body">
                    <form action="login.php" method="post">
                        <div class="form-group">
                            <label for="idUtilisateur">Votre identifiant :</label>
                            <input class="form-control" name="idUtilisateur" id="idUtilisateur" type="number" placeholder="Identifiant" value="<?php if(isset($_POST['idUtilisateur'])) echo $_POST['idUtilisateur']; ?>">
                        </div>
                        <div class="form-group">
                            <label for="passwordUtilisateur">Mot de passe :</label>
                            <input class="form-control" name="passwordUtilisateur" id="passwordUtilisateur" type="password" placeholder="Mot de passe">
                        </div>
                        <?php if($erreur != '') echo '<div class="alert alert-danger">'.$erreur.'</div>'; ?>
                        <div class="text-center"><button type="submit" class="btn btn-primary">Connexion</button></div>
                    </form>
                    <div class="text-center">
                        <?php //<a class="d-block small mt-3" href="register.php">Register an Account</a> ?>
                        <!--<a class="d-block small mt-3" href="forgot-password.php">Mot de passe oublié</a>-->
                    </div>
                </div>
            </div>
        </div>
        <!-- Bootstrap core JavaScript-->
        <script src="vendor/jquery/jquery.min.js"></script>
        <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <!-- Core plugin JavaScript-->
        <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    </body>
</html>

