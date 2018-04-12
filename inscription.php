<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="text-center">Page d'inscription</h1>
        </div>
    </div>
    <form method="post" action="index.php?page=inscription">
        <div class="row">
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="genre">Genre<span class="obligatoire">*</span> :</label>
                    <select id="genre" name="genre" class="form-control">
                        <option value="homme">Mr</option>
                        <option value="femme">Mme</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="prenom">Prénom<span class="obligatoire">*</span> :</label>
                    <input type="text" class="form-control" id="prenom" placeholder="Saisissez le prenom" name="prenom">
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="nom">Nom<span class="obligatoire">*</span> :</label>
                    <input type="text" class="form-control" id="nom" placeholder="Saisissez le nom" name="nom">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="teldom">Téléphone domicile :</label>
                    <input type="tel" class="form-control" id="teldom" placeholder="Saisissez le numéro" name="teldom">
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="teltrav">Téléphone travail :</label>
                    <input type="tel" class="form-control" id="teltrav" placeholder="Saisissez le numéro" name="teltrav">
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="telport">Téléphone portable<span class="obligatoire">*</span> :</label>
                    <input type="tel" class="form-control" id="telport" placeholder="Saisissez le numéro" name="telport">
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary center-block">Enregistrer</button>
    </form>
</div>
