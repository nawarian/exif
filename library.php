<?php

declare(strict_types=1);

namespace Nawarian\Exif;

use Generator;
use InvalidArgumentException;
use PhpBinaryReader\BinaryReader;
use PhpBinaryReader\Endian;
use SplFileInfo;
use UnexpectedValueException;

const ENDIANNESS_MAP = [
    // Motorola
    0x4D4D => Endian::BIG,

    // Intel
    0x4949 => Endian::LITTLE,
];

const TAG_IMAGE_MAKE = 0x010F;

if (false === function_exists('\Nawarian\Exif\__exif_read_tag')) {
    function __exif_read_tag(BinaryReader $reader, int $tiffHeaderOffset): array
    {
        $tagOffset = $reader->getPosition();
        $tagID = (int) $reader->readUInt16();
        $format = (int) $reader->readUInt16();
        $count = $reader->readUInt32();
        $tagValue = $reader->readUInt32();

        $bytesPerComponent = 0;
        switch ($format) {
            case 1:
            case 2:
            case 6:
            case 7:
                $bytesPerComponent = 1;
                break;
            case 3:
            case 8:
                $bytesPerComponent = 2;
                break;
            case 4:
            case 9:
            case 11:
                $bytesPerComponent = 4;
                break;
            case 5:
            case 10:
            case 12:
                $bytesPerComponent = 8;
                break;
        }

        $size = $count * $bytesPerComponent;
        if ($size <= 4) {
            // The format is "UNDEFINED", fetch data as 4 bytes
            if ($format === 7 || $format === 2 || $tagID === 0x0000) {
                $reader->setPosition($reader->getPosition() - 4);
                $tagValue = $reader->readBytes(4);
            }
            return [$tagID, $tagValue];
        }

        // If size is over 4 bytes, $data represents an offset from the TIFF Header
        $oldPosition = $reader->getPosition();
        $reader->setPosition($tagValue + $tiffHeaderOffset);

        // @todo -> must consider other format types.
        // @see section 4.6.2 of https://web.archive.org/web/20190624045241if_/http://www.cipa.jp:80/std/documents/e/DC-008-Translation-2019-E.pdf
        $tagValue = $reader->readBytes($size);

        if (2 === $format) {
            $tagValue = trim($tagValue);
        }

        // Bring cursor back to the end of this ifd tag
        $reader->setPosition($oldPosition);

        return [$tagID, $tagValue];
    }
}

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

        if (true === is_string($image)) {
            $image = fopen($image, 'rb');
        }

        if (false === is_resource($image)) {
            throw new InvalidArgumentException(
                sprintf('$image must be of type resource|string|SplFileInfo, %s given.', gettype($image)),
            );
        }

        $reader = new BinaryReader($image, Endian::BIG);
        $reader->setPosition(0);

        // @todo cover this
        if ($reader->readUInt16() !== 0xFFD8) {
            throw new UnexpectedValueException('Invalid JPEG provided.');
        }

        // Jump APP0 marker (JPEG Marker)
        $marker = $reader->readUInt16();

        // Marker Start
        if (0xFFE0 === $marker) {
            // Data size (2bytes) is inclusive, so we must remove 2 bytes
            $reader->readBytes($reader->readUInt16() - 2);
            $marker = $reader->readUInt16();
        }

        // Jump APP1 marker, but make sure it is there.
        if (0xFFE1 !== $marker) {
            // @todo create CorruptExifException and throw it instead
            throw new UnexpectedValueException('Could not find APP1 marker.');
        }
        $reader->readBytes(2);

        // Exif Header
        if ('Exif' !== $reader->readString(4)) {
            // @todo create CorruptExifException and throw it instead
            throw new UnexpectedValueException('Could not find the Exif header.');
        }
        // Jump bytes 0x00 after "Exif"
        $reader->readBytes(2);

        // Tiff Header
        $tiffHeaderOffset = $reader->getPosition();
        $reader->setEndian(ENDIANNESS_MAP[$reader->readBits(16)]);

        // Jump TAG mark 0x002A
        $reader->readBytes(2);

        // Relative to Tiff Header (current pos - 8 bytes)
        $ifdRelativeOffset = $reader->readUInt32();

        // Read IFD0 tags
        $tagCount = $reader->readUInt16();
        $exifOffset = null;
        $gpsOffset = null;

        for ($i = 0; $i < $tagCount; $i++) {
            list($tagId, $tagValue) = __exif_read_tag($reader, $tiffHeaderOffset);

            if ([] === $tags || true === in_array($tagId, $tags)) {
                yield $tagId => $tagValue;
            }

            // If points to EXIF offset, mark it
            if (0x8769 === $tagId) {
                $exifOffset = $tiffHeaderOffset + $tagValue;
                continue;
            }

            // If points to GPS offset, mark it
            if (0x8825 === $tagId) {
                $gpsOffset = $tiffHeaderOffset + $tagValue;
                continue;
            }
        }
    }
}

function hex_dump($data, $newline="\n")
{
    static $from = '';
    static $to = '';

    static $width = 16; # number of bytes per line

    static $pad = '.'; # padding for non-visible characters

    if ($from==='')
    {
        for ($i=0; $i<=0xFF; $i++)
        {
            $from .= chr($i);
            $to .= ($i >= 0x20 && $i <= 0x7E) ? chr($i) : $pad;
        }
    }

    $hex = str_split(bin2hex($data), $width*2);
    $chars = str_split(strtr($data, $from, $to), $width);

    $offset = 0;
    foreach ($hex as $i => $line)
    {
        echo sprintf('%6X',$offset).' : '.implode(' ', str_split($line,2)) . ' [' . $chars[$i] . ']' . $newline;
        $offset += $width;
    }
}
