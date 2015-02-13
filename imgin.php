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

$rootPath = dirname(__FILE__);

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

interface ImginSource
{
    public function getType();
    public function getPath($key);
}

// File
class ImginFileSource implements ImginSource
{
    private $rootPath;
    public function __construct($rootPath)
    {
        $this->rootPath = $rootPath;
    }
    public function getType()
    {
        return 'File';
    }
    public function getPath($key)
    {
        return $this->rootPath.DS.$key;
    }
}

// S3
class ImginS3Source implements ImginSource
{
    private $client;
    private $bucket;
    private $prefix;
    public function __construct(Aws\S3\S3Client $client, $bucket, $prefix = '')
    {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->prefix = $prefix;
    }
    public function getType()
    {
        return 'S3';
    }
    public function getPath($key)
    {
        $tmpPath = DS.'tmp'.DS.'imgincache'.DS.$key;
        @unlink($tmpPath);
        return $this->createObject($key, $tmpPath);
    }
    public function createObject($key, $path){
        try {
            mkdir(dirname($path), 0777, true);
            $result = $this->client->getObject(array(
                'Bucket' => $this->bucket,
                'Key' => $this->prefix.$key,
                'SaveAs' => $path,
            ));

            return $path;
        } catch (Exception $e) {
            erorr_log($e->getMessage(), 0);

            return $path;
        }
    }
}

// Load config.php
require dirname(__FILE__).'/config.php';

function cleardir($dir)
{
    if (is_dir($dir) && !is_link($dir)) {
        array_map('cleardir',   glob($dir.DS.'*', GLOB_ONLYDIR));
        array_map('unlink', glob($dir.DS.'*'));
        rmdir($dir);
    }
}

/**
 * Clear manipulated image by CLI
 *
 */
if (php_sapi_name() == 'cli') {
    $imgin = new Commando\Command();
    $imgin->option()
          ->require()
          ->describedAs('Clear manipulated image')
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
        // S3: Clear original cache image
        if ($source->getType() === 'S3') {
            unlink($originalImagePath);
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

if (preg_match('#^'.DS.'(\d+)x(\d+)'.DS.'(.+)$#', $imageUrl, $matches)) {
    $width = $matches[1];
    $height = $matches[2];
    $originalImageKey = $matches[3];
} else {
    // S3: Create original cache image
    if ($source->getType() === 'S3') {
        $originalImageKey = preg_replace('#^'.DS.'#', '', $imageUrl);
        $cacheImagePath = $rootPath.$imageUrl;
        $path = $source->createObject($originalImageKey, $cacheImagePath);
        if (file_exists($path)) {
            header('Location: '.$requestUri, true, 307);
            exit;
        }
    }
    header('HTTP', true, 404);
    exit;
}

// allow manipulated image cache pattern
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

$originalImagePath = $source->getPath($originalImageKey);
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
            throw new OutOfBoundsException('Directory permission denied');
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
