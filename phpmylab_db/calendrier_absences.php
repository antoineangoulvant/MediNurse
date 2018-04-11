<?PHP
/**
* Génération de l'image du calendrier des absences.
*
* Le calendrier généré au moyen de la bibliothèque GD est une image PNG.
*
* @version 1.0.1
* @author Emmanuel Delage
* @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.html
* @copyright CNRS (c) 2015
* @package phpMyLab
* @subpackage Absences
*/

error_reporting(E_ALL ^ E_NOTICE);

$annee=(int)$_REQUEST['uneannee'];//ou fonction intval(str);

//inclure de façon inconditionnel (sans if) ; bonne pratique avec require_once
require_once("calendrier_variables.php");

$mois_deb=$_REQUEST['moisdeb'];//ou fonction intval(str);
$mois_fin=$_REQUEST['moisfin'];//ou fonction intval(str);
$groupe=$_REQUEST['groupe'];

//variables speciales impression et générales
$print=$_REQUEST['print'];
$font_print=2;
$taille_case=20;
$difference_a_droite=0;
$im_larg=810;
$diff_taille=$taille_case;//suite de l'opération apres if ($print==1)
if ($print==1)
{
$font_print=1;
$lesMois2=$lesMois;
$taille_case=17;
$difference_a_droite=26;
$im_larg=691;//jusqu'a 710?
}
$diff_taille-=$taille_case;
$nb_mois=$mois_fin-$mois_deb+1;

/////////////////////////////////////////////////////////
//SQL: recuperer le/les membres du groupe
$chemin=$_REQUEST['chemin'];//ou fonction intval(str);
$login=$_REQUEST['login'];//ou fonction intval(str);
include($chemin);
$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

$where='';
if (strlen($login)>=4) $where='UTILISATEUR=\''.$login.'\' AND ';

//$query = 'SELECT * FROM T_UTILISATEUR WHERE GROUPE=\''.$groupe.'\' ORDER BY NOM';
$query = 'SELECT * FROM T_UTILISATEUR WHERE '.$where.'GROUPE=\''.$groupe.'\' ORDER BY NOM';
$result = mysqli_query($link,$query) or die('Requete de : ' . mysqli_error());
$nb_membres=0;
$membres=array(array());
if ($result)
{
	while ($line = mysqli_fetch_array($result, MYSQL_ASSOC))
	{
	$nom_prenom=$line["NOM"].' '.$line["PRENOM"];
	if (strlen($nom_prenom)>21) {
		$nom_prenom=substr($nom_prenom,0,21);
		$nom_prenom[20]='.';
		}
	$membres[$nb_membres][0]=$line["UTILISATEUR"];
	$membres[$nb_membres][1]=$nom_prenom;
	$nb_membres++;
	}//fin de while ($line)
	mysqli_free_result($result);
}

//////////////////////////////////////////////
//Creation de l'image
//+2*($mois_fin-$mois_deb) pour le decalage des trait Horizontaux
$im_haut=20+1+$nb_mois*$nb_membres*($taille_case)-$diff_taille+2*($mois_fin-$mois_deb);
//$haut_max=3393;
$haut_max=3300;//Un peu de marge par rapport a 3391
if ($im_haut>$haut_max) $im_haut=$haut_max;//Taille maximum de l'image
$im = imageCreate($im_larg,$im_haut);

//////////////////////couleurs/////////////////////////////
// Background
$imColor = hex2int(validHexColor('dddddd'));
$background = imageColorAllocate($im, $imColor['r'], $imColor['g'], $imColor['b']);
$background = imageColorTransparent($im,$background);
//couleur pour
$imColor = hex2int(validHexColor('FFFFFF'));
$blanc = imageColorAllocate($im, $imColor['r'], $imColor['g'], $imColor['b']);
//couleur des cases des jours
$imColor = hex2int(validHexColor('333333'));
$rect = imageColorAllocate($im, $imColor['r'], $imColor['g'], $imColor['b']);
//couleur pour ecrire du texte
$imColor = hex2int(validHexColor('000000'));
$txtC = imageColorAllocate($im, $imColor['r'], $imColor['g'], $imColor['b']);
//couleur pour remplissage des WE
$imColor = hex2int(validHexColor('777777'));
$weC = imageColorAllocate($im, $imColor['r'], $imColor['g'], $imColor['b']);
//couleur de texte d'erreur
$imColor = hex2int(validHexColor('FF0000'));
$Err = imageColorAllocate($im, $imColor['r'], $imColor['g'], $imColor['b']);
//couleur des jours feries 
$imColor = hex2int(validHexColor('ffaaaa'));
$ferC = imageColorAllocate($im, $imColor['r'], $imColor['g'], $imColor['b']);
//couleur des missions et congés 
$imColor = hex2int(validHexColor('77FF77'));
$congC = imageColorAllocate($im, $imColor['r'], $imColor['g'], $imColor['b']);
$imColor = hex2int(validHexColor('7777FF'));
$missC = imageColorAllocate($im, $imColor['r'], $imColor['g'], $imColor['b']);

