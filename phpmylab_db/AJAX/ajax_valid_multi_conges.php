<?php

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

//$sid=$_POST[ 'sid' ];//Numero Session

// Start PHP session
//session_id($sid);
session_name('phpmylab');
session_start();

/**
* Envoyer un mail.
*
* Formate le message à envoyer de manière à etre compatible avec la majorité des clients de messagerie et envoie le message au moyen de la fonction mail().
* @param string Adresse mail du destinataire 
* @param string Message
* @param string Objet du mail.
* @param string Adresse mail de l'envoyeur
* @param string Nom de l'envoyeur
* @param bool Avec ou sans fichier attaché 
* @return Retourne le résultat de la fonction mail() 
*/
function send_mail($to, $body, $subject, $fromaddress, $fromname, $attachments=false)
{
  $eol="\r\n";
  $mime_boundary=md5(time());

  # Common Headers
  $headers = "From: ".$fromname."<".$fromaddress.">".$eol;
  $headers .= "Reply-To: ".$fromname."<".$fromaddress.">".$eol;
  $headers .= "Return-Path: ".$fromname."<".$fromaddress.">".$eol;    // these two to set reply address
  $headers .= "Message-ID: <".time()."-".$fromaddress.">".$eol;
  $headers .= "X-Mailer: PHP v".phpversion().$eol;          // These two to help avoid spam-filters

  # Boundry for marking the split & Multitype Headers
//  $headers .= 'MIME-Version: 1.0'.$eol.$eol;
  $headers .= 'MIME-Version: 1.0'.$eol;
  $headers .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"".$eol.$eol;

  # Open the first part of the mail
  $msg = "--".$mime_boundary.$eol;
 
  $htmlalt_mime_boundary = $mime_boundary."_htmlalt"; //we must define a different MIME boundary for this section
  # Setup for text OR html -
  $msg .= "Content-Type: multipart/alternative; boundary=\"".$htmlalt_mime_boundary."\"".$eol.$eol;

  # Text Version
  $msg .= "--".$htmlalt_mime_boundary.$eol;
  $msg .= "Content-Type: text/plain; charset=iso-8859-1".$eol;
  $msg .= "Content-Transfer-Encoding: 8bit".$eol.$eol;
  $msg .= strip_tags(str_replace("<br>", "\n", substr($body, (strpos($body, "<body>")+6)))).$eol.$eol;

  # HTML Version
  $msg .= "--".$htmlalt_mime_boundary.$eol;
  $msg .= "Content-Type: text/html; charset=iso-8859-1".$eol;
  $msg .= "Content-Transfer-Encoding: 8bit".$eol.$eol;
  $msg .= $body.$eol.$eol;

  //close the html/plain text alternate portion
  $msg .= "--".$htmlalt_mime_boundary."--".$eol.$eol;

  if ($attachments !== false)
  {
    for($i=0; $i < count($attachments); $i++)
    {
      if (is_file($attachments[$i]["file"]))
      {  
        # File for Attachment
        $file_name = substr($attachments[$i]["file"], (strrpos($attachments[$i]["file"], "/")+1));
       
        $handle=fopen($attachments[$i]["file"], 'rb');
        $f_contents=fread($handle, filesize($attachments[$i]["file"]));
        $f_contents=chunk_split(base64_encode($f_contents));    //Encode The Data For Transition using base64_encode();
        $f_type=filetype($attachments[$i]["file"]);
        fclose($handle);
       
        # Attachment
        $msg .= "--".$mime_boundary.$eol;
        $msg .= "Content-Type: ".$attachments[$i]["content_type"]."; name=\"".$file_name."\"".$eol;  // sometimes i have to send MS Word, use 'msword' instead of 'pdf'
        $msg .= "Content-Transfer-Encoding: base64".$eol;
        $msg .= "Content-Description: ".$file_name.$eol;
        $msg .= "Content-Disposition: attachment; filename=\"".$file_name."\"".$eol.$eol; // !! This line needs TWO end of lines !! IMPORTANT !!
        $msg .= $f_contents.$eol.$eol;
      }
    }
  }

  # Finished
  $msg .= "--".$mime_boundary."--".$eol.$eol;  // finish with two eol's for better security. see Injection.
 
  # SEND THE EMAIL
  ini_set('sendmail_from',$fromaddress);  // the INI lines are to force the From Address to be used !
//  $mail_sent = mail($to, $subject, $msg, $headers);
  $mail_sent = mail($to, utf8_decode($subject), $msg, $headers);
 
  ini_restore('sendmail_from');
 
  return $mail_sent;
}



