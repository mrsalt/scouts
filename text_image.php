<?php

//echo 'width='.$_GET['width'].', height='.$_GET['height'].'<br />';

/////////////////////////////////
// Outputs an image of text passed in as string
// GET variables used:
// string: text to be displayed on image
// font-size: 1-5
// background-color: background color in FFFFFF format
// color: text color in FFFFFF format
// width: image width in pixels
// height: image height in pixels
// rotate: degrees to rotate image
// x: x-start position of text
// y: y-start position of text
/////////////////////////////////


if(!$_GET['font-size'])
{
	$_GET['font-size'] = 5;
}
if(!$_GET['background-color'])
{
	$_GET['background-color'] = 'FFFFFF';
}
// create blank image
$im = imagecreate($_GET['width'],$_GET['height']);

$rgb_bg = splitrgb($_GET['background-color']);
$bg = imagecolorallocate($im, $rgb_bg['red'],$rgb_bg['green'],$rgb_bg['blue']); // background color

$rgb_text = splitrgb($_GET['color']);
$textcolor = imagecolorallocate($im, $rgb_text['red'],$rgb_text['green'],$rgb_text['blue']); // text color

// write the string at the top left
imagestring($im, $_GET['font-size'], $_GET['x'], $_GET['y'], $_GET['string'], $textcolor);
if($_GET['rotate'])
{
	if(function_exists(imagerotate))
	{
		$im = imagerotate($im,$_GET['rotate'] + 180,$bg);
	}
	else
	{
		$im = ImageRotateRightAngle($im, $_GET['rotate']);
	}
}

// output the image
header("Content-type: image/png");
imagepng($im);

function splitrgb($string)
{
   sscanf($string, "%2x%2x%2x", $red, $green, $blue);
   return Array('red' => $red, 'green' => $green, 'blue' => $blue);
}


// $src_img - a GD image resource
// $angle - degrees to rotate clockwise, in degrees
// returns a GD image resource
// USAGE:
// $im = imagecreatefrompng('test.png');
// $im = imagerotate($im, 15);
// header('Content-type: image/png');
// imagepng($im);
/*
function imageRotate($src_img, $angle, $bicubic=false)
{
   // convert degrees to radians
   $angle = $angle + 180;
   $angle = deg2rad($angle);
 
   $src_x = imagesx($src_img);
   $src_y = imagesy($src_img);
 
   $center_x = floor($src_x/2);
   $center_y = floor($src_y/2);

   $cosangle = cos($angle);
   $sinangle = sin($angle);

   $corners=array(array(0,0), array($src_x,0), array($src_x,$src_y), array(0,$src_y));

   foreach($corners as $key=>$value) {
     $value[0]-=$center_x;        //Translate coords to center for rotation
     $value[1]-=$center_y;
     $temp=array();
     $temp[0]=$value[0]*$cosangle+$value[1]*$sinangle;
     $temp[1]=$value[1]*$cosangle-$value[0]*$sinangle;
     $corners[$key]=$temp;   
   }
  
   $min_x=1000000000000000;
   $max_x=-1000000000000000;
   $min_y=1000000000000000;
   $max_y=-1000000000000000;
  
   foreach($corners as $key => $value) {
     if($value[0]<$min_x)
       $min_x=$value[0];
     if($value[0]>$max_x)
       $max_x=$value[0];
  
     if($value[1]<$min_y)
       $min_y=$value[1];
     if($value[1]>$max_y)
       $max_y=$value[1];
   }

   $rotate_width=round($max_x-$min_x);
   $rotate_height=round($max_y-$min_y);

   $rotate=$src_img;//imagecreatetruecolor($rotate_width,$rotate_height);
//   imagealphablending($rotate, false);
//   imagesavealpha($rotate, true);

   //Reset center to center of our image
   $newcenter_x = ($rotate_width)/2;
   $newcenter_y = ($rotate_height)/2;

   for ($y = 0; $y < ($rotate_height); $y++) {
     for ($x = 0; $x < ($rotate_width); $x++) {
       // rotate...
       $old_x = round((($newcenter_x-$x) * $cosangle + ($newcenter_y-$y) * $sinangle)) + $center_x;
       $old_y = round((($newcenter_y-$y) * $cosangle - ($newcenter_x-$x) * $sinangle)) + $center_y;
    
       if ( $old_x >= 0 && $old_x < $src_x
             && $old_y >= 0 && $old_y < $src_y ) {

           $color = imagecolorat($src_img, $old_x, $old_y);
       } else {
         // this line sets the background colour
         $color = imagecolorallocatealpha($src_img, 255, 255, 150, 0);
       }
       imagesetpixel($rotate, $x, $y, $color);
     }
   }
  
  return($rotate);
}
*/

