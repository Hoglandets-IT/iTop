<?php
/*
 * @copyright   Copyright (C) 2010-2024 Combodo SAS
 * @license     http://opensource.org/licenses/AGPL-3.0
 */


namespace Combodo\iTop\Test\UnitTest\Core;

use Combodo\iTop\Test\UnitTest\ItopDataTestCase;
use InlineImage;

class InlineImageTest extends ItopDataTestCase
{
	/**
	 * @dataProvider OnFormCancelInvalidTempIdProvider
	 *
	 * @param $sTempId
	 * @param bool $bExpectedReturn
	 *
	 * @throws \ArchivedObjectException
	 * @throws \CoreCannotSaveObjectException
	 * @throws \CoreException
	 * @throws \CoreUnexpectedValue
	 * @throws \DeleteException
	 * @throws \MySQLException
	 * @throws \MySQLHasGoneAwayException
	 * @throws \OQLException
	 * @covers       InlineImage::OnFormCancel()
	 */
	public function testOnFormCancelInvalidTempId($sTempId, bool $bExpectedReturn)
	{
		$bTestReturn = InlineImage::OnFormCancel($sTempId);
		$this->assertEquals($bExpectedReturn, $bTestReturn);
	}

	public function OnFormCancelInvalidTempIdProvider()
	{
		return [
			'Null temp_id' => [
				null,
				false,
			],
			'Empty temp_id' => [
				'',
				false,
			],
			'0 as integer temp_id' => [
				0,
				true,
			],
			'0 as string temp_id' => [
				'0',
				true,
			],
			'String temp_id' => [
				'fake_temp_id',
				true,
			],
		];
	}

	public function testSetDefaultOrgIdWhenLoggedInWithContact()
	{
		$iContactOrgId = $this->GivenObjectInDB('Organization', ['name' => 'TestOrg']);
		$this->GivenUserLoggedInWithContact($iContactOrgId);

		$oInlineImage = \MetaModel::NewObject('InlineImage',['item_class' => 'UserRequest']);
		$oInlineImage->SetDefaultOrgId();
		$this->assertEquals($iContactOrgId, $oInlineImage->Get('item_org_id'),'The org_id should be the one of the contact');

		$oInlineImage = \MetaModel::NewObject('InlineImage',['item_class' => 'TriggerOnObjectCreation']);
		$oInlineImage->SetDefaultOrgId();
		$this->assertEquals(0, $oInlineImage->Get('item_org_id'),'The org_id should be left undefined');
	}


	public function testSetDefaultOrgIdWhenLoggedInWithoutContact()
	{
		$this->GivenUserLoggedInWithoutContact();

		$oInlineImage = \MetaModel::NewObject('InlineImage',['item_class' => 'UserRequest']);
		$oInlineImage->SetDefaultOrgId();
		$this->assertEquals(0, $oInlineImage->Get('item_org_id'),'The org_id should be left undefined');

		$oInlineImage = \MetaModel::NewObject('InlineImage',['item_class' => 'TriggerOnObjectCreation']);
		$oInlineImage->SetDefaultOrgId();
		$this->assertEquals(0, $oInlineImage->Get('item_org_id'),'The org_id should be left undefined');
	}

	private function GivenUserLoggedInWithContact(int $iContactOrgId)
	{
		$iContactId = $this->GivenObjectInDB('Person', [
			'first_name' => 'TestContact',
			'name' => 'TestContact',
			'org_id' => $iContactOrgId]);
		$sLogin = 'demo_test_'.uniqid(__CLASS__, true);
		$iUser = $this->GivenObjectInDB('UserLocal', [
			'login' => $sLogin,
			'password' => 'tagada-Secret,007',
			'language' => 'EN US',
			'contactid' => $iContactId,
			'profile_list' => [
				'profileid:'.self::$aURP_Profiles['Configuration Manager']
			]
		]);
		\UserRights::Login($sLogin);
	}
	private function GivenUserLoggedInWithoutContact()
	{
		$sLogin = 'demo_test_'.uniqid(__CLASS__, true);
		$iUser = $this->GivenObjectInDB('UserLocal', [
			'login' => $sLogin,
			'password' => 'tagada-Secret,007',
			'language' => 'EN US',
			'profile_list' => [
				'profileid:'.self::$aURP_Profiles['Configuration Manager']
			]
		]);
		\UserRights::Login($sLogin);
	}
}
