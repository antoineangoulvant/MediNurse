<?PHP
/**
* Génération de l'image du calendrier des missions.
*
* Le calendrier généré au moyen de la bibliothèque GD est une image PNG.
*
* @version 1.2.0
* @author Emmanuel Delage
* @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.html
* @copyright CNRS (c) 2015
* @package phpMyLab
* @subpackage Missions
*/

error_reporting(E_ALL ^ E_NOTICE);

// convert guaranteed valid hex value to array of integers
$imColor = hex2int(validHexColor('dddddd'));

// Step 1. Create a new blank image
$im = imageCreate(660,260);

// Step 2. Set background to 'color'
$background = imageColorAllocate($im, $imColor['r'], $imColor['g'], $imColor['b']);
$background = imageColorTransparent($im,$background);

$annee=(int)$_REQUEST['uneannee'];//ou fonction intval(str);

//inclure de façon inconditionnel (sans if) ; bonne pratique avec require_once
require_once("calendrier_variables.php");

$imColor = hex2int(validHexColor('FFFFFF'));
$blanc = imageColorAllocate($im, $imColor['r'], $imColor['g'], $imColor['b']);
//affichage des cases des jours
$imColor = hex2int(validHexColor('333333'));
$rect = imageColorAllocate($im, $imColor['r'], $imColor['g'], $imColor['b']);
$i=0;
$j=0;
for ($i=0;$i<31;$i++)
for ($j=0;$j<12;$j++)
// ---------CODE AJOUTE PAR CEDRIC----------------
if ($i<$dureeMois[$j]) 
	if (date("j") == $i+1 && date("n") == $j+1 && date("Y") == $annee) // On trace un rectangle orange pour le jour actuel
	{
		$orange = imagecolorallocate($im, 255, 102, 0); // Couleur orange
		imagesetthickness($im, 1); // Epaisseur de 2px
		imageRectangle($im,40+20*$i+1,20+20*$j+1,40+20*$i+19,20+20*$j+19,$orange); //on trace un rectangle orange pour la date actuelle
		imageRectangle($im,40+20*$i+1+3,20+20*$j+1+3,40+20*$i+19-3,20+20*$j+19-3,$orange); //on trace un rectangle orange pour la date actuelle
		imagesetthickness($im, 1); //Remise de l'epaisseur des traits a 1
	}
	else imageRectangle($im,40+20*$i+1,20+20*$j+1,40+20*$i+19,20+20*$j+19,$rect);
// --------FIN CODE AJOUTE PAR CEDRIC-------------
//imagerectangle($im,50,50,60,60,$rect);

//ecrire en abscisse et ordonnee
$imColor = hex2int(validHexColor('000000'));
$txtC = imageColorAllocate($im, $imColor['r'], $imColor['g'], $imColor['b']);
$font=3;
for ($i=0;$i<31;$i++)
{
$string=sprintf("%d",$i+1);
imageString($im,$font,40+20*$i+4,8,$string,$txtC) ;
}
for ($j=0;$j<12;$j++)
{
$string=$lesMois[$j];
imageString($im,$font,1,20+20*$j+4,$string,$txtC);
}

//remplissage des week-end
$imColor = hex2int(validHexColor('777777'));
$weC = imageColorAllocate($im, $imColor['r'], $imColor['g'], $imColor['b']);
$j=0;
$i=$PS-1;
if ($PD==1) imagefill($im,40+1+1,20+1+1,$weC);
for ($j=0;$j<12;$j++)
{
	while($i<$dureeMois[$j])
	{
		imagefill($im,40+20*$i+1+1,20+20*$j+1+1,$weC);	$i++;
		//traiter le cas si un WE est a cheval entre 2 mois et si ce n'est pas le dernier mois
		if (($i==$dureeMois[$j]) && ($j!=11))//dessiner le dimanche
		{imagefill($im,40+1+1,20+20*($j+1)+1+1,$weC);$i+=6;}
		else //cas normal on remplit la case suivante pour le dimanche
		{imagefill($im,40+20*$i+1+1,20+20*$j+1+1,$weC);$i+=6;}
	}
	$i=$i-$dureeMois[$j];
}

//Remplissage des jours feries 
$imColor = hex2int(validHexColor('ffaaaa'));
$ferC = imageColorAllocate($im, $imColor['r'], $imColor['g'], $imColor['b']);
for ($j=0;$j<count($feries);$j++)
{
	//imagefill($im,40+20*$feriesFixes[$j][0]+1+1,20+20*$feriesFixes[$j][1]+1+1,$ferC);
	imagefilledellipse($im,40+20*$feries[$j][0]+10,20+20*$feries[$j][1]+10,16,16,$ferC);
}
////////////////////fin dessin sans l'affichage des absences

////////////////////requete SQL
$chemin=$_REQUEST['chemin'];//ou fonction intval(str);
$nom=$_REQUEST['nom'];//ou fonction intval(str);
$prenom=$_REQUEST['prenom'];//ou fonction intval(str);
$dest=$_REQUEST['dest'];

include($chemin);

$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

$where='';
$where_nom='';
$where_prenom='';
$where_groupe='';
$where_dest='';

