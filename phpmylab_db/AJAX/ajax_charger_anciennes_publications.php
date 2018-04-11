<?php
	//But: Renvoie 10 publications suivantes (anciennes)


//--------FONCTIONS--------------


/**
* Retourne une date mise en forme  partir d'un timestamp
*
*/
function mise_en_forme_date($timestamp)
{	
	if(date("j-m-Y",$timestamp) == date("j-m-Y"))
		return 'Aujourd\'hui &agrave; '.date("G\hi",$timestamp);
	else return 'Le '.date("d/m/Y",$timestamp).' &agrave; '.date("H\hi",$timestamp);
}


/**
* Retourne le nombre de "+" d'une publication
*
*/
function get_nb_plus($id_publication)
{
	include("../config.php");
	include("../".$chemin_connection);
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_error());
	mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

	$query='SELECT PLUS FROM T_PUBLICATION WHERE ID_PUBLICATION="'.$id_publication.'"';
	$result=mysqli_query($link,$query);

	$plus = mysqli_fetch_array($result, MYSQL_BOTH);
	mysqli_free_result($result);
	mysqli_close($link);
	
	if($plus[0] == "")	return 0;
	else return count(explode("#",$plus[0]));
	
}

/**
* Retourne le nombre de "-" d'une publication
*
*/
function get_nb_moins($id_publication)
{
	include("../config.php");
	include("../".$chemin_connection);
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_error());
	mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

	$query='SELECT MOINS FROM T_PUBLICATION WHERE ID_PUBLICATION="'.$id_publication.'"';
	$result=mysqli_query($link,$query);

	$plus = mysqli_fetch_array($result, MYSQL_BOTH);
	mysqli_free_result($result);
	mysqli_close($link);
	
	if($plus[0] == "")	return 0;
	else return count(explode("#",$plus[0]));
	
}

/**
* Retourne un booleen indiquant si l'utilisateur a donné son avis sur une publication(+/-)
*
*/
function avis_donne($login_user,$id_publication)
{
	include("../config.php");
	include("../".$chemin_connection);
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_error());
	mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

	$query='SELECT PLUS,MOINS FROM T_PUBLICATION WHERE ID_PUBLICATION="'.$id_publication.'"';
	$result=mysqli_query($link,$query);

	$donnees = mysqli_fetch_array($result, MYSQL_BOTH);
	mysqli_free_result($result);
	mysqli_close($link);
	
	$tab_user_plus = explode("#",$donnees[ 'PLUS' ]);
	$tab_user_moins = explode("#",$donnees[ 'MOINS' ]);	
	if(in_array($login_user,$tab_user_plus) OR in_array($login_user,$tab_user_moins))
		return 1;
	else return 0;
}

/**
* Retourne img         : Image
* 	   youtube     : Video issue de YouTube
*          dailymotion : Video issue de Dailymotion
*          inconnu     : Lien internet ne venant pas de youtube ou dailymotion
*/
function get_file_type($file)
{
	if(preg_match('/^(http|www)/',$file))//Lien internet donc video
	{
		if(preg_match('/youtube/',$file))//Video Youtube
			return 'youtube';
		elseif(preg_match('/dailymotion/',$file))//Video Dailymotion
			return 'dailymotion';
		else return 'inconnu';
	}
	else //Image
	{
		return 'image';
	}
}