function calc_nb_conges_a_valider($login) {

	include("../config.php");
	include("../".$chemin_connection);
	// Connecting, selecting database:
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
	or die('Could not connect: ' . mysqli_connect_error());
	mysqli_select_db($link,$mysql_base) or die('L15 : Could not select database : '. mysqli_error());

	if($_SESSION[ 'connection' ][ 'utilisateur' ]==$directeur)
	{
		$query = 'SELECT COUNT(ID_CONGE) FROM T_CONGE, T_UTILISATEUR WHERE T_CONGE.UTILISATEUR=T_UTILISATEUR.UTILISATEUR AND T_UTILISATEUR.STATUS in (3,4,6) AND T_CONGE.VALIDE = 0';
		$result = mysqli_query($link,$query) or die('L20 : Query '.$query.'| failed: ' . mysqli_error());
		$donnee = mysqli_fetch_array($result, MYSQL_NUM);
		mysqli_free_result($result);

		$retour=$donnee[0];
	}
	elseif($_SESSION[ 'connection' ][ 'status' ]==6)
	{
		$query = 'SELECT COUNT(ID_CONGE) FROM T_CONGE, T_UTILISATEUR WHERE T_CONGE.UTILISATEUR=T_UTILISATEUR.UTILISATEUR AND T_UTILISATEUR.STATUS=5 AND T_CONGE.GROUPE=\''.$_SESSION[ 'connection' ][ 'groupe' ].'\' AND T_CONGE.VALIDE = 0';
		$result = mysqli_query($link,$query) or die('L29 : Query '.$query.'| failed: ' . mysqli_error());
		$donnee = mysqli_fetch_array($result, MYSQL_NUM);
		mysqli_free_result($result);

		$retour=$donnee[0];
	}
	elseif(in_array($_SESSION[ 'connection' ][ 'status' ], array(3,4)))
	{
		$query = 'SELECT COUNT(*) FROM T_CONGE WHERE VALIDE = 0 AND UTILISATEUR IN(SELECT UTILISATEUR FROM T_UTILISATEUR WHERE GROUPE=\''.$_SESSION[ 'connection' ][ 'groupe' ].'\' AND STATUS IN(1,2))';
		$result = mysqli_query($link,$query) or die('L38 : Query '.$query.'| failed: ' . mysqli_error());
		$donnee = mysqli_fetch_array($result, MYSQL_NUM);
		mysqli_free_result($result);

		$retour=$donnee[0];
	}
	else $retour=-1;

	mysqli_close($link);
	return $retour;
}


