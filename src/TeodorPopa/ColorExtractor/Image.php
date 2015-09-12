<?php
/*
    Copyright (c) 2015 The League of Extraordinary Packages.
    (https://github.com/thephpleague/color-extractor)

    Permission is hereby granted, free of charge, to any person obtaining
    a copy of this software and associated documentation files (the "Software"),
    to deal in the Software without restriction, including without limitation
    the rights to use, copy, modify, merge, publish, distribute, sublicense,
    and/or sell copies of the Software, and to permit persons to whom the Software
    is furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in
    all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
    EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
    OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
    IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
    CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
    TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
    OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/
/*
    Changes
    2015-09-12: Teodor Popa: Extends the functionality to extract also the RGB index and color percentage.
*/


namespace TeodorPopa\ColorExtractor;

use TeodorPopa\ColorExtractor\Algorithms\CIEDE2000;
use TeodorPopa\ColorExtractor\Assets\Color;

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
     * @param resource $resource
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
    public function extract($maxPaletteSize = 5)
    {
        $colors = $this->getImageColors();
        $colorPercentage = $this->getPercentageOfColors($colors);

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

                        $colorPercentage[$refColor] += $colorPercentage[$cmpColor];
                        unset($colors[$cmpColor]);
                        unset($colorPercentage[$cmpColor]);

                        if ($i > 1) {
                            $i = 0;
                        }
                    }
                }
            }
        }

        arsort($colorPercentage);
        $finalColors = $this->parseFinalColors($colorPercentage);

        return array_slice($finalColors, 0, $paletteSize);
    }

    /**
     * @param $colorsArray
     * @return array
     */
    protected function getPercentageOfColors($colorsArray)
    {
        $totalColors = 0;
        $percentageArray = [];

        foreach ($colorsArray as $colorCount) {
            $totalColors += $colorCount;
        }

        foreach ($colorsArray as $color => $colorCount) {
            $percentage = ($colorCount/$totalColors) * 100;
            $percentageArray[$color] = $percentage;
        }

        return $percentageArray;
    }

    /**
     * @param array $percentageArray
     * @return Color[]
     */
    protected function parseFinalColors($percentageArray)
    {
        $colors = [];

        foreach ($percentageArray as $color => $info) {
            $colors[] = new Color([
                'hex' => $this->toHex($color),
                'rgb' => $this->getRGBComponents($color),
                'percentage' => round($info, 2),
            ]);
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

                $color = ($rgba['red'] * 65536) + ($rgba['green'] * 256) + ($rgba['blue']);

                if (isset($colors[$color])) {
                    $colors[$color]++;
                } else {
                    $colors[$color] = 1;
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
            (1 - $luminosity) * $count / $colorsCount : $count * $saturation * $luminosity;
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
            $component / 12.92 : pow(($component + .055) / 1.055, 2.4);
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
            pow($XYZComponent, 1 / 3) : (1 / 3) * pow(29 / 6, 2) * $XYZComponent + (4 / 29);
    }
}