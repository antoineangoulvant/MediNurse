<?php
	if (file_exists("config.php"))
	{
		include("config.php");
		if(isset($configuration_terminee))
		{
			//echo 'reception';
			header("Location: reception.php");
		}
		else
		{
			//echo 'configuration';
		 	header("Location: configuration/index.php");
		}
	}
	else
	{
		header("Location: configuration/index.php");
		//echo 'configuration';
	}

// Affiche toutes les informations, comme le ferait INFO_ALL
//phpinfo();

// Affiche uniquement le module d'information.
// phpinfo(8) fournirait les mês informations.
//phpinfo(INFO_MODULES);
?>
