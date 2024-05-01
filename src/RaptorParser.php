<?php

declare(strict_types=1);

namespace RaptorParser;

use Error;
use LogicException;
use Psr\Http\Message\StreamInterface;
use rdfInterface\DataFactoryInterface;
use rdfInterface\ParserInterface;
use rdfInterface\QuadIteratorInterface;
use ValueError;

/**
 * This class is a proxy for the rapper command of Raptor RDF parsing and serializing utility.
 */
class RaptorParser implements ParserInterface
{
    private DataFactoryInterface $dataFactory;

    /**
     * @var non-empty-string
     */
    private string $format = '--guess';

    /**
     * @var non-empty-string
     */
    private string $path;

    /**
     * @param array<non-empty-string>
     */
    private array $tempFiles = [];

    public function __construct(DataFactoryInterface $dataFactory)
    {
        $this->dataFactory = $dataFactory;

        $this->setDirPathForTemporaryFiles(sys_get_temp_dir());
    }

    public function __destruct()
    {
        // try to remove temporary created files in the end
        foreach ($this->tempFiles as $tempFile) {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    /**
     * @param non-empty-string $format
     *
     * @throws \ValueError
     */
    public function setFormat(string $format): void
    {
        if (0 < strlen($format)) {
            $this->format = '-i '.$format;
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
     * @param string $input RDF input
     */
    public function parse(string $input): QuadIteratorInterface
    {
        $tempFilePath = tempnam($this->path, 'raptor_parser_');
        $this->tempFiles[] = $tempFilePath;

        $command = 'rapper '.$this->format.' -o ntriples '.$localFilePath.' > '.$tempFilePath;
    }

    /**
     * @param resource|\Psr\Http\Message\StreamInterface $input
     * @return \rdfInterface\QuadIteratorInterface
     */
    public function parseStream($input): QuadIteratorInterface
    {

    }
}