/**
* Permet d'obtenir les congés à valider par un reponsable donné
*
* Renvoie un tableau contenant les congés.
*
* @param string login d'un utilisateur 
* @return Tableau contenant les congés
*/
function conges_a_valider($login) {

	$nb_conges=calc_nb_conges_a_valider($login);
	$tab_conges = array();

	include("../config.php");
	include("../".$chemin_connection);
	// Connecting, selecting database:
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
	or die('L68 : Could not connect: ' . mysqli_connect_error());
	mysqli_select_db($link,$mysql_base) or die('L69 : Could not select database : '. mysqli_error());
	
	if($_SESSION[ 'connection' ][ 'utilisateur' ]==$directeur && $nb_conges > 0)
	{
		$query = 'SELECT ID_CONGE,T_CONGE.UTILISATEUR,T_UTILISATEUR.NOM, T_UTILISATEUR.PRENOM,T_CONGE.GROUPE,DEBUT_DATE,FIN_DATE,DEBUT_AM,FIN_PM,INFORMER_GP,TYPE,NB_JOURS_OUVRES FROM T_CONGE, T_UTILISATEUR WHERE T_CONGE.UTILISATEUR=T_UTILISATEUR.UTILISATEUR AND T_UTILISATEUR.STATUS in (3,4,6) AND T_CONGE.VALIDE = 0';
		$result = mysqli_query($link,$query) or die('L74 : Query '.$query.'| failed: ' . mysqli_error());
	}
	elseif($_SESSION[ 'connection' ][ 'status' ]==6 && $nb_conges > 0)
	{
		$query = 'SELECT ID_CONGE,T_CONGE.UTILISATEUR, T_UTILISATEUR.NOM, T_UTILISATEUR.PRENOM,T_CONGE.GROUPE,DEBUT_DATE,FIN_DATE,DEBUT_AM,FIN_PM,INFORMER_GP,TYPE,NB_JOURS_OUVRES FROM T_CONGE, T_UTILISATEUR WHERE T_CONGE.UTILISATEUR=T_UTILISATEUR.UTILISATEUR AND T_UTILISATEUR.STATUS=5 AND T_CONGE.GROUPE=\''.$_SESSION[ 'connection' ][ 'groupe' ].'\' AND T_CONGE.VALIDE = 0';
		$result = mysqli_query($link,$query) or die('L80 : Query '.$query.'| failed: ' . mysqli_error());
	}
	elseif($nb_conges > 0)
	{
		$query = 'SELECT ID_CONGE,T_CONGE.UTILISATEUR,T_UTILISATEUR.NOM, T_UTILISATEUR.PRENOM,T_CONGE.GROUPE,DEBUT_DATE,FIN_DATE,DEBUT_AM,FIN_PM,INFORMER_GP,TYPE,NB_JOURS_OUVRES FROM T_CONGE,T_UTILISATEUR WHERE T_CONGE.UTILISATEUR=T_UTILISATEUR.UTILISATEUR AND VALIDE = 0 AND T_CONGE.UTILISATEUR IN(SELECT UTILISATEUR FROM T_UTILISATEUR WHERE GROUPE=\''.$_SESSION[ 'connection' ][ 'groupe' ].'\' AND STATUS IN(1,2))';

		$result = mysqli_query($link,$query) or die('L86 : Query '.$query.'| failed: ' . mysqli_error());
	}

	while($donnee=mysqli_fetch_array($result, MYSQL_BOTH))
	{
		$conge[ 'id' ] = $donnee[ 'ID_CONGE' ];
		$conge[ 'login' ] = $donnee[ 'UTILISATEUR' ];
		$conge[ 'nom' ] = ucfirst(strtolower($donnee[ 'NOM' ]));
		$conge[ 'prenom' ] = ucfirst(strtolower($donnee[ 'PRENOM' ]));
		$conge[ 'groupe' ] = $donnee[ 'GROUPE' ];
		$conge[ 'date_debut' ] = $donnee[ 'DEBUT_DATE' ];
		$conge[ 'date_fin' ] = $donnee[ 'FIN_DATE' ];
		$conge[ 'date_AM' ] = $donnee[ 'DEBUT_AM' ];
		$conge[ 'date_PM' ] = $donnee[ 'FIN_PM' ];
		$conge[ 'mailGroupe' ] = $donnee[ 'INFORMER_GP' ];
		$conge[ 'type' ] = $donnee[ 'TYPE' ];
		$conge[ 'nb_jours_ouvres' ] = $donnee[ 'NB_JOURS_OUVRES' ];
		array_push($tab_conges,$conge);
	}


	mysqli_free_result($result);
	mysqli_close($link);
	return $tab_conges;
}


