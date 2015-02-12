<?php

// ImagineInterface
$imagine = new Imagine\Gd\Imagine();

// allow manipurated image cache pattern
$allowCachePattern = array(
    '\d+x\d+', // full open
    // '100x200',
    // '140x100',
);
