<?php
    session_start();
    if( !isset($_SESSION['id']) ){
        header( 'Location: login.php');
    }

    $page = 'accueil';
    if( isset($_GET['page'])) $page = $_GET['page'];

    $titre = 'MediNurse - Accueil';
    switch ($page){
        case 'inscription':
            $titre = 'Inscription';
            break;
        case 'listeutilisateurs':
            $titre = 'Liste des utilisateurs';
            break;
        case 'utilisateur':
            $titre = 'Utilisateur';
            break;
        case 'inscription_patient':
            $titre = 'Saisie d\'un patient';
            break;
        case 'liste_patients':
            $titre = 'Liste des patients';
            break;
        case 'patient':
            $titre = 'Fiche patient';
            break;
        case 'gestionchambre':
            $titre = 'Gestion des chambres';
            break;
        case 'gestionlit':
            $titre = 'Gestion des lits';
            break;
        case 'lecturepatient':
            $titre = 'Lecture patient';
            break;
        case 'ajouter':
            $titre = 'Ajout ToDoList';
            break;
        case 'voirliste':
            $titre = 'Voir ToDoList';
            break;
        default:
            $titre = 'Accueil';
    }
?>

<!DOCTYPE html>
<html lang="fr">

    <?php include 'head.php' ?>

    <body class="fixed-nav sticky-footer bg-dark" id="page-top">
        <!-- Navigation-->
        <?php include 'navbar.php' ?>

        <div class="content-wrapper">
            <div class="container-fluid">
                <!-- Breadcrumbs-->
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="index.php">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item active"><?php echo $titre ?></li>
                </ol>
            </div>

            <?php
                switch ($page){
                    case 'inscription':
                        include 'gestion_utilisateur/inscription_utilisateur.php';
                        break;
                    case 'listeutilisateurs':
                        include 'gestion_utilisateur/liste_utilisateurs.php';
                        break;
                    case 'utilisateur':
                        include 'gestion_utilisateur/utilisateur.php';
                        break;
                    case 'inscription_patient':
                        include 'gestion_patient/inscription_patient.php';
                        break;
                    case 'liste_patients':
                        include 'gestion_patient/liste_patients.php';
                        break;
                    case 'patient':
                        include 'gestion_patient/patient.php';
                        break;
                    case 'gestionchambre':
                        include 'gestion_chambre.php';
                        break;
                    case 'gestionlit':
                        include 'gestion_lit.php';
                        break;
                    case 'lecturepatient':
                        include 'gestion_patient/lecture_patient.php';
                        break;
                    case 'voirliste':
                        include 'gestion_patient/voirliste.php';
                }
            ?>
        </div>
        <!-- /.container-fluid-->
        <!-- /.content-wrapper-->
        <footer class="sticky-footer">
            <div class="container">
                <div class="text-center">
                    <small>Réalisé par Antoine Angoulvant - David Luong - Guillaume Rougeau - Jérôme Mialon</small>
                </div>
            </div>
        </footer>
        <!-- Scroll to Top Button-->
        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fa fa-angle-up"></i>
        </a>
        <!-- Logout Modal-->
        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Déconnexion</h5>
                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">Selectionner "Déconnexion" pour fermer votre session</div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-dismiss="modal">Annuler</button>
                        <a class="btn btn-primary" href="deconnexion.php">Déconnexion</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Bootstrap core JavaScript-->
        <script src="vendor/jquery/jquery.min.js"></script>
        <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <!-- Core plugin JavaScript-->
        <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
        <!-- Page level plugin JavaScript-->
        <script src="vendor/chart.js/Chart.min.js"></script>
        <script src="vendor/datatables/jquery.dataTables.js"></script>
        <script src="vendor/datatables/dataTables.bootstrap4.js"></script>
        <!-- Custom scripts for all pages-->
        <script src="js/sb-admin.min.js"></script>
        <!-- Custom scripts for this page-->
        <script src="js/sb-admin-datatables.min.js"></script>
        <script src="js/sb-admin-charts.min.js"></script>
    </body>
</html>
