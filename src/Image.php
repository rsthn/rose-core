<?php
/*
**	Rose\Image
**
**	Copyright (c) 2018-2020, RedStar Technologies, All rights reserved.
**	https://rsthn.com/
**
**	THIS LIBRARY IS PROVIDED BY REDSTAR TECHNOLOGIES "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
**	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A 
**	PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL REDSTAR TECHNOLOGIES BE LIABLE FOR ANY
**	DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT 
**	NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; 
**	OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, 
**	STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
**	USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

namespace Rose;

use Rose\IO\Path;
use Rose\Errors\Error;

/*
**	Provides an interface to manipulate images. Supports JPG, PNG and GIF files.
*/

class Image
{
	/*
	**	Image object. Set after a call to the load() method.
	*/
	private $image;

	/*
	**	Type of the image. Used only by the save() method when no parameters are specified.
	*/
	private $type;

	/*
	**	Filename used by the load() method. Used by the save() method to save the image to the original file.
	*/
    private $filename;

	/*
	**	Flag constants for the smartcut method (onTooWide and onTooTall).
	*/
    public const CENTER 	=	+0;
    public const LEFT		=	-1;
    public const RIGHT		=	+1;
    public const TOP		=	-1;
    public const BOTTOM		=	+1;

	/*
	**	Creates an image instance. Loads the given filename if not null.
	*/
    public function __construct (string $filename=null)
    {
        $this->type = 'png';
		$this->filename = 'output.png';

		if ($filename != null && Path::exists($filename))
			$this->load($filename);
    }

	/*
	**	Prepares the image instance and sets to the specified size.
	*/
    public function prepare (int $width, int $height)
    {
        $this->image = imagecreatetruecolor($width, $height);
        return $this;
    }

	/*
	**	Changes the internal image resource.
	*/
    private function setRes ($image)
    {
        $this->image = $image;
        return $this;
    }

	/*
	**	Creates an image object from the given image resource.
	*/
    public static function fromRes ($image)
    {
        return (new Image())->setRes($image);
    }

	/*
	**	Loads an image from the given file, throws an exception if the file type is unsupported,
	**	or if there was an error while trying to load information about the image.
	*/
    public function load (string $source)
    {
        $p = getimagesize ($this->filename = $source);
        if (!$p) {
			throw new Error ('Unable to get image information.');
		}

        switch ($p[2])
        {
            case 1:
            	$this->image = imagecreatefromgif ($source);
            	$this->type = 'gif';
				break;

            case 2:
            	$this->image = imagecreatefromjpeg ($source);
            	$this->type = 'jpg';
				break;

            case 3:
            	$this->image = imagecreatefrompng ($source);
            	$this->type = 'png';
				break;

            case 6:
            	$this->image = imagecreatefrombmp ($source);
            	$this->type = 'bmp';
				break;

            default:
 	           throw new Error ('Unsupported image format: '.$p[2]);
		}

        return $this;
    }

	/*
	**	Saves the image to the given target file, throws an exception if the file type is unsupported, or if there was an
	**	error while trying to save. If no target specified the filename used in load() will be used.
	*/
    public function save (string $target=null, string $type=null)
    {
        if ($target == null)
			$target = $this->filename;

        if ($type == null)
			$type = $this->type;

        imagealphablending ($this->image, false);
		imagesavealpha ($this->image, true);

        switch ($type)
        {
            case 'jpg':
            	imagejpeg ($this->image, $target, 95);
				break;

            case 'gif':
            	imagegif ($this->image, $target);
				break;

            case 'png':
            	imagepng ($this->image, $target);
				break;

            default:
            	throw new error ('Unsupported output format: '.$type);
        }
    }

