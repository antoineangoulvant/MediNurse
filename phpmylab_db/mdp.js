/**
* Fichier javascript comportant les fonctions nécessaires pour afficher la complixité d'un mot de passe.
*
* @version 1.2.0
* @author Cedric Gagnevin <cedric.gagnevin@laposte.net>
* @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.html
* @copyright CNRS (c) 2015
* @package phpMyLab
*/

//Codé à partir de : http://m-gut.developpez.com/tutoriels/php/verif-password/

/*****************************************************************
*********************** Variables globales ***********************
*****************************************************************/
var mdp1 = "connection_mot_de_passe_new1"; // Nouveau mot de passe (id de l'input)
var mdp2 = "connection_mot_de_passe_new2"; // Confirmation (id de l'input)

var point_min = 1.5; // Point pour une lettre minuscule
var point_maj = 2.2; // Points pour une lettre majuscule
var point_chiffre = 3.3; // Points pour un chiffre
var point_caractere = 4; // Points pour une caractère spécial

var seuil_facile = 0;
var seuil_moyen = 90;
var seuil_fort = 180;

/*-------------- Couleurs ---------------*/
var init_identique = 'white';
var init_different = 'white';
var init_faible = 'white';
var init_moyen = 'white';
var init_fort = 'white';

var activ_identique = '#07DA0F'; //vert
var activ_different = '#EF1109'; //rouge
var activ_faible = '#F8FB29'; //jaune
var activ_moyen = '#F5790A'; //orange
var activ_fort = '#EF1109'; //rouge
/*---------------------------------------*/

/*****************************************************************
*****************************************************************/

function scorePassword(mdp)	
{
	var longueur = mdp.length;
    //Le mdp doit etre < 16
    if(longueur > 16)
        alert('Le mot de passe doit etre compris entre 6 et 16 caracteres');
	
	var min=0;var maj=0;var num=0;var charac=0;var point=0; //Initialisation des variables de présence et de la variable du nombre de points

	for(var i = 0; i < longueur; i++) 	
	{
		var lettre = mdp.charAt(i);
		if (lettre>='a' && lettre<='z')
		{
			point += point_min;
			min = point_min;
		}		
		else if (lettre>='A' && lettre <='Z')
		{
			point += point_maj;
			maj = point_maj;
		}
			else if (lettre>='0' && lettre<='9')
			{
				point = point + point_chiffre;
				num = point_chiffre;
			}
				else 
				{
					point += point_caractere;
					charac = point_caractere;
				}
	}
	
	etape1 = point / longueur; // Calcul du coefficient points/longueur
	etape2 = min + maj + num + charac; // Calcul du coefficient de la diversité des types de caractères...
	resultat = etape1 * etape2; // Multiplication du coefficient de diversité avec celui de la longueur
	score = resultat * longueur; // Multiplication du résultat par la longueur de la chaîne

	return score; 
}

/* Faible | Moyen | Fort */
function showComplexity()
{
	var pwd1 = document.getElementById(mdp1).value;
	
	if(pwd1.length == 0)
	{
		document.getElementById("faible").style.background = init_faible;
		document.getElementById("moyen").style.background = init_moyen;
		document.getElementById("fort").style.background = init_fort;
		return;
	}
	
	var score = scorePassword(pwd1);
	
	if(score >= seuil_facile && score < seuil_moyen)
	{
		document.getElementById("faible").style.background = activ_faible;
		document.getElementById("moyen").style.background = init_moyen;
		document.getElementById("fort").style.background = init_fort;
	}
	else if(score >= seuil_moyen && score < seuil_fort)
		 {
			document.getElementById("faible").style.background = init_faible;
			document.getElementById("moyen").style.background = activ_moyen;
			document.getElementById("fort").style.background = init_fort;
		 }			
		 else if(score >= seuil_fort)
			  {
					document.getElementById("faible").style.background = init_faible;
					document.getElementById("moyen").style.background = init_moyen;
					document.getElementById("fort").style.background = activ_fort;
			  }
}

/* Identique | Différent */
function comparePassword()
{
	var pwd1 = document.getElementById(mdp1).value;
	var pwd2 = document.getElementById(mdp2).value;
	

	
	if(pwd1 == pwd2)
	{
		document.getElementById("identique").style.background = activ_identique;
		document.getElementById("different").style.background = init_different;
	}
	else 
	{
		document.getElementById("identique").style.background = init_identique;
		document.getElementById("different").style.background = activ_different;
	}			
}