/**
* Insere le player adéquat pour pouvoir lire la video
*
*/
function integrer_video($url_video)
{
	if(get_file_type($url_video) == 'youtube')//Insertion du player youtube
	{
		$pos_debut_id = strrpos($url_video,'?v=')+3; //Position du début de l'id de la video
		$pos_fin_id = strpos($url_video,'&',$pos_debut_id);//Position de fin de l'id
		if(empty($pos_fin_id)) $pos_fin_id=strlen($url_video);

		$length = $pos_fin_id-$pos_debut_id;//Longueur de l'id
		$id_video = substr($url_video,$pos_debut_id,$length);//ID

		return '<iframe width="560" height="315" src="http://www.youtube.com/embed/'.$id_video.'" frameborder="0" allowfullscreen></iframe>';
	}
	elseif(get_file_type($url_video) == 'dailymotion')//Insertion du player daylimotion
	{
		$pos_debut_id = strrpos($url_video,'video/')+6; //Position du début de l'id de la video
		$pos_fin_id = strpos($url_video,'_',$pos_debut_id);//Position de fin de l'id
		if(empty($pos_fin_id)) $pos_fin_id=strlen($url_video);
		$length = $pos_fin_id-$pos_debut_id;//Longueur de l'id
		$id_video = substr($url_video,$pos_debut_id,$length);//ID

		return '<object width="480" height="270"><param name="movie" value="http://www.dailymotion.com/swf/video/'.$id_video.'"></param><param name="allowFullScreen" value="true"></param><embed type="application/x-shockwave-flash" src="http://www.dailymotion.com/swf/video/'.$id_video.'" width="560" height="315" wmode="transparent" allowfullscreen="true"></embed></object></i>';
	}
}

/**
* Renvoie un message sur l'avis donné par l'utilisateur.
* 		+ : Vous recommandez 
*		- : Vous déconseillez
*
*/
function get_avis_donne($login_user,$id_publication)
{
	if(avis_donne($login_user,$id_publication))
	{
		include("../config.php");
		include("../".$chemin_connection);
		$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_error());
		mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');
	
		$query='SELECT PLUS,MOINS FROM T_PUBLICATION WHERE ID_PUBLICATION="'.$id_publication.'"';
		$result=mysqli_query($link,$query);
	
		$donnees = mysqli_fetch_array($result, MYSQL_BOTH);
		mysqli_free_result($result);
		mysqli_close($link);
		
		$tab_user_plus = explode("#",$donnees[ 'PLUS' ]);
		$tab_user_moins = explode("#",$donnees[ 'MOINS' ]);	
		if(in_array($login_user,$tab_user_plus))
		{	
			return '<img src="images/recommandation.png" alt="Vous recommandez" class="bulle_avis" />';
		}
		else if(in_array($login_user,$tab_user_moins))
		{	
			return '<img src="images/deconseil.png" alt="Vous d&eacute;conseillez" class="bulle_avis" />';
		}
		else return -1;
	}
	else return -1;
}


//--------FIN FONCTIONS----------	

	$date_originale = $_POST[ 'oldest_date' ];
	$utilisateur = $_POST[ 'utilisateur' ];

	//Transformation de la date en timestamp
	
	//La chaine recueilli peut avoir 2 formats : Ajd ou non
	if(preg_match('/^Aujourd\'hui/',$date_originale))
	{
		$annee=date("Y");
		$mois=date("m");
		$jour=date("j");

		$pos_h=strrpos($date_originale,'h',12);
		$heures=substr($date_originale,15,$pos_h-15);

		$minutes=substr($date_originale,$pos_h+1);

		$timestamp=mktime($heures,$minutes,0,$mois,$jour,$annee);	
	}
	else 
	{
		$annee=substr($date_originale,9,4);//L'année est tjr sur 4 chiffres
		$mois=substr($date_originale,6,2);//Le mois est tjr sur 2 chiffres
		$jour=substr($date_originale,3,2);//Le jour est tjr sur 2 chiffres

		$heures=substr($date_originale,17,2);
		$minutes=substr($date_originale,20);

		$timestamp=mktime($heures,$minutes,0,$mois,$jour,$annee);	
	}
	
	include("../config.php");
	include("../".$chemin_connection);

	// Connecting, selecting database:
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
	or die(' Could not connect: ' . mysqli_error());
	mysqli_select_db($link,$mysql_base) or die(' Could not select database : '. mysqli_error());

	$query='SELECT * FROM T_PUBLICATION WHERE DATE_PUBLICATION < '.$timestamp.' ORDER BY DATE_PUBLICATION DESC LIMIT 0,10';
	$result=mysqli_query($link,$query);
	
	

	$publications_retour='';
	
	while($publication=mysqli_fetch_array($result, MYSQL_BOTH))
	{	
		$publications_retour.= '<div  id="publication_'.$publication[ 'ID_PUBLICATION' ].'">';
		$id_pub=$publication[ 'ID_PUBLICATION' ];
		$publications_retour.= '<table class="publication">';
		$publications_retour.= '<tr class="ligne_grise">
			<td colspan=2>
				<input type=hidden name="publication_'.$id_pub.'" id="publication_'.$id_pub.'" value="'.$id_pub.'" class="id_publication" />
				<span class="auteur">'.$publication[ 'UTILISATEUR' ].'</span> - 
				<span class="titre">'.$publication[ 'TITRE' ].'</span>';

				//Droit de suppression pour les gestionnaires
				if(in_array($_SESSION[ 'connection' ][ 'utilisateur' ],$gestionnaires_community))
				{
					echo '<img src="images/croix_rouge.png" alt="Supprimer" class="btn_supprimer_pub" onclick="supprimerPublication('.$publication[ 'ID_PUBLICATION' ].',this)" />';
				}
		$publications_retour.= '	</td>
			</tr>';
		$publications_retour.= '<tr>
			<td colspan=2>';
		if(!empty($publication[ 'FICHIER' ]) && get_file_type($publication[ 'FICHIER' ]) == 'image') //Image
		{
			$publications_retour.= '<img class="fichier_pub" src="community_files/'.$publication[ 'FICHIER' ].'" />';
		}	
		else if(!empty($publication[ 'FICHIER' ]) && in_array(get_file_type($publication[ 'FICHIER' ]),array('dailymotion','youtube'))) //Video dailymotion ou youtube
		{
			$publications_retour.= '<table class="centrerBloc"><tr><td class="centrer">';
			$publications_retour.= integrer_video($publication[ 'FICHIER' ]);
			$publications_retour.= '</td></tr></table>';
		}
		
		
		$publications_retour.= '	<p>'.$publication[ 'CONTENU' ].'</p>
			</td>
			</tr>';
		$publications_retour.= '<tr>
			<td>
				<span class="nb_plus">'.get_nb_plus($id_pub).'</span> <img src="images/btn_plus.png" title="Recommander" alt="+" height=27 width=27 ';
				if(!avis_donne($utilisateur, $publication[ 'ID_PUBLICATION' ]))
					$publications_retour.= 'class="cursor_pointer" onclick="recommander(this)" ';
				else  $publications_retour.= 'class="avis_donne" ';
				$publications_retour.= '/>';
				$publications_retour.= '<span class="nb_moins">'.get_nb_moins($id_pub).'</span>  <img src="images/btn_moins.png" title="Deconseiller" alt="-" height=27 width=27 ';
				if(!avis_donne($utilisateur, $publication[ 'ID_PUBLICATION' ]))
					$publications_retour.= 'class="cursor_pointer" onclick="deconseiller(this)" ';
				else  $publications_retour.= 'class="avis_donne" ';
				$publications_retour.= '/>';
	
				//Bulle d'avis de l'utilisateur
				if(get_avis_donne($utilisateur, $publication[ 'ID_PUBLICATION' ]) != -1)
					echo get_avis_donne($utilisateur, $publication[ 'ID_PUBLICATION' ]);


		$publications_retour.= '	</td>
			<td class="alignDroite">
				<span class="date_categ_publ"><i>'.$publication[ 'CATEGORIE' ].'</i><br/><time>'.mise_en_forme_date($publication[ 'DATE_PUBLICATION' ]).'</time></span>
			</td>
			</tr>';
		
		$publications_retour.= '</table></div>';
	}

	mysqli_free_result($result);	
	mysqli_close($link);

	echo $publications_retour;
	

	
?>
