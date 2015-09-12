<?php

namespace TeodorPopa\ColorExtractor\Exporters;

use TeodorPopa\ColorExtractor\Assets\Color;
use TeodorPopa\ColorExtractor\ColorExporter;

class Aco implements ExporterInterface
{

    /**
     * @var string
     */
    protected $filename = 'aco_file.aco';

    /**
     * @param Color[] $colors
     * @param string $filename
     */
    public function export($colors, $options = [])
    {
        $filename = (!empty($options[ColorExporter::EXPORTER_OPTION_FILENAME])) ?: $this->filename;

        $acoFile = $this->createAcoFile($colors);

        header("Content-type: application/octet-stream");
        header('Content-Length: ' . strlen($acoFile));
        header("Content-disposition: attachment; filename=\"" . $filename . "\"");

        print $acoFile;
    }

    /**
     * @param Color[] $colors
     * @return string
     */
    protected function createAcoFile($colors)
    {
        $colorCount = count($colors);

        $out = $this->convert(1);
        $out .= $this->convert($colorCount);

        for ($k = 1; $k <= $colorCount; $k++) {
            $out .= $this->convert(0);
            $out .= $this->convert(($colors[$k]->rgb[0]<<8)|$colors[$k]->rgb[0]);
            $out .= $this->convert(($colors[$k]->rgb[1]<<8)|$colors[$k]->rgb[1]);
            $out .= $this->convert(($colors[$k]->rgb[2]<<8)|$colors[$k]->rgb[2]);
            $out .= $this->convert(0);
        }

        $out .= $this->convert(2);
        $out .= $this->convert($colorCount);

        for ($l = 1; $l <= $colorCount; $l++) {
            $out .= $this->convert(0);
            $out .= $this->convert(($colors[$l]->rgb[0]<<8)|$colors[$l]->rgb[0]);
            $out .= $this->convert(($colors[$l]->rgb[1]<<8)|$colors[$l]->rgb[1]);
            $out .= $this->convert(($colors[$l]->rgb[2]<<8)|$colors[$l]->rgb[2]);
            $out .= $this->convert(0);
            $out .= $this->convert(0);
            $out .= $this->convert(strlen($this->colors[$l]->hex) + 1);

            for ($m = 0; $m <= strlen($this->colors[$l]->hex) - 1; $m++) {
                $out .= $this->convert(ord(substr($this->colors[$l]->hex, $m, $m + 1)));
            }

            $out .= $this->convert(0);
        }

        return $out;
    }

    /**
     * @param $x
     * @return string
     */
    protected function convert($x)
    {
        return sprintf("%c%c", ($x>>8)&0xff, $x&0xff);
    }


}