if ($_REQUEST['groupe'] != $_SESSION['groupe'][0]) $where_groupe='T_MISSION.GROUPE=\''.$_REQUEST['groupe'].'\'';
if ($nom!='') $where_nom='INSTR(T_UTILISATEUR.NOM,UPPER(\''.$nom.'\'))>0';
if ($prenom!='') $where_prenom='INSTR(T_UTILISATEUR.PRENOM,UPPER(\''.$prenom.'\'))>0';
if ($dest!='') $where_dest='INSTR(DESTINATION,UPPER(\''.$dest.'\'))>0';
$nb_clause=0;
if ($where_nom!='' || $where_prenom!='' || $where_groupe!='' || $where_dest!='') $where='WHERE ';
if ($where_groupe!='') {$where.=$where_groupe;$nb_clause++;}
if ($where_nom!='') {if ($nb_clause==1) {$where.=' AND ';} $where.=$where_nom; $nb_clause++;}
if ($where_prenom!='') {if ($nb_clause>=1) {$where.=' AND ';} $where.=$where_prenom; $nb_clause++;}
if ($where_dest!='') {if ($nb_clause>=1) {$where.=' AND ';} $where.=$where_dest; $nb_clause++;}

$query = 'SELECT ID_MISSION,NOM,PRENOM,ALLER_DATE,RETOUR_DATE,VALIDE,ALLER_H_DEPART,RETOUR_H_ARRIVEE FROM T_MISSION INNER JOIN T_UTILISATEUR ON T_MISSION.UTILISATEUR=T_UTILISATEUR.UTILISATEUR '.$where.' '.$orderby.' ';

$result = mysqli_query($link,$query) or die('Requete de recherche evolue des missions: ' . mysqli_error());
if ($result)
{
	$imColor = hex2int(validHexColor('7777ff'));
	$missC = imageColorAllocate($im, $imColor['r'], $imColor['g'], $imColor['b']);
	$imColor = hex2int(validHexColor('D01030'));//couleur de l'id_mission
	$idC = imageColorAllocate($im, $imColor['r'], $imColor['g'], $imColor['b']);

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

		$dRetour=explode("-",$line["RETOUR_DATE"]);

		//afficher les dates
		if ((int)($dAller[1])!=(int)($dRetour[1]))//cas le mois d'arrivee et different du depart
		{	//la fin du mois du debut de mission
			for ($i=(int)($dAller[2]-1) ; $i<($dureeMois[(int)($dAller[1]-1)]) ; $i++)
			{ 
			imageFilledRectangle($im,40+20*$i+1+1+(int)($h_dep),20+20*((int)($dAller[1])-1)+1+4,40+20*$i+18,20+20*((int)($dAller[1])-1)+15,$missC);
			$h_dep=0.;
			}
			//si mois intermediaires entre debut de mission et fin de mission:
			//le mois de retour peut etre < si c l'annee d'apres
			$dRetour_temp=$dRetour[1];
			if ($dAller[1]>$dRetour[1]) $dRetour_temp=12+1;
			for ($j=(int)($dAller[1]+1) ; $j<((int)($dRetour_temp)) ; $j++)
			for ($i=0 ; $i<($dureeMois[$j-1]) ; $i++)
			{
			imageFilledRectangle($im,40+20*$i+1+1,20+20*($j-1)+1+4,40+20*$i+18,20+20*($j-1)+15,$missC);
			}
			//le debut du mois de fin de mission si la meme annee
			$h_temp=18;
			if ($dAller[0]==$dRetour[0])
			for ($i=0 ; $i<=((int)($dRetour[2])-1) ; $i++) 
			{
			//on grandit la derniere case en fct de h_ret
			if ($i==((int)($dRetour[2])-1)) $h_temp=1+(int)$h_ret;
			imageFilledRectangle($im,40+20*$i+1+1,20+20*((int)($dRetour[1])-1)+1+4,40+20*$i+$h_temp,20+20*((int)($dRetour[1])-1)+15,$missC);
			$h_dep=0.;
			//au cas ou les missions se chevauche (car on ecrit les nb sur 4 carac.)
			imageline($im,40+20*$i+19,20+20*((int)($dRetour[1])-1)+1+4,40+20*$i+19,20+20*((int)($dRetour[1])-1)+15,$rect);//$rect$ferC
			imageline($im,40+20*$i+19+1,20+20*((int)($dRetour[1])-1)+1+4,40+20*$i+19+1,20+20*((int)($dRetour[1])-1)+15,$blanc);//$rect$ferC
			}
		}
		else //si c le meme mois
		{
			$h_temp2=18;
			for ($i=(int)($dAller[2]-1) ; $i<=((int)($dRetour[2])-1) ; $i++)
			{ 
			if ($i==((int)($dRetour[2])-1)) $h_temp2=1+(int)$h_ret;
			imageFilledRectangle($im,40+20*$i+1+1+(int)($h_dep),20+20*((int)($dRetour[1])-1)+1+4,40+20*$i+$h_temp2,20+20*((int)($dRetour[1])-1)+15,$missC);
			//au cas ou les missions se chevauche (car on ecrit les nb sur 4 carac.)
			imageline($im,40+20*$i+19,20+20*((int)($dRetour[1])-1)+1+4,40+20*$i+19,20+20*((int)($dRetour[1])-1)+15,$rect);//$rect$ferC
			imageline($im,40+20*$i+19+1,20+20*((int)($dRetour[1])-1)+1+4,40+20*$i+19+1,20+20*((int)($dRetour[1])-1)+15,$blanc);//$rect$ferC
			$h_dep=0.;
			}
			//au cas ou les missions se chevauche (car on ecrit les nb sur 4 carac.)
			//TODO?
		}
	//ecrire le numero de mission en dernier...
	imageFilledRectangle($im,40+20*((int)($dAller[2]-1))+1+1,20+20*((int)($dAller[1])-1)+5,40+20*((int)($dAller[2]-1))+20,20+20*((int)($dAller[1])-1)+10,$missC);
	imageString($im,$font-2,40+20*((int)($dAller[2]-1))+2,20+20*((int)($dAller[1])-1)+4,sprintf("%d",$id_mission),$idC) ;
	}
	}//fin de while ($line)
	mysqli_free_result($result);
}//fin de if result

////////////////////fin requetes SQL
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
