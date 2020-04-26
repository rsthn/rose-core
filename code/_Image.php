<?php

class _Image
{
    private static $__classAttributes = null;
    private $image;
    private $type;
    private $filename;

    public static $CENTER=0;
    public static $LEFT=-1;
    public static $RIGHT=1;
    public static $TOP=-1;
    public static $BOTTOM=1;

    public static function classAttributes ()
    {
        return _Image::$__classAttributes;

    }

    private static function __instanceInit ($__this__)
    {
        $__this__->type='png';
        $__this__->filename='output.png';
    }

    public static function __classInit ()
    {
        $clsAttr = array();
    }


    public function __destruct ()
    {
    }

    public function __construct ($filename=null)
    {
        _Image::__instanceInit ($this);
        if (($filename!=null))
        {
            if (_File::exists($this->filename=$filename))
            {
                $this->load($filename);
            }
        }
    }

    public function create ($width, $height)
    {
        $this->image=imagecreatetruecolor($width,$height);
        return $this;
    }

    private function setDescriptor ($image)
    {
        $this->image=$image;
        return $this;
    }

    public static function fromDescriptor ($image)
    {
        return alpha (new _Image ())->setDescriptor($image);
    }

    public function load ($source)
    {
        $p=getimagesize($this->filename=$source);
        if (!$p)
        {
            throw alpha (new _Exception ('Unable to get image information.'));
        }
        switch ($p[2])
        {
            case 1:
            $this->image=imagecreatefromgif($source);
            $this->type='gif';
            break;
            case 2:
            $this->image=imagecreatefromjpeg($source);
            $this->type='jpg';
            break;
            case 3:
            $this->image=imagecreatefrompng($source);
            $this->type='png';
            break;
            case 6:
            $this->image=imagecreatefrombmp($source);
            $this->type='bmp';
            break;
            default:
            throw alpha (new _Exception ('Unsupported image type code: '.$p[2]));
        }
        return $this;
    }

    public function save ($target=null, $type=null)
    {
        if (($target==null))
        {
            $target=$this->filename;
        }
        if (($type==null))
        {
            $type=$this->type;
        }
        imagealphablending($this->image,false);
        imagesavealpha($this->image,true);
        switch ($type)
        {
            case 'jpg':
            imagejpeg($this->image,$target,95);
            break;
            case 'gif':
            imagegif($this->image,$target);
            break;
            case 'png':
            imagepng($this->image,$target);
            break;
            default:
            throw alpha (new _Exception ('Unsupported output image type: '.$type));
        }
    }

    public function output ($type='jpg')
    {
        header('content-type: image/'.$type);
        imagealphablending($this->image,false);
        imagesavealpha($this->image,true);
        switch ($type)
        {
            case 'jpg':
            imagejpeg($this->image,null,75);
            break;
            case 'gif':
            imagegif($this->image,null);
            break;
            case 'png':
            imagepng($this->image,null);
            break;
            default:
            throw alpha (new _Exception ('Unsupported output image type: '.$type));
        }
    }

    public function data ($type='png', $base64=false, $dataHdr=false)
    {
        ob_start();
        imagealphablending($this->image,false);
        imagesavealpha($this->image,true);
        switch ($type)
        {
            case 'jpg':
            imagejpeg($this->image,null,75);
            break;
            case 'gif':
            imagegif($this->image,null);
            break;
            case 'png':
            imagepng($this->image,null);
            break;
        }
        return ($base64?(($dataHdr?'data:image/'.$type.';base64,':'')).base64_encode(ob_get_clean()):ob_get_clean());
    }

    public function width ($width=null, $holdAspect=true)
    {
        if (($width!==null))
        {
            $h=$this->height();
            if ($holdAspect)
            {
                $h=((($h/$this->width()))*$width);
            }
            $this->resize($width,$h);
        }
        else
        {
            return imagesx($this->image);
        }
    }

    public function height ($height=null, $holdAspect=true)
    {
        if (($height!==null))
        {
            $w=$this->width();
            if ($holdAspect)
            {
                $w=((($w/$this->height()))*$height);
            }
            $this->resize($w,$height);
        }
        else
        {
            return imagesy($this->image);
        }
    }

    public function resize ($width, $height, $onSelf=true)
    {
        $image=imagecreatetruecolor($width,$height);
        imagealphablending($image,false);
        imagesavealpha($image,true);
        imagecopyresampled($image,$this->image,0,0,0,0,$width,$height,$this->width(),$this->height());
        if ($onSelf)
        {
            return $this->setDescriptor($image);
        }
        else
        {
            return _Image::fromDescriptor($image);
        }
    }

    public function scale ($wf, $hf, $onSelf=true)
    {
        return $this->resize(($this->width()*$wf),($this->height()*$hf),$onSelf);
    }

    public function fit ($width=0, $height=0, $onSelf=false)
    {
        if (($height<=0))
        {
            $height=$this->height();
        }
        if (($width<=0))
        {
            $width=$this->width();
        }
        $cw=$this->width();
        $ch=$this->height();
        $max=($width*$height);
        $f1=($width/$cw);
        $f2=($height/$ch);
        $a1=((($cw*$ch)*$f1)*$f1);
        $a2=((($cw*$ch)*$f2)*$f2);
        if ((((($a1<$max)&&($a1>$a2)))||($a2>$max)))
        {
            return $this->scale($f1,$f1,$onSelf);
        }
        else
        {
            return $this->scale($f2,$f2,$onSelf);
        }
    }