function imageRotateBicubic($src_img, $angle, $bicubic=false) {
  
   // convert degrees to radians
   $angle = $angle + 180;
   $angle = deg2rad($angle);
  
   $src_x = imagesx($src_img);
   $src_y = imagesy($src_img);
  
   $center_x = floor($src_x/2);
   $center_y = floor($src_y/2);
  
   $rotate = imagecreatetruecolor($src_x, $src_y);
   imagealphablending($rotate, false);
   imagesavealpha($rotate, true);

   $cosangle = cos($angle);
   $sinangle = sin($angle);
  
   for ($y = 0; $y < $src_y; $y++) {
     for ($x = 0; $x < $src_x; $x++) {
   // rotate...
   $old_x = (($center_x-$x) * $cosangle + ($center_y-$y) * $sinangle)
     + $center_x;
   $old_y = (($center_y-$y) * $cosangle - ($center_x-$x) * $sinangle)
     + $center_y;
  
   if ( $old_x >= 0 && $old_x < $src_x
         && $old_y >= 0 && $old_y < $src_y ) {
     if ($bicubic == true) {
       $sY  = $old_y + 1;
       $siY  = $old_y;
       $siY2 = $old_y - 1;
       $sX  = $old_x + 1;
       $siX  = $old_x;
       $siX2 = $old_x - 1;
      
       $c1 = imagecolorsforindex($src_img, imagecolorat($src_img, $siX, $siY2));
       $c2 = imagecolorsforindex($src_img, imagecolorat($src_img, $siX, $siY));
       $c3 = imagecolorsforindex($src_img, imagecolorat($src_img, $siX2, $siY2));
       $c4 = imagecolorsforindex($src_img, imagecolorat($src_img, $siX2, $siY));
      
       $r = ($c1['red']  + $c2['red']  + $c3['red']  + $c4['red']  ) << 14;
       $g = ($c1['green'] + $c2['green'] + $c3['green'] + $c4['green']) << 6;
       $b = ($c1['blue']  + $c2['blue']  + $c3['blue']  + $c4['blue'] ) >> 2;
       $a = ($c1['alpha']  + $c2['alpha']  + $c3['alpha']  + $c4['alpha'] ) >> 2;
       $color = imagecolorallocatealpha($src_img, $r,$g,$b,$a);
     } else {
       $color = imagecolorat($src_img, $old_x, $old_y);
     }
   } else {
         // this line sets the background colour
     $color = imagecolorallocatealpha($src_img, 255, 255, 255, 127);
   }
   imagesetpixel($rotate, $x, $y, $color);
     }
   }
   return $rotate;
}

// $imgSrc - GD image handle of source image
// $angle - angle of rotation. Needs to be positive integer
// angle shall be 0,90,180,270, but if you give other it
// will be rouned to nearest right angle (i.e. 52->90 degs,
// 96->90 degs)
// returns GD image handle of rotated image.
function ImageRotateRightAngle( $imgSrc, $angle )
{
   // ensuring we got really RightAngle (if not we choose the closest one)
   $angle = min( ( (int)(($angle+45) / 90) * 90), 270 );

   // no need to fight
   if( $angle == 0 )
       return( $imgSrc );

   // dimenstion of source image
   $srcX = imagesx( $imgSrc );
   $srcY = imagesy( $imgSrc );

   switch( $angle )
       {
       case 90:
           $imgDest = imagecreatetruecolor( $srcY, $srcX );
           for( $x=0; $x<$srcX; $x++ )
               for( $y=0; $y<$srcY; $y++ )
                   imagecopy($imgDest, $imgSrc, $srcY-$y-1, $x, $x, $y, 1, 1);
           break;

       case 180:
           $imgDest = ImageFlip( $imgSrc, IMAGE_FLIP_BOTH );
           break;

       case 270:
           $imgDest = imagecreatetruecolor( $srcY, $srcX );
           for( $x=0; $x<$srcX; $x++ )
               for( $y=0; $y<$srcY; $y++ )
                   imagecopy($imgDest, $imgSrc, $y, $srcX-$x-1, $x, $y, 1, 1);
           break;
       }

   return( $imgDest );
}

?> 