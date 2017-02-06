<?php

namespace TeodorPopa\ColorExtractor\Exporters;

use TeodorPopa\ColorExtractor\ColorExporter;

class Png implements ExporterInterface
{
    /**
     * @var string
     */
    protected $filename = 'color_export.png';

    /**
     * @var resource
     */
    protected $img;

    /**
     * @var array
     */
    protected $exportColors = [];

    /**
     * Color box width
     */
    const COLOR_WIDTH = 60;

    /**
     * Color box height
     */
    const COLOR_HEIGHT = 40;

    /**
     * Color box padding
     */
    const COLOR_PADDING = 20;

    /**
     * Number of colors per row
     */
    const COLORS_ON_ROW = 3;

    /**
     * Title font size
     */
    const FONT_TITLE_SIZE = 14;

    /**
     * Color code font size
     */
    const FONT_COLOR_SIZE = 11;

    /**
     * @var array
     */
    protected $colors = [
        'black' => [0, 0, 0],
        'white' => [255, 255, 255]
    ];

    /**
     * Export colors
     *
     * @param $colors
     * @param array $options
     */
    public function export($colors, $options = [])
    {
        // set colors to export
        $this->setExportColors($colors);

        // calculate image size and build image
        $imageSize = $this->calculateImageSize();
        $this->setImage($this->createNewImage($imageSize['width'], $imageSize['height']));

        //read image base colors
        $black = $this->getColor('black');
        $blackColor = imagecolorallocate($this->getImage(), $black['red'], $black['green'], $black['blue']);

        $white = $this->getColor('white');
        $whiteColor = imagecolorallocate($this->getImage(), $white['red'], $white['green'], $white['blue']);

        // fill background
        imagefill($this->getImage(), 0, 0, $whiteColor);

        // write image title
        imagettftext($this->getImage(), self::FONT_TITLE_SIZE, 0, self::COLOR_PADDING, self::COLOR_PADDING, $blackColor, $this->getFont(), 'Logo colors: ');

        // build color boxes
        $this->buildColors($colors, $blackColor);

        // display / download image
        $this->displayImage();
    }

    /**
     * Build color boxes
     *
     * @param $colors
     */
    protected function buildColors($colors, $fontColor)
    {
        $x = self::COLOR_PADDING;
        $y = $this->getInitialYStartPoint();
        $row = 1;
        $colorNo = 0;

        foreach ($colors as $color) {

            $squareColors = imagecolorallocate($this->getImage(), $color->rgb[0], $color->rgb[1], $color->rgb[2]);
            imagettftext($this->getImage(), self::FONT_COLOR_SIZE, 0, $x + 3, $y + self::COLOR_PADDING, $fontColor, $this->getFont(), $color->hex);
            imagefilledrectangle($this->getImage(), $x, $y + self::FONT_COLOR_SIZE + self::FONT_COLOR_SIZE, $x + self::COLOR_WIDTH, $y + self::COLOR_HEIGHT + self::FONT_COLOR_SIZE, $squareColors);

            $colorNo++;
            $x = ((self::COLOR_WIDTH + self::COLOR_PADDING) * $colorNo) + self::COLOR_PADDING;

            if ($colorNo == self::COLORS_ON_ROW) {
                $row++;
                $colorNo = 0;
                $x = self::COLOR_PADDING;
                $y = $this->getInitialYStartPoint() + (self::COLOR_PADDING * ($row + 1));
            }
        }
    }

    /**
     * Get font path
     *
     * @return string
     */
    protected function getFont()
    {
        return dirname(__FILE__) . '/../../../../fonts/calibri.ttf';
    }

    /**
     * Calculate initial Y star point
     *
     * @return int
     */
    protected function getInitialYStartPoint()
    {
        return self::COLOR_PADDING * 2 + self::FONT_TITLE_SIZE;
    }

    /**
     * Get color array from RGB values
     *
     * @param $colorName
     * @return array|null
     */
    public function getColor($colorName)
    {
        if (array_key_exists($colorName, $this->colors)) {
            return array_combine(['red', 'green', 'blue'], $this->colors[$colorName]);
        } else {
            return null;
        }
    }

    /**
     * Calculate total image size
     *
     * @return array
     */
    protected function calculateImageSize()
    {
        $colors = count($this->getExportColors());
        $rows = ceil($colors / self::COLORS_ON_ROW);

        return [
            'width' => ((self::COLORS_ON_ROW * self::COLOR_WIDTH) + (self::COLORS_ON_ROW * self::COLOR_PADDING) + self::COLOR_PADDING),
            'height' => (($rows * self::COLOR_HEIGHT) + ($rows * self::COLOR_PADDING) + self::COLOR_PADDING + $this->getInitialYStartPoint())
        ];
    }

    /**
     * @param $image
     */
    protected function setImage($image)
    {
        $this->img = $image;
    }

    /**
     * @return resource
     */
    public function getImage()
    {
        return $this->img;
    }

    /**
     * @param $colors
     */
    protected function setExportColors($colors)
    {
        $this->exportColors = $colors;
    }

    /**
     * @return array
     */
    public function getExportColors()
    {
        return $this->exportColors;
    }

    /**
     * @param $width
     * @param $height
     * @return resource
     */
    protected function createNewImage($width, $height)
    {
        return imagecreatetruecolor($width, $height);
    }

    /**
     * Display / Download the generated image
     */
    protected function displayImage()
    {
        $filename = (!empty($options[ColorExporter::EXPORTER_OPTION_FILENAME])) ?
            $options[ColorExporter::EXPORTER_OPTION_FILENAME] : $this->filename;

        header('Content-Disposition: Attachment;filename=' . $filename);
        header('Content-type: image/png');

        imagepng($this->getImage());
        imagedestroy($this->getImage());
    }
}