////////////////////////////////////////////////
//ecrire le Titre
$font_chiffres=1;
$font=3;
imageString($im,$font,200,1,"Calendrier des absences ".$annee." (".$groupe.")",$txtC) ;
$font=2;

////////////////////////////////////////////////////////
//dessin trait entre nom(s) et cases
imageline($im,128+1,20-$diff_taille,128+1,$im_haut,$rect);
//dessin trait entre cases et mois
imageline($im,$im_larg-(58-$difference_a_droite)-1,20-$diff_taille,$im_larg-(58-$difference_a_droite)-1,$im_haut,$rect);


//Calcul $PS et $PD du mois de depart pour remplissage des week-end
$PS-=1;
for ($j=0;$j<$mois_deb;$j++)
{
	while($PS<$dureeMois[$j]) $PS+=7;
	$PS-=$dureeMois[$j];
}
if ($PS==6) $PD=1; else $PD=0;

////////////////////////////////////////////////////////
//boucle sur les mois
$nb_trait_hor=0;//pour le decalage des trait horizontaux sur le reste de l'image
for ($j=$mois_deb;$j<=$mois_fin;$j++)
{
//ecrire le mois:
$string=$lesMois2[$j];
imageString($im,$font,$im_larg-(54-$difference_a_droite),20+(20-$diff_taille)*($j-$mois_deb)*$nb_membres+4-$diff_taille+$nb_trait_hor*2,$string,$txtC);
//ecrire le nom des membres
for ($i=0;$i<$nb_membres;$i++)
{
//21 caracteres max
   imageString($im,$font,1,20+4+(20-$diff_taille)*$i-$diff_taille+($j-$mois_deb)*$taille_case*$nb_membres+$nb_trait_hor*2,$membres[$i][1],$txtC) ;

//dessiner les cases
  for ($k=0;$k<31;$k++)
	// ---------CODE AJOUTE PAR CEDRIC----------------
	if ($k<$dureeMois[$j]) 
		if (date("j") == $k+1 && date("n") == $j+1 && date("Y") == $annee) // On trace un rectangle orange pour le jour actuel
		{
			$orange = imagecolorallocate($im, 255, 102, 0); // Couleur orange
			imagesetthickness($im, 1); // Epaisseur de 2px
			imageRectangle($im,130+$taille_case*$k+1,$taille_case+$taille_case*(($j-$mois_deb)*$nb_membres)+1+$i*$taille_case+$nb_trait_hor*2,130+$taille_case*$k+$taille_case-1,$taille_case+$taille_case*(($j-$mois_deb)*$nb_membres)+$taille_case-1+$i*$taille_case+$nb_trait_hor*2,$orange); //on trace un rectangle orange pour la date actuelle
			imageRectangle($im,130+$taille_case*$k+1+3,$taille_case+$taille_case*(($j-$mois_deb)*$nb_membres)+1+$i*$taille_case+$nb_trait_hor*2+3,130+$taille_case*$k+$taille_case-1-3,$taille_case+$taille_case*(($j-$mois_deb)*$nb_membres)+$taille_case-1+$i*$taille_case+$nb_trait_hor*2-3,$orange); //on trace un rectangle orange pour la date actuelle
			imagesetthickness($im, 1); //Remise de l'epaisseur des traits a 1
		}
		else imageRectangle($im,130+$taille_case*$k+1,$taille_case+$taille_case*(($j-$mois_deb)*$nb_membres)+1+$i*$taille_case+$nb_trait_hor*2,130+$taille_case*$k+$taille_case-1,$taille_case+$taille_case*(($j-$mois_deb)*$nb_membres)+$taille_case-1+$i*$taille_case+$nb_trait_hor*2,$rect);
	// --------FIN CODE AJOUTE PAR CEDRIC-------------

//remplissage des week-end
//le dimanche en debut
   if ($PD==1)
   {
	$y=$taille_case+$taille_case*(($j-$mois_deb)*$nb_membres)+1+$i*$taille_case+$nb_trait_hor*2+1;
	imagefill($im,130+1+1,$y,$weC);
   if ($i==$nb_membres-1) $PD=0;
   }
//les samedi suivis des dimanches
   $PS_temp=$PS;
   while($PS<$dureeMois[$j])
   {
	$y=$taille_case+$taille_case*(($j-$mois_deb)*$nb_membres)+1+$i*$taille_case+$nb_trait_hor*2+1;
	imagefill($im,130+$taille_case*$PS+1+1,$y,$weC);
//dessiner le dimanche
	if ($PS+1<$dureeMois[$j])
	{
		$y=$taille_case+$taille_case*(($j-$mois_deb)*$nb_membres)+1+$i*$taille_case+$nb_trait_hor*2+1;
		imagefill($im,130+$taille_case*($PS+1)+1+1,$y,$weC);
	}
	else if ($PS+1==$dureeMois[$j] && $i==$nb_membres-1) $PD=1;
	$PS+=7;
   }//fin du while($PS<$dureeMois[$j])
   if ($i<$nb_membres-1) $PS=$PS_temp;
//FIN: remplissage des week-end

///////////////////////////////////////////////////////////
//Remplissage des jours feries 
   for ($k=0;$k<count($feries);$k++)
   {
	if ($j==$feries[$k][1])//[numero_ferie][1]=>mois ,[numero_ferie][0]=>jour 
	{
	$x=130+$taille_case*$feries[$k][0];
	$y=$taille_case+$taille_case*(($feries[$k][1]-$mois_deb)*$nb_membres)+$i*$taille_case+$nb_trait_hor*2;
	imagefilledellipse($im,$x+$taille_case/2,$y+$taille_case/2,$taille_case-4,$taille_case-4,$ferC);
	}
   }
///////////////////////////////////////////////////////////
}//fin boucle sur $i [les membres]
//passage au mois suivant pour les Week-end
$PS=$PS-$dureeMois[$j];

//dessin trait horizontale separant les mois
if ($j!=$mois_fin)
{
$y=20+(20-$diff_taille)*($j+1-$mois_deb)*$nb_membres-$diff_taille+1+2*$nb_trait_hor;
imageline($im,0,$y,$im_larg,$y,$rect);
$nb_trait_hor++;
}

}//fin boucle sur $j [les mois]