	/*
	**	Outputs the image to the browser. This method will send header information, therefore it is required
	**	that no data had been output when called.
	*/
    public function output (string $type='jpg')
    {
		header ('content-type: image/'.$type);

        imagealphablending ($this->image, false);
		imagesavealpha ($this->image, true);

        switch ($type)
        {
            case 'jpg':
            	imagejpeg ($this->image, null, 75);
				break;

            case 'gif':
            	imagegif ($this->image, null);
				break;

            case 'png':
            	imagepng ($this->image, null);
				break;

            default:
            	throw new Error ('Unsupported output format: '.$type);
        }
    }

	/*
	**	Returns the binary image data. If base64 parameter is set the data will be returned in base64. When the
	**	dataHr parameter is set a data-URI will be returned.
	*/
    public function data (string $type='png', bool $base64=false, bool $dataHdr=false)
    {
		ob_start();

        imagealphablending ($this->image, false);
		imagesavealpha ($this->image, true);

        switch ($type)
        {
            case 'jpg':
            	imagejpeg ($this->image, null, 75);
				break;

            case 'gif':
            	imagegif ($this->image, null);
				break;

            case 'png':
            	imagepng ($this->image, null);
            	break;
		}

		$data = ob_get_clean();

		if ($dataHdr)
			return 'data:image/'.$type.';base64,'.base64_encode($data);

        return $base64 ? base64_encode($data) : $data;
    }

	/*
	**	Returns the width of the image. If the width parameter is not null the image will be horizontally resized to the given
	**	width, and the height will be adjusted if the holdAspect parameter is set to true.
	*/
    public function width (int $width=null, bool $holdAspect=true)
    {
        if ($width !== null)
        {
            $h = $this->height();
            if ($holdAspect)
                $h = ($h / $this->width()) * $width;

            $this->resize ($width, $h);
        }
        else
            return imagesx ($this->image);
    }

	/*
	**	Returns the height of the image. If the height parameter is not null the image will be vertically resized to the given
	**	height, and the width will be adjusted if the holdAspect parameter is set to true.
	*/
    public function height (int $height=null, bool $holdAspect=true)
    {
        if ($height !== null)
        {
            $w = $this->width();
            if ($holdAspect)
				$w = ($w / $this->height()) * $height;

            $this->resize ($w, $height);
        }
        else
            return imagesy ($this->image);
    }

	/*
	**	Resizes the image to the given width and height.
	*/
    public function resize (int $width, int $height, $rewrite=true)
    {
        $image = imagecreatetruecolor ($width, $height);

		imagealphablending ($image, false);
        imagesavealpha ($image, true);
		imagecopyresampled ($image, $this->image, 0, 0, 0, 0, $width, $height, $this->width(), $this->height());

        return $rewrite ? $this->setRes($image) : Image::fromRes($image);
    }

	/*
	**	Scales the image to the given the width and height factors. Each factor must be a real number between 0 and 1 (inclusive).
	*/
    public function scale (float $wf, float $hf=null, $rewrite=true)
    {
		if ($hf === null) $hf = $wf;
        return $this->resize ($this->width()*$wf, $this->height()*$hf, $rewrite);
    }

	/*
	**	Resizes the image to fit in the given area, maintains the aspect ratio.
	*/
    public function fit (int $width=0, int $height=0, bool $rewrite=false)
    {
		// Load image width and height if not specified.
		if ($height <= 0) $height = $this->height();
		if ($width <= 0) $width = $this->width();

		// Read the resolution of the current image.
        $cw = $this->width();
		$ch = $this->height();

		// Calculate maximum area.
		$max = $width * $height;

		// Calculate width and height-based factors.
        $f1 = $width / $cw;
		$f2 = $height / $ch;

		// Calculate both areas using each factor.
        $a1 = $cw * $ch * $f1 * $f1;
		$a2 = $cw * $ch * $f2 * $f2;

		// Determine which area is the nearest.
        if (($a1 < $max && $a1 > $a2) || $a2 > $max)
            return $this->scale ($f1, $f1, $rewrite);
        else
            return $this->scale ($f2, $f2, $rewrite);
    }

