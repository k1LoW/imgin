<?php
/**
 * imgin
 *
 * ## Support pattern
 *
 * - /100x80/
 *
 */
require dirname(__FILE__).'/vendor/autoload.php';
require dirname(__FILE__).'/config.php';

$rootPath = dirname(__FILE__);

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

function cleardir($dir)
{
    if (is_dir($dir) && !is_link($dir)) {
        array_map('cleardir',   glob($dir.DS.'*', GLOB_ONLYDIR));
        array_map('unlink', glob($dir.DS.'*'));
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
          ->must(function ($cmd) {
              return in_array($cmd, array('clearcache'));
          })
          ->option()
          ->describedAs('Original image path')
          ->must(function ($originalImagePath) {
              if (is_null($originalImagePath)) {
                  return true;
              }
              if (!file_exists($originalImagePath)) {
                  throw new \Exception(sprintf('%s not exists', $originalImagePath));
              }

              return true;
          })
          ->option('a')
          ->aka('all')
          ->describedAs('When clear cache all, use this option')
          ->boolean();

    // clearcache
    if ($imgin[0] === 'clearcache') {

        // --all
        if ($imgin['all']) {
            foreach (glob($rootPath.DS.'*', GLOB_ONLYDIR) as $dirname) {
                if (preg_match('#/(\d+x\d+)$#', $dirname)) {
                    cleardir($dirname);
                }
            }

            return;
        }

        $originalImagePath = $imgin[1];
        if (preg_match('#^'.$rootPath.'(.+)#', $originalImagePath, $matches)) {
            $relativeImagePath = $matches[1];
            foreach (glob($rootPath.DS.'*', GLOB_ONLYDIR) as $dirname) {
                if (preg_match('#/(\d+x\d+)$#', $dirname, $matches)) {
                    $resizedImagePath = $rootPath.DS.$matches[1].$relativeImagePath;
                    if (file_exists($resizedImagePath)) {
                        unlink($resizedImagePath);
                    }
                }
            }
        }

        return;
    }
}

/**
 * Manipurate image by HTTP Request
 *
 */
$baseUrl = dirname($_SERVER['SCRIPT_NAME']);
$dirname = basename(dirname($_SERVER['SCRIPT_NAME']));
$requestUri = $_SERVER['REQUEST_URI'];
$imageUrl = preg_replace('#.+'.$dirname.'#', '', $requestUri);

// allow manipurated image cache pattern
$allow = false;
foreach ($allowCachePattern as $pattern) {
    if (preg_match('#^'.DS.$pattern.DS.'#', $imageUrl)) {
        $allow = true;
    }
}
if (!$allow) {
    header('HTTP', true, 404);
    exit;
}

if (preg_match('#^'.DS.'(\d+)x(\d+)'.DS.'(.+)$#', $imageUrl, $matches)) {
    $width = $matches[1];
    $height = $matches[2];
    $originalImageKey = $matches[3];
} else {
    header('HTTP', true, 404);
    exit;
}

$originalImagePath = $rootPath.DS.$originalImageKey;
$resizedImagePath = $rootPath.$imageUrl;

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
    header('Location: '.$requestUri, true, 307);
} catch (Exception $e) {
    header('HTTP', true, 500);
    echo $e->getMessage();
    exit;
}