//indiquer la limite d'impression
if ($print==1 && $im_haut>1042)
{
imageline($im,0,1043,$im_larg,1043,$Err);
imageString($im,5,140,1043+2,"Attention, Ce qui suit ne sera pas imprime!!!",$Err) ;
}

//message d'erreur
if ($im_haut==$haut_max) imageString($im,5,140,$im_haut-20,"Attention, taille maximum de l'image atteinte => image tronquee!!!",$Err) ;
////////////////////fin dessin sans les absences

////////////////////requete SQL
$chemin=$_REQUEST['chemin'];//ou fonction intval(str);
$login=$_REQUEST['login'];//ou fonction intval(str);

include($chemin);

$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

for ($i=0;$i<$nb_membres;$i++)
{
////////////////////////////DEBUT CONGE////////////////////////////////////
$where='WHERE T_CONGE.UTILISATEUR=\''.$membres[$i][0].'\'';
$moisD=$mois_deb+1;
$moisF=$mois_fin+1;
$where.='AND T_CONGE.FIN_DATE>=STR_TO_DATE(\'1/'.$moisD.'/'.$annee.'\',\'%d/%m/%Y\')';
$where.='AND T_CONGE.DEBUT_DATE<=STR_TO_DATE(\''.$dureeMois[$mois_fin].'/'.$moisF.'/'.$annee.'\',\'%d/%m/%Y\')';

$query = 'SELECT ID_CONGE,UTILISATEUR,DEBUT_DATE,FIN_DATE,VALIDE,DEBUT_AM,FIN_PM FROM T_CONGE  '.$where.' ';

$result = mysqli_query($link,$query) or die('Requete de recherche des conges: ' . mysqli_error());

if ($result)
{
   while ($line = mysqli_fetch_array($result, MYSQL_ASSOC))
   {
   //raffraichissement de l'affichage avec la demande trouvee
   $id_mission=$line["ID_CONGE"];
   $dAller=explode("-",$line["DEBUT_DATE"]);
   if ((int)$line["VALIDE"]>-1)
   if ((int)($dAller[0])==$annee)
   {
	//Recuperer l'heure de depart
	$h_dep=(float)$line["DEBUT_AM"]*8.;// (18 pixels dans la case de 9h a 18h heurs de bureau)
	//Recuperer l'heure de retour
	$h_ret=(float)$line["FIN_PM"]*8.+9.;// (18 pixels dans la case de 9h a 18h heurs de bureau)

	//repere de date dans une mission(marque le debut ou la fin)
	$dRetour=explode("-",$line["FIN_DATE"]);

	//afficher les dates intermediaire
	if ((int)($dAller[1])!=(int)($dRetour[1]))//cas le mois d'arrivee et different du depart
	{
	//la fin du mois du debut de conge
		if ($dAller[1]>$mois_deb)//Pour bug affich avant 1er mois de la periode
		for ($j=(int)($dAller[2]-1) ; $j<($dureeMois[(int)($dAller[1]-1)]) ; $j++)
		{ 
$x=130+$taille_case*$j+2;
$y=$taille_case+2+$taille_case*(($dAller[1]-$mois_deb-1)*$nb_membres)+$i*$taille_case+($dAller[1]-$mois_deb-1)*2;
imageFilledRectangle($im,$x+(int)($h_dep),$y+$font_print+1,$x+$taille_case-4,$y+$taille_case-4-$font_print-1,$congC);
$h_dep=0.;
		}
	//si mois intermediaires entre debut de conge et fin de conge:
	//le mois de retour peut etre < si c l'annee d'apres
		$dRetour_temp=$dRetour[1];
		if ($dAller[1]>$dRetour[1]) $dRetour_temp=12+1;
		if ($dAller[1]>=$mois_deb)//Pour bug affich avant 1er mois de la periode
		for ($m=(int)($dAller[1]+1) ; $m<((int)($dRetour_temp)) ; $m++)
		for ($j=0 ; $j<($dureeMois[$m-1]) ; $j++)
		{
$x=130+$taille_case*$j+2;
$y=$taille_case+2+$taille_case*(($m-$mois_deb-1)*$nb_membres)+$i*$taille_case+($m-$mois_deb-1)*2;
imageFilledRectangle($im,$x,$y+$font_print+1,$x+$taille_case-4,$y+$taille_case-4-$font_print-1,$congC);
		}

	//le debut du mois de fin de conge si la meme anne
		if ($dAller[0]==$dRetour[0])
		for ($j=0 ; $j<=((int)($dRetour[2])-1) ; $j++) 
		{
		//on grandit la derniere case en fct de h_ret
		//if ($j==((int)($dRetour[2])-1)) $h_temp=1+(int)$h_ret;
$x=130+$taille_case*$j+2;
$y=$taille_case+2+$taille_case*(($dRetour[1]-$mois_deb-1)*$nb_membres)+$i*$taille_case+($dRetour[1]-$mois_deb-1)*2;
imageFilledRectangle($im,$x+(int)($h_dep),$y+$font_print+1,$x+$taille_case-4,$y+$taille_case-4-$font_print-1,$congC);
		$h_dep=0.;
		}

		}
		else //si c le meme mois
		{
			//$h_temp2=18;
			$h_temp2=$taille_case-2;
			for ($j=(int)($dAller[2]-1) ; $j<=((int)($dRetour[2])-1) ; $j++)
			{ 
if ($j==((int)($dRetour[2])-1)) $h_temp2=1+(int)$h_ret-$diff_taille;
$x=130+$taille_case*$j+2;
$y=$taille_case+2+$taille_case*(($dAller[1]-$mois_deb-1)*$nb_membres)+$i*$taille_case+($dAller[1]-$mois_deb-1)*2;
imageFilledRectangle($im,$x+(int)($h_dep),$y+$font_print+1,$x+$h_temp2-2,$y+$taille_case-4-$font_print-1,$congC);
$h_dep=0.;
			}
		}
	}//fin de if ((int)($dAller[0])==$annee)
	else
	{
//////////cas particulier fin de conge en debut d'annee
	//Recuperer l'heure de retour
		$h_ret=(float)$line["FIN_PM"]*8.+9.;// (18 pixels dans la case de 9h a 18h heurs de bureau)

	//repere de date dans un conge(marque le debut ou la fin)
		$dRetour=explode("-",$line["FIN_DATE"]);

		for ($m=(int)(1) ; $m<((int)($dRetour[1])) ; $m++)
		for ($j=0 ; $j<($dureeMois[$m-1]) ; $j++)
		{
$x=130+$taille_case*$j+2;
$y=$taille_case+2+$taille_case*(($m-$mois_deb-1)*$nb_membres)+$i*$taille_case+($m-$mois_deb-1)*2;
imageFilledRectangle($im,$x,$y+$font_print+1,$x+$taille_case-4,$y+$taille_case-4-$font_print-1,$congC);
		}

	//cas particulier fin de conge en debut d'annee
		if ((int)($dAller[0])==$annee-1)
		for ($j=0 ; $j<=((int)($dRetour[2])-1) ; $j++) 
		{
		//on grandit la derniere case en fct de h_ret
		//if ($j==((int)($dRetour[2])-1)) $h_temp=1+(int)$h_ret;
$x=130+$taille_case*$j+2;
$y=$taille_case+2+$taille_case*(($dRetour[1]-$mois_deb-1)*$nb_membres)+$i*$taille_case+($dRetour[1]-$mois_deb-1)*2;
imageFilledRectangle($im,$x+(int)($h_dep),$y+$font_print+1,$x+$taille_case-4,$y+$taille_case-4-$font_print-1,$congC);
		$h_dep=0.;
		}
	}//fin du else
	}//fin de while ($line)
	mysqli_free_result($result);
}//fin de if result

////////////////////////////FIN CONGE//////////////////////////////////////
////////////////////////////DEBUT MISSION//////////////////////////////////
$where='WHERE T_MISSION.UTILISATEUR=\''.$membres[$i][0].'\'';
$moisD=$mois_deb+1;
$moisF=$mois_fin+1;
$where.='AND T_MISSION.RETOUR_DATE>=STR_TO_DATE(\'1/'.$moisD.'/'.$annee.'\',\'%d/%m/%Y\')';
$where.='AND T_MISSION.ALLER_DATE<=STR_TO_DATE(\''.$dureeMois[$mois_fin].'/'.$moisF.'/'.$annee.'\',\'%d/%m/%Y\')';

$query = 'SELECT ID_MISSION,UTILISATEUR,ALLER_DATE,RETOUR_DATE,VALIDE,ALLER_H_DEPART,RETOUR_H_ARRIVEE FROM T_MISSION  '.$where.' ';

$result = mysqli_query($link,$query) or die('Requete de recherche des missions: ' . mysqli_error());
if ($result)
{
   while ($line = mysqli_fetch_array($result, MYSQL_ASSOC))
   {
	//raffraichissement de l'affichage avec la demande trouvee
	$id_mission=$line["ID_MISSION"];
	$dAller=explode("-",$line["ALLER_DATE"]);
	if ((int)$line["VALIDE"]>-1)
	if ((int)($dAller[0])==$annee)
	{
		//Recuperer l'heure de depart
		$h_dep=(float)$line["ALLER_H_DEPART"];// (18 pixels dans la case de 9h a 18h heurs de bureau)
		if ($h_dep<9) $h_dep=0.; else $h_dep-=9.;
		if ($h_dep>9) $h_dep=9.; //il reste les valeurs comprises entre 0 et 9
		$h_dep*=1.89;//normalisation entre 0 et 17 pour fiter aux 18 pixels 
		//Recuperer l'heure de retour
		$h_ret=(float)$line["RETOUR_H_ARRIVEE"];// (18 pixels dans la case de 9h a 18h heurs de bureau)
		if ($h_ret==0) $h_ret=24.;//cas des gens qui mettent 0h pour 24h
		if ($h_ret<=9.) $h_ret=0.; else $h_ret-=9.;
		if ($h_ret>9.) $h_ret=9.; //il reste les valeurs comprises entre 0 et 9
		$h_ret*=1.89;//normalisation entre 0 et 17 pour fiter aux 18 pixels
		if ($h_ret<1.) $h_ret=1.;//eviter un bogue d'affichage pour valeur 0

		//repere de date dans une mission(marque le debut ou la fin)
		$dRetour=explode("-",$line["RETOUR_DATE"]);

		//afficher les dates intermediaire
		if ((int)($dAller[1])!=(int)($dRetour[1]))//cas le mois d'arrivee et different du depart
		{	//la fin du mois du debut de conge
			for ($j=(int)($dAller[2]-1) ; $j<($dureeMois[(int)($dAller[1]-1)]) ; $j++)
			{ 
$x=130+$taille_case*$j+2;
$y=$taille_case+2+$taille_case*(($dAller[1]-$mois_deb-1)*$nb_membres)+$i*$taille_case+($dAller[1]-$mois_deb-1)*2;
imageFilledRectangle($im,$x+(int)($h_dep),$y+$font_print+1,$x+$taille_case-4,$y+$taille_case-4-$font_print-1,$missC);
$h_dep=0.;
			}
			//si mois intermediaires entre debut de conge et fin de conge:
			//le mois de retour peut etre < si c l'annee d'apres
			$dRetour_temp=$dRetour[1];
			if ($dAller[1]>$dRetour[1]) $dRetour_temp=12+1;
			for ($m=(int)($dAller[1]+1) ; $m<((int)($dRetour_temp)) ; $m++)
			for ($j=0 ; $j<($dureeMois[$m-1]) ; $j++)
			{
$x=130+$taille_case*$j+2;
$y=$taille_case+2+$taille_case*(($m-$mois_deb-1)*$nb_membres)+$i*$taille_case+($m-$mois_deb-1)*2;
imageFilledRectangle($im,$x,$y+$font_print+1,$x+$taille_case-4,$y+$taille_case-4-$font_print-1,$missC);
			}
			//le debut du mois de fin de conge si la meme anne
			if ($dAller[0]==$dRetour[0])
			for ($j=0 ; $j<=((int)($dRetour[2])-1) ; $j++) 
			{
			//on grandit la derniere case en fct de h_ret
			//if ($j==((int)($dRetour[2])-1)) $h_temp=1+(int)$h_ret;
$x=130+$taille_case*$j+2;
$y=$taille_case+2+$taille_case*(($dRetour[1]-$mois_deb-1)*$nb_membres)+$i*$taille_case+($dRetour[1]-$mois_deb-1)*2;
imageFilledRectangle($im,$x+(int)($h_dep),$y+$font_print+1,$x+$taille_case-4,$y+$taille_case-4-$font_print-1,$missC);
$h_dep=0.;
			}

		}
		else //si c le meme mois
		{
			//$h_temp2=18;
			$h_temp2=$taille_case-2;
			for ($j=(int)($dAller[2]-1) ; $j<=((int)($dRetour[2])-1) ; $j++)
			{ 
if ($j==((int)($dRetour[2])-1)) $h_temp2=1+(int)$h_ret-$diff_taille;
$x=130+$taille_case*$j+2;
$y=$taille_case+2+$taille_case*(($dAller[1]-$mois_deb-1)*$nb_membres)+$i*$taille_case+($dAller[1]-$mois_deb-1)*2;
imageFilledRectangle($im,$x+(int)($h_dep),$y+$font_print+1,$x+$h_temp2-2,$y+$taille_case-4-$font_print-1,$missC);
$h_dep=0.;
			}
		}
	}//fin de if ((int)($dAller[0])==$annee)
	else
	{
///////////cas particulier fin de conge en debut d'annee
		$h_ret=(float)$line["RETOUR_H_ARRIVEE"];// (18 pixels dans la case de 9h a 18h heurs de bureau)
		if ($h_ret==0) $h_ret=24.;//cas des gens qui mettent 0h pour 24h
		if ($h_ret<=9.) $h_ret=0.; else $h_ret-=9.;
		if ($h_ret>9.) $h_ret=9.; //il reste les valeurs comprises entre 0 et 9
		$h_ret*=1.89;//normalisation entre 0 et 17 pour fiter aux 18 pixels
		if ($h_ret<1.) $h_ret=1.;//eviter un bogue d'affichage pour valeur 0

		//repere de date dans une mission(marque le debut ou la fin)
		$dRetour=explode("-",$line["RETOUR_DATE"]);

	//cas particulier fin de conge en debut d'annee
		if ((int)($dAller[0])==$annee-1)
		for ($j=0 ; $j<=((int)($dRetour[2])-1) ; $j++) 
		{
		//on grandit la derniere case en fct de h_ret
$x=130+$taille_case*$j+2;
$y=$taille_case+2+$taille_case*(($dRetour[1]-$mois_deb-1)*$nb_membres)+$i*$taille_case+($dRetour[1]-$mois_deb-1)*2;
imageFilledRectangle($im,$x+(int)($h_dep),$y+$font_print+1,$x+$taille_case-4,$y+$taille_case-4-$font_print-1,$missC);
$h_dep=0.;
		}
	}//fin du else
   }//fin de while ($line)
   mysqli_free_result($result);
}//fin de if result
////////////////////////////FIN MISSION////////////////////////////////////

}//fin boucle sur $i [les membres]


