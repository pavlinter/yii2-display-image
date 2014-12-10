<?php

/**
 * @copyright Copyright &copy; Pavels Radajevs, 2014
 * @package yii2-display-image
 * @version 1.0.0
 */

namespace pavlinter\display;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

/**
 * Class DisplayHelper
 */
class DisplayHelper
{
    static $config;

    /**
     * @return null
     */
    public static function getConfig()
    {
        if (static::$config === null) {
            $definitions = Yii::$container->getDefinitions();
            if (isset($definitions['pavlinter\display\DisplayImage'])) {
                return $definitions['pavlinter\display\DisplayImage'];
            }
        }
        return static::$config;
    }

    /**
     * @param $category
     * @param array $options
     * @return array|bool
     * @throws InvalidConfigException
     */
    public static function getFiles($category, $options = [])
    {
        $globalConfig = self::getConfig();
        if (empty($globalConfig['config'])) {
            return false;
        }
        $categories = $globalConfig['config'];

        if (!isset($categories[$category])) {
            return false;
        }
        $config = $categories[$category];
        if (!isset($config['imagesDir'])) {
            throw new InvalidConfigException('The "imagesDir" property must be set for "' . $category . '".');
        }
        $imagesDir      = Yii::getAlias($config['imagesDir']) . '/';
        $imagesWebDir   = Yii::getAlias($config['imagesWebDir']) . '/';

        $options = ArrayHelper::merge([
            'recursive' => false,
            'dir' => '',
            'isDisplayImagePath' => false,
            'id_row' => null,
            'defaultImage' => null,
            'keyCallback' => function($data){
                return basename($data['dirName']);
            },
        ], $options);

        $dir = ArrayHelper::remove($options, 'dir');
        $dir = $dir ? $dir . '/' : '';
        $keyCallback = ArrayHelper::remove($options, 'keyCallback');
        $isDisplayImagePath = ArrayHelper::remove($options, 'isDisplayImagePath');
        $defaultImage = ArrayHelper::remove($options, 'defaultImage');
        $id_row = ArrayHelper::remove($options, 'id_row');
        $id_row = $id_row ? $id_row . '/' : '';

        FileHelper::createDirectory($imagesDir . $id_row . $dir);
        $images = FileHelper::findFiles($imagesDir . $id_row . $dir, $options);
        $resImages = [];
        if ($isDisplayImagePath) {
            foreach ($images as $k => $image) {
                $pathName = str_replace($imagesDir . $id_row, '', $image);
                $key = call_user_func($keyCallback, [
                    'key' => $k,
                    'fullPath' => $image,
                    'dirName' => $pathName,
                    'imagesDir' => $imagesDir . $id_row,
                    'imagesWebDir' => $imagesWebDir . $id_row,
                ]);
                $resImages[$key] = $pathName;
            }
        } else {
            foreach ($images as $k => $image) {
                $pathName = str_replace($imagesDir . $id_row, '', $image);
                $key = call_user_func($keyCallback,[
                    'id_row' => $id_row,
                    'key' => $k,
                    'fullPath' => $image,
                    'dirName' => $pathName,
                    'imagesDir' => $imagesDir,
                    'imagesWebDir' => $imagesWebDir,
                ]);
                $resImages[$key] = $imagesWebDir . $id_row . $pathName;
            }
        }

        if (empty($resImages) && $defaultImage) {
            $resImages[$defaultImage] = $defaultImage;
        }

        return $resImages;
    }
    /**
     * @param $id_row
     * @param $category
     * @param array $options
     * @return array|bool
     * @throws InvalidConfigException
     */
    public static function getOriginalImages($id_row, $category, $options = [])
    {
        if ($id_row) {
            $options['id_row'] = $id_row;
        }
        if (isset($options['dir'])) {
            $options['dir'] = trim($options['dir'], '/');
        }
        if (!isset($options['only'])) {
            $extensions = self::supported();
            foreach ($extensions as $ext) {
                $options['only'][] = '*.' . $ext;
            }
            if ($options['only']) {
                $options['caseSensitive'] = false;
            }
        }

        $files = self::getFiles($category, $options);

        if (!is_array($files)) {
            return [];
        }
        return $files;
    }

    /**
     * @param $id_row
     * @param $category
     * @param array $options
     * @return mixed|null
     */
    public static function getOriginalImage($id_row, $category, $options = [])
    {
        $images = self::getOriginalImages($id_row, $category, $options);
        if (empty($images)) {
            return null;
        }
        return reset($images);
    }

    /**
     * @param $id_row
     * @param $category
     * @param array $widget
     * @param array $options
     * @return array
     */
    public static function getImages($id_row, $category, $widget = [], $options = [])
    {
        $options['isDisplayImagePath'] = true;
        $minImages  = ArrayHelper::remove($options, 'minImages');
        $images     = self::getOriginalImages($id_row, $category, $options);

        if ($minImages && ($count = $minImages - count($images)) > 0) {
            for ($i = 0; $i < $count; $i++) {
                $images[] = 'default';
            }
        }

        $displayImages = [];
        if (!isset($widget['returnSrc'])) {
            $widget['returnSrc'] = true;
        }
        $widget['category'] = $category;
        if ($id_row) {
            $widget['id_row'] = $id_row;
        }
        foreach ($images as $k => $image) {
            $widget['image'] = $image;
            $displayImages[$k] = DisplayImage::widget($widget);
        }
        return $displayImages;
    }

    /**
     * @param $id_row
     * @param $category
     * @param array $widget
     * @param array $options
     * @return mixed
     */
    public static function getImage($id_row, $category, $widget = [], $options = [])
    {
        $images = self::getImages($id_row, $category, $widget, $options);

        if (empty($images)) {
            return null;
        }
        return reset($images);
    }

    /**
     * @param $category
     * @param null $id_row
     * @return bool
     */
    public static function clear($category, $id_row = null)
    {
        $globalConfig = self::getConfig();
        $categories = ArrayHelper::remove($globalConfig, 'config');

        if (!isset($categories[$category])) {
            return false;
        }
        $innerCacheDir = ArrayHelper::remove($globalConfig, 'innerCacheDir');
        $cacheDir = rtrim(ArrayHelper::remove($globalConfig, 'cacheDir', '@webroot/display-images-cache'), '/');
        if ($id_row) {
            $id_row = '/' . $id_row;
        }
        if ($innerCacheDir) {
            $imagesDir = rtrim(ArrayHelper::remove($categories[$category], 'imagesDir'), '/');
            if (empty($imagesDir)) {
                return false;
            }
            $cacheDir = $imagesDir . $id_row . '/' . $innerCacheDir;
        } else {
            $cacheDir = $cacheDir . '/' . $category . $id_row;
        }
        $cacheDir = Yii::getAlias($cacheDir);
        FileHelper::removeDirectory($cacheDir);
        return true;
    }

    /**
     * @param $path
     * @return array|bool
     */
    public static function is_image($path)
    {
        if (!is_file($path)) {
            return false;
        }
        $ext = self::getExtension($path);
        return self::supported($ext);
    }

    /**
     * @param $path
     * @return string
     */
    public static function getExtension($path)
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /**
     * @param $string
     * @return mixed|string
     */
    public static function encodeName($string) {

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

    /**
     * @param null $format
     * @return array|bool
     */
    public static function supported($format = null)
    {
        $formats = ['gif', 'jpeg', 'jpg', 'png', 'wbmp', 'xbm'];

        if ($format === null) {
            return $formats;
        }
        $format  = strtolower($format);
        return in_array($format, $formats);
    }
}
