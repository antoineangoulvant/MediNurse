<?php
include '../config.php';
include '../'.$chemin_connection;
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

//====================================================================================================
//====================================================================================================
//====================================================================================================

	//Controle si l'utilisateur a le droit de supprimer et supprime la publication

	if(!empty($_POST[ 'id_publication' ]) && is_numeric($_POST[ 'id_publication' ]) && !empty($_POST[ 'utilisateur' ]))
	{
	
		// Connecting, selecting database:
		$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
		or die(' Could not connect: ' . mysqli_error());
		mysqli_select_db($link,$mysql_base) or die(' Could not select database : '. mysqli_error());
		
		$login_user = mysqli_real_escape_string($link,$_POST[ 'utilisateur' ]);
		$id_publication = mysqli_real_escape_string($link,$_POST[ 'id_publication' ]);

		include("../config.php");
		include("../".$chemin_connection);

		//Controle de l'autorisation de suppression
		if(in_array($login_user, $gestionnaires_community))
		{
			
	
			$query='SELECT FICHIER FROM T_PUBLICATION WHERE ID_PUBLICATION='.$id_publication;
			$result=mysqli_query($link,$query);
			$donnee=mysqli_fetch_array($result, MYSQL_BOTH);
			mysqli_free_result($result);	
			
			if(!empty($donnee[ 'FICHIER' ]))
			{
				if(get_file_type($donnee[ 'FICHIER' ]) == 'image')
				{
					//On supprime l'image associée
					$chemin_image="../community_files/".$donnee[ 'FICHIER' ];
					if (file_exists($chemin_image))
						unlink($chemin_image);
				}
			}
	
			$query='DELETE FROM T_PUBLICATION WHERE ID_PUBLICATION='.$id_publication;
			$result=mysqli_query($link,$query);
	
			if($result)
				echo 1;
			else echo 0;
	
			mysqli_close($link);
		}
		else echo -1;
	}
?>
