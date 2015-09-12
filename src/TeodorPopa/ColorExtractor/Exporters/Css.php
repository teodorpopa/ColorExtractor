<?php

namespace TeodorPopa\ColorExtractor\Exporters;

use TeodorPopa\ColorExtractor\Assets\Color;

class Css implements ExporterInterface
{
    /**
     * @var string
     */
    protected $filename = 'css_file.css';

    /**
     * @var string
     */
    protected $css = '';

    public function export($colors, $options = [])
    {
        $filename = (!empty($options[ColorExporter::EXPORTER_OPTION_FILENAME])) ?: $this->filename;

        if (in_array(ColorExporter::EXPORTER_OPTION_COMMENT, $options)) {
            $this->prependCssComment($options[ColorExporter::EXPORTER_OPTION_COMMENT]);
        }

        $this->createCssFile($colors);

        $cssString = (in_array(ColorExporter::EXPORTER_OPTION_MINIFY, $options)) ? $this->minify() : $this->css;

        header("Content-type: text/css");
        header('Content-Length: ' . strlen($cssString));
        header("Content-disposition: attachment; filename=\"" . $filename . "\"");

        print $cssString;
    }

    /**
     * @param Color[] $colors
     * @return bool
     */
    protected function createCssFile($colors)
    {
        $css = $this->css;

        foreach ($colors as $color) {
            $css .= ".color_" . ltrim($color->hex, '#') . " {";
            $css .= "\tcolor: " . $color->hex;
            $css .= "}";
            $css .= "\n";
            $css .= ".background_" . ltrim($color->hex, '#') . " {";
            $css .= "\tbackground-color: " . $color->hex;
            $css .= "}";
            $css .= "\n";
        }

        $this->css = $css;

        return true;
    }

    /**
     * @param string $comment
     */
    protected function prependCssComment($comment)
    {
        $string = '/*' . PHP_EOL;
        $string .= ' *' . PHP_EOL;

        $lines = explode("/n", $comment);

        foreach ($lines as $line) {
            $string .= ' * ' . $line;
        }

        $string .= ' *' . PHP_EOL;
        $string .= ' *//';

        $this->css .= $string;

        return true;
    }

    /**
     * @param string $content
     * @return string
     */
    protected function minify()
    {
        $content = $this->css;

        // strip comments
        $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);

        // strip space after colons
        $content = str_replace(': ', ':', $content);

        // strip whitespace
        $content = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $content);

        return $content;
    }
}