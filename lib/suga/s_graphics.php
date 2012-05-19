<?php


class S_Graphics extends Graphics {

	static function thumb($file, $dimx, $dimy, $die = TRUE) {
		preg_match('/\.(gif|jp[e]*g|png)$/', $file, $ext);
		if ($ext) {
			$ext[1] = str_replace('jpg', 'jpeg', $ext[1]);
			$file = self::fixslashes(self::resolve($file));
			$img = imagecreatefromstring(file_get_contents($file));
			// Get image dimensions
			$oldx = imagesx($img);
			$oldy = imagesy($img);
			// Adjust dimensions; retain aspect ratio
			$ratio = $oldx / $oldy;
			if ($dimx <= $oldx && $dimx / $ratio <= $dimy
			) // Adjust height
				$dimy = $dimx / $ratio; elseif ($dimy <= $oldy && $dimy * $ratio <= $dimx
			) // Adjust width
				$dimx = $dimy * $ratio; else {
				// Retain size if dimensions exceed original image
				$dimx = $oldx;
				$dimy = $oldy;
			}
			// Create blank image
			$tmp = imagecreatetruecolor($dimx, $dimy);
			list($r, $g, $b) = self::rgb(self::$vars['BGCOLOR']);
			$bg = imagecolorallocate($tmp, $r, $g, $b);
			imagefill($tmp, 0, 0, $bg);
			// Resize
			imagecopyresampled($tmp, $img, 0, 0, 0, 0, $dimx, $dimy, $oldx, $oldy);
			// Make the background transparent
			imagecolortransparent($tmp, $bg);
			if (PHP_SAPI != 'cli') header(self::HTTP_Content . ': image/' . $ext[1]);
			// Send output in same graphics format as original
			eval('image' . $ext[1] . '($tmp);');
		} else
			trigger_error(self::TEXT_Image);
		if ($die) die;
	}


	public static function resizecrop($file, $w, $h) {
		preg_match('/\.(gif|jp[e]*g|png)$/', $file, $ext);
		if ($ext) {
			$ext[1] = str_replace('jpg', 'jpeg', $ext[1]);
			$file = self::fixslashes(self::resolve($file));
			if (file_exists($file)){
				$in = @getimagesize($file);

				if ($in) {
					$sw = $in[0] / $w;
					$sh = $in[1] / $h;
					$s = $sw < $sh ? $sw : $sh;

					/* crop the center of the image */
					$x0 = floor(($in[0] - ($w * $s)) * 0.5);
					$y0 = floor(($in[1] - ($h * $s)) * 0.5);

					/* support JPG, PNG and GIF */
					$im = @imagecreatefromjpeg($file) or
					       $im = @imagecreatefrompng($file) or
					              $im = @imagecreatefromgif($file) or
					                     $im = false;
					if (!$im) {
						/* something went wrong, output the image */
						readfile($file);
					} else {

						header(F3::HTTP_Content . ': image/' . $ext[1]);
						$thumb = imagecreatetruecolor($w, $h);
						imagecopyresampled($thumb, $im, 0, 0, $x0, $y0, $w, $h, ($w * $s), ($h * $s));
						imagejpeg($thumb);

					}

				} else {
					Graphics::fakeImage($w, $h);
				}
			} else {
				Graphics::fakeImage($w, $h);
			}

		}


	}
}

?>
