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

        self::assertEquals(null, exif_read_data($rawPath)->current());
        self::assertEquals(null, exif_read_data($splFileInfo)->current());
        self::assertEquals(null, exif_read_data($resource)->current());

        self::expectExceptionMessage('$image must be of type resource|string|SplFileInfo, boolean given.');
        self::expectException(InvalidArgumentException::class);
        exif_read_data(false)->current();
    }
}
