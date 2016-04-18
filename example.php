<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

// call class
require('mosaic.php');

// define params
$arrImages = array(
	'pdf' => 'origin/temp/example.pdf',
	'temppath' => 'origin/temp/',
	'background' => array(
		'originpath' => 'origin/',
		'file' => 'bg.jpg',
		'alpha' => 45,
		'ratio' => 72/25.4,
	),
	'images' => array(
		'cols' => 10,
		'rows' => 15,
		'alpha' => 55,
		'ratio' => 72/25.4,
		'originpath' => 'origin/images/',
		'files' => array(
			'1.jpg',
			'2.jpg',
			'3.jpg',
			'4.jpg',
			'5.jpg',
		)
	),
);

// initialize class
$pdf=new Mosaic( 'p', 'mm', array('210','297') );
$pdf->SetDisplayMode('fullpage');
$pdf->AddPage();

// initialize X and Y 
$xOrd=0;
$yOrd=0;

// if background image exists create pdf
if(is_file($arrImages['background']['originpath'].$arrImages['background']['file'])) {
	// get background image size
	$size = @getimagesize($arrImages['background']['originpath'].$arrImages['background']['file']);
	$wBg = $size[0];
	$hBg = $size[1];
	
	// define width and height of each image of mosaic 
	$wPic= round($pdf->w/$arrImages['images']['cols'],6); 
	$hPic= round($pdf->h/$arrImages['images']['rows'],6); 
	
	$xIni = 0; 
	
	// define alpha for all images of mosaic
	$pdf->SetAlpha($arrImages['images']['alpha']/100);
	
	// get the images quantity
	$qImages = ($arrImages['images']['cols']*$arrImages['images']['rows']);
	
	// loop to create images
	for($i=0;$i<$qImages;$i++) {
		
		if($xOrd > $arrImages['images']['cols']-1) { 
			$xOrd = 0;
			$yOrd += 1;
		}
		// if quantity of images defined are less than that you need to create mosaic repeat some of them randomically
		if(count($arrImages['images']['files']) < $qImages) {
			$rand_num = rand(0,count($arrImages['images']['files'])-1);
			$pic = $arrImages['images']['files'][$rand_num];
		} else {
			$pic = $arrImages['images']['files'][$i];
		}
		// if file exists crop it and create a new one to add to pdf
		if(is_file($arrImages['images']['originpath'].$pic)) {
			$pdf->put_crop_img(($xIni+($wPic*$xOrd)),(0+($hPic*$yOrd)),$pic,$arrImages['images']['originpath'],$arrImages['temppath'],'small',$wPic*$arrImages['images']['ratio'],$hPic*$arrImages['images']['ratio'],$arrImages['images']['ratio']);
		}
		$xOrd ++;
	}
	
	// define alpha for background
	$pdf->SetAlpha($arrImages['background']['alpha']/100);
	// crop background and add to pdf
	$pdf->put_crop_img($xIni,0,$arrImages['background']['file'],$arrImages['background']['originpath'],$arrImages['temppath'],'big',$pdf->w*$arrImages['background']['ratio'],$pdf->h*$arrImages['background']['ratio'],$arrImages['background']['ratio']);
	$pdf->SetAlpha(1);
	// save pdf
	$pdf->Output($arrImages['pdf']); 
}


