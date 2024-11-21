<?php

namespace Application\UI\Base\Components\QuickCreate;

use Combodo\iTop\Application\UI\Base\Component\QuickCreate\QuickCreateHelper;
use Combodo\iTop\Test\UnitTest\ItopDataTestCase;

class QuickCreateHelperTest extends ItopDataTestCase
{

	/**
	 * Test class removal from popular when it is in recent classes
	 */
	public function testNoDuplicateInPopularAndLast()
	{
		$sPopularClass = 'Server';
		$aClasses = ['ApplicationSolution', 'BusinessProcess', 'DatabaseSchema', 'MiddlewareInstance', 'Enclosure'];
		$aPopularClassesInitial = QuickCreateHelper::GetPopularClasses();
		QuickCreateHelper::AddClassToHistory($sPopularClass);
		$aPopularClassNoParam = QuickCreateHelper::GetPopularClasses();

		for($iIdx = 0; $iIdx < count($aPopularClassNoParam); $iIdx++)
		{
			$this->assertNotEquals($aPopularClassNoParam[$iIdx]['class'], $sPopularClass);
		}

		return [$aClasses, $aPopularClassesInitial];
	}

	/**
	 *  Test class addition in popular after being removed from recent classes
	 *  @depends testNoDuplicateInPopularAndLast
	 */
	public function testPopularClassBackAfterRecent(array $aNoDuplicateResult){
		[$aClasses, $aPopularClassesInitial] = $aNoDuplicateResult;

		foreach($aClasses as $sClass)
		{
			QuickCreateHelper::AddClassToHistory($sClass);
		}
		$aPopularClassesFinal = QuickCreateHelper::GetPopularClasses();
		$this->assertEquals($aPopularClassesInitial, $aPopularClassesFinal);
	}
}