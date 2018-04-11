<?php
/**
* Ensemble des vaiables des calendriers.
*
* Ces variables sont utiles pour l'affichage des calendrier [2008-2015].
* @package phpMyLab
*/

$lesMois=array("Janv.","Fev.","Mars","Avril","Mai","Juin","Juil.","Aout","Sept.","Oct.","Nov.","Dec.");
$lesMois2=array("Janvier","Fevrier","Mars","Avril","Mai","Juin","Juillet","Aout","Septembre","Octobre","Novembre","Decembre");
$dureeMois=array(31,28,31,30,31,30,31,31,30,31,30,31);
if ($annee%4==0) {$dureeMois[1]=29;}//annee bissextile

//PS position en jour du 1er samedi de l'annee
//Jours feries fixes (jour-1,mois-1) : Nouvel an,Fete du W,8Mai victoire 45,14Juil revolution,15 Aout Assomption,1Nov Toussaint, 11Nov Armistice 18, Noel ou decale appelle "jour ferie comptable"
//Les Jours feries chretiens variables (Lundi Paques, Jeudi Ascension)
if ($annee==2008){$PS=5;$PD=0;}//PremierSamedi [1..7], PremierDimanche a 1 si cas particulier
if ($annee==2009){$PS=3;$PD=0;}
if ($annee==2010){$PS=2;$PD=0;}
if ($annee==2011){$PS=1;$PD=0;}
if ($annee==2012){$PS=7;$PD=1;}
if ($annee==2013){$PS=5;$PD=0;}
if ($annee==2014){$PS=4;$PD=0;}
if ($annee==2015){$PS=3;$PD=0;}
if ($annee==2016){$PS=2;$PD=0;}
if ($annee==2017){$PS=7;$PD=1;}
if ($annee==2018){$PS=1;$PD=0;}
if ($annee==2019){$PS=5;$PD=0;}
if ($annee==2020){$PS=4;$PD=0;}
if ($annee==2021){$PS=2;$PD=0;}
if ($annee==2022){$PS=1;$PD=0;}
if ($annee==2023){$PS=7;$PD=1;}
if ($annee==2024){$PS=6;$PD=0;}
if ($annee==2025){$PS=4;$PD=0;}
//voir jddayofweek(cal_to_jd(CAL_GREGORIAN,date("m",1),date("d",1),date("m",annee))

$easter = easter_date($annee) ;
$paques = $easter + 86400;
$ascension = $easter + 86400*39;
$pentecote = $easter + 86400*50;

$feries=array(array(0,0),// Jour de l'an
array((int)(date('d',$paques))-1,(int)(date('m',$paques))-1),
array(0,4),// Fete du travail
array(7,4),// Victoire 1945
array((int)(date('d',$ascension))-1,(int)(date('m',$ascension))-1),
array((int)(date('d',$pentecote))-1,(int)(date('m',$pentecote))-1),
array(13,6),// Fete nationale
array(14,7),// Assomption
array(0,10),// Toussaint
array(10,10),// Armistice 1918
array(24,11));// Noel

?>
