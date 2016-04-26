<?php

$mysqli = new mysqli("127.0.0.1", "root", "");
if ($mysqli->connect_errno){
	echo "faild connect";
	exit();
}
$mysqli->select_db("image");
$resize_width = 20;
$resize_height = 20;

$file_path = "/Users/usr0600438/Documents/images/";
$images = get_file_list($file_path);
foreach($images as $image_path){
	$image = imagecreatefromjpeg($image_path);
	if ($image === FALSE) continue;
	$width = imagesx($image);
	$height = imagesy($image);

	$resize = imagecreatetruecolor($resize_width, $resize_height);
	imagecopyresized($resize, $image, 0, 0, 0, 0, $resize_width, $resize_height, $width, $height);
		
	$hist = array();
	for ($i = 0; $i < 64; $i++){
		$hist[$i] = 0;
	}

	for ($y = 0; $y < $resize_height; $y++){
		for ($x = 0; $x < $resize_width; $x++){
			$color_index = imagecolorat($resize, $x, $y);
			$color = imagecolorsforindex($resize, $color_index);

			$r = floor($color['red'] / 64);
			$g = floor($color['green'] / 64);
			$b = floor($color['blue'] / 64);

			$bin = $r * 16 + $g * 4 + $b;
			$hist[$bin]++;
		}
	}
	var_dump($hist);
	exit();

	$query = build_insert_query($image_path, $hist);
	if (!$mysqli->query($query)){
		echo $mysqli->error . "\n";
		exit();
	}
	
}


function build_insert_query($path, $bins){
	$query = "insert into image (path, ";
	for ($i = 1; $i <= 64; $i++){
		$query .= "bin_${i},";
	}
	$query = trim($query, ",");

	$query .= ") VALUES ('${path}',";
	foreach($bins as $value){
		$query .= "${value},";
	}
	$query = trim($query, ",") . ")";
	return $query;
}

function get_file_list($dir){
	$files = scandir($dir);
	$images =array();

	foreach($files as $item){
		if ($item != "." && $item != ".."){
			$images[] = $dir .  $item;
		}
	}
	return $images;

}
