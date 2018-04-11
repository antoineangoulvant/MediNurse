<?php
/**
* Ensemble des variables de configuration.
*
* @package phpMyLab
*/

//Chemin de la connexion a la base
$chemin_connection='connectionPHPMYLABDB.php';

// Version du logiciel
$version = trim(substr('$Revision: 3.0.0 $', 10, -1));
//organisme de rattachement (en haut et en bas de page)
$organisme='Ma societe';
//Liens du site de l'organisme
$lien_organisme='http://masociete.fr';
//nom d'utilisateur du directeur
$directeur='logindudirecteur';
//mail de la liste de diffusion du service administratif
$mel_gestiolab='gestionnaire@masociete.fr';
//chemin pour le lien vers une mission par exemple dans les mails
$chemin_mel='masociete.fr';
$chemin_mel.=substr($_SERVER[ 'PHP_SELF' ],0,-strlen(strrchr($_SERVER[ 'PHP_SELF' ],'/')));
$chemin_mel.='/reception.php';
//Domaine pour le serveur de mail (ex: toto@domaine)
$domaine='mail.societe.fr';
//adresse electronique des webmasters
$web_adress='webmaster@mail.societe.fr';
//liste des annee pour le calendrier
$annees=array("----","2014","2015","2016","2017","2018","2019","2020","2021","2022","2023","2024","2025");
//annee actuelle
$annee_en_cours=date('Y');
//mode de test ou de production
$mode_test=1;//mode de test
$mel_test='webmaster@mail.societe.fr';//en mode test tous les mails sont envoyes la.

$cas = 1;//Indique que l'on veut utiliser CAS
$captcha = 1;//Indique que l'on veut utiliser un captcha sur le formulaire de demande d'identifiants

//liste de modules du logiciel
//Supprimez un module de cette liste selon vos besoins
//ne modifiez pas l'orthographe
//Attention, le module PLANNING decoule des modules MISSIONS et/ou CONGES
$modules=array("MISSIONS","CONGES","PLANNING","EXPEDITIONS","INVENTAIRE","COMMUNITY");

/////////////////////////////////////////////////////////
// Variables module MISSIONS
//liste de moyens de transport
$vehicules=array("Choisir un v&eacute;hicule","Voiture de location","Train","Avion","V&eacute;hicule personnel","Voiture de service 1");
//liste pour champs objet pour aide comme $vehicules
$objets=array("Choisir un objet","R&eacute;union","Colloque","Workshop","Congr&egrave;s","S&eacute;minaire","Ecole th&eacute;matique","Formation","Maintenance");
$libelle_lien1='Système de r&eacute;servations';//texte du bouton
$adresse_lien1='http://masociete.fr/resa';//adresse du lien
$libelle_lien2='';//texte du bouton
$adresse_lien2='';//adresse du lien
$libelle_lien3='';//texte du bouton
$adresse_lien3='';//adresse du lien
$libelle_lien4='';//texte du bouton
$adresse_lien4='';//adresse du lien
$libelle_lien5='';//texte du bouton
$adresse_lien5='';//adresse du lien
$libelle_lien6='';//texte du bouton
$adresse_lien6='';//adresse du lien

/////////////////////////////////////////////////////////
// Variables module CONGES
//liste des differents type contrats avec le nombre de conge associe
$type_contrats=array(array("Choix du contrat",0),array("Personnel",35),array("Chef &eacute;quipe",47));
//liste de type de conges (avec solde) non modifiable!
$conge_type=array("Cong&eacute;s annuels","Compte &eacute;pargne temps","R&eacute;cup&eacute;ration","Autres...");
//liste de type de conges (sans solde)
$conge_sans_solde=array("Cong&eacute;s naissance","Cong&eacute;s enfant malade","Arr&ecirc;t de travail","Cong&eacute;s d&eacute;m&eacute;nagement","Mariage","Mariage enfant","D&eacute;c&egrave;s","Conjoint maladie grave","Cong&eacute;s maladie","Activit&eacute; secondaire");

/////////////////////////////////////////////////////////
// Variables module EXPEDITIONS
$gestionnaires_expeditions=array("gestionnaire1","gestionnaire2");

/////////////////////////////////////////////////////////
// Variables module INVENTAIRE
$gestionnaires_inventaire=array("gestionnaire1");
$visibilite_inventaire=1;//Visible seulement par les gestionnaires (1 -> par tout le monde)

/////////////////////////////////////////////////////////
// Variables module COMMUNITY
$categories_community = array('Hotels','Restaurants','Vacances','Annonces','Scientifique','Divers');
$gestionnaires_community = array("gestionnaire1");

//Indique que la configuration est terminee
$configuration_terminee = 1;
?>
