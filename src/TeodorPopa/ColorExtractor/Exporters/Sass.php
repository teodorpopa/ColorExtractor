<?php

namespace TeodorPopa\ColorExtractor\Exporters;

use TeodorPopa\ColorExtractor\Assets\Color;
use TeodorPopa\ColorExtractor\ColorExporter;

class Sass implements ExporterInterface
{
    /**
     * @var string
     */
    protected $filename = 'sass_file.scss';

    /**
     * @var string
     */
    protected $sass = '';

    public function export($colors, $options = [])
    {
        $filename = (!empty($options[ColorExporter::EXPORTER_OPTION_FILENAME])) ?
            $options[ColorExporter::EXPORTER_OPTION_FILENAME] : $this->filename;

        if (array_key_exists(ColorExporter::EXPORTER_OPTION_COMMENT, $options)) {
            $this->prependSassComment($options[ColorExporter::EXPORTER_OPTION_COMMENT]);
        }

        $this->createSassFile($colors);

        header("Content-type: text/plain");
        header('Content-Length: ' . strlen($this->sass));
        header("Content-disposition: attachment; filename=\"" . $filename . "\"");

        print $this->sass;
    }

    /**
     * @param Color[] $colors
     * @return bool
     */
    protected function createSassFile($colors)
    {
        $sass = $this->sass;

        foreach ($colors as $color) {
            $hex = (substr($color->hex, 0, 1) == "#") ? $color->hex : "#" . $color->hex;
            $sass .= "\$color_" . ltrim($hex, '#') . ": " . $hex . PHP_EOL;
        }

        $this->sass = $sass;

        return true;
    }

    /**
     * @param string $comment
     */
    protected function prependSassComment($comment)
    {
        $string = '/*' . PHP_EOL;
        $string .= ' *' . PHP_EOL;

        $lines = explode("/n", $comment);

        foreach ($lines as $line) {
            $string .= ' * ' . $line;
        }

        $string .= ' *' . PHP_EOL;
        $string .= ' *//';

        $this->sass .= $string;

        return true;
    }
}