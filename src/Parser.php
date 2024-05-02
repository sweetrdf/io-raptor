<?php

declare(strict_types=1);

/*
 * (c) Konrad abicht <hi@inspirito.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace quickRdfIo\Raptor;

use Error;
use Exception;
use Psr\Http\Message\ResponseInterface;
use quickRdfIo\NQuadsParser;
use quickRdfIo\RdfIoException;
use quickRdfIo\ResourceWrapper;
use rdfInterface\DataFactoryInterface;
use rdfInterface\ParserInterface;
use rdfInterface\QuadIteratorInterface;
use Stringable;
use ValueError;

/**
 * This class provides parsing functionality through the rapper command of Raptor RDF parsing and serializing utility.
 *
 * @api
 */
class Parser implements ParserInterface
{
    private DataFactoryInterface $dataFactory;

    /**
     * @var non-empty-string|null
     */
    private string|null $format = null;

    /**
     * @var non-empty-string
     */
    private string $path;

    /**
     * @var array<non-empty-string>
     */
    private array $tempFiles = [];

    /**
     * @throws \Error
     * @throws \Exception
     */
    public function __construct(DataFactoryInterface $dataFactory)
    {
        $this->dataFactory = $dataFactory;

        $tempDir = sys_get_temp_dir();
        if (0 < strlen($tempDir)) {
            $this->setDirPathForTemporaryFiles($tempDir);
        } else {
            throw new Exception('sys_get_temp_dir() return empty dir path');
        }

        if (false === RapperCommand::rapperCommandIsAvailable()) {
            throw new Exception('rapper command is not available (install raptor2-utils)');
        }
    }

    public function __destruct()
    {
        // try to remove temporary created files in the end
        foreach ($this->tempFiles as $tempFile) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * @throws \ValueError
     */
    public function setFormat(string|null $format): void
    {
        if (is_string($format) && 0 < strlen($format)) {
            $this->format = '-i '.$format;
        } elseif (null === $format) {
            $this->format = null;
        } else {
            throw new ValueError('Parameter $format must not be empty.');
        }
    }

    /**
     * Dir path for temporary files.
     *
     * @param non-empty-string $path
     *
     * @throws \Error
     */
    public function setDirPathForTemporaryFiles(string $path): void
    {
        if (file_exists($path)) {
            // removes / at the end
            if (str_ends_with($path, '/')) {
                $path = substr_replace($path, '/', -1);
            }

            $this->path = $path;
        } else {
            throw new Error('Given dir path for temporary files does not exist.');
        }
    }

    /**
     * Implementation is heavily inspired by quickRdfIo's Util::parse method.
     *
     * First, how does rapper command work? It reads a given file and output triples in terminal.
     * To avoid using too much memory, the output can be redirected to another file.
     * This allows reading the target file using a quickRdfIo parser without the need to load all of
     * its content to the memory.
     *
     * @param resource|string|\Psr\Http\Message\ResponseInterface|\Psr\Http\Message\StreamInterface $input
     *
     * @see https://github.com/sweetrdf/quickRdfIo/blob/master/src/quickRdfIo/Util.php
     *
     * @throws \ValueError
     * @throws \quickRdfIo\RdfIoException
     */
    public function parse($input): QuadIteratorInterface
    {
        if ($input instanceof ResponseInterface) {
            //       ,--- makes it explicit that the return value is a string
            $input = (string) $input->getBody();
        }

        // this catches $input being a string or of type StreamInterface
        if (is_string($input) || $input instanceof Stringable) {
            $input = (string) $input;
            // if it can't be fopen()-ed, treat it as a string containing RDF and turn it into temp stream
            $stream = @fopen($input, 'r');
            if ($stream === false) {
                // creates a temporary file
                $tempFilePath = tempnam($this->path, 'raptor_parser_source_file_');
                if (false === $tempFilePath) {
                    throw new RdfIoException('Failed to create temporary file using tempnam');
                } elseif(0 == strlen($tempFilePath)) {
                    throw new RdfIoException('tempnam return an empty string');
                }
                $this->tempFiles[] = $tempFilePath;

                // create a file handle and write $input to file
                file_put_contents($tempFilePath, $input);

                // create file handle for file
                $stream = fopen($tempFilePath, 'r') ?: throw new RdfIoException('Failed to convert input to a stream');
            }
            $input = $stream;
        }

        if (is_resource($input)) {
            // turn resource input into StreamInterface for uniform read API
            $input = new ResourceWrapper($input);
        } else {
            // we only reach this point if it was not possible to convert $input to a readable file handle
            throw new RdfIoException('Invalid case reached: $input is in invalid format');
        }

        // at the point the input is available via a given file handle or
        // a file handle we created on the fly

        // get full file path from file handle
        $metaData = $input->getMetadata();
        if (is_array($metaData) && isset($metaData['uri'])) {
            /** @var non-empty-string */
            $sourceFilePath = $metaData['uri'];
        } else {
            throw new RdfIoException('Could not get filepath from source file');
        }

        // generate a target file to put generated RDF in
        $targetFilepath = tempnam($this->path, 'raptor_parser_source_file_');
        if (false === $targetFilepath) {
            throw new RdfIoException('tempnam for target file failed');
        } elseif (0 == strlen($targetFilepath)) {
            throw new RdfIoException('$targetFilepath can not be an empty string');
        }

        $this->tempFiles[] = $targetFilepath;

        // read source file and transform to target RDF
        RapperCommand::parseSourceFileAndPutGeneratedRdfIntoTargetFile(
            $sourceFilePath,
            $targetFilepath,
            $this->format
        );

        // create NQuads parser to parse generated target file
        $parser = new NQuadsParser($this->dataFactory, false, NQuadsParser::MODE_TRIPLES);

        $fileHandle = fopen($targetFilepath, 'r');
        if (false === $fileHandle) {
            throw new RdfIoException('fopen for file target file failed: '.$targetFilepath);
        }

        return $parser->parseStream($fileHandle);
    }

    /**
     * @param resource|\Psr\Http\Message\StreamInterface $input
     *
     * @return \rdfInterface\QuadIteratorInterface
     *
     * @throws \ValueError
     * @throws \quickRdfIo\RdfIoException
     */
    public function parseStream($input): QuadIteratorInterface
    {
        return $this->parse($input);
    }
}
