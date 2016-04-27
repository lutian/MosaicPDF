# MosaicPDF

MosaicPDF is a PHP class that create a image mosaic in PDF format. FPDF class extended

# Usage

```php

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

// define folder configuration
$pdf->setConfig($arrImages);

// gen mosaic
$pdf->createMosaic();
```
