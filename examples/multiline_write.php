<?php
    require_once('../image.php'); // Load image class
    
    $a = new Image(); // Create new Image object
    
    $small_string = 'Hello world!';   
    $large_string = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam vestibulum velit sodales turpis feugiat dignissim pharetra dui consectetur. Aenean nec ante at enim sollicitudin ornare vel porta metus. Fusce lorem turpis, tristique nec consectetur vel, sodales cursus sapien. Curabitur in laoreet ante. ';

    // simple write small text on the image
        $a->write(100,200,'times.ttf',120,null,'#FFFFFF',$small_string);

    // write large text to the some area on the image with automaticaly word-wrap
    // and font size decreasing to fit all the text in specified box
        $config = array(
        'font' => 'times.ttf',
                'startFontSize' => 150, // If text wouldn't fit the $boxHeight, then font size will be decreased
                'stepFontSize' => 10,   // step for font size decreasing
                'angle' => null,
                'color' => '#FFFFFF',
                'lineSpacing' => null,  // if not definedm then will be startFontSize/10
                'padding' => '20 10',   // almost in css format :), acceptable two or one parameters
        );
        $box_config = array(
			'top' => 300,
			'left' => 0,
			'width' => null, // may be null, in this case will used $a->width
			'height' => 300 // may be null, in this case will used $a->height
		);
        $this->image->writeMultiline($large_string, $box_config, $config);
    
    $a->show(); // Show image
?>