$nb_trait_hor=0;
for ($j=$mois_deb;$j<=$mois_fin;$j++)
{
   for ($i=0;$i<$nb_membres;$i++)
   {
/////////////////ecrire les chiffres
   $k=0;
   while ($k<$dureeMois[$j])
   { 
	$k_temp=$k;
	$k_temp=$k-$nb_membres*(int)($k_temp/$nb_membres);
	if (($k_temp+1)/($i+1)==1)
	{
	$string=sprintf("%d",$k+1);
	if ($k<9) imageString($im,$font_chiffres+$font_print-1,$font_print+130+$taille_case*$k+5,$taille_case+$taille_case*(($j-$mois_deb)*$nb_membres)+1+$i*$taille_case+$nb_trait_hor*2+4-($font_print-1),$string,$txtC) ;
	else imageString($im,$font_chiffres+$font_print-1,$font_print+130+$taille_case*$k+3,$taille_case+$taille_case*(($j-$mois_deb)*$nb_membres)+1+$i*$taille_case+$nb_trait_hor*2+4-($font_print-1),$string,$txtC) ;
	}
   $k++;
   }//fin du while
///////////////////////////////////////////////////////////
   }//fin boucle sur $i [les membres]
   if ($j!=$mois_fin){$nb_trait_hor++;}
}//fin de boucle sur les mois

////////////////////fin requete SQL
mysqli_close($link);

// Step 3. Send the headers (at last possible time)
header('Content-type: image/png');

// Step 4. Output the image as a PNG 
imagePNG($im);

// Step 5. Delete the image from memory
imageDestroy($im); 


/**
 * @param    $hex string        6-digit hexadecimal color
 * @return    array            3 elements 'r', 'g', & 'b' = int color values
 * @desc Converts a 6 digit hexadecimal number into an array of
 *       3 integer values ('r'  => red value, 'g'  => green, 'b'  => blue)
 */
function hex2int($hex) {
        return array( 'r' => hexdec(substr($hex, 0, 2)), // 1st pair of digits
                      'g' => hexdec(substr($hex, 2, 2)), // 2nd pair
                      'b' => hexdec(substr($hex, 4, 2))  // 3rd pair
                    );
}

/**
 * @param $input string     6-digit hexadecimal string to be validated
 * @param $default string   default color to be returned if $input isn't valid
 * @return string           the validated 6-digit hexadecimal color
 * @desc returns $input if it is a valid hexadecimal color, 
 *       otherwise returns $default (which defaults to black)
 */
function validHexColor($input = '000000', $default = '000000') {
    // A valid Hexadecimal color is exactly 6 characters long
    // and eigher a digit or letter from a to f
    return (preg_match('#^[0-9a-fA-F]{6}$#', $input)) ? $input : $default ;
}

?> 
