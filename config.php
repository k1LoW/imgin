<?php
/*
  Imgin configs

  $imagine
  $allowCachePattern
  $source
*/

// ImagineInterface
$imagine = new Imagine\Gd\Imagine();

// allow manipurated image cache pattern
$allowCachePattern = array(
    '\d+x\d+', // full open
    // '100x200',
    // '140x100',
);

// ImginSource
// File
$source = new ImginFileSource($rootPath);

/*
// S3
$client = S3Client::factory(array(
            'key' => 'Your Access Key',
            'secret' => 'Your Secret Key',
            'region' => Region::AP_NORTHEAST_1,
            'credentials.cache' => true,
          ));
$source = new ImginS3Source($client, 's3-bucket-name');
*/
