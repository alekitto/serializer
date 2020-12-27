<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Serializer;

use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Tests\Fixtures\Csv\BomObject;
use Kcs\Serializer\Tests\Fixtures\Csv\DelimiterObject;
use Kcs\Serializer\Tests\Fixtures\Csv\EnclosureObject;
use Kcs\Serializer\Tests\Fixtures\Csv\EscapeCharObject;
use Kcs\Serializer\Tests\Fixtures\Csv\EscapeFormulas;
use Kcs\Serializer\Tests\Fixtures\Csv\KeySeparator;
use Kcs\Serializer\Tests\Fixtures\Csv\NoHeadersObject;
use Kcs\Serializer\Tests\Fixtures\Price;
use Prophecy\PhpUnit\ProphecyTrait;

class CsvSerializationTest extends BaseSerializationTest
{
    use ProphecyTrait;

    public function testCsvDelimiter(): void
    {
        self::assertEquals($this->getContent('delimiter'), $this->serialize(new DelimiterObject()));
    }

    public function testCsvEnclosure(): void
    {
        self::assertEquals($this->getContent('enclosure'), $this->serialize(new EnclosureObject()));
    }

    public function testCsvEscapeChar(): void
    {
        self::assertEquals($this->getContent('escape_char'), $this->serialize(new EscapeCharObject()));
    }

    public function testCsvEscapeFormulas(): void
    {
        self::assertEquals($this->getContent('escape_formulas'), $this->serialize([
            new EscapeFormulas('=2+3'),
            new EscapeFormulas('+2+3'),
            new EscapeFormulas('-2+3'),
            new EscapeFormulas('@MyData'),
        ]));
    }

    public function testCsvKeySeparator(): void
    {
        self::assertEquals($this->getContent('key_separator'), $this->serialize(new KeySeparator(new Price(12.34))));
    }

    public function testCsvNoHeaders(): void
    {
        self::assertEquals($this->getContent('no_headers'), $this->serialize(new NoHeadersObject()));
    }

    public function testCsvOutputBom(): void
    {
        self::assertEquals($this->getContent('bom'), $this->serialize(new BomObject()));
    }

    /**
     * {@inheritdoc}
     */
    protected function getContent(string $key): string
    {
        if (! \file_exists($file = __DIR__.'/csv/'.$key.'.csv')) {
            throw new RuntimeException(\sprintf('The content with key "%s" does not exist.', $key));
        }

        return \file_get_contents($file);
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormat(): string
    {
        return 'csv';
    }

    /**
     * {@inheritdoc}
     */
    protected function hasDeserializer(): bool
    {
        return false;
    }
}
