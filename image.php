<?php
/**
 * Image
 *
 * Easy image handling using PHP 5  and the GDlib
 *
 * @copyright     Copyright 20010-2011, ushi <ushi@porkbox.net>
 * @link          https://github.com/ushis/PHP-Image-Class
 * @license       DBAD License (http://philsturgeon.co.uk/code/dbad-license)
 */
class Image {

	/**
	 * Image resource
	 *
	 * @var resource
	 * @acces private
	 */
	private $image;

	/**
	 * Type of image
	 *
	 * @var string
	 * @acces private
	 */
	private $type = 'png';

	/**
	 * Width of the image in pixel
	 *
	 * @var integer
	 * @acces private
	 */
	private $width;

	/**
	 * Height of the image in pixel
	 *
	 * @var integer
	 * @acces private
	 */
	private $height;

	/**
	 * Return infos about the image
	 *
	 * @return array image infos
	 * @acces public
	 */
	function getInfos($key = false) {
		
		if ($key && isset($this->{$key}))
			return $this->{$key};
		else
		return array(
			'width' => $this->width,
			'height' => $this->height,
			'type' => $this->type,
			'resource' => $this->image,
		);
	}

	/**
	 * Get color in rgb
	 *
	 * @param string $hex Color in hexadecimal code
	 * @return array Color in rgb
	 * @acces public
	 */
	function hex2rgb($hex) {
		$hex = str_replace('#', '', $hex);
		$hex = (preg_match('/^([a-fA-F0-9]{3})|([a-fA-F0-9]{6})$/', $hex)) ? $hex : '000';

		switch(strlen($hex)) {
	 		case 3:
				$rgb['r'] = hexdec(substr($hex, 0, 1).substr($hex, 0, 1));
				$rgb['g'] = hexdec(substr($hex, 1, 1).substr($hex, 1, 1));
				$rgb['b'] = hexdec(substr($hex, 2, 1).substr($hex, 2, 1));
				break;

			case 6:
				$rgb['r'] = hexdec(substr($hex, 0, 2));
				$rgb['g'] = hexdec(substr($hex, 2, 2));
				$rgb['b'] = hexdec(substr($hex, 4, 2));
				break;
		}

		return $rgb;
	}

	/**
	 * Creates image resource from file
	 *
	 * @param string $path Path to an image
	 * @return boolean true if resource was created
	 * @acces public
	 */
	function createFromFile($path) {
		$file = @fopen($path, 'r');

		if(!$file)
			return false;

		fclose($file);
		$info = getimagesize($path);

		switch($info[2]) {
			case 1:
				$this->image = imagecreatefromgif($path);
				$this->type = 'gif';
				break;

			case 2:
				$this->image = imagecreatefromjpeg($path);
				$this->type = 'jpg';
				break;

			case 3:
				$this->image = imagecreatefrompng($path);
				$this->type = 'png';
				imagealphablending($this->image, false);
				imagesavealpha($this->image,true);
				break;

			default:
				return false;
		}

		$this->width = $info[0];
		$this->height = $info[1];
		return true;
	}


	/**
	 * Creates image resource with background
	 *
	 * @param integer $width Width of the image
	 * @param integer $height Height of the image
	 * @param string $background Background color in hexadecimal code
	 * @return boolean true if resource was created
	 * @acces public
	 */
	function create($width, $height, $background = null) {
		if($width > 0 && $height > 0) {
			$this->image = imagecreatetruecolor($width, $height);
			$this->width = $width;
			$this->height = $height;

			if(preg_match('/^([a-fA-F0-9]{3})|([a-fA-F0-9]{6})$/', $background)) {
				$rgb = $this->hex2rgb($background);
				$background = imagecolorallocate($this->image, $rgb['r'], $rgb['g'], $rgb['b']);
				imagefill($this->image, 0, 0, $background);
			} else {
				imagealphablending($this->image, false);
				$black = imagecolorallocate($this->image, 0, 0, 0);
				imagefilledrectangle($this->image, 0, 0, $this->width, $this->height, $black);
				imagecolortransparent($this->image, $black);
				imagealphablending($this->image, true);
			}

			return true;
		}
		
		return false;
	}


