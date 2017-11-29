<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Serializer;

use Kcs\Serializer\Handler\DateHandler;
use PHPUnit\Framework\TestCase;

class DateIntervalFormatTest extends TestCase
{
    public function testFormat()
    {
        $dtf = new DateHandler();

        $iso8601DateIntervalString = $dtf->formatInterval(new \DateInterval('PT45M'));

        $this->assertEquals($iso8601DateIntervalString, 'PT45M');

        $iso8601DateIntervalString = $dtf->formatInterval(new \DateInterval('P2YT45M'));

        $this->assertEquals($iso8601DateIntervalString, 'P2YT45M');

        $iso8601DateIntervalString = $dtf->formatInterval(new \DateInterval('P2Y4DT6H8M16S'));

        $this->assertEquals($iso8601DateIntervalString, 'P2Y4DT6H8M16S');
    }
}
