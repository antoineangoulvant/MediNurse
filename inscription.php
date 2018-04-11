<?php
/**
 * Created by PhpStorm.
 * User: antoi
 * Date: 15/03/2018
 * Time: 19:30
 */

?>

<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="text-center">Page d'inscription</h1>
        </div>
        <form method="post" action="index.php?page=inscription">
            <div class="col-lg-12">
                <div class="form-group">
                    <label for="prenom">Prénom :</label>
                    <input type="text" class="form-control" id="prenom" placeholder="Saisissez le prenom" name="prenom">
                </div>
                <div class="form-group">
                    <label for="nom">Nom :</label>
                    <input type="text" class="form-control" id="nom" placeholder="Saisissez le nom" name="nom">
                </div>
            </div>

        </form>
    </div>
</div>