/**
* Valide les congés sélectionnés par le responsable
*
* @param string login d'un utilisateur 
* @param Tableau des congés à valider
* @return int le nombre de congés validés
*/
function validation_conges($login,$tab_conges) {

	$tab_conges=conges_a_valider($_SESSION[ 'connection' ][ 'utilisateur' ]);
	$conges_selectionnes=explode("#",$_POST[ 'conges' ]);

	include("../config.php");
	include("../".$chemin_connection);
	// Connecting, selecting database:
	$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password)
	or die('L122 : Could not connect: ' . mysqli_connect_error());
	mysqli_select_db($link,$mysql_base) or die('L123 : Could not select database : '. mysqli_error());

	$nb_conges_valides=0;

	foreach($tab_conges as $conge)
	{
		if(in_array($conge[ 'id' ],$conges_selectionnes))
		{
			//--------- MAJ de la validité du congé --------- 
			$query = 'UPDATE T_CONGE SET VALIDE = 1 WHERE ID_CONGE = '.$conge[ 'id' ];
			mysqli_query($link,$query) or die('L133 : Query '.$query.'| failed: ' . mysqli_error());


			//--------- MAJ du solde de l'utilisateur --------- 
				//Récupération des données du solde de l'utilisateur
				$query = 'SELECT * FROM T_CONGE_SOLDE WHERE UTILISATEUR = "'.$conge[ 'login' ].'"';// 1 seul resultat car c'est une clé primaire
				$result=mysqli_query($link,$query) or die('L139 : Query '.$query.'| failed: ' . mysqli_error());
				$donnees=mysqli_fetch_array($result, MYSQL_BOTH);
				$sca=$donnees[ 'SOLDE_CA' ];
				$sca1=$donnees[ 'SOLDE_CA_1' ];
				$srecup=$donnees[ 'SOLDE_CET' ];
				$scet=$donnees[ 'SOLDE_RECUP' ];

			if($conge[ 'type' ] < 3)
			{
				if ($conge[ 'type' ] == 0)
				{
					if ($sca1 > 0)
					{
						if ($sca1-$conge[ 'nb_jours_ouvres' ] < 0) 
						{
						//echo 'update ca-1';
							$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CA_1="0" WHERE UTILISATEUR="'.$conge[ 'login' ].'"';
							$result = mysqli_query($link,$query) or die('Requete de modification du status: ' . mysqli_error());
							//mysql_free_result($result);
							//$_SESSION[ 'conge' ][ 'CA-1' ]=0;
							//$_SESSION[ 'solde' ][ 'CA-1' ]=0;
						//echo 'update ca';
							$diff=$conge[ 'nb_jours_ouvres' ]-$sca1;
							$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CA=SOLDE_CA-'.$diff.' WHERE UTILISATEUR="'.$conge[ 'login' ].'"';
							$result = mysqli_query($link,$query) or die('Requete de modification du status: ' . mysqli_error());
							//mysql_free_result($result);
							//$_SESSION[ 'conge' ][ 'CA' ]=$_SESSION[ 'conge' ][ 'CA' ]-$diff;
							//$_SESSION[ 'solde' ][ 'CA' ]=$_SESSION[ 'conge' ][ 'CA' ];
						}
						else
						{
						//echo 'update ca-1';
							$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CA_1=SOLDE_CA_1-'.$conge[ 'nb_jours_ouvres' ].' WHERE UTILISATEUR="'.$conge[ 'login' ].'"';
							$result = mysqli_query($link,$query) or die('Requete de modification du status: ' . mysqli_error());
							//mysql_free_result($result);
							//$_SESSION[ 'conge' ][ 'CA-1' ]=$_SESSION[ 'conge' ][ 'CA-1' ]- $_SESSION["conge"]["nb_jours_ouvres_recalc"];
							//$_SESSION[ 'solde' ][ 'CA-1' ]=$_SESSION[ 'conge' ][ 'CA-1' ];
						}
					}
					else
					{
					//echo 'update ca';
						$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CA=SOLDE_CA-'.$conge[ 'nb_jours_ouvres' ].' WHERE UTILISATEUR="'.$conge[ 'login' ].'"';
						$result = mysqli_query($link,$query) or die('Requete de modification du status: ' . mysqli_error());
						//mysql_free_result($result);
						//$_SESSION[ 'conge' ][ 'CA' ]=$_SESSION[ 'conge' ][ 'CA' ] -$_SESSION["conge"]["nb_jours_ouvres_recalc"];
						//$_SESSION[ 'solde' ][ 'CA' ]=$_SESSION[ 'conge' ][ 'CA' ];
					}
				}
				else if ($conge[ 'type' ] == 1)
				{
				//echo 'update cet';
					$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_CET=SOLDE_CET-'.$conge[ 'nb_jours_ouvres' ].' WHERE UTILISATEUR="'.$conge[ 'login' ].'"';
					$result = mysqli_query($link,$query) or die('Requete de modification du status: ' . mysqli_error());
					//mysql_free_result($result);
					//$_SESSION[ 'conge' ][ 'CET' ]=$_SESSION[ 'conge' ][ 'CET' ]-$_SESSION["conge"]["nb_jours_ouvres_recalc"];
					//$_SESSION[ 'solde' ][ 'CET' ]=$_SESSION[ 'conge' ][ 'CET' ];
				}
				else if ($conge[ 'type' ] == 2)
				{
				//echo 'update recup';
					$query = 'UPDATE T_CONGE_SOLDE SET SOLDE_RECUP=SOLDE_RECUP-'.$conge[ 'nb_jours_ouvres' ].' WHERE UTILISATEUR="'.$conge[ 'login' ].'"';
					$result = mysqli_query($link,$query) or die('Requete de modification du status: ' . mysqli_error());
					//mysql_free_result($result);
					//$_SESSION[ 'conge' ][ 'RECUP' ]=$_SESSION[ 'conge' ][ 'RECUP' ]-$_SESSION["conge"]["nb_jours_ouvres_recalc"];
					//$_SESSION[ 'solde' ][ 'RECUP' ]=$_SESSION[ 'conge' ][ 'RECUP' ];
				}
			}//fin de if (!$annule && $_SESSION["conge"]["type"]<3)
			

			//--------- Envoi d'emails --------- 
			$subject = "CONGE: Validation demande de congé (ID=".$conge[ 'id' ].")";
			$tab=explode("/",$chemin_mel);
			unset($tab[count($tab)-2]);
			$chemin_reception=implode('/',$tab);//Enleve le '/AJAX' dans l'URL

		
			//demandeur de conge (quelquesoit son status)
			if ($mode_test) $TO = $mel_test;
			else $TO = $conge[ 'login' ].'@'.$domaine;
			$message = "<body>Bonjour ".$conge[ 'nom' ]." ".$conge['prenom'].",<br> votre demande de congé a été validée,<br> ";
			$message .= "suivez le lien <a href=".$chemin_reception."?dec=".$conge[ 'id' ].">".$chemin_reception."?dec=".$conge[ 'id' ]."</a> pour l'afficher.</body>";
			$message=utf8_decode($message);
			send_mail($TO, $message, $subject, $_SESSION['connection']['utilisateur'].'@'.$domaine, $_SESSION['connection']['nom']." ".$_SESSION['connection']['prenom']);
			
			//au responsable d'equipe ou de service du groupe selectionné pour le conge
			//if ($directeur!=$_SESSION[ 'connection' ][ 'utilisateur' ])
			if ($directeur!=$conge[ 'login' ])
			{
				$pourqui='responsable';
				$util=$_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']];
				if ($_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']]==$conge[ 'login' ]) {$util=$directeur;$pourqui='directeur';}
				if ($mode_test) $TO = $mel_test;
				else $TO = $util.'@'.$domaine;
				$message = "<body>Bonjour ".$pourqui." ".$util.",<br> ";
				$message .= "le congé <a href=".$chemin_reception."?dec=".$conge[ 'id' ].">".$chemin_reception."?dec=".$conge[ 'id' ]."</a> émis par ".$conge[ 'prenom' ]." ".$conge[ 'nom' ]." est validé.</body>";
				$message=utf8_decode($message);
				send_mail($TO, $message, $subject, $_SESSION['connection']['utilisateur'].'@'.$domaine, $_SESSION['connection']['nom']." ".$_SESSION['connection']['prenom']);
			}
		
			//envoie aux admins
			$query2 = 'SELECT UTILISATEUR FROM T_UTILISATEUR WHERE ADMIN=1';
			$result2 = mysqli_query($link,$query2);
			while ($line2 = mysqli_fetch_array($result2, MYSQL_NUM))
			{
				if ($mode_test) $TO = $mel_test;
				else $TO = $line2[0].'@'.$domaine;
				$message = "<body>Bonjour administrateur ".$line2[0].",<br> ";
				$message .= "le congé <a href=".$chemin_reception."?dec=".$conge[ 'id' ].">".$chemin_reception."?dec=".$conge[ 'id' ]."</a> émis par ".$conge[ 'prenom' ]." ".$conge[ 'nom' ]." est validé.</body>";
				$message=utf8_decode($message);
				send_mail($TO, $message, $subject, $_SESSION['connection']['utilisateur'].'@'.$domaine, $_SESSION['connection']['nom']." ".$_SESSION['connection']['prenom']);
			}
			mysqli_free_result($result2);
		
			//envoie aux utilisateurs du meme groupe (si case cochee)
			if ($conge[ 'mailGroupe' ]==1)
			{
				$query2 = 'SELECT UTILISATEUR FROM T_UTILISATEUR WHERE GROUPE=\''.$conge[ 'groupe' ].'\'';
				$result2 = mysqli_query($link,$query2);
				while ($line2 = mysqli_fetch_array($result2, MYSQL_NUM))
				{
					if ($line2[0]!=$conge[ 'login' ] && $line2[0]!=$_SESSION[ 'correspondance' ]['responsable'][$_SESSION['groupe_indice']])
					{
					if ($mode_test) $TO = $mel_test;
					else $TO = $line2[0].'@'.$domaine;
				
			//		$message = "<body>Bonjour collègue ".$line2[0].",<br> ";
					$message = "<body>Cher(e) collègue,<br> ";
					$debAMouPM="Matin";
					if ($conge['date_AM']==1) $debAMouPM="Aprés-midi";
					$finAMouPM="Matin";
					if ($conge['date_PM']==1) $finAMouPM="Après-midi";
					$message .= "je suis en congé du ".$conge['date_debut']."(".$debAMouPM.") au  ".$conge['date_fin']."(".$finAMouPM.").<br>Cordialement,<br>".$conge['prenom']."  ".$conge['nom']."</body>";
			//		$message .= "je suis en congé du ".$_SESSION[conge][date_debut]." au  ".$_SESSION[conge][date_fin].".<br>Cordialement,<br>".$_SESSION[conge][prenom]."  ".$_SESSION[conge][nom]."</body>";
					$message=utf8_decode($message);
					send_mail($TO, $message, $subject, $conge['login'].'@'.$domaine, $conge['nom']." ".$conge['prenom']);
					}
				}
				mysqli_free_result($result2);
			}


			$nb_conges_valides++;
		}
	}

	mysqli_close($link);

	return $nb_conges_valides;
}



