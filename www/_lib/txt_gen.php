<?php

//============================================================================
//
// txt_gen.php
// -----------
//
// Generates vertical text labels for monitor screen.
//
// t=text (string)
// x=x size (pixels)
// y=y size (pixels)
// 
// R Fisher 03/10
//
//============================================================================

// Get the variables , and define them if they haven't been set

$t = isset($_GET["t"]) ? $_GET["t"] : "no text";
$x = isset($_GET["x"]) ? $_GET["x"] : 16;
$y = isset($_GET["y"]) ? $_GET["y"] : 100;

$im = imagecreatetruecolor($x, $y);

// Black text on a grey background, matching the stylesheet

$bgcol = imagecolorallocate($im, 0xCC, 0xCC, 0xCC);
$fgcol = imagecolorallocate($im, 0, 0, 0);

imagefilledrectangle($im, 0, 0, $x, $y, $bgcol);
imagefilledrectangle($im, 0, $y - 2, $x, $y, $fgcol);

imagestringup($im, 2, 0, $y - 8,  $t, $fgcol);

header('Content-type: image/png');
imagepng($im);

imagedestroy($im);

?>

