<?php

namespace Doka\Helper;
use Doka\Helper\Rect;
use Doka\Helper\Vector;

class Utils
{
    public static function calculateOutputSize($imageSize, $outputAspectRatio, $zoom = 1) {

        $outputWidth = 1;
        $outputHeight = $outputAspectRatio;
        $imageAspectRatio = $imageSize->height / $imageSize->width;
        $imageWidth = 1;
        $imageHeight = $imageAspectRatio;

        if ($imageHeight > $outputHeight) {
            $imageHeight = $outputHeight;
            $imageWidth = $imageHeight / $imageAspectRatio;
        }

        $scalar = max($outputWidth / $imageWidth, $outputHeight / $imageHeight);

        $width = $imageSize->width / ($zoom * $imageWidth * $scalar);
        $height = $width * $outputAspectRatio;

        return new Rect(0, 0, $width, $height);
    }

    public static function getImageRectZoomFactor($imageSize, $cropRect, $rotation = 0, $center) {

        $cx = $center->x > .5 ? 1 - $center->x : $center->x;
        $cy = $center->y > .5 ? 1 - $center->y : $center->y;
        $imageWidth = $cx * 2 * $imageSize->width;
        $imageHeight = $cy * 2 * $imageSize->height;

        $rotatedCropSize = self::getRotatedRectSize($cropRect, $rotation);

        return max(
            $rotatedCropSize->width / $imageWidth,
            $rotatedCropSize->height / $imageHeight
        );
    }

    public static function getOffsetPointOnEdge($length, $rotation) {

        $a = $length;

        $A = 1.5707963267948966;
        $B = $rotation;
        $C = 1.5707963267948966 - $rotation;

        $sinA = sin($A);
        $sinB = sin($B);
        $sinC = sin($C);
        $cosC = cos($C);
        $ratio = $a / $sinA;
        $b = $ratio * $sinB;
        $c = $ratio * $sinC;

        return new Vector($cosC * $b, $cosC * $c);
    }

    public static function getRotatedRectSize($cropRect, $rotation) {

        $w = $cropRect->width;
        $h = $cropRect->height;

        $hor = self::getOffsetPointOnEdge($w, $rotation);
        $ver = self::getOffsetPointOnEdge($h, $rotation);

        $tl = new Vector(
            $cropRect->x + abs($hor->x),
            $cropRect->y - abs($hor->y)
        );

        $tr = new Vector(
            $cropRect->x + $cropRect->width + abs($ver->y),
            $cropRect->y + abs($ver->x)
        );

        $bl = new Vector(
            $cropRect->x - abs($ver->y),
            ($cropRect->y + $cropRect->height) - abs($ver->x)
        );

        return new Rect(0, 0, self::vectorDistance($tl, $tr), self::vectorDistance($tl, $bl));
    }

    public static function getCenteredCropRect($containerRect, $aspect_ratio) {

        $width = $containerRect->width;
        $height = $width * $aspect_ratio;
        if ($height > $containerRect->height) {
            $height = $containerRect->height;
            $width = $height / $aspect_ratio;
        }

        $x = ($containerRect->width - $width) * .5;
        $y = ($containerRect->height - $height) * .5;

        return new Rect($x, $y, $width, $height);
    }

    public static function vectorDistance($a, $b) {
        return sqrt(self::vectorDistanceSquared($a, $b));
    }

    public static function vectorDistanceSquared($a, $b) {
        return self::vectorDot(self::vectorSubtract($a, $b), self::vectorSubtract($a, $b));
    }

    public static function vectorSubtract($a, $b) {
        return new Vector($a->x - $b->x, $a->y - $b->y);
    }

    public static function vectorDot($a, $b) {
        return $a->x * $b->x + $a->y * $b->y;
    }
}
