<?php

namespace Doka;

require_once('Image.class.php');
require_once('Vector.class.php');
require_once('Rect.class.php');
require_once('Utils.php');

function transform($source, $target, $transforms) {
        
    $image = new Image($source);

    foreach ($transforms as $transform) {
        $action = key((array)$transform);
        call_user_func(array('Doka\\Core', $action), $image, $transform[$action]);
    }

    $image->save($target, -1);

}

class Core {

    public static function crop($image, $crop) {

        function rotate($image, $rotation) {
            return imagerotate($image, -$rotation * (180 / pi()), 0);
        }

        function scale($image, $scale) {
            return imagescale(
                $image, 
                imagesx($image) * $scale, 
                imagesy($image) * $scale
            );
        }

        // get image size
        $image_size = $image->getSize();

        $aspect_ratio = $crop->aspectRatio ? $crop->aspectRatio : $image_size->height / $image_size->width;

        $crop_center = new Vector(
            $crop->center ? $crop->center->x : .5,
            $crop->center ? $crop->center->y : .5
        );
        
        $output_rect = calculateOutputSize($image_size, $aspect_ratio, $crop->zoom);

        $stage_zoom_factor = getImageRectZoomFactor(
            $image_size,
            getCenteredCropRect(
                $output_rect,
                $aspect_ratio
            ),
            $crop->rotation,
            $crop_center
        );

        $scale = ($crop->zoom ? $crop->zoom : 1) * $stage_zoom_factor;

        // flip
        $transformed = $image->flip($crop->flip->horizontal, $crop->flip->vertical);

        // apply scale
        $transformed = scale($transformed, $scale);
        
        // apply rotation
        $transformed = rotate($transformed, $crop->rotation);

        // get output size
        $scaled_rotated_size = new Rect(0, 0, imagesx($transformed),  imagesy($transformed));
        
        // set center to move rotated and scaled image to center of output image
        $translate_x = (-$scaled_rotated_size->center->x) + $output_rect->center->x;
        $translate_y = (-$scaled_rotated_size->center->y) + $output_rect->center->y;

        $width_scaled = $image_size->width * $scale;
        $height_scaled = $image_size->height * $scale;

        // correct offset with only center values
        $offset_x = ($width_scaled * .5) - ($width_scaled * $crop_center->x);
        $offset_y = ($height_scaled * .5) - ($height_scaled * $crop_center->y);
        
        // correct for image rotation
        $sin = sin($crop->rotation);
        $cos = cos($crop->rotation);
        $translate_x += ($cos * $offset_x) - ($sin * $offset_y);
        $translate_y += ($sin * $offset_x) + ($cos * $offset_y);

        $output = $image->createWithSameFormat($output_rect->width, $output_rect->height);
        imagecopy(
            // target <= source
            $output, $transformed,

            // translate to image offset
            $translate_x, $translate_y,

            // draw whole of output image
            0, 0, $scaled_rotated_size->width, $scaled_rotated_size->height
        );

        $image->update($output);
        
        // remove transformed version from memory
        imagedestroy($transformed);
    }

    public static function resize($image, $size) {

        $image_size = $image->getSize();

        $output_size = new Rect(0, 0,
            isset($size->width) ? $size->width : $size->height,
            isset($size->height) ? $size->height : $size->width
        );

        if ($output_size->width == null && $output_size->height == null) {
            return $input;
        }

        $target_rect = new Rect(0, 0, $output_size->width, $output_size->height);

        if ($size->mode !== 'force') {

            $x = 0;
            $y = 0;
            
            $scalar_width = $target_rect->width / $image_size->width;
            $scalar_height = $target_rect->height / $image_size->height;
            $scalar = 1;

            if ($size->mode === 'cover') {
                $scalar = max($scalar_width, $scalar_height);
            }
            else if ($size->mode === 'contain') {
                $scalar = min($scalar_width, $scalar_height);
            }

            if ($scalar > 1 && $size->upscale === false) {
                return $input;
            }

            $width = $image_size->width * $scalar;
            $height = $image_size->height * $scalar;

            if ($size->mode === 'cover') {
                $x = $target_rect->width * .5 - $width * .5;
                $y = $target_rect->height * .5 - $height * .5;
            }
            else if ($size->mode === 'contain') {
                $output_size = new Rect(0, 0,
                    $width,
                    $height
                );
            }
            
            $target_rect = new Rect($x, $y, $width, $height);
        }

        $output = $image->createWithSameFormat($output_size->width, $output_size->height);

        imagecopyresampled(
            // target <= source
            $output, $image->resource,

            // dx, dy
            $target_rect->x, $target_rect->y,

            // sx, sy
            0, 0,
            
            // dw, dh
            $target_rect->width, $target_rect->height,

            // sw, sh
            $image_size->width, $image_size->height
        );

        $image->update($output);
    }

}