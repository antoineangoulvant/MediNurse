<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 18/05/2018
 * Time: 00:40
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

<div class="container">
        <nav>
            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-tension" role="tab" aria-controls="nav-home" aria-selected="true">Tension</a>
                <a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#nav-temp" role="tab" aria-controls="nav-profile" aria-selected="false">Température</a>
                <a class="nav-item nav-link" id="nav-contact-tab" data-toggle="tab" href="#nav-rythme" role="tab" aria-controls="nav-contact" aria-selected="false">Rythme cardiaque</a>
            </div>
        </nav>
        <div class="tab-content" id="nav-tabContent">
            <div class="tab-pane fade show active" id="nav-tension" role="tabpanel" aria-labelledby="nav-home-tab">...</div>
            <div class="tab-pane fade" id="nav-temp" role="tabpanel" aria-labelledby="nav-profile-tab">...</div>
            <div class="tab-pane fade" id="nav-rythme" role="tabpanel" aria-labelledby="nav-contact-tab">...</div>
        </div>
</div>
