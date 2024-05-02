<?php

declare(strict_types=1);

/*
 * (c) Konrad abicht <hi@inspirito.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use quickRdf\DataFactory;
use quickRdfIo\Raptor\Parser;
use quickRdfIo\Raptor\RapperCommand;
use rdfInterface\QuadIteratorInterface;

class ParserTest extends TestCase
{
    private string $testRdfString = '<http://bar> <http://baz> "1" .'.PHP_EOL.'<http://bar> <http://baz> "2" .'.PHP_EOL;

    public function setUp(): void
    {
        parent::setUp();

        if (false === RapperCommand::rapperCommandIsAvailable()) {
            $this->markTestSkipped('rapper command line tool not available (install raptor2-utils');
        }
    }

    private function getSubjectUnderTest(): Parser
    {
        $subjectUnderTest = new Parser(new DataFactory());
        $subjectUnderTest->setDirPathForTemporaryFiles(__DIR__.'/../cache');
        return $subjectUnderTest;
    }

    /**
     * @return array<non-empty-string>
     */
    private function generateTripleStringArray(QuadIteratorInterface $iterator): array
    {
        $generated = [];

        foreach ($iterator as $quad) {
            $generated[] = (string) $quad;
        }

        return $generated;
    }

    public function testParseString(): void
    {
        $iterator = $this->getSubjectUnderTest()->parse($this->testRdfString);

        $this->assertEquals(
            ['http://bar http://baz 1', 'http://bar http://baz 2'],
            $this->generateTripleStringArray($iterator)
        );
    }

    public function testParseResource(): void
    {
        // put test RDF into a temp. file
        $filepath = tempnam(sys_get_temp_dir(), 'phpunit_parsertest_');
        file_put_contents($filepath, $this->testRdfString);
        $resource = fopen($filepath, 'r');

        $iterator = $this->getSubjectUnderTest()->parse($resource);

        $this->assertEquals(
            ['http://bar http://baz 1', 'http://bar http://baz 2'],
            $this->generateTripleStringArray($iterator)
        );
    }

    public function testParseResponseInterface(): void
    {
        // create a mock class for StreamInterface and ResponseInterface
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn($this->testRdfString);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $iterator = $this->getSubjectUnderTest()->parse($response);

        $this->assertEquals(
            ['http://bar http://baz 1', 'http://bar http://baz 2'],
            $this->generateTripleStringArray($iterator)
        );
    }

    public function testParseStreamInterface(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn($this->testRdfString);

        $iterator = $this->getSubjectUnderTest()->parse($stream);

        $this->assertEquals(
            ['http://bar http://baz 1', 'http://bar http://baz 2'],
            $this->generateTripleStringArray($iterator)
        );
    }

    public function testParseStreamResource(): void
    {
        // put test RDF into a temp. file
        $filepath = tempnam(sys_get_temp_dir(), 'phpunit_parsertest_');
        file_put_contents($filepath, $this->testRdfString);
        $resource = fopen($filepath, 'r');

        $iterator = $this->getSubjectUnderTest()->parseStream($resource);

        $this->assertEquals(
            ['http://bar http://baz 1', 'http://bar http://baz 2'],
            $this->generateTripleStringArray($iterator)
        );
    }

    public function testParseStreamStreamInterface(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn($this->testRdfString);

        $iterator = $this->getSubjectUnderTest()->parseStream($stream);

        $this->assertEquals(
            ['http://bar http://baz 1', 'http://bar http://baz 2'],
            $this->generateTripleStringArray($iterator)
        );
    }
}
