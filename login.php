<?php
/**
 * Created by PhpStorm.
 * User: antoi
 * Date: 21/02/2018
 * Time: 15:15
 */
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
                    <form action="connexion_post.php" method="post">
                        <div class="form-group">
                            <label for="idUtilisateur">Votre identifiant :</label>
                            <input class="form-control" name="idUtilisateur" id="idUtilisateur" type="number" placeholder="Identifiant">
                        </div>
                        <div class="form-group">
                            <label for="passwordUtilisateur">Mot de passe :</label>
                            <input class="form-control" name="passwordUtilisateur" id="passwordUtilisateur" type="password" placeholder="Mot de passe">
                        </div>
                        <div class="text-center"><button type="submit" class="btn btn-primary">Connexion</button></div>
                    </form>
                    <div class="text-center">
                        <?php //<a class="d-block small mt-3" href="register.html">Register an Account</a> ?>
                        <a class="d-block small mt-3" href="forgot-password.html">Mot de passe oublié</a>
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

