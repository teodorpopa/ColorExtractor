<?php

namespace TeodorPopa\ColorExtractor;

/**
 * Class ColorExtractor
 * @package TeodorPopa\ColorExtractor
 */
class ColorExtractor
{

    /**
     * @param $imagePath
     * @return Image
     * @throws \Exception
     */
    public static function load($imagePath)
    {
        try {
            $imageInfo = getimagesize($imagePath);

            if(!is_array($imageInfo) || empty($imageInfo[2])) {
                throw new \Exception('Error reading the image type');
            }

            switch ($imageInfo[2]) {
                case IMAGETYPE_PNG:
                    $imageResource = imagecreatefrompng($imagePath);
                    break;
                case IMAGETYPE_JPEG:
                    $imageResource = imagecreatefromjpeg($imagePath);
                    break;
                case IMAGETYPE_GIF:
                    $imageResource = imagecreatefromgif($imagePath);
                    break;
                case IMAGETYPE_BMP:
                    $imageResource = imagecreatefrombmp($imagePath);
                    break;
                default:
                    throw new \Exception('This image type is not supported.');
            }

            return new Image($imageResource);
        } catch (\Exception $e) {
            throw $e;
        }
    }

}