//Validation multiple des congés sélectionnés
$tableau_conges=conges_a_valider($_SESSION[ 'connection' ][ 'utilisateur' ]);
$nb_conges_val=validation_conges($_SESSION[ 'connection' ][ 'utilisateur' ],$tableau_conges);

//Nombre de congés validés
if($nb_conges_val==0)
	$nb_valides='<p class="rouge gras">Aucun cong&eacute; valid&eacute;</p>';
else if($nb_conges_val==1)
	$nb_valides='<p class="vert gras">1 cong&eacute; valid&eacute;</p>';
else $nb_valides='<p class="vert gras">'.$nb_conges_val.' cong&eacute;s valid&eacute;s</p>';

//------
//------------ Mise à jour de la liste après validation
//------

//Nombre de congés à valider restant
$nb_conges=calc_nb_conges_a_valider($_SESSION[ 'connection' ][ 'utilisateur' ]);
if($nb_conges==0)
	$nb_conges_restant='Tous les cong&eacute;s sont valid&eacute;s!';
elseif($nb_conges==1)
	$nb_conges_restant='Vous avez '.$nb_conges.' cong&eacute; &agrave; valider';
elseif($nb_conges>1)
	$nb_conges_restant='Vous avez '.$nb_conges.' cong&eacute;s &agrave; valider';

