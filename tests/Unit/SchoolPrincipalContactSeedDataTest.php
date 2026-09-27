<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SchoolPrincipalContactSeedDataTest extends TestCase
{
    public function test_school_principal_contact_source_has_one_complete_record_per_seeded_school(): void
    {
        $path = dirname(__DIR__, 2).'/database/seeders/data/school-name-principal-name-mobile-no.json';
        $records = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(110, $records);

        foreach ($records as $index => $record) {
            $this->assertSame($index + 1, $record['id']);
            $this->assertNotSame('', trim($record['school_name']));
            $this->assertNotSame('', trim($record['principal_name']));
            $this->assertNotSame('', trim($record['principal_phone']));
        }
    }
}