    public function cut ($w=null, $h=null, $sx=null, $sy=null)
    {
        $tx=null;;
        $ty=null;;
        $image=null;;
        $sh=$this->height();
        $sw=$this->width();
        if (($h===null))
        {
            $h=$sh;
        }
        if (($sy===null))
        {
            $sy=((($sh-$h))/2);
        }
        if (($w===null))
        {
            $w=$sw;
        }
        if (($sx===null))
        {
            $sx=((($sw-$w))/2);
        }
        if (($sy<0))
        {
            $ty=-$sy;
            $sy=0;
        }
        else
        {
            $ty=0;
        }
        if (($sx<0))
        {
            $tx=-$sx;
            $sx=0;
        }
        else
        {
            $tx=0;
        }
        if ((($sx>$sw)||($sy>$sh)))
        {
            return null;
        }
        $image=imagecreatetruecolor($w,$h);
        imagealphablending($image,false);
        imagesavealpha($image,true);
        imagecopyresampled($image,$this->image,$tx,$ty,$sx,$sy,$w,$h,$w,$h);
        return _Image::fromDescriptor($image);
    }

    public function crop ($w=null, $h=null, $sx=null, $sy=null)
    {
        $tx=null;;
        $ty=null;;
        $image=null;;
        $sh=$this->height();
        $sw=$this->width();
        if (($h===null))
        {
            $h=$sh;
        }
        if (($sy===null))
        {
            $sy=((($sh-$h))/2);
        }
        if (($w===null))
        {
            $w=$sw;
        }
        if (($sx===null))
        {
            $sx=((($sw-$w))/2);
        }
        if (($sy<0))
        {
            $ty=-$sy;
            $sy=0;
        }
        else
        {
            $ty=0;
        }
        if (($sx<0))
        {
            $tx=-$sx;
            $sx=0;
        }
        else
        {
            $tx=0;
        }
        if ((($sx>$sw)||($sy>$sh)))
        {
            return null;
        }
        $image=imagecreatetruecolor($w,$h);
        imagealphablending($image,false);
        imagesavealpha($image,true);
        imagecopyresampled($image,$this->image,$tx,$ty,$sx,$sy,$w,$h,$w,$h);
        return $this->setDescriptor($image);
    }

    public function smartCut ($w=null, $h=null, $onTooWide=0, $onTooTall=0, $onSelf=false)
    {
        $sx=null;;
        $sy=null;;
        $image=null;;
        $sh=$this->height();
        $sw=$this->width();
        if ((!$h&&!$w))
        {
            return $this;
        }
        if (!$h)
        {
            $h=($sh*(($w/$sw)));
        }
        if (!$w)
        {
            $w=($sw*(($h/$sh)));
        }
        $dw=($sw-$w);
        $dh=($sh-$h);
        $k=($w/$h);
        if (((($sw*$sw)/$k)<(($sh*$sh)*$k)))
        {
            $dh=($h*((($dw=$sw)/$w)));
            $sx=0;
            $sy=($sh-$dh);
            if (!$onTooTall)
            {
                $sy/=2;
            }
            else if (($onTooTall<0))
            {
                $sy=0;
            }
        }
        else
        {
            $dw=($w*((($dh=$sh)/$h)));
            $sy=0;
            $sx=($sw-$dw);
            if (!$onTooWide)
            {
                $sx/=2;
            }
            else if (($onTooWide<0))
            {
                $sx=0;
            }
        }
        $image=imagecreatetruecolor($w,$h);
        imagealphablending($image,false);
        imagesavealpha($image,true);
        imagecopyresampled($image,$this->image,0,0,$sx,$sy,$w,$h,$dw,$dh);
        if ($onSelf)
        {
            return $this->setDescriptor($image);
        }
        else
        {
            return _Image::fromDescriptor($image);
        }
    }

    public function writeText ($font, $x, $y, $color, $text)
    {
        imagestring($this->image,((int)$font),((int)$x),((int)$y),$text,((int)$color));
        return $this;
    }

    public function writeTextTTF ($font, $size, $angle, $x, $y, $color, $text)
    {
        imagettftext($this->image,((float)$size),((int)$angle),((int)$x),((int)$y),((int)$color),$font,$text);
        return $this;
    }

    public function fillRect ($x, $y, $w, $h, $color)
    {
        imagefilledrectangle($this->image,((int)$x),((int)$y),(($x+$w)-1),(($y+$h)-1),((int)$color));
        return $this;
    }

    public function thickness ($value)
    {
        imagesetthickness($this->image,((int)$value));
        return $this;
    }

    public function line ($x1, $y1, $x2, $y2, $color)
    {
        imageline($this->image,((int)$x1),((int)$y1),((int)$x2),((int)$y2),((int)$color));
        return $this;
    }

    public static function boundBoxTTF ($font, $size, $angle, $text)
    {
        return _Array::fromNativeArray(imagettfbbox(((float)$size),((int)$angle),$font,$text));
    }

    public function __get ($gsprn)
    {
        switch ($gsprn)
        {
        }

        if (method_exists (get_parent_class (), '__get')) return parent::__get ($gsprn);
        throw new _UndefinedProperty ($gsprn);
    }

    public function __set ($gsprn, $sprv)
    {
        switch ($gsprn)
        {
        }
        if (method_exists (get_parent_class (), '__set')) parent::__set ($gsprn, $sprv);
    }

    public function __toString ()
    {
        return $this->__typeCast('String');
    }

}