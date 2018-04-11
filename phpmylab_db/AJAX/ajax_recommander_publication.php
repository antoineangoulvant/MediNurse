<?php
	//Ajout le login de l'utilisateur qui recommande la publication dans le champ PLUS correspond à cette meme publication

	if(!empty($_POST[ 'id_publication' ]) && is_numeric($_POST[ 'id_publication' ]) && !empty($_POST[ 'utilisateur' ]))
	{
		

		include("../config.php");
		include("../".$chemin_connection);
		
		// Connecting, selecting database:
		$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
		or die(' Could not connect: ' . mysqli_error());
		mysqli_select_db($link,$mysql_base) or die(' Could not select database : '. mysqli_error());
		
		$login_user = mysqli_real_escape_string($link,$_POST[ 'utilisateur' ]);
		$id_publication = mysqli_real_escape_string($link,$_POST[ 'id_publication' ]);
		
		$query='SELECT PLUS FROM T_PUBLICATION WHERE ID_PUBLICATION="'.$id_publication.'"';
		$result=mysqli_query($link,$query);
		$donnee=mysqli_fetch_array($result, MYSQL_BOTH);

		if(empty($donnee[ 'PLUS' ]))
		{
			$nb_plus=0;
			$plus_update = $login_user;
		}
		else
		{
					
			$nb_plus=count(explode("#",$donnee[ 'PLUS' ]));
			$plus_update = $donnee[ 'PLUS' ].'#'.$login_user; //# : caractere séparateur
		}

		mysqli_free_result($result);
	
		$result=mysqli_query($link,'UPDATE T_PUBLICATION SET PLUS="'.$plus_update.'" WHERE ID_PUBLICATION ="'.$id_publication.'"');
		if($result)
			echo $nb_plus+1;
		else echo -1;

		mysqli_free_result($result);
		mysqli_close($link);
	}
?>
