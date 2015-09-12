<?php

namespace TeodorPopa\ColorExtractor;

use TeodorPopa\ColorExtractor\Assets\Color;
use TeodorPopa\ColorExtractor\Exporters\Aco;
use TeodorPopa\ColorExtractor\Exporters\Css;
use TeodorPopa\ColorExtractor\Exporters\Sass;
use TeodorPopa\ColorExtractor\Exporters\Png;

class ColorExporter
{

    /**
     * option to set the filename of an export
     */
    const EXPORTER_OPTION_FILENAME = 'filename';

    /**
     * prepend a comment in the exported file
     * for .css or .sass
     */
    const EXPORTER_OPTION_COMMENT = 'comment';

    /**
     * minify a exported file content
     * for .css
     */
    const EXPORTER_OPTION_MINIFY = 'minify';

    /**
     * Export a color palette in one of the following formats:
     * - aco -> Adobe Color Format [.aco]
     * - css -> Cascading Style Sheet [.css]
     * - png -> Portable Network Graphics [.png]
     * - sass -> Syntactically Awesome Style Sheets [.scss]
     *
     * @param Color[] $colors
     * @param string $format
     */
    public static function export(array $colors, $format = 'aco')
    {
        if (empty($colors)) {
            throw new \Exception('Please provide a valid Color[] to export.');
        }

        switch ($format) {
            case 'aco':
                $exporter = new Aco();
                break;
            case 'css':
                $exporter = new Css();
                break;
            case 'png':
                $exporter = new Png();
                break;
            case 'sass':
                $exporter = new Sass();
                break;
            default:
                throw new \Exception('Invalid export format');
        }

        return $exporter->export($colors);
    }
}