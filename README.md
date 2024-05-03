# Raptor Parser (RDF)

It provides a RDF parser using `rapper` command line tool from [Raptor RDF parsing and serializing utility](https://librdf.org/raptor/rapper.html).
The generated internal PHP-representations (quads, ...) are compatible to [https://github.com/sweetrdf/rdfInterface](https://github.com/sweetrdf/rdfInterface) and can be used together with other rdfInterface implementations, such as [quickRdfIo](https://github.com/sweetrdf/quickRdfIo) to serialize RDF in another format, for instance.

## Installation

### Requirements

* PHP 8.0+
* Raptor RDF parser utility is installed (we need its `rapper` command line tool); its usually not part of the default installation
  * on Debian/Ubuntu: run `apt-get install raptor2-utils`
  * Package might be namend differently in other Linux distributions

### Composer

Install it using Composer: `composer require sweetrdf/io-raptor`.

You **need** an implementation of rdfInterfaces, such as quickRdf.
For starters, just use `composer require sweetrdf/quick-rdf`

## Usage

**Note:** We are using rdfInterface implementations from https://github.com/sweetrdf/quickRdf in the following.
Make sure you installed the package before running the code.

### Parsing

**Parse a file:**

```php
use \quickrdf\DataFactory;
use \quickRdfIo\Raptor\Parser;

// create a file handle for a n-quads/n-triple file
$fileHandle = fopen('/path/to/n-quads-file.nq', 'r');

// init a parser instance and read file handle
$parser = new Parser(new DataFactory());
$quadsIterator = $parser->parseStream($fileHandle);

// iterate through the quads
// note: the file isn't read before, only as you iterating $quadIterator
foreach ($quadsIterator as $quad ) {
    var_dump($quad);
}

// free file handle
fclose($fileHandle);
```

**Parse a string:**

```php
use \quickrdf\DataFactory;
use \quickRdfIo\Raptor\Parser;

$str = '_:foo <http://foo> <http://bar> .';

// init a parser instance and read RDF string
$parser = new Parser(new DataFactory());
$quadsIterator = $parser->parse($str);

// iterate through the quads
foreach ($quadsIterator as $quad ) {
    var_dump($quad);
}
```

## Caveats and known issues

When using this class keep the following in mind:

* Even though output buffering is used, `exec` keeps leaking output in the terminal (maybe in the browser too?). It is shown at least in PHPUnit tests.
* The `Parser` class is basically a wrapper around the `rapper` command, which means input must be prepared for further processing (may impact performance). A readable local file as source is needed as well as a local file for generated N-Quads output, which is used for the internal NQuads parser later on.

## License

[MIT](./LICENSE)
