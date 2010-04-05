<?php
$x = $_GET['width'];
$y = $_GET['height'];
$filename = $_GET['picture'];
createthumb($filename,$x,$y);

// Image Resize
function createthumb($IMAGE_SOURCE,$THUMB_X,$THUMB_Y)
{
	preg_match('/\.(.*)/',$IMAGE_SOURCE,$match);
	$type = strtolower($match[1]);
	header('Content-type: ' .image_type_to_mime_type($type));
  $IMAGE_PROPERTIES = GetImageSize($IMAGE_SOURCE);
  if (!$IMAGE_PROPERTIES[2] == 2)
  {
   return(0);
  }
  else
  {
  	if($type == 'jpg')
  	{
   		$SRC_IMAGE = ImageCreateFromJPEG($IMAGE_SOURCE);
   	}
   	else // png
   	{
   		$SRC_IMAGE = ImageCreateFromPNG($IMAGE_SOURCE);
   	}
   $SRC_X = ImageSX($SRC_IMAGE);
   $SRC_Y = ImageSY($SRC_IMAGE);
   if(!$THUMB_X)
   	$THUMB_X = $SRC_X;
   if(!$THUMB_Y)
   	$THUMB_Y = $SRC_Y;
   if (($THUMB_Y == "0") && ($THUMB_X == "0"))
   {
     return(0);
   }
   else if ($THUMB_Y == "0")
   {
     $SCALEX = $THUMB_X/($SRC_X-1);
     $THUMB_Y = $SRC_Y*$SCALEX;
   }
   else if ($THUMB_X == "0")
   {
     $SCALEY = $THUMB_Y/($SRC_Y-1);
     $THUMB_X = $SRC_X*$SCALEY;
   }
  $aspect_x = ($THUMB_X/$SRC_X);
  $aspect_y = ($THUMB_Y/$SRC_Y);
  if ($aspect_y > $aspect_x)
  {
    $aspect = $aspect_x;
    $THUMB_Y = ($SRC_Y * $aspect);
	}
	else
	{
    $aspect = $aspect_y;
    $THUMB_X = ($SRC_X * $aspect);
	}
   $THUMB_X = (int)($THUMB_X);
   $THUMB_Y = (int)($THUMB_Y);
//   echo 'x='.$THUMB_X.'<br>';
//   echo 'y='.$THUMB_Y.'<br>';
//   exit;
if($type == 'png')
{
	$tpcolor = imagecolorat($SRC_IMAGE, 0, 0);
   // in the real world, you'd better test all four corners, not just one!
   $DEST_IMAGE = imagecreate($THUMB_X, $THUMB_Y);
   // $dest automatically has a black fill...
   imagepalettecopy($DEST_IMAGE, $SRC_IMAGE);
   imagecopyresized($DEST_IMAGE, $SRC_IMAGE, 0, 0, 0, 0, $THUMB_X, $THUMB_Y, $SRC_X, $SRC_Y);
   $pixel_over_black = imagecolorat($DEST_IMAGE, 0, 0);
   // ...but now make the fill white...
   $bg = imagecolorallocate($DEST_IMAGE, 255, 255, 255);
   imagefilledrectangle($DEST_IMAGE, 0, 0, $THUMB_X, $THUMB_Y, $bg);
//   imagecopyresized($DEST_IMAGE, $SRC_IMAGE, 0, 0, 0, 0, $THUMB_X, $THUMB_Y, $SRC_X, $SRC_Y);
   $pixel_over_white = imagecolorat($DEST_IMAGE, 0, 0);
   // ...to test if transparency causes the fill color to show through:
   if($pixel_over_black != $pixel_over_white)
   {
     // Background IS transparent
     imagefilledrectangle($DEST_IMAGE, 0, 0, $THUMB_X, $THUMB_Y, $tpcolor);
     imagecopyresized($DEST_IMAGE, $SRC_IMAGE, 0, 0, 0, 0, $THUMB_X, $THUMB_Y, $SRC_X, $SRC_Y);
     imagecolortransparent($DEST_IMAGE, $tpcolor);
   }
   imagedestroy($SRC_IMAGE);
   imagepng($DEST_IMAGE);
   imagedestroy($DEST_IMAGE);
}
else
{
   $DEST_IMAGE = imagecreatetruecolor($THUMB_X, $THUMB_Y);
   if (!imagecopyresized($DEST_IMAGE, $SRC_IMAGE, 0, 0, 0, 0, $THUMB_X, $THUMB_Y, $SRC_X, $SRC_Y)) {
     imagedestroy($SRC_IMAGE);
     imagedestroy($DEST_IMAGE);
     return(0);
   } else {
     imagedestroy($SRC_IMAGE);
     if (imagepng($DEST_IMAGE)) {
       imagedestroy($DEST_IMAGE);
       return(1);
     }
     imagedestroy($DEST_IMAGE);
    }
   }
   return(0);
  }

} # end createthumb


?>