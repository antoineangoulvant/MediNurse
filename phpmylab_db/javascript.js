/**
* Fichier javascript contenant les fonctions utilisées régulièrement dans l'application.
*
* @version 1.2.0
* @author Cedric Gagnevin <cedric.gagnevin@laposte.net>, Emmanuel Delage
* @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.html
* @copyright CNRS (c) 2015
* @package phpMyLab
* @subpackage Conges
*/

//Affiche la date et l'heure courante
function clock()
{
	today = new Date;
	heure = today.getHours();
	min = today.getMinutes();
	sec = today.getSeconds();
	
	
	if (sec < 10)
		sec = "0" + sec;
	
	if (min < 10)
		min = "0" + min;
	
	txt = heure + ":" + min + ":" + sec;
	
	document.getElementById('dateCourante').value = txt;
	timer = setTimeout("clock()",1000);
	
}

//Limite le nombre de caracteres
function CaracMax(texte, max)
{
	if (texte.value.length >= max)
	{
		alert('Pas plus de ' + max + ' caracteres!') ;
		texte.value = texte.value.substr(0, max - 1) ;
	}
}

//Permet de mettre le 2eme champ a la meme date que le 1er champ (par defaut)
function initDate()
{
	document.getElementById('date2').value = document.getElementById('date1').value;
}
