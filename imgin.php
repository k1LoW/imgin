<?php
/**
 * imgin
 *
 * ## Support pattern
 * 
 * - /100x80/
 *
 */
require dirname(__FILE__) . '/vendor/autoload.php';
require dirname(__FILE__) . '/config.php';

$rootPath = dirname(__FILE__);

function cleardir($dir) {
    if (is_dir($dir) && !is_link($dir)) {
        array_map('cleardir',   glob($dir.'/*', GLOB_ONLYDIR));
        array_map('unlink', glob($dir.'/*'));
        rmdir($dir);
    }
}

/**
 * Clear manipurated image by CLI
 *
 */
if (php_sapi_name() == 'cli') {
    $imgin = new Commando\Command();
    $imgin->option()
          ->require()
          ->describedAs('Clear manipurated image')
          ->must(function($cmd) {
              return in_array($cmd, array('clear', 'clearall'));
          })
          ->option();

    // clear
    if ($imgin[0] === 'clear') {
        $originalImagePath = $imgin[1];
        if (!file_exists($originalImagePath)) {
            $imgin->error(new \Exception(sprintf('%s not exists', $originalImagePath)));
        }
        if (preg_match('#^'. $rootPath . '(.+)#', $originalImagePath, $matches)) {
            $relativeImagePath = $matches[1];
            foreach(glob($rootPath . '/*', GLOB_ONLYDIR) as $dirname) {
                if (preg_match('#(/\d+x\d+)$#', $dirname, $matches)) {
                    $resizedImagePath = $rootPath . $matches[1] . $relativeImagePath;
                    if (file_exists($resizedImagePath)) {
                        unlink($resizedImagePath);
                    }
                }
            }
        }
    }

    // clearall
    if ($imgin[0] === 'clearall') {
        foreach(glob($rootPath . '/*', GLOB_ONLYDIR) as $dirname) {
            if (preg_match('#(/\d+x\d+)$#', $dirname)) {
                cleardir($dirname);
            }
        }
    }
    
    return;
}

/**
 * Manipurate image by HTTP Request
 *
 */
$baseUrl = dirname($_SERVER['SCRIPT_NAME']);
$dirname = basename(dirname($_SERVER['SCRIPT_NAME']));
$imageUrl = preg_replace('#.+' . $dirname . '#', '', $_SERVER['REQUEST_URI']);

if (preg_match('#^/(\d+)x(\d+)(/.+)$#', $imageUrl, $matches)) {
    $width = $matches[1];
    $height = $matches[2];
    $originalImageUrl = $matches[3];
} else {
    header('HTTP', true, 404);
    exit;
}

$originalImagePath = $rootPath . $originalImageUrl;
$resizedImagePath = $rootPath . $imageUrl;

if (!file_exists($originalImagePath)) {
    header('HTTP', true, 404);
    exit;
}

try {
    if (!is_dir(dirname($resizedImagePath))) {
        umask(0);
        $result = mkdir(dirname($resizedImagePath), 0777, true);
        if (!$result) {
            throw new OutOfBoundsException('Permission denied');
        }
    }
    $image = $imagine->open($originalImagePath);

    if (($image->getSize()->getWidth() / $width) > ($image->getSize()->getHeight() / $height)) {
        $relative = new Imagine\Filter\Advanced\RelativeResize('widen', $width);
    } else {
        $relative = new Imagine\Filter\Advanced\RelativeResize('heighten', $height);
    }
    $relative->apply($image)
             ->save($resizedImagePath);
    header('Location: ' . $_SERVER['REQUEST_URI'], true, 307);
} catch (Exception $e) {
    header('HTTP', true, 500);
    echo $e->getMessage();
    exit;
}
