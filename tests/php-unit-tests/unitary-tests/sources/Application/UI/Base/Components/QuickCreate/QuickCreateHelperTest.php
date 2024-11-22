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
		$aClasses = ['ApplicationSolution', 'BusinessProcess', 'DatabaseSchema', 'MiddlewareInstance', 'Enclosure'];
		$aPopularClassesInitial = QuickCreateHelper::GetPopularClasses(); // Should contain the first Popular class (FunctionalCI if default)
		$sPopularClass = $aPopularClassesInitial[0]['class'];
		QuickCreateHelper::AddClassToHistory($sPopularClass);
		$aPopularClassNoParam = QuickCreateHelper::GetPopularClasses(); // Popular class should now be in Recents and no longer in Popular

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
			QuickCreateHelper::AddClassToHistory($sClass); // Creating as many classes as needed for UserRequest to no longer be in the Recent classes (at least equal to 'quick_create.max_history_results')
		}
		$aPopularClassesFinal = QuickCreateHelper::GetPopularClasses(); // Should contain UserRequest
		$this->assertEquals($aPopularClassesInitial, $aPopularClassesFinal);
	}
}