	/*
	**	Cuts a portion of the image and returns a new image object. If the top point coordinates are null, they will be
	**	centered. If the size parameters are null they will be set to the size of the image.
	*/
    public function cut (int $w=null, int $h=null, int $sx=null, int $sy=null)
    {
		$tx=null; $ty=null; $image=null;

        $sh = $this->height();
		$sw = $this->width();

        if ($h === null) $h = $sh;
        if ($sy === null) $sy = ($sh - $h) / 2;

        if ($w === null) $w = $sw;
        if ($sx === null) $sx= ($sw - $w) / 2;

        if ($sy < 0) { $ty =- $sy; $sy = 0; } else $ty = 0;
        if ($sx < 0) { $tx =- $sx; $sx = 0; } else $tx = 0;

        if ($sx > $sw || $sy > $sh)
            return null;

        $image = imagecreatetruecolor ($w, $h);

		imagealphablending ($image, false);
        imagesavealpha ($image, true);
		imagecopyresampled ($image, $this->image, $tx, $ty, $sx, $sy, $w, $h, $w, $h);

        return Image::fromRes($image);
    }

	/*
	**	Crops to a portion of the image. This is similar to cutting a portion of the image and replacing the original
	**	by the portion.
	*/
    public function crop (int $w=null, int $h=null, int $sx=null, int $sy=null)
    {
		$tx=null; $ty=null; $image=null;

        $sh = $this->height();
		$sw = $this->width();

        if ($h === null) $h = $sh;
        if ($sy === null) $sy = ($sh - $h) / 2;

        if ($w === null) $w = $sw;
        if ($sx === null) $sx= ($sw - $w) / 2;

        if ($sy < 0) { $ty =- $sy; $sy = 0; } else $ty = 0;
        if ($sx < 0) { $tx =- $sx; $sx = 0; } else $tx = 0;

        if ($sx > $sw || $sy > $sh)
            return null;

        $image = imagecreatetruecolor ($w, $h);

		imagealphablending ($image, false);
        imagesavealpha ($image, true);
		imagecopyresampled ($image, $this->image, $tx, $ty, $sx, $sy, $w, $h, $w, $h);

        return $this->setRes($image);
    }

	/*
	**	Smart cuts a portion of the image, detects if scaling is needed and will scale accordingly. Returns a new
	**	image only if the rewrite parameter is set to false. The onTooWide and onTooTall parameters indicates how
	**	to cut if the image is either too wide or too tall respectively.
	*/
    public function smartCut (int $w=null, int $h=null, int $onTooWide=Image::CENTER, int $onTooTall=Image::CENTER, bool $rewrite=false)
    {
		$sx=null; $sy=null; $image=null;

        $sh = $this->height();
		$sw = $this->width();

		if (!$h && !$w) return $this;

        if (!$h) $h = $sh * ($w / $sw);
        if (!$w) $w = $sw * ($h / $sh);

        $dw = $sw - $w;
		$dh = $sh - $h;

		$k = $w / $h;

		if ($sw*$sw/$k < $sh*$sh*$k)
        {
            $dh = $h * (($dw = $sw) / $w);
			$sx = 0; $sy = $sh - $dh;

            if (!$onTooTall) $sy /= 2; else if ($onTooTall < 0) $sy = 0;
        }
        else
        {
            $dw = $w * (($dh = $sh) / $h);
			$sy = 0; $sx = $sw - $dw;

            if (!$onTooWide) $sx /= 2; else if ($onTooWide < 0) $sx = 0;
		}

		$image = imagecreatetruecolor ($w, $h);

        imagealphablending ($image, false);
        imagesavealpha ($image, true);
		imagecopyresampled ($image, $this->image, 0, 0, $sx, $sy, $w, $h, $dw, $dh);

        return $rewrite ? $this->setRes($image) : Image::fromRes($image);
	}
	
	/*
	**	Returns a string representation of the image (png data-uri).
	*/
	public function __toString()
	{
		return $this->data ('png', true, true);
	}
};
