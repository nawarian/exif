<?php

declare(strict_types=1);

namespace Nawarian\Exif;

use Generator;
use InvalidArgumentException;
use SplFileInfo;

if (false === function_exists('\Nawarian\Exif\exif_read_data')) {
    /**
     * Reads all $tags from $image's EXIF metadata.
     *
     * @param resource|string|SplFileInfo $image
     * @param int[] $tags A filter filled with EXIF_TAG_* constants. If empty, all tags are read.
     *
     * @return Generator<int, mixed>
     */
    function exif_read_data($image, array $tags = []): Generator
    {
        if ($image instanceof SplFileInfo) {
            $image = $image->getRealPath();
        }

        $resource = null;
        if (is_string($image)) {
            $resource = fopen($image, 'rb');
        } elseif (is_resource($image)) {
            $resource = $image;
        }

        if (null === $resource) {
            throw new InvalidArgumentException(
                sprintf('$image must be of type resource|string|SplFileInfo, %s given.', gettype($image))
            );
        }

        yield null;
    }
}
