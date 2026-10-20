<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function test_rupiah_formats_with_dot_thousands_separator(): void
    {
        $this->assertSame('Rp1.250.000', rupiah(1250000));
        $this->assertSame('Rp0', rupiah(null));
    }

    public function test_rupiah_short_uses_indonesian_suffixes(): void
    {
        $this->assertSame('Rp26,3 jt', rupiah_short(26345500));
        $this->assertSame('Rp1,5 M', rupiah_short(1500000000));
        $this->assertSame('Rp25 rb', rupiah_short(25000));
    }
}
