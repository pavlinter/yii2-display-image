Yii2 Display Image
================

Installation
------------
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pavlinter/yii2-display-image "dev-master"
```

or add

```
"pavlinter/yii2-display-image": "dev-master"
```

to the require section of your `composer.json` file.

Configuration
-------------
* Update config file
```php
Yii::$container->set('pavlinter\display\DisplayImage', [
    //'returnSrc' => false,
    //'mode' => DisplayImage::MODE_INSET,
    //'defaultImage' => 'default.png',
    //'bgColor' => '000000',
    //'bgAlpha' => 0,
    //'cacheDir' => '@webroot/display-images-cache',
    //'cacheWebDir' => '@web/display-images-cache',
    //'innerCacheDir' => null, //example: 'cacheDirectory' takes precedence over [[cacheDir]]
    //'generalDefaultDir' => true
    //'defaultCategory' = 'default',
    'config' => [
        'items' => [
            'imagesWebDir' => '@web/display-images/items',
            'imagesDir' => '@webroot/display-images/items',
            'defaultWebDir' => '@web/display-images/default',
            'defaultDir' => '@webroot/display-images/default',
            'mode' => \pavlinter\display\DisplayImage::MODE_STATIC,
        ],
        'all' => [
            'imagesWebDir' => '@web/display-images/images',
            'imagesDir' => '@webroot/display-images/images',
            'defaultWebDir' => '@web/display-images/default',
            'defaultDir' => '@webroot/display-images/default',
            'mode' => \pavlinter\display\DisplayImage::MODE_OUTBOUND,
        ],
        'users' => [
            'imagesWebDir' => '@web/display-images/users',
            'imagesDir' => '@webroot/display-images/users',
            'defaultWebDir' => '@web/display-images/default',
            'defaultDir' => '@webroot/display-images/default',
            'mode' => 'ownMode',
            'bgColor' => 'ff0000',
            'resize' => function ($sender, $originalImage) {
                    /* @var $sender \pavlinter\display\DisplayImage */
                    /* @var $originalImage \Imagine\Imagick\Image */

                    $Box = new \Imagine\Image\Box($sender->width, $sender->height);
                    $newImage = $originalImage->thumbnail($Box);

                    $point = new \Imagine\Image\Point(0, 0);
                    $color = new \Imagine\Image\Color($sender->bgColor, $sender->bgAlpha);

                    return yii\imagine\Image::getImagine()->create($Box, $color)->paste($newImage, $point);
            },
        ],
    ]
]);
return [
  ...
];
```

Usage
-----
```php
use pavlinter\display\DisplayHelper;
use pavlinter\display\DisplayImage;

echo DisplayImage::widget([
    'width' => 120,
    'image' => '1.jpg',
    'category' => 'all',
    'cacheSeconds' => 'auto', //auto is default value. Rewrite image if modified file date is different
]);

echo DisplayImage::widget([ //subfolders image
    'width' => 120,
    'image' => '/subfolders/bg.jpg', // or subfolders/bg.jpg
    'category' => 'all',
]);

echo DisplayImage::widget([ //return resized Html::img
    'id_row' => 2,
    'width' => 100,
    'image' => 'desktopwal.jpg',
    'category' => 'items',
    'cacheSeconds' => 60 * 5, //rewrite resized image after 5 min
]);

echo DisplayImage::widget([ //return resized Html::img
    'width' => 100,
    'image' => '1.jpeg',
    'category' => 'all',
]);

echo DisplayImage::widget([ //return original Html::img
    'image' => '1.jpeg',
    'category' => 'all',
]);

echo DisplayImage::widget([
    'name' => 'newName',
    'width' => 100,
    'height' => 130,
    'image' => '334.gif',
    'category' => 'all',
]);

echo DisplayImage::widget([ //return default Html::img from items category
    'id_row' => 2,
    'width' => 100,
    'image' => 'rddddd',
    'category' => 'items',
]);

echo DisplayImage::widget([ //return default Html::img from all category
    'width' => 100,
    'height' => 130,
    'image' => 'aaaaaaaaa',
    'category' => 'all',
]);

echo DisplayImage::widget([ //return resized image path
    'returnSrc' => true,
    'width' => 100,
    'height' => 130,
    'image' => '334.gif',
    'category' => 'all',
]);

echo DisplayImage::widget([ //own resize mode
    'id_row' => 3,
    'width' => 100,
    'height' => 160,
    'image' => '3.jpeg',
    'category' => 'users',
]);

$images  = DisplayHelper::getImages(null, 'all', [ //or [[getImage()]] return first image
    'width' => 70,
    'height' => 70,
], [
    'minImages' => 6,
    'maxImages' => 10,
]);
/*Array(
    [1.jpeg] => /display-images-cache/all/\/70x70_outbound_000000_0/1.jpeg
    [334.gif] => /display-images-cache/all/\/70x70_outbound_000000_0/334.gif
    [360958.jpeg] => /display-images-cache/all/\/70x70_outbound_000000_0/360958.jpeg
    [926.jpg] => /display-images-cache/all/\/70x70_outbound_000000_0/926.jpg
    [0] => /display-images-cache/default/70x70_outbound_000000_0/default.png
    [1] => /display-images-cache/default/70x70_outbound_000000_0/default.png
)*/

$images  = DisplayHelper::getOriginalImages(null, 'all'); //or [[getOriginalImage()]] return first image

/*Array(
    [1.jpeg] => /display-images/images/\1.jpeg
    [334.gif] => /display-images/images/\334.gif
    [360958s.JPEG] => /display-images/images/\360958s.JPEG
    [926.jpg] => /display-images/images/\926.jpg
)*/

$images  = DisplayHelper::getImages(null, 'all', [ //or [[getImage()]] return first image
    'width' => 70,
    'height' => 70,
], [
    'return' => function ($data) {
        return $data; //required return string|array and image key
    },
]);

/*Array(
    [1.jpeg] => Array(
        [key] => 0
        [fullPath] => D:/localhost/wamp/www/my/test/yii2/advanced/frontend/web/display-images/images/\1.jpeg
        [dirName] => 1.jpeg
        [imagesDir] => D:/localhost/wamp/www/my/test/yii2/advanced/frontend/web/display-images/images/
        [imagesWebDir] => /display-images/images/
        [image] => 1.jpeg
        [display] => /display-images-cache/all/70x70_outbound_000000_0/1.jpeg
    )
    [334.gif] => Array(
        [key] => 1
        [fullPath] => D:/localhost/wamp/www/my/test/yii2/advanced/frontend/web/display-images/images/\334.gif
        [dirName] => 334.gif
        [imagesDir] => D:/localhost/wamp/www/my/test/yii2/advanced/frontend/web/display-images/images/
        [imagesWebDir] => /display-images/images/
        [image] => 334.gif
        [display] => /display-images-cache/all/70x70_outbound_000000_0/334.gif
    )
)*/

$clearItemId = DisplayHelper::clear('items', 2);
$clearItems = DisplayHelper::clear('items');

if ($clearItemId) {
    echo 'Clear item id 2!<br/>';
}
if ($clearItems) {
    echo 'Clear items category!<br/>';
}

$clearCacheDir = DisplayHelper::clearCacheDir(); //Only for outer directory cache [[innerCacheDir]]
if ($clearCacheDir) {
    echo 'Clear cache!<br/>';
}

```