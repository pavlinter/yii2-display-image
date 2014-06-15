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
"pavlinter/pavlinter/yii2-display-image": "dev-master"
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
    'config' => [
        'items' => [
            'imagesWebDir' => '@web/display-images/items',
            'imagesDir' => '@webroot/display-images/items',
            'defaultWebDir' => '@web/display-images/default',
            'defaultDir' => '@webroot/display-images/default',
            'mode' => DisplayImage::MODE_OUTBOUND,
        ],
        'all' => [
            'imagesWebDir' => '@web/display-images/images',
            'imagesDir' => '@webroot/display-images/images',
            'defaultWebDir' => '@web/display-images/default',
            'defaultDir' => '@webroot/display-images/default',
        ]
    ]
]);
return [
  ...
];
```

Usage
-----
```php
use pavlinter\display\DisplayImage;

echo DisplayImage::widget([ //return resized Html::img
    'id_row' => 2,
    'width' => 100,
    'image' => 'desktopwal.jpg',
    'category' => 'items',
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
```