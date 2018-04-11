<?php

include '../../config.php';
include '../../'.$chemin_connection;

$link = mysqli_connect($mysql_location,$mysql_user,$mysql_password) or die('Connection impossible: ' . mysqli_connect_error());
mysqli_select_db($link,$mysql_base) or die('Selection de la base impossible.');

// Fix magic_quotes_gpc garbage
if (get_magic_quotes_gpc())
{ 
   function stripslashes_deep($value)
   { return (is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value));}
   $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
}

// To allow multiple independent portail sessions,
// propagate session ID in the URL instead of a cookie.
//ini_set('session.use_cookies', '0');
ini_set('session.use_cookies', '1');
// We'll add the session ID to URLs ourselves - disable trans_sid
ini_set('url_rewriter.tags', '');

// Rather dumb character set detection:
// Try switching to UTF-8 automagically on stuff like "NLS_LANG=american_america.UTF8"
$charset = 'ISO-8859-1';
if (getenv('NLS_LANG'))
  if (strtoupper(substr(getenv('NLS_LANG'), -5)) == '.UTF8')
	$charset = 'UTF-8';

//les dates en francais:
setlocale(LC_TIME, "fr_FR");

$tomorrow=date("Y-m-d",strtotime("+1 day"));
$one_week_later=date("Y-m-d",strtotime($tomorrow."+1 week"));



echo "demain: ".$tomorrow." --->  Dans 1 semaine: ".$one_week_later.'<br>'; 
$query="SELECT * FROM T_EVENT WHERE title IN (SELECT prenom AS title FROM T_UTILISATEUR) AND start BETWEEN '".$tomorrow."' AND '".$one_week_later."'";

$result = mysqli_query($link,$query) or die('Error: ' . mysqli_error($result));
while($line = mysqli_fetch_array($result))
{	 
	echo "<br>";
	 //print_r($line);
	 echo "<br>";
	 $start=date("Y-m-d",strtotime($line['start']));

		
	echo $tomorrow." --- ".$start;

	if(strtotime($tomorrow)==strtotime($line['start']))
	{
		
		echo "<br>Event: ".$line['title']."<br> le ".$line['start']." et qui finit le ".$line['end'];
		echo "<br>tu prends la rel&egraveve demain";
	}
	if($one_week_later==date("Y-m-d",strtotime($line['start'])))
	{
			echo "<br>Event: ".$line['title']."<br> le ".$line['start']." et qui finit le ".$line['end'];
			echo "<br>tu prends la rel&egraveve dans 1 semaine";
		
	}
}
/*
$query="SELECT * FROM T_EVENT WHERE start BETWEEN ".$tomorrow." AND ".$one_week_later;

$result = mysqli_query($link,$query) or die('Error: ' . mysqli_error());
while($line = mysqli_fetch_array($result, MYSQL_NUM))
{
			print_r($line);
}
*/

?>
