<?php

declare(strict_types=1);

namespace Nawarian\Exif;

use PHPUnit\Framework\TestCase;
use SplFileInfo;
use InvalidArgumentException;

/**
 * @covers \Nawarian\Exif\exif_read_data
 */
final class ExifReadDataTest extends TestCase
{
    /**
     * @test
     */
    public function mixedInputTypes(): void
    {
        $rawPath = __DIR__ . '/resources/exif-samples/jpg/Canon_40D.jpg';
        $splFileInfo = new SplFileInfo($rawPath);
        $resource = fopen($rawPath, 'rb');

        self::assertEquals(TAG_IMAGE_MAKE, exif_read_data($rawPath)->key());
        self::assertEquals(TAG_IMAGE_MAKE, exif_read_data($splFileInfo)->key());
        self::assertEquals(TAG_IMAGE_MAKE, exif_read_data($resource)->key());

        self::expectExceptionMessage('$image must be of type resource|string|SplFileInfo, boolean given.');
        self::expectException(InvalidArgumentException::class);
        exif_read_data(false)->current();
    }

    /**
     * @test
     */
    public function readMakerNote(): void
    {
        $rawPath = __DIR__ . '/resources/exif-samples/jpg/Canon_40D.jpg';

        $withStringInput = exif_read_data($rawPath, [TAG_IMAGE_MAKE]);
        self::assertEquals(TAG_IMAGE_MAKE, $withStringInput->key());
        self::assertEquals('Canon', $withStringInput->current());

        $withResourceInput = exif_read_data(fopen($rawPath, 'rb'), [TAG_IMAGE_MAKE]);
        self::assertEquals(TAG_IMAGE_MAKE, $withResourceInput->key());
        self::assertEquals('Canon', $withResourceInput->current());

        $withSplFileInfoInput = exif_read_data(new SplFileInfo($rawPath), [TAG_IMAGE_MAKE]);
        self::assertEquals(TAG_IMAGE_MAKE, $withSplFileInfoInput->key());
        self::assertEquals('Canon', $withSplFileInfoInput->current());
    }
}