	/**
	 * Resizes the image
	 *
	 * @param integer $width New width
	 * @param integer $height New height
	 * @return boolean true if image was resized
	 * @acces public
	 */
	function resize($width, $height) {
		if($width <= 0 && $height <= 0)
			return false;
		elseif($width > 0 && $height <= 0)
			$height = $this->height*$width/$this->width;
		elseif($width <= 0 && $height > 0)
			$width = $this->width*$height/$this->height;

		$image = imagecreatetruecolor($width, $height);
		imagealphablending($image, false);
		imagesavealpha($image, true);
		imagecopyresampled($image, $this->image, 0, 0, 0, 0, $width, $height, $this->width,$this->height);
		$this->image = $image;
		$this->width = $width;
		$this->height = $height;
		return true;
	}

	
	/**
	 * Resizes the image
	 *
	 * @param integer $width New width
	 * @param integer $height New height
	 * @return boolean true if image was resized
	 * @acces public
	 */
	function resizeToMax($max_width, $max_height) {
		
		$current_width = $this->width;
		$current_height = $this->height;		

		if($current_width > $max_width or $current_height > $max_height)
		{
			while($current_width > $max_width or $current_height > $max_height)
			{
				$current_width--;
				$current_height -= $current_height/$current_width;
			}

			return $this->resize($current_width,floor($current_height));
		}
		else
			return true;

	}
	
	
	/**
	 * Crops a part of the image
	 *
	 * @param integer $x X-coordinate
	 * @param integer $y Y-coordinate
	 * @param integer $width Width of cutout
	 * @param integer $height Height of cutout
	 * @acces public
	 */
	function crop($x, $y, $width, $height) {
		$image = imagecreatetruecolor($width, $height);
		imagealphablending($image, false);
		imagesavealpha($image, true);
		imagecopyresampled($image, $this->image, 0, 0, $x, $y, $width, $height, $width, $height);
		$this->image = $image;
		$this->width = $width;
		$this->height = $height;
	}

	/**
	 * Rotates image
	 *
	 * @param integer $angle in degree
	 * @acces public
	 */
	function rotate($angle) {
		$this->image = imagerotate($this->image, $angle, 0);
	}

	/**
	 * Creates rectangle
	 *
	 * @param integer $x1 X1-coordinate
	 * @param integer $y1 Y1-coordinate
	 * @param integer $x2 X2-coordinate
	 * @param integer $y2 Y2-coordinate
	 * @param string $color Color in hexadecimal code
	 * @acces public
	 */
	function rectangle($x1, $y1, $x2, $y2, $color) {
		$rgb = $this->hex2rgb($color);
		$color = imagecolorallocate($this->image, $rgb['r'], $rgb['g'], $rgb['b']);
		imagefilledrectangle($this->image, $x1, $y1, $x2, $y2, $color);
	}

	/**
	 * Creates ellipse
	 *
	 * @param integer $x X-coordinate
	 * @param integer $y Y-coordinate
	 * @param integer $width Width of ellipse
	 * @param integer $height Height of ellipse
	 * @param string $color Color in hexadecimal code
	 * @acces public
	 */
	function ellipse($x, $y, $width, $height, $color) {
		$rgb = $this->hex2rgb($color);
		$color = imagecolorallocate($this->image, $rgb['r'], $rgb['g'], $rgb['b']);
		imagefilledellipse($this->image, $x, $y, $width, $height, $color);
	}

	/**
	 * Creates polygon
	 *
	 * @param array $points Coordinates of the vertices
	 * @param string $color Color in hexadecimal code
	 * @acces public
	 */
	function polygon($points, $color) {
		$rgb = $this->hex2rgb($color);
		$color = imagecolorallocate($this->image, $rgb['r'], $rgb['g'], $rgb['b']);
		$num = count($points)/2;
		imagefilledpolygon($this->image, $points, $num, $color);
	}

	/**
	 * Draws a line
	 *
	 * @param array $points Coordinates of the vertices
	 * @param string $color Color in hexadecimal code
	 * @acces public
	 */
	function line($points, $color) {
		$rgb = $this->hex2rgb($color);
		$color = imagecolorallocate($this->image, $rgb['r'], $rgb['g'], $rgb['b']);
		imageline($this->image, $points[0], $points[1], $points[2], $points[3], $color);
	}

	/**
	 * Writes on image
	 *
	 * @param integer $x X-coordinate
	 * @param integer $y Y-coordinate
	 * @param string $font Path to ttf
	 * @param integer $size Font size
	 * @param integer $angle in degree
	 * @param string $color Color in hexadecimal code
	 * @param string $text Text
	 * @param string $textAlign align of text: left, right, center
	 * @param integer $boxWidth, equal to $this->width by default
	 * @param integer $boxHeight, equal to $this->height by default
	 * @acces public
	 */
	function write($x, $y, $font, $size, $angle, $color, $text, $textAlign = 'left', $boxWidth = null, $boxHeight = null) {
		
		$boxWidth = !is_null($boxWidth) ? $boxWidth : $this->width;
		$boxHeight = !is_null($boxHeight) ? $boxHeight : $this->height;
		
		$text_coord = $this->getTextSize($font, $size, $angle, $text);
		
		switch($textAlign)
		{
			case 'right':
				$new_x = $x + $boxWidth - $text_coord['width'];
			break;
		
			case 'center':
				$new_x = $x + round( ($boxWidth-$text_coord['width']) /2);
			break;

		}
		
		
		$rgb = $this->hex2rgb($color);
		$color = imagecolorallocate($this->image, $rgb['r'], $rgb['g'], $rgb['b']);
		return imagettftext($this->image, $size, $angle, $new_x, $y, $color, $font, $text);
	}
	
	
	
	

