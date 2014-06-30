<?php

/**
 * @copyright Copyright &copy; Pavels Radajevs, 2014
 * @package yii2-display-image
 * @version 1.0.0
 */

namespace pavlinter\display;

use Imagine\Image\Box;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\imagine\Image;
use Imagine\Image\ManipulatorInterface;

class DisplayImage extends \yii\base\Widget
{
    const MODE_INSET    = ManipulatorInterface::THUMBNAIL_INSET;
    const MODE_OUTBOUND = ManipulatorInterface::THUMBNAIL_OUTBOUND;
    const MODE_STATIC   = 'static';

    /**
     * @var integer id from db
     */
    public $id_row;
    /**
     * @var string new image name after resize
     */
    public $name;
    /**
     * @var integer image width
     */
    public $width;
    /**
     * @var integer image height
     */
    public $height;
    /**
     * @var integer image height
     */
    public $image;
    /**
     * @var string the image category
     */
    public $category;
    /**
     * @var string the background color for [[DisplayImage::MODE_STATIC]] or [[resize]]
     * default white value
     */
    public $bgColor = '000000';
    /**
     * @var integer the background transparent for [[DisplayImage::MODE_STATIC]] or [[resize]]
     * default 0 value (not transparent)
     * range 0 - 100
     */
    public $bgAlpha = 0;
    /**
     * @var array html options
     */
    public $options = [];
    /**
     * @var array the global config
     * example:
     *'items' => [
     *  'imagesWebDir' => '@web/display-images/items',
     *  'imagesDir' => '@webroot/display-images/items',
     *  'defaultWebDir' => '@web/display-images/default',
     *  'defaultDir' => '@webroot/display-images/default',
     *],
     *'all' => [
     *  'imagesWebDir' => '@web/display-images/images',
     *  'imagesDir' => '@webroot/display-images/images',
     *  'defaultWebDir' => '@web/display-images/default',
     *  'defaultDir' => '@webroot/display-images/default',
     *]
     */
    public $config = [];
    /**
     * @var boolean if value true, widget return path to image
     */
    public $returnSrc = false;
    /**
     * @var string [[DisplayImage::MODE_INSET || DisplayImage::MODE_OUTBOUND || DisplayImage::MODE_STATIC]]
     * or create own resize [[resize]]
     */
    public $mode = self::MODE_INSET;
    /**
     * @var function encode new image name
     */
    public $encodeName;
    /**
     * @var string the url to images directory
     */
    public $imagesWebDir;
    /**
     * @var string the path to images directory
     */
    public $imagesDir;
    /**
     * @var string the url where default image
     */
    public $defaultWebDir;
    /**
     * @var string the path where default image
     */
    public $defaultDir;
    /**
     * @var string the name default image
     */
    public $defaultImage = 'default.png';
    /**
     * @var function the own resize
     */
    public $resize;
    /**
     * @var string generate size directory name
     */
    public $sizeDirectory;


    public function init()
    {
        parent::init();
        if (empty($this->config)) {
            throw new InvalidConfigException('The "config" property must be set.');
        }
        if (empty($this->category)) {
            throw new InvalidConfigException('The "category" property must be set.');
        }
        if (!isset($this->config[$this->category])) {
            throw new InvalidConfigException('Set "config" for "' . $this->category . '".');
        }

        $config = ArrayHelper::merge([
            'imagesWebDir' => $this->imagesWebDir,
            'imagesDir' => $this->imagesDir,
            'defaultWebDir' => $this->defaultWebDir,
            'defaultDir' => $this->defaultDir,
            'defaultImage' => $this->defaultImage,
            'mode' => $this->mode,
        ],$this->config[$this->category]);

        if (empty($config['imagesWebDir'])) {
            throw new InvalidConfigException('The "imagesWebDir" property must be set for "' . $this->category . '".');
        }
        if (empty($config['imagesDir'])) {
            throw new InvalidConfigException('The "imagesDir" property must be set for "' . $this->category . '".');
        }
        if (empty($config['defaultWebDir'])) {
            throw new InvalidConfigException('The "defaultWebDir" property must be set for "' . $this->category . '".');
        }
        if (empty($config['defaultDir'])) {
            throw new InvalidConfigException('The "defaultDir" property must be set for "' . $this->category . '".');
        }

        foreach ($config as $prop=>$value) {
            if ($this->hasProperty($prop)) {
                $this->{$prop} = $value;
            }
        }

        if ($this->resize !== null && !is_callable($this->resize)) {
            throw new InvalidConfigException('The "resize" property must be anonymous function.');
        }

        $this->imagesDir    = Yii::getAlias(rtrim($this->imagesDir, '/')) . '/';
        $this->imagesWebDir = Yii::getAlias(rtrim($this->imagesWebDir, '/')) . '/';

        if ($this->width && !$this->height) {
            $this->height = $this->width;
        } elseif(!$this->width && $this->height) {
            $this->width = $this->height;
        }

        if ($this->sizeDirectory === null) {
            $this->sizeDirectory = $this->width . 'x' . $this->height . '_' . $this->mode . '_' . $this->bgColor . '_' . $this->bgAlpha .  '/';
        }

        if ($this->id_row) {
            FileHelper::createDirectory($this->imagesDir . $this->id_row);
            $idRowPath = $this->id_row . '/';
        } else {
            $idRowPath = '';
        }

        if ($this->image && $this->is_image($this->imagesDir . $idRowPath . $this->image)) {
            if (!$this->width && !$this->height) {
                $src = $this->imagesWebDir . $idRowPath . $this->image;
            } else {
                $src = $this->resize($this->imagesDir . $idRowPath . $this->image, $idRowPath);
            }
        } else {
            $this->defaultDir       = Yii::getAlias(rtrim($this->defaultDir, '/')) . '/';
            $this->defaultWebDir    = Yii::getAlias(rtrim($this->defaultWebDir, '/')) . '/';
            if (!$this->width && !$this->height) {
                $src = $this->defaultDir . $this->defaultImage;
            } else {
                $src = $this->resizeDefault($this->defaultDir . $this->defaultImage);
            }
        }
        echo $this->display($src);
    }

    public function resizeDefault($filename)
    {
        if (!file_exists($this->defaultDir . $this->sizeDirectory . $this->defaultImage)) {
            FileHelper::createDirectory($this->defaultDir . $this->sizeDirectory);
            $img = Image::getImagine()->open($filename);
            if ($this->resize) {
                $img = call_user_func($this->resize, $this, $img);
            } elseif ($this->mode === self::MODE_STATIC) {
                $img = $this->resizeStatic($this->width,$this->height,$img);
            } else {
                $img = $img->thumbnail(new Box($this->width, $this->height), $this->mode);
            }
            $img->save($this->defaultDir . $this->sizeDirectory . $this->defaultImage);
        }
        return $this->defaultWebDir . $this->sizeDirectory . $this->defaultImage;
    }
    public function resize($filename, $idRowPath)
    {
        $img = Image::getImagine()->open($filename);
        $image = $this->image;

        if ($this->name) {
            $ext = '.' . $this->getExtension($this->image);
            if (is_callable($this->encodeName)) {
                $image = call_user_func($this->encodeName, $this->name). $ext;
            } else {
                $image = $this->encodeName($this->name) . $ext;
            }
        }
        if (!isset($this->options['alt'])) {
            $this->options['alt'] = $image;
        }
        if (!file_exists($this->imagesDir . $idRowPath . $this->sizeDirectory . $image)) {
            if ($this->resize) {
                $img = call_user_func($this->resize, $this, $img);
            } elseif ($this->mode === self::MODE_STATIC) {
                $img = $this->resizeStatic($this->width, $this->height, $img);
            } else {
                $img = $img->thumbnail(new Box($this->width, $this->height), $this->mode);
            }
            FileHelper::createDirectory($this->imagesDir . $idRowPath . $this->sizeDirectory);
            $img->save($this->imagesDir . $idRowPath . $this->sizeDirectory . $image);
        }
        return $this->imagesWebDir . $idRowPath . $this->sizeDirectory . $image;
    }
    public function resizeStatic($width,$height,$originalImage)
    {
        $Box = new Box($width,$height);
        $newImage = $originalImage->thumbnail($Box);
        $boxNew = $newImage->getSize();

        $x = ($Box->getWidth() - $boxNew->getWidth())/2;
        $y = ($Box->getHeight() - $boxNew->getHeight())/2;

        $point = new \Imagine\Image\Point($x,$y);
        $color = new \Imagine\Image\Color('#' . $this->bgColor,$this->bgAlpha);

        return Image::getImagine()->create($Box, $color)->paste($newImage,$point);
    }
    public function display($src)
    {
        if ($this->returnSrc) {
            return $src;
        }
        return Html::img($src, $this->options);
    }
    public function is_image($path)
    {
        if (!is_file($path)) {
            return false;
        }
        $ext = $this->getExtension($path);
        return $this->supported($ext);
    }
    public function getExtension($path)
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }
    private  function encodeName($string) {

        if (function_exists('iconv')) {
            $string = @iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        }
        $string = preg_replace("/[^a-zA-Z0-9 \-]/", "", $string);
        $string = str_replace("-",' ', $string);
        $string = trim(preg_replace("/\\s+/", " ", $string));
        $string = strtolower($string);
        $string = str_replace(" ", "-", $string);

        return $string;
    }
    private function supported(&$format = null)
    {
        $formats = ['gif', 'jpeg', 'png', 'wbmp', 'xbm'];

        if ($format === null) {
            return $formats;
        }
        $format  = strtolower($format);

        if ('jpg' === $format || 'pjpeg' === $format) {
            $format = 'jpeg';
        }
        return in_array($format, $formats);
    }
    
}
