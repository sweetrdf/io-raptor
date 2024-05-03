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
    private static function getNormalizedFormat(string|null $format): string
    {
        if (null === $format) {
            // rapper will guess the format by itself
            return '--guess';
        } else {
            $allowedFormats = [
                'atom',
                'dot',
                'html',
                'json-triples',
                'json',
                'nquads',
                'ntriples',
                'rdfxml',
                'rdfxml-abbrev',
                'rdfxml-xmp',
                'rss-1.0',
                'turtle',
            ];

            if (in_array($format, $allowedFormats, true)) {
                return '-i '.$format;
            } else {
                $msg = 'Given format is invalid, it must be one of: '.implode(', ', $allowedFormats);
                throw new ValueError($msg);
            }
        }
    }

    /**
     * Checks if rapper command is available.
     */
    public static function rapperCommandIsAvailable(): bool
    {
        $checkCommand = str_contains(PHP_OS, 'WIN') ? 'where' : 'command -v';

        $shellResult = (string) shell_exec($checkCommand.' rapper');

        return is_executable(trim($shellResult));
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
    ): void {
        $normalizedFormat = self::getNormalizedFormat($sourceFileFormat);

        if (file_exists($sourceFilepath) && file_exists($targetFilepath)) {
            $command = 'rapper --quiet '.$normalizedFormat.' -o ntriples '.$sourceFilepath.' > '.$targetFilepath;
            exec($command);
        } else {
            throw new RdfIoException('Either source or target filepath points to a non existing file');
        }
    }
}
