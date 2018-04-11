<?php
		include "commun_function.php";
		
		header("content-type: application/json"); 
		// List of events
		 $json = array();
		 
		$joursferies=array();
		for($i=0 ; $i<3 ; $i++)
		{
			$annee=date("Y")+($i-1);//Pour faire Y-1 , Y et Y+1
			include "../../calendrier_variables.php";
			//Transformation des jours ouvrés pour que ça soit directement insérable dans la config du calendar
			//Format : Libelle du jour ferié -- Annee -- Mois -- Jour
			array_push($joursferies,array('Nouvel an',$annee,"0".($feries[0][1]+1)."",$feries[0][0]+1));//Nouvel an de l'année en cours 
			array_push($joursferies,array('Pâques',$annee,"0".($feries[1][1]+1)."",$feries[1][0]+1));//Paques
			array_push($joursferies,array('Fête du travail',$annee,"0".($feries[2][1]+1)."",$feries[2][0]+1));//Fete du travail
			array_push($joursferies,array('Victoire 1945',$annee,"0".($feries[3][1]+1)."",$feries[3][0]+1));//Victoire 1945
			array_push($joursferies,array('Ascension',$annee,"0".($feries[4][1]+1)."",$feries[4][0]+1));//Ascension	
			array_push($joursferies,array('Pentecôte',$annee,"0".($feries[5][1]+1)."",$feries[5][0]+1));//Pentecote
			array_push($joursferies,array('Fete nationale',$annee,"0".($feries[6][1]+1)."",$feries[6][0]+1));//Fete nationale
			array_push($joursferies,array('Assomption',$annee,"0".($feries[7][1]+1)."",$feries[7][0]+1));//Assomption
			array_push($joursferies,array('Toussaint',$annee,$feries[8][1]+1,$feries[8][0]+1));//Toussaint
			array_push($joursferies,array('Armistice 1918',$annee,$feries[9][1]+1,$feries[9][0]+1));//Armistice 1918 
			array_push($joursferies,array('Noël',$annee,$feries[10][1]+1,$feries[10][0]+1));//Noel
		}
	
	 $return_array = array();  //Jours feries à inserer sur le planning comme evenement en rouge	

	 foreach($joursferies as $jourferie)
	 {
		    $event_array = array();
			if($jourferie[3]<10)
			{
				$jourferie[3]="0".$jourferie[3]."";
			}
			/*if($jourferie[2]<10)
			{
				$jourferie[2]="0".$jourferie[2]."";
			}*/
			
            $event_array['title'] = "Jour férié";
            $event_array['start'] = "".$jourferie[1]."-".$jourferie[2]."-".$jourferie[3]." 01:00:00";
            $event_array['end'] = "".$jourferie[1]."-".$jourferie[2]."-".$jourferie[3]." 23:00:00";
			$event_array['backgroundColor'] = 'red';      //default event color 
			$event_array['description'] = $jourferie[0];       

            $event_array['borderColor'] = 'black';			//default event border color 
            $event_array['textColor'] = 'black';			//default event fontcolor
      
            // Merge the event array into the return array
            array_push($return_array, $event_array);
	
	 }
	 echo json_encode($return_array);
?>
