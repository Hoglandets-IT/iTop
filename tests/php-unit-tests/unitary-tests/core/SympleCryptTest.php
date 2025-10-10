<?php
/*!
 * @copyright   Copyright (C) 2010-2024 Combodo SAS
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Test\UnitTest\Core;

use Combodo\iTop\Test\UnitTest\ItopDataTestCase;
use SodiumException;

/**
 * Tests of the ormPassword class
 */
class SympleCryptTest extends ItopDataTestCase
{
	public function testDecryptWithNullValue()
	{
		$oSimpleCrypt = new \SimpleCrypt("Sodium");
		$this->assertEquals(null, $oSimpleCrypt->Decrypt("dd", null));
	}

	public function testDecryptWithEmptyValue()
	{
		$oSimpleCrypt = new \SimpleCrypt("Sodium");
		$this->assertEquals('', $oSimpleCrypt->Decrypt("dd", ""));
	}

	public function testDecrypNonDecryptableValue()
	{
		$this->expectException(SodiumException::class);
		$oSimpleCrypt = new \SimpleCrypt("Sodium");
		$this->assertEquals('', $oSimpleCrypt->Decrypt("dd", "gabuzomeu"));
	}

}
