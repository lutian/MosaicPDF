<?php

@set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require('fpdf.php');

class Mosaic extends FPDF
{
	var $extgstates;
	var $format = array('$w','$h');
	
	private $folders_config = array(
			'pdf' => 'origin/temp/example.pdf',
			'temppath' => 'origin/temp/',
			'background' => array(
				'originpath' => 'origin/',
				'file' => 'bg.jpg',
				'alpha' => 55,
				'ratio' => 2.834,
			),
			'images' => array(
				'cols' => 15,
				'rows' => 15,
				'alpha' => 55,
				'ratio' => 2.834,
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

	function Mosaic($orientation='P',$unit='mm',$format='A4')
	{
		parent::fpdf($orientation,$unit,$format);

	}

	// alpha: real value from 0 (transparent) to 1 (opaque)
	function SetAlpha($alpha, $bm='Normal')
	{
		$gs = $this->AddExtGState(array('ca'=>$alpha, 'CA'=>$alpha, 'BM'=>'/'.$bm));
		$this->SetExtGState($gs);
	}

	function AddExtGState($parms)
	{
		$n = count($this->extgstates)+1;
		$this->extgstates[$n]['parms'] = $parms;
		return $n;
	}

	function SetExtGState($gs)
	{
		$this->_out(sprintf('/GS%d gs', $gs));
	}

	function _enddoc()
	{
		if(!isset($this->extgstates) && $this->PDFVersion<'1.4')
			$this->PDFVersion='1.4';
		parent::_enddoc();
	}

	function _putextgstates()
	{
		for ($i = 1; $i <= count($this->extgstates); $i++)
		{
			$this->_newobj();
			$this->extgstates[$i]['n'] = $this->n;
			$this->_out('<</Type /ExtGState');
			foreach ($this->extgstates[$i]['parms'] as $k=>$v)
				$this->_out('/'.$k.' '.$v);
			$this->_out('>>');
			$this->_out('endobj');
		}
	}

	function _putresourcedict()
	{
		parent::_putresourcedict();
		$this->_out('/ExtGState <<');
		foreach($this->extgstates as $k=>$extgstate)
			$this->_out('/GS'.$k.' '.$extgstate['n'].' 0 R');
		$this->_out('>>');
	}

	function _putresources()
	{
		$this->_putextgstates();
		parent::_putresources();
		if (!empty($this->javascript)) {
			$this->_putjavascript();
		}
	}
	
	function setConfig($settings) {
		$this->folders_config = $settings;
	}
	
	function checkFolder() {
		if (!file_exists($this->folders_config['temppath'])) {
			mkdir($this->folders_config['temppath'], 0777, true);
		}
	}
	
	function createMosaic() {
		// initialize X and Y 
		$xOrd=0;
		$yOrd=0;

		// if background image exists create pdf
		if(is_file($this->folders_config['background']['originpath'].$this->folders_config['background']['file'])) {
			// verify if temp folder exists
			$this->checkFolder();
			
			// get background image size
			$size = @getimagesize($this->folders_config['background']['originpath'].$this->folders_config['background']['file']);
			$wBg = $size[0];
			$hBg = $size[1];
			
			// define width and height of each image of mosaic 
			$wPic= round($this->w/$this->folders_config['images']['cols'],6); 
			$hPic= round($this->h/$this->folders_config['images']['rows'],6); 
			
			$xIni = 0; 
			
			// define alpha for all images of mosaic
			$this->SetAlpha($this->folders_config['images']['alpha']/100);
			
			// get the images quantity
			$qImages = ($this->folders_config['images']['cols']*$this->folders_config['images']['rows']);
			
			// loop to create images
			for($i=0;$i<$qImages;$i++) {
				
				if($xOrd > $this->folders_config['images']['cols']-1) { 
					$xOrd = 0;
					$yOrd += 1;
				}
				// if quantity of images defined are less than that you need to create mosaic repeat some of them randomically
				if(count($this->folders_config['images']['files']) < $qImages) {
					$rand_num = rand(0,count($this->folders_config['images']['files'])-1);
					$pic = $this->folders_config['images']['files'][$rand_num];
				} else {
					$pic = $this->folders_config['images']['files'][$i];
				}
				// if file exists crop it and create a new one to add to pdf
				if(is_file($this->folders_config['images']['originpath'].$pic)) {
					$this->put_crop_img(($xIni+($wPic*$xOrd)),(0+($hPic*$yOrd)),$pic,$this->folders_config['images']['originpath'],$this->folders_config['temppath'],'small',$wPic*$this->folders_config['images']['ratio'],$hPic*$this->folders_config['images']['ratio'],$this->folders_config['images']['ratio']);
				}
				$xOrd ++;
			}
			
			// define alpha for background
			$this->SetAlpha($this->folders_config['background']['alpha']/100);
			// crop background and add to pdf
			$this->put_crop_img($xIni,0,$this->folders_config['background']['file'],$this->folders_config['background']['originpath'],$this->folders_config['temppath'],'big',$this->w*$this->folders_config['background']['ratio'],$this->h*$this->folders_config['background']['ratio'],$this->folders_config['background']['ratio']);
			$this->SetAlpha(1);
			// save pdf
			$this->Output($this->folders_config['pdf']); 
		}

	}

	// crop image and add to pdf file
    function put_crop_img($x,$y,$file,$originpath,$destpath,$type='big',$imgW_New=500,$imgH_New=725,$ratio=1,$url='') {

		 $jpegqual = 100; 
		 $gdversion = 2; 
		 $size = @getimagesize($originpath.$file);
		  
		 if($type=="small") {
			$maxwinw = ($imgW_New < $size[0])? 75:$imgW_New; 
			$maxwinh = ($imgH_New < $size[1])? 75:$imgH_New; 
		 } elseif($type=="big") {
			$maxwinw = ($imgW_New < $size[0])? $size[0]:$imgW_New; 
			$maxwinh = ($imgH_New < $size[1])? $size[1]:$imgH_New; 
		 }

		 $trueW=$size[0];
		 $trueH=$size[1];

		  if (($maxwinh/$maxwinw) > ($trueH/$trueW)){
			 $imgH = $maxwinh;
			 $imgW = ($maxwinh / $trueH) * $trueW;
			 $imgProp = $trueH / $imgH;
			 $imgS = $imgW_New - 2;
		  } else {
			 $imgW = $maxwinw;
			 $imgH = ($maxwinw / $trueW) * $trueH;
			 $imgProp = $trueW / $imgW;
			 $imgS = $imgW_New - 2;
		  }

		 $im = $this->CR_make_crop(($imgW/2)-($imgW_New/2),($imgH/2)-($imgH_New/2),$imgS,$imgW_New,$imgH_New,$originpath.$file,$imgProp,$gdversion);
		 if(!is_file($destpath.$file)) imagejpeg($im,$destpath.$file,$jpegqual);
		 
		 $this->Image($destpath.$file,$x,$y,$imgW_New/$ratio,$imgH_New/$ratio,'',$url);
		 
		 imagedestroy($im);
		 unlink($destpath.$file);
    	
    }
	
	function CR_make_crop($l,$t,$s,$w,$h,$filepath,$imgProp,$gdversion){
     
		$l1 = ceil($imgProp * $l);
		$t1 = ceil($imgProp * $t);
		$s1 = ceil($imgProp * $s);
		$s2 = ceil(($h / $w)* $s1);
		$img = imagecreatefromjpeg ($filepath);
		if ($gdversion == 2){
			$new = imagecreatetruecolor($w,$h); 
		} else {
			$new = imagecreate($w,$h);
		}
		$fond = imagecolorallocatealpha($new, 255, 255, 255, 0);
		imagefill($new, 0, 0, $fond);
		imagecopyresampled ($new, $img, 0, 0, $l1, $t1, $w, $h, $s1, $s2);
		imagedestroy($img);
		return $new;
	}

	// PREFERENCES

    var $DisplayPreferences='';

    function DisplayPreferences($preferences) {
	$this->DisplayPreferences.=$preferences;
    }

    function _putcatalog()
    {
	parent::_putcatalog();
	if(is_int(strpos($this->DisplayPreferences,'FullScreen')))
		$this->_out('/PageMode /FullScreen');
		if($this->DisplayPreferences) {
			$this->_out('/ViewerPreferences<<');
			if(is_int(strpos($this->DisplayPreferences,'HideMenubar')))
				$this->_out('/HideMenubar true');
			if(is_int(strpos($this->DisplayPreferences,'HideToolbar')))
				$this->_out('/HideToolbar true');
			if(is_int(strpos($this->DisplayPreferences,'HideWindowUI')))
				$this->_out('/HideWindowUI true');
			if(is_int(strpos($this->DisplayPreferences,'DisplayDocTitle')))
				$this->_out('/DisplayDocTitle true');
			if(is_int(strpos($this->DisplayPreferences,'CenterWindow')))
				$this->_out('/CenterWindow true');
			if(is_int(strpos($this->DisplayPreferences,'FitWindow')))
				$this->_out('/FitWindow true');
			$this->_out('>>');
		}
    }

}

	

