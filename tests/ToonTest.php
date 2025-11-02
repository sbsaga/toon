<?php

use PHPUnit\Framework\TestCase;
use Sbsagar\Toon\Converters\ToonConverter;

class ToonTest extends TestCase
{
    public function testArrayOfObjectsProducesTabular()
    {
        $conv = new ToonConverter(['min_rows_to_tabular' => 1]);
        $json = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob']
        ];

        $out = $conv->toToon($json);
        $this->assertStringContainsString('items[2]{id,name}:', $out);
        $this->assertStringContainsString('1,Alice', $out);
        $this->assertStringContainsString('2,Bob', $out);
    }
}
