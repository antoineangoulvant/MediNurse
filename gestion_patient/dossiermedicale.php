<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 16/05/2018
 * Time: 19:37
 */

try
{
    $bdd = new PDO('mysql:host=e88487-mysql.services.easyname.eu;dbname=u139724db1;charset=utf8', 'u139724db1', 'pp5959he');
}
catch (Exception $e)
{
    die('Erreur : ' . $e->getMessage());
}

?>
<script>
    function showUser(str) {
        if (str == "") {
            document.getElementById("txtHint").innerHTML = "";
            return;
        } else {
            if (window.XMLHttpRequest) {
                // code for IE7+, Firefox, Chrome, Opera, Safari
                xmlhttp = new XMLHttpRequest();
            } else {
                // code for IE6, IE5
                xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
            }
            xmlhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    document.getElementById("txtHint").innerHTML = this.responseText;
                }
            };
            xmlhttp.open("GET","getuser.php?q="+str,true);
            xmlhttp.send();
        }
    }
</script>
<div class="container">
    <h3 class="text-center"> Dossier médical du patient</h3>
    <form>
        <div class="row margininscription">
            <div class="col-lg-12">
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <label class="input-group-text" for="inputGroupSelect01">Patient</label>
                    </div>
                    <select class="custom-select" name="users"id="inputGroupSelect01" onchange="showUser(this.value)">
                        <?php
                        $rep= $bdd->query("SELECT * FROM patient ORDER BY nom ASC");
                        while ($donnees = $rep->fetch()){ ?>
                            <option value="<?php echo $donnees['id_patient'] ?>"><?php echo $donnees['nom'] ." ". $donnees['prenom'] ?> </option>
                            <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
    </form>
    <div id="txtHint"><b>Les informations apparaitront après sélection d'un patient</b></div>
</div>

