<?php

#データベース接続
$mysqli = new mysqli("127.0.0.1", "root", "");

if ($mysqli->connect_errno){
	echo "faild connect";
	exit();
}

$mysqli->select_db("image");

#画像サイズ
$width = 1500;
$height = 1500;

#分割数
$division_number = 50;

#元画像のダウンロード
$source_image = imagecreatefromjpeg("/Users/usr0600438/Documents/antipop.jpg");
$source_width = imagesx($source_image);
$source_height = imagesy($source_image);

#リサイズ後画像生成画像の作成 300 * 300
$resized_image = imagecreatetruecolor($width, $height);
$result_image = imagecreatetruecolor($width, $height);

imagecopyresized($resized_image, $source_image, 0, 0, 0, 0, $width, $height, $source_width, $source_height);

imagedestroy($source_image);

#画像分割して似てる画像はめていく
$offset_x = floor($width / $division_number);
$offset_y = floor($height / $division_number);

echo "x: ${width} y: ${height}\n";

for($x = 0; $x < $width; $x += $offset_x){
	for($y = 0; $y < $height; $y += $offset_y){
		
		$temp = imagecreatetruecolor($offset_x, $offset_y);
		imagecopy($temp, $resized_image, 0, 0, $x, $y, $x + $offset_x, $y + $offset_y);

		$hist = caluclate_hist($temp);
		//echo $hist[32] . "\n";
		$query = build_select_query($hist);
		#echo $query . "\n\n\n";
		if ($res = $mysqli->query($query)){
			$row = $res->fetch_assoc();
			$image_path = $row['path'];
		} else {
			echo "faild\n";
			echo $mysqli->error . "\n";
			echo $query , "\n";
			exit();
		}

		$image = imagecreatefromjpeg($image_path);
		$image_width = imagesx($image);
		$image_height = imagesy($image);

		$resize = imagecreatetruecolor($offset_x, $offset_y);
		imagecopyresized($resize, $image, 0, 0, 0, 0, $offset_x, $offset_y, $image_width, $image_height);
		imagecopy($result_image, $resize, $x, $y, 0, 0, $offset_x, $offset_y);
	}
}

imagejpeg($result_image, "/Users/usr0600438/Documents/test.jpg");


function build_select_query($bins){
	#$query = "select path, min(";
	$query = "select path from image where ";

	#for ($i = 1; $i <= 64; $i++){
	#	$query .= "bin_${i}+";
#	}
	#$query = trim($query, "+") . ")=(select min(";
	$where = "";
	foreach($bins as $key => $bin){
		$no = $key + 1;
	#	$where .= "pow('bin_${no}' - ${bin}, 2)+";
		$where .= "LEAST(bin_${no}, ${bin})+"; 
		#$query .= "pow('bin_${no}' - ${bin}, 2)+";
	}
	$where = trim($where, "+");
	$query .= "(" .  $where . ")=(select max(" . $where . ") from image) order by rand() limit 1";

	#$query = trim($query, "+") . ") from image)";
	return $query;
}

function caluclate_hist($image){
	$width = imagesx($image);
	$height = imagesy($image);
			
	$hist = array_fill(0, 64, 0);
	for ($y = 0; $y < $height; $y++){
		for ($x = 0; $x < $width; $x++){
			$color_index = imagecolorat($image, $x, $y);
			$color = imagecolorsforindex($image, $color_index);

			$r = floor($color['red'] / 64);
			$g = floor($color['green'] / 64);
			$b = floor($color['blue'] / 64);

			$bin = $r * 16 + $g * 4 + $b;
			$hist[$bin]++;
		}
	}
	return $hist;
}