	/**
	 * Merges image with another
	 *
	 * @param Image $img object
	 * @param mixed $x X-coordinate
	 * @param mixed $y Y-coordinate
	 * @acces public
	 */
	function merge($img, $x, $y) {
		$infos = $img->getInfos();

		switch($x) {
			case 'left':
				$x = 0;
				break;

			case 'right':
				$x = $this->width-$infos['width'];
				break;

			default:
				$x = $x;
		}

		switch($y) {
			case 'top':
				$y = 0;
				break;

			case 'bottom':
				$y = $this->height-$infos['height'];
				break;

			default:
				$y = $y;
		}

		imagealphablending($this->image, true);
		imagecopy($this->image, $infos['resource'], $x, $y, 0, 0, $infos['width'], $infos['height']);
	}

	/**
	 * Shows image
	 *
	 * @param string $type Filetype
	 * @acces public
	 */
	function show($type = 'png') {
		$type = ($type != 'gif' && $type != 'jpeg' && $type != 'png') ? $this->type : $type;

		switch($type) {
			case 'gif':
				header('Content-type: image/gif');
				imagegif($this->image);
				break;

			case 'jpeg':
				header('Content-type: image/jpeg');
				imagejpeg($this->image, '', 100);
				break;

			default:
				header('Content-type: image/png');
				imagepng($this->image);
		}
	}

	/**
	 * Saves image
	 *
	 * @param string $path Path to location
	 * @param string $type Filetype
	 * @return boolean true if image was saved
	 * @acces public
	 */
	function save($path) {
		$dir = dirname($path);
		$type = pathinfo($path, PATHINFO_EXTENSION);

		if(!file_exists($dir) || !is_dir($dir))
			return false;

		if(!is_writable($dir))
			return false;

		if($type != 'gif' && $type != 'jpeg' && $type != 'jpg' && $type != 'png') {
			$type = $this->type;
			$path .= '.'.$type;
		}

		switch($type) {
			case 'gif':
				imagegif($this->image, $path);
				break;

			case 'jpeg': case 'jpg':
				imagejpeg($this->image, $path, 100);
				break;

			default:
				imagepng($this->image, $path);
		}
		
		return true;
	}
	

	/**
	 * Saves image temporary
	 *
	 * @return boolean true if image was saved
	 * @acces public
	 */
	function saveTemporary() {
		
		$new_path = tempnam(null,'');
		
		if (imagepng($this->image, $new_path))
			return $new_path;
		else
			return false;
	}	
	
	
	/**
	 * Get text size
	 *
	 * @param string $font Path to ttf
	 * @param integer $size Font size
	 * @param integer $angle in degree
	 * @param string $text Text
	 * @acces public
	 */
	public function getTextSize($font, $size, $angle, $text) {
		
		$_coords = imagettfbbox($size, $angle, $font, $text);

		$coords['blx'] = $_coords[0];
		$coords['bly'] = $_coords[1];
		$coords['brx'] = $_coords[2];
		$coords['bry'] = $_coords[3];
		$coords['trx'] = $_coords[4];
		$coords['try'] = $_coords[5];
		$coords['tlx'] = $_coords[6];
		$coords['tly'] = $_coords[7];
		
		$coords['width'] = $coords['trx'] - $coords['tlx'];
		$coords['height'] = $coords['bly'] - $coords['tly'];

		return $coords;
	}
	
	
	
	
	/**
	 * Writes on image
	 *
	 * @param string $text Text
	 * @param array $boxParams Text
	  		'left' => integer, X-coordinate
	  		'top' => integer, Y-coordinate
	  		'width' => integer, width of the box with text
	  		'height' => integer, height of the box with text
	 * @param array $params
	  		'font' => string, path to font
			'startFontSize' => integer, font size for starting. If text wouldn't fit the height, then font size will be decreased
			'stepFontSize' => integer, step for font size decreasing
			'angle' => font angle
			'color' => conf color
			'lineSpacing' => integer, spacing between lines
			'padding' => string in format: '20 10', where first - padding for top and bottom, and second - for left and right
			'text-align' => string, align of text - left, right, center
	 * @acces public
	 */
	public function writeMultiline($text, $boxParams = array(), $params = array())
	{
		$config = array(
			'font' => ( isset($params['font']) ? $params['font'] : null ),
			'startFontSize' => ( isset($params['startFontSize']) ? $params['startFontSize'] : 12 ),
			'stepFontSize' => ( isset($params['stepFontSize']) ? $params['stepFontSize'] : 1 ),
			'angle' => ( isset($params['angle']) ? $params['angle'] : null ),
			'color' => ( isset($params['color']) ? $params['color'] : '#000000' ),
			'lineSpacing' => ( isset($params['lineSpacing']) ? $params['lineSpacing'] : null ),
			'padding' => ( isset($params['padding']) ? $params['padding'] : '0 0' ),
			'text-align' => ( isset($params['text-align']) ? $params['text-align'] : 'left' )
		);
		
		$boxConfig = array(
			'left' => ( isset($boxParams['left']) ? $boxParams['left'] : 0 ),
			'top' => ( isset($boxParams['top']) ? $boxParams['top'] : 0 ),
			'width' => ( isset($boxParams['width']) ? $boxParams['width'] : 0 ),
			'height' => ( isset($boxParams['height']) ? $boxParams['height'] : 0 ),
		);
		
		// parsing padding
			$padding = array();
			$config['padding'] = str_replace('px','',$config['padding']);
			if (strstr($config['padding'],' '))
				list($padding['top_bottom'],$padding['left_right']) = explode(' ',$config['padding']);
			else
			{
				$padding['top_bottom'] = $config['padding'];
				$padding['left_right'] = $config['padding'];
			}
		
		// if lineSpacing not defined
			if (is_null($config['lineSpacing']))
			{
				$config['lineSpacing'] = ceil($config['startFontSize']/10);
			}
	
		// box width with padding
			$boxWidth = !is_null($boxConfig['width']) ? $boxConfig['width'] : $this->width;
			$boxWidth = $boxConfig['width'] - $padding['left_right']*2;
	
		// box height with padding
			$boxHeight = !is_null($boxConfig['height']) ? $boxConfig['height'] : $this->height;
			$boxHeight = $boxConfig['height'] - $padding['top_bottom']*2;
		
		$words = explode(' ',$text);
		$forever = true;
		
		while($forever)
		{
			$lines = array();
			$i = 0;
			$line_length = 0;
			$lines_height = 0;

			foreach($words as $k => $word)
			{
				if (!isset($lines[$i]['string']))
				{
					$lines[$i]['string'] = '';
					$lines[$i]['words'] = array();
					$lines[$i]['width'] = 0;
					$lines[$i]['height'] = 0;
				}
				
				$word = $word.' ';
				
				$previous = $this->getTextSize($config['font'], $config['startFontSize'], $config['angle'], $lines[$i]['string']);
				$current = $this->getTextSize($config['font'], $config['startFontSize'], $config['angle'], $word);

				if ($previous['width'] + $current['width'] > $boxWidth)
				{
					$lines[$i]['fontSize'] = $config['startFontSize'];
					$lines[$i]['width'] = $previous['width'];
					$lines[$i]['height'] = $previous['height'];

					$i++;
					
					// new line
						$lines[$i]['string'] = $word;
						$lines[$i]['fontSize'] = $config['startFontSize'];
						$lines[$i]['words'] = array(trim($word));
						$lines[$i]['width'] = $current['width'];
						$lines[$i]['height'] = $current['height'];
					
				}
				else
				{
					$lines[$i]['words'][] = trim($word);
					$lines[$i]['string'] .= $word;
				}
				
			}

			// check height of lines
				$lines_height = ($config['startFontSize'] + $config['lineSpacing']) * ($i+1);
				if ($lines_height > $boxHeight)
				{
					// decreasing font size
					$config['startFontSize'] -= $config['stepFontSize'];
					continue;
				}
				else
				{
					// chack width bounds
					$widthToLarge = false;
					foreach($lines as $line)
					{
						if ($line['width'] > $boxWidth)
						{
							$config['startFontSize'] -= $config['stepFontSize'];
							$widthToLarge = true;
						}
					}
					
					if ($widthToLarge)
					{
						// decreasing font size
						$config['startFontSize'] -= $config['stepFontSize'];
						continue;
					}
					else
						break;
				}

		}
		
		// start real writing to image
			$startY = $boxConfig['top'] + $padding['top_bottom'] + $config['startFontSize'];
			
			if ($boxHeight > $lines_height)
				$startY += (abs($boxHeight-$lines_height)/2);
				

			foreach($lines as $line)
			{
				$this->write($boxConfig['left'], $startY, $config['font'], $line['fontSize'], $config['angle'], $config['color'], $line['string'], $config['text-align'], $boxWidth, $boxHeight);
				$startY += $line['height'];
			}
	}
	
	
	
}
?>
