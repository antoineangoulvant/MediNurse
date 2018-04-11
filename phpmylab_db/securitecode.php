<?php
/******************************************************************************/
/*                                                                            */
/*                       __        ____                                       */
/*                 ___  / /  ___  / __/__  __ _____________ ___               */
/*                / _ \/ _ \/ _ \_\ \/ _ \/ // / __/ __/ -_|_-<               */
/*               / .__/_//_/ .__/___/\___/\_,_/_/  \__/\__/___/               */
/*              /_/       /_/                                                 */
/*                                                                            */
/*                                                                            */
/******************************************************************************/
/*                                                                            */
/* Titre          : Sécurité captcha protection anti spam                     */
/*                                                                            */
/* URL            : http://www.phpsources.org/scripts468-PHP.htm              */
/* Auteur         : PhpSources                                                */
/* Date édition   : 19 Nov 2008                                               */
/* Website auteur : http://www.phpsources.org                                 */
/*                                                                            */
/******************************************************************************/

$sid = $_GET[ 'sid' ];

// Start PHP session
session_id($sid);
session_name('phpmylab');
session_start();

$largeur  = 120;
$hauteur  = 40;
$longueur = 5;
$liste = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
$code    = '';
$counter = 0;

$image = @imagecreate($largeur, $hauteur) or die('Impossible d\'initializer GD')
;

for( $i=0; $i<10; $i++ ) {
   imageline($image,
   mt_rand(0,$largeur), mt_rand(0,$hauteur),
   mt_rand(0,$largeur), mt_rand(0,$hauteur),
   imagecolorallocate($image, mt_rand(200,255),mt_rand(200,255),mt_rand(200,255)
));
 }

for( $i=0, $x=0; $i<$longueur; $i++ ) {
   $charactere = substr($liste, rand(0, strlen($liste)-1), 1);
   $x += 10 + mt_rand(0,10);
   imagechar($image, mt_rand(3,5), $x, mt_rand(5,20), $charactere,
   imagecolorallocate($image, mt_rand(0,155), mt_rand(0,155), mt_rand(0,155)));
   $code .= strtolower($charactere);
 }
   
  header('Content-Type: image/jpeg');
  imagejpeg($image);
  imagedestroy($image);

  $_SESSION['securecode'] = $code;
// fin du code
?>
