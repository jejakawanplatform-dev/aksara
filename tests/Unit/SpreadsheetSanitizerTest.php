<?php

/**
 * Aksara — platform pembelajaran berbantuan AI.
 *
 * @copyright 2026 jejakawan (https://jejakawan.com)
 * @license   MIT
 *
 * Clone, fork, and modification are permitted under the MIT License.
 * See the LICENSE file in the project root.
 */

namespace Tests\Unit;

use App\Support\Spreadsheet\SpreadsheetSanitizer;
use PHPUnit\Framework\TestCase;

class SpreadsheetSanitizerTest extends TestCase
{
    public function test_normal_string_is_unmodified(): void
    {
        $this->assertEquals('Matematika Kelas 7', SpreadsheetSanitizer::sanitize('Matematika Kelas 7'));
        $this->assertEquals('12345', SpreadsheetSanitizer::sanitize('12345'));
        $this->assertEquals('John Doe (Guru)', SpreadsheetSanitizer::sanitize('John Doe (Guru)'));
    }

    public function test_dangerous_prefixes_are_neutralized_with_single_quote(): void
    {
        // Formula =
        $this->assertEquals("'=1+1", SpreadsheetSanitizer::sanitize('=1+1'));
        $this->assertEquals("'=cmd|' /C calc'!A0", SpreadsheetSanitizer::sanitize("=cmd|' /C calc'!A0"));
        $this->assertEquals("'=HYPERLINK(\"http://evil.com\",\"Click\")", SpreadsheetSanitizer::sanitize('=HYPERLINK("http://evil.com","Click")'));

        // Formula +
        $this->assertEquals("'+1+1", SpreadsheetSanitizer::sanitize('+1+1'));

        // Formula -
        $this->assertEquals("'-1+1", SpreadsheetSanitizer::sanitize('-1+1'));

        // Formula @
        $this->assertEquals("'@SUM(1,2)", SpreadsheetSanitizer::sanitize('@SUM(1,2)'));

        // Tab & CR
        $this->assertEquals("'\tcmd", SpreadsheetSanitizer::sanitize("\tcmd"));
        $this->assertEquals("'\rcmd", SpreadsheetSanitizer::sanitize("\rcmd"));
    }

    public function test_whitespace_with_dangerous_prefix_is_neutralized(): void
    {
        $this->assertEquals("'   =HYPERLINK(1)", SpreadsheetSanitizer::sanitize('   =HYPERLINK(1)'));
    }

    public function test_null_and_empty_string_are_handled(): void
    {
        $this->assertEquals('', SpreadsheetSanitizer::sanitize(null));
        $this->assertEquals('', SpreadsheetSanitizer::sanitize(''));
    }
}
