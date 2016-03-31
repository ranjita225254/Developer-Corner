<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
session_start();
$md5_hash = md5(rand(0, 999));
//We don't need a 32 character long string so we trim it down to 5
$security_code = substr($md5_hash, 13, 8);
$_SESSION['captcha_code'] = $security_code;

//Set the image width and height
$width = 170;
$height = 25;

//Create the image resource
$image = ImageCreate($width, $height);

//We are making three colors, white, black and gray
$white = ImageColorAllocate($image, 255, 255, 255);
$black = ImageColorAllocate($image, 0, 0, 0);
$grey = ImageColorAllocate($image, 204, 204, 204);
$red = ImageColorAllocate($image, 255, 0, 0);
$green = ImageColorAllocate($image, 99, 99, 99);

//Make the background black
ImageFill($image, 0, 0, $green);

//Add randomly generated string in white to the image
ImageString($image, 4, 68, 7, $security_code, $white);

//Throw in some lines to make it a little bit harder for any bots to break
ImageRectangle($image, 0, 0, $width - 1, $height - 1, $grey);
//imagearc($image, 0, 0, $width, $height, 0, 45, $red);
//imageline($image, 0, $height / 2, $width, $height / 2, $white);
//imageline($image, $width / 2, 0, $width / 2, $height, $white);
//Tell the browser what kind of file is come in
header("Content-Type: image/jpeg");

//Output the newly created image in jpeg format
ImageJpeg($image);
