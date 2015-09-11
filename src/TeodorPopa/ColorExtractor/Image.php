<?php

namespace TeodorPopa\ColorExtractor;

use TeodorPopa\ColorExtractor\Algorithm\CIEDE2000;

/**
 * Class Image
 * @package TeodorPopa\ColorExtractor
 */
class Image
{
    /**
     * @var resource
     */
    protected $image;

    /**
     * @var array
     */
    protected $colors;

    /**
     * @param resource $imageResource
     */
    public function __construct($resource)
    {
        $this->image = $resource;
    }

    /**
     * @param int $maxPaletteSize
     *
     * @return array
     */
    public function extract($maxPaletteSize = 8)
    {
        $colors = $this->getImageColors();

        $totalColorCount = $finalColorCount = count($colors);

        foreach ($colors as $color => &$data) {
            $data = $this->getColorScore($color, $data, $totalColorCount);
        }

        arsort($colors, SORT_NUMERIC);

        $paletteSize = min($maxPaletteSize, $finalColorCount);

        if ($finalColorCount > $maxPaletteSize) {
            $minDeltaE = 100 / ($paletteSize + 1);
            $LabCache = array();

            $i = 0;
            $mergeCount = 0;
            while ($i++ < $paletteSize) {
                if ($paletteSize >= count($colors)) {
                    break;
                }
                $j = 0;
                reset($colors);
                while (++$j < $i) {
                    next($colors);
                }
                $refColor = key($colors);

                if (!isset($LabCache[$refColor])) {
                    $LabCache[$refColor] = $this->getLabFromColor($refColor);
                }

                if ($mergeCount) {
                    $offset = max($i, $paletteSize - $mergeCount - 1);
                    while ($j++ < $offset) {
                        next($colors);
                    }
                    $mergeCount = 0;
                }
                while ($j++ <= $paletteSize) {
                    if (next($colors) === false) {
                        break;
                    };
                    $cmpColor = key($colors);

                    if (!isset($LabCache[$cmpColor])) {
                        $LabCache[$cmpColor] = $this->getLabFromColor($cmpColor);
                    }

                    if (CIEDE2000::diff($LabCache[$refColor], $LabCache[$cmpColor]) <= $minDeltaE) {
                        $j--;
                        $mergeCount++;
                        prev($colors);
                        unset($colors[$cmpColor]);
                        if ($i > 1) {
                            $i = 0;
                        }
                    }
                }
            }
        }

        $finalColors = $this->parseFinalColors($colors);

        return array_slice($finalColors, 0, $paletteSize);
    }

    /**
     * @param array $colorsArray
     * @return array
     */
    protected function parseFinalColors($colorsArray)
    {
        $colors = [];

        foreach ($colorsArray as $color => $info) {
            $colors[] = [
                'hex' => $this->toHex($color),
                'rgb' => $this->getRGBComponents($color),
                'percent' => $info
            ];
        }

        return $colors;
    }

    /**
     * @return array
     */
    protected function getImageColors()
    {
        $imageWidth = imagesx($this->image);
        $imageHeight = imagesy($this->image);

        if (imageistruecolor($this->image) !== true) {
            imagepalettetotruecolor($this->image);
        }

        $colors = [];

        for ($x = 0; $x < $imageWidth; $x++) {
            for ($y = 0; $y < $imageHeight; $y++) {
                $color = imagecolorat($this->image, $x, $y);
                $rgba = imagecolorsforindex($this->image, $color);

                if ($rgba['alpha'] == 127) {
                    continue;
                }

                $color = ($rgba['red']*65536) + ($rgba['green']*256) + ($rgba['blue']);

                if (isset($colors[$color])) {
                    $colors[$color]['count']++;
                } else {
                    $colors[$color]['count'] = 1;
                }
            }
        }

        return $colors;
    }

    /**
     * @param $color
     *
     * @return string
     */
    protected function toHex($color)
    {
        $rgb = $this->getRGBComponents($color);

        return sprintf('#%02X%02X%02X', $rgb[0], $rgb[1], $rgb[2]);
    }

    /**
     * @param $color
     *
     * @return array
     */
    protected function getLabFromColor($color)
    {
        return $this->getLabFromSRGB($this->getSRGBComponents($this->getRGBComponents($color)));
    }

    /**
     * @param $sRGBComponents
     *
     * @return array
     */
    protected function getLabFromSRGB($sRGBComponents)
    {
        return $this->getLabComponents($this->getXYZComponents($sRGBComponents));
    }

    /**
     * @param $color
     *
     * @return array
     */
    protected function getRGBComponents($color)
    {
        return array(
            ($color >> 16) & 0xFF,
            ($color >> 8) & 0xFF,
            $color & 0xFF,
        );
    }

    /**
     * @param $color
     * @param $count
     * @param $colorsCount
     *
     * @return float
     */
    protected function getColorScore($color, $count, $colorsCount)
    {
        $sRGBComponents = $this->getSRGBComponents($this->getRGBComponents($color));
        $max = max($sRGBComponents);
        $min = min($sRGBComponents);
        $diff = $max - $min;
        $sum = $max + $min;
        $saturation = $diff ? ($sum / 2 > .5 ? $diff / (2 - $diff) : $diff / $sum) : 0;
        $luminosity = ($sum / 2 + .2126 * $sRGBComponents[0] + .7152 * $sRGBComponents[1] + .0722 * $sRGBComponents[2])
            / 2;

        return $saturation < .5 ?
            (1 - $luminosity) * $count / $colorsCount :
            $count * ($saturation) * $luminosity;
    }

    /**
     * @param $RGBComponents
     *
     * @return array
     */
    protected function getSRGBComponents($RGBComponents)
    {
        return array(
            $this->getSRGBComponent($RGBComponents[0]),
            $this->getSRGBComponent($RGBComponents[1]),
            $this->getSRGBComponent($RGBComponents[2]),
        );
    }

    /**
     * @param $component
     *
     * @return float|number
     */
    protected function getSRGBComponent($component)
    {
        $component /= 255;

        return $component <= .03928 ?
            $component / 12.92 :
            pow(($component + .055) / 1.055, 2.4);
    }

    /**
     * @param $sRGBComponents
     *
     * @return array
     */
    protected function getXYZComponents($sRGBComponents)
    {
        list($r, $g, $b) = $sRGBComponents;

        return array(
            .4124 * $r + .3576 * $g + .1805 * $b,
            .2126 * $r + .7152 * $g + .0722 * $b,
            .0193 * $r + .1192 * $g + .9505 * $b,
        );
    }

    /**
     * @param $XYZComponents
     *
     * @return array
     */
    protected function getLabComponents($XYZComponents)
    {
        list($x, $y, $z) = $XYZComponents;
        $fY = $this->xyzToLabStep($y);

        return array(
            116 * $fY - 16,
            500 * ($this->xyzToLabStep($x) - $fY),
            200 * ($fY - $this->xyzToLabStep($z)),
        );
    }

    /**
     * @param $XYZComponent
     *
     * @return float|number
     */
    protected function xyzToLabStep($XYZComponent)
    {
        return $XYZComponent > pow(6 / 29, 3) ?
            pow($XYZComponent, 1 / 3) :
            (1 / 3) * pow(29 / 6, 2) * $XYZComponent + (4 / 29);
    }
}