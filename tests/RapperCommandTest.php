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
use quickRdfIo\Raptor\RapperCommand;

class RapperCommandTest extends TestCase
{
    public function testRapperCommandIsAvailable(): void
    {
        $this->assertTrue(
            RapperCommand::rapperCommandIsAvailable(),
            'rapper not found, please install raptor2-utils'
        );
    }
}