//Congés restant
$tab_conges_restant=conges_a_valider($_SESSION[ 'connection' ][ 'utilisateur' ]);
$conges_restant='';

$conges_restant.='<table class="centrerBloc">';
foreach($tab_conges_restant as $conge)
{
//	$conges_restant.='<tr><td><input type="checkbox" name="'.$conge[ 'id' ].'" /> n&deg; <a href="conges.php?sid='.$sid.'&dec='.$conge[ 'id' ].'#DEMANDE_CONGES">'.$conge[ 'id' ].'</a>  |  <strong>'.$conge[ 'login' ].'</strong> ('.$conge[ 'groupe' ].') du <em>'.$conge[ 'date_debut' ].'</em> au <em>'.$conge[ 'date_fin' ].'</em>.</td></tr>';
	$conges_restant.='<tr><td><input type="checkbox" name="'.$conge[ 'id' ].'" /> n&deg; <a href="conges.php?dec='.$conge[ 'id' ].'#DEMANDE_CONGES">'.$conge[ 'id' ].'</a>  |  <strong>'.$conge[ 'login' ].'</strong> ('.$conge[ 'groupe' ].') du <em>'.$conge[ 'date_debut' ].'</em> au <em>'.$conge[ 'date_fin' ].'</em>.</td></tr>';
}
$conges_restant.='</table>';

//REPONSE : RETOUR DE LA REQUETE
echo $nb_valides.'##'.$nb_conges_restant.'##'.$conges_restant;

?>
