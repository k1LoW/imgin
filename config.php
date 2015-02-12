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
            'region' => Region::AP_NORTHEAST_1));
$source = new ImginS3Source($client, 's3-bucket-name');
*/

$client = Aws\S3\S3Client::factory(array(
            'key' => 'AKIAJHFUO473BKOTZ6MA',
            'secret' => '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',
            'region' => Aws\Common\Enum\Region::AP_NORTHEAST_1));
$source = new ImginS3Source($client, 'imgin-test');