<?php

namespace Application\UI\Base\Components\QuickCreate;

use appUserPreferences;
use Combodo\iTop\Application\UI\Base\Component\QuickCreate\QuickCreateHelper;
use Combodo\iTop\Test\UnitTest\ItopDataTestCase;

class QuickCreateHelperTest extends ItopDataTestCase
{
	private array $aInitialUserPref = [];
	protected function setUp(): void {
		parent::setUp();
		$this->aInitialUserPref = appUserPreferences::GetPref(QuickCreateHelper::USER_PREF_CODE, []);
	}

	protected function tearDown(): void{
		parent::tearDown();
		appUserPreferences::SetPref(QuickCreateHelper::USER_PREF_CODE, $this->aInitialUserPref);
	}

	/**
	 * Test class removal from popular when it is in recent classes
	 */
	public function testNoDuplicateInPopularAndLast()
	{
		$aClasses = ['ApplicationSolution', 'BusinessProcess', 'DatabaseSchema', 'MiddlewareInstance', 'Enclosure'];
		// Should contain the first Popular class (FunctionalCI if default)
		$aPopularClassesInitial = QuickCreateHelper::GetPopularClasses();
		$sPopularClass = $aPopularClassesInitial[0]['class'];
		QuickCreateHelper::AddClassToHistory($sPopularClass);
		// Popular class should now be in Recents and no longer in Popular
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
			// Creating as many classes as needed for UserRequest to no longer be in the Recent classes (at least equal to 'quick_create.max_history_results')
			QuickCreateHelper::AddClassToHistory($sClass);
		}
		// Should contain the first Popular class (FunctionalCI if default)
		$aPopularClassesFinal = QuickCreateHelper::GetPopularClasses();
		$this->assertEquals($aPopularClassesInitial, $aPopularClassesFinal);
	}
}