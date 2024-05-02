<?php

declare(strict_types=1);

/*
 * (c) Konrad abicht <hi@inspirito.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace quickRdfIo\Raptor;

use quickRdfIo\RdfIoException;
use ValueError;

/**
 * This class is a proxy for the rapper command of Raptor RDF parsing and serializing utility.
 *
 * @see https://librdf.org/raptor/rapper.html
 *
 * @internal don't rely on it, may change in the future without further notice
 */
class RapperCommand
{
    /**
     * Normalize format to avoid security problems by injecting malicious code.
     *
     * @throws \ValueError
     */
    private static function getNormalizedFormat(string|null $string): string
    {
        if (null === $string) {
            return '--guess';
        } else {
            switch ($string) {
                case 'atom':
                case 'dot':
                case 'html':
                case 'json-triples':
                case 'json':
                case 'nquads':
                case 'ntriples':
                case 'rdfxml':
                case 'rdfxml-abbrev':
                case 'rdfxml-xmp':
                case 'rss-1.0':
                case 'turtle':
                    return $string;
                default:
                    throw new ValueError('Invalid $format given.');
            }
        }
    }

    public static function rapperCommandIsAvailable(): bool
    {
        // TODO: output buffer does not work properly, it still leaks output
        ob_start();
        passthru('rapper', $resultCode);
        ob_end_clean();

        return 1 == $resultCode;
    }

    /**
     * Executes rapper using shell_exec.
     *
     * @param non-empty-string $sourceFilepath Full filepath to source file
     * @param non-empty-string $targetFilepath Full filepath to target file
     * @param non-empty-string|null $sourceFileFormat Valid RDF notation, must be one of:
     *      atom            - Atom 1.0,
     *      dot             - GraphViz DOT format,
     *      html            - HTML Table,
     *      json-triples    - RDF/JSON Triples,
     *      json            - RDF/JSON Resource-Centric,
     *      nquads          - N-Quads,
     *      ntriples        - N-Triples (default),
     *      rdfxml          - RDF/XML,
     *      rdfxml-abbrev   - RDF/XML (Abbreviated),
     *      rdfxml-xmp      - RDF/XML (XMP Profile),
     *      rss-1.0         - RSS 1.0,
     *      turtle          - Turtle Terse RDF Triple Language
     *
     * @throws \quickRdfIo\RdfIoException
     * @throws \ValueError
     */
    public static function parseSourceFileAndPutGeneratedRdfIntoTargetFile(
        string $sourceFilepath,
        string $targetFilepath,
        string|null $sourceFileFormat = null
    ): string {
        $normalizedFormat = self::getNormalizedFormat($sourceFileFormat);

        if (file_exists($sourceFilepath) && file_exists($targetFilepath)) {
            $command = 'rapper --quiet '.$normalizedFormat.' -o ntriples '.$sourceFilepath.' > '.$targetFilepath;

            ob_start();
            // without this ob_-stuff rapper would print out info on the terminal/browser
            shell_exec($command);
            $output = (string) ob_get_clean();
            return $output;
        } else {
            throw new RdfIoException('Either source or target filepath points to a non existing file');
        }
    }
}
