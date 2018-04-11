<?php
/**
* Calcul du nombre de jours ouvrés. Cette fonction est déjà présente dans conges.php 
*
* @param int date de début 
* @param int date de fin 
* @return int nombre de jours ouvrés
*/
function get_nb_open_days($date_start, $date_stop , $AM_inclus, $PM_inclus) {
  $arr_bank_holidays = array(); // Tableau des jours feriés
  // On boucle dans le cas oé l'année de départ serait différente de l'année d'arrivée
  $diff_year = date('Y', $date_stop) - date('Y', $date_start);
  for ($i = 0; $i <= $diff_year; $i++) {
  $year = (int)date('Y', $date_start) + $i;
  // Liste des jours feriés
  $arr_bank_holidays[] = '1_1_'.$year; // Jour de l'an
  $arr_bank_holidays[] = '1_5_'.$year; // Fete du travail
  $arr_bank_holidays[] = '8_5_'.$year; // Victoire 1945
  $arr_bank_holidays[] = '14_7_'.$year; // Fete nationale
  $arr_bank_holidays[] = '15_8_'.$year; // Assomption
  $arr_bank_holidays[] = '1_11_'.$year; // Toussaint
  $arr_bank_holidays[] = '11_11_'.$year; // Armistice 1918
  $arr_bank_holidays[] = '25_12_'.$year; // Noel
  // Récupération de paques. Permet ensuite d'obtenir le jour de l'ascension et celui de la pentecote
  $easter = easter_date($year);
  $arr_bank_holidays[] = date('j_n_'.$year, $easter + 86400); // Paques
  $arr_bank_holidays[] = date('j_n_'.$year, $easter + (86400*39)); // Ascension
  $arr_bank_holidays[] = date('j_n_'.$year, $easter + (86400*50)); // Pentecote
  }
  //print_r($arr_bank_holidays);
  //ajout date start sauve
  $date_start_sauve=$date_start;
  $nb_days_open = 0;
  while ($date_start < $date_stop) {
  // Si le jour suivant n'est ni un dimanche (0) ou un samedi (6), ni un jour férié, on incrémente les jours ouvrés
  if (!in_array(date('w', $date_start), array(0, 6))
  && !in_array(date('j_n_'.date('Y', $date_start), $date_start), $arr_bank_holidays)) {
  $nb_days_open++;
  }
  $date_start += 86400;
  }
  //ajout pour si debut de conge l'aprem et non ouvre
  if ((!in_array(date('w', $date_start_sauve), array(0, 6))
  && !in_array(date('j_n_'.date('Y', $date_start_sauve), $date_start_sauve), $arr_bank_holidays)) && ($AM_inclus==1)) $nb_days_open-=0.5;
  //ajout pour si fin de conge le matin et non ouvre
  if ((!in_array(date('w', $date_stop), array(0, 6))
  && !in_array(date('j_n_'.date('Y', $date_stop), $date_stop), $arr_bank_holidays)) && ($PM_inclus==0)) $nb_days_open-=0.5;

  return $nb_days_open;
}

//Controle des donnees 
if(!empty( $_POST[ 'date1' ])) $date1=$_POST[ 'date1' ]; else { echo '0';exit;}
if(!empty( $_POST[ 'date2' ])) $date2=$_POST[ 'date2' ]; else { echo '0';exit;}

list($jour1, $mois1, $annee1) = explode('/', $date1); 
list($jour2, $mois2, $annee2) = explode('/', $date2);
$timestamp1 = mktime(0,0,0,$mois1,$jour1,$annee1);
$timestamp2 = mktime(12,0,0,$mois2,$jour2,$annee2);//12 heure pour compter le dernier jour
$timestamp_solde=0;
if ((!checkdate($mois1,$jour1,$annee1)) || (!checkdate($mois2,$jour2,$annee2)) || ($timestamp2<$timestamp1))
{
	$_SESSION['nbjo'] = 0;
}
else {
	$nb_jours_ouvres = (float)get_nb_open_days($timestamp1, $timestamp2, $_POST[ 'dateAM' ],$_POST[ 'datePM' ]);
	//if ($_SESSION['conge']['date_AM']==1) $nb_jours_ouvres-=0.5;
	//if ($_SESSION['conge']['date_PM']==0) $nb_jours_ouvres-=0.5;
	echo $nb_jours_ouvres;
}
	
?>