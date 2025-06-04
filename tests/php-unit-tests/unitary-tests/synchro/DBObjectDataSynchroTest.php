<?php
/**
 * Copyright (C) 2013-2024 Combodo SAS
 * This file is part of iTop.
 * iTop is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * iTop is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License
 */

namespace Combodo\iTop\Test\UnitTest\Synchro;

use CMDBObject;
use Combodo\iTop\Test\UnitTest\ItopCustomDatamodelTestCase;
use DBObject;
use DBObjectSearch;
use DBObjectSet;
use DBSearch;
use EventWithTitleAsReconciliationKey;
use Exception;
use MetaModel;
use SynchroDataSource;
use UserLocal;
use utils;

/**
 * Class DataSynchroTest
 *
 * @package Combodo\iTop\Test\UnitTest\Synchro
 * @group dataSynchro
 * @group defaultProfiles
 */
class DBObjectDataSynchroTest extends ItopCustomDatamodelTestCase
{
	protected const AUTH_USER = 'DBObjectDataSynchroTest';
	protected const AUTH_PWD = 'sdf234(-fgh;,dfgDFG';
	const USE_TRANSACTION = false;

	public function GetDatamodelDeltaAbsPath(): string
	{
		return __DIR__.'/add-dbobject-with-reconciliation-key.xml';
	}

	protected function setUp(): void
	{
		parent::setUp();

		$oSearch = DBSearch::FromOQL('SELECT User WHERE login = "'.static::AUTH_USER.'"');
		$oSet = new DBObjectSet($oSearch);
		if ($oSet->Count() == 0)
		{
			$iProfileId = self::$aURP_Profiles['REST Services User'];
			$oProfileSearch = DBSearch::FromOQL("SELECT URP_Profiles WHERE id = $iProfileId");
			$oProfileSearch->AllowAllData();
			$oProfileSet = new DBObjectSet($oProfileSearch);
			$oAdminProfile = $oProfileSet->fetch();

			$oUser = MetaModel::NewObject('UserLocal',  array(
				'login' => static::AUTH_USER,
				'password' => static::AUTH_PWD,
				'expiration' => UserLocal::EXPIRE_NEVER,
			));
			$oProfiles = $oUser->Get('profile_list');
			$oProfiles->AddItem(MetaModel::NewObject('URP_UserProfile', array(
				'profileid' => $oAdminProfile->GetKey()
			)));
			$oUser->Set('profile_list', $oProfiles);
			$oUser->DBInsertNoReload();
		}
	}

	protected function ExecSynchroImport($aParams, $bSynchroByHttp)
	{
		if (!$bSynchroByHttp) {
			return utils::ExecITopScript('synchro/synchro_import.php', $aParams, static::AUTH_USER, static::AUTH_PWD);
		}

		$aParams['auth_user'] = static::AUTH_USER;
		$aParams['auth_pwd'] = static::AUTH_PWD;

		//$aParams['output'] = 'details';
		$aParams['csvdata'] = file_get_contents($aParams['csvfile']);


		$sUrl = \MetaModel::GetConfig()->Get('app_root_url').'/synchro/synchro_import.php?login_mode=form';
		$sResult = utils::DoPostRequest($sUrl, $aParams, null, $aResponseHeaders, [
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => 0,
		]);
		// Read the status code from the last line
		$aLines = explode("\n", trim(strip_tags($sResult)));
		//$sLastLine = array_pop($aLines);

		return array(0, $aLines);
	}

	/**
	 * Run a series of data synchronization through the REST API
	 * @throws \ArchivedObjectException
	 * @throws \CoreCannotSaveObjectException
	 * @throws \CoreException
	 * @throws \CoreUnexpectedValue
	 * @throws \CoreWarning
	 * @throws \MySQLException
	 * @throws \OQLException
	 */
	public function RunDataSynchroTest($aUserLoginUsecase)
	{
		$sDescription = $aUserLoginUsecase['desc'];
		$sTargetClass = $aUserLoginUsecase['target_class'];
		$aSourceProperties = $aUserLoginUsecase['source_properties'];
		$aSourceData = $aUserLoginUsecase['source_data'];
		$aTargetData = $aUserLoginUsecase['target_data'];
		$aAttributes =$aUserLoginUsecase['attributes'];
		$bSynchroByHttp = $aUserLoginUsecase['bSynchroByHttp'];

		$sClass = $sTargetClass;

		$aTargetAttributes = array_shift($aTargetData);
		$aSourceAttributes = array_shift($aSourceData);

		if (count($aSourceData) + 1 != count($aTargetData))
		{
			throw new Exception("Target data must contain exactly ".(count($aSourceData) + 1)." items, found ".count($aTargetData));
		}

		// Create the data source
		//
		$oDataSource = new SynchroDataSource();
		$oDataSource->Set('name', 'Test data sync '.time());
		$oDataSource->Set('description', 'unit test - created automatically');
		$oDataSource->Set('status', 'production');
		$oDataSource->Set('user_id', 0);
		$oDataSource->Set('scope_class', $sClass);
		foreach ($aSourceProperties as $sProperty => $value)
		{
			$oDataSource->Set($sProperty, $value);
		}
		$iDataSourceId = $oDataSource->DBInsert();

		$oAttributeSet = $oDataSource->Get('attribute_list');
		while ($oAttribute = $oAttributeSet->Fetch())
		{
			if (array_key_exists($oAttribute->Get('attcode'), $aAttributes))
			{
				$aAttribInfo = $aAttributes[$oAttribute->Get('attcode')];
				if (array_key_exists('reconciliation_attcode', $aAttribInfo))
				{
					$oAttribute->Set('reconciliation_attcode', $aAttribInfo['reconciliation_attcode']);
				}
				$oAttribute->Set('update', $aAttribInfo['do_update']);
				$oAttribute->Set('reconcile', $aAttribInfo['do_reconcile']);
			}
			else
			{
				$oAttribute->Set('update', false);
				$oAttribute->Set('reconcile', false);
			}
			$oAttribute->DBUpdate();
		}

		// Prepare list of prefixes -> make sure objects are unique with regard to the reconciliation scheme
		$aPrefixes = array(); // attcode => prefix
		foreach($aSourceAttributes as $iDummy => $sAttCode)
		{
			$aPrefixes[$sAttCode] = ''; // init with something
		}
		foreach($aAttributes as $sAttCode => $aAttribInfo)
		{
			if (isset($aAttribInfo['automatic_prefix']) && $aAttribInfo['automatic_prefix'])
			{
				$aPrefixes[$sAttCode] = 'TEST_'.$iDataSourceId.'_';
			}
		}

		// List existing objects (to be ignored in the analysis)
		//
		$oAllObjects = new DBObjectSet(new DBObjectSearch($sClass));
		$aExisting = $oAllObjects->ToArray(true);
		$sExistingIds = implode(', ', array_keys($aExisting));

		// Create the initial object list
		//
		$aInitialTarget = $aTargetData[0];
		foreach($aInitialTarget as $aObjFields)
		{
			$oNewTarget = MetaModel::NewObject($sClass);
			foreach($aTargetAttributes as $iAtt => $sAttCode)
			{
				$oNewTarget->Set($sAttCode, $aPrefixes[$sAttCode].$aObjFields[$iAtt]);
			}
			$oNewTarget->DBInsertNoReload();
		}

		//add sleep to make sure expected objects will be found
		usleep(10000);
		foreach($aTargetData as $iRow => $aExpectedObjects)
		{
			// Check the status (while ignoring existing objects)
			//
			if (empty($sExistingIds))
			{
				$oObjects = new DBObjectSet(DBObjectSearch::FromOQL("SELECT $sClass"));
			}
			else
			{
				$oObjects = new DBObjectSet(DBObjectSearch::FromOQL("SELECT $sClass WHERE id NOT IN($sExistingIds)"));
			}
			$aFound = $oObjects->ToArray();
			$aErrors_Unexpected = array();
			foreach($aFound as $iObj => $oObj)
			{
				// Is this object in the expected objects list
				$bFoundMatch = false;
				foreach($aExpectedObjects as $iExp => $aValues)
				{
					$bDoesMatch = true;
					foreach($aTargetAttributes as $iCol => $sAttCode)
					{
						if ($oObj->Get($sAttCode) != $aPrefixes[$sAttCode].$aValues[$iCol])
						{
							$bDoesMatch = false;
							break;
						}
					}
					if ($bDoesMatch)
					{
						$bFoundMatch = true;
						unset($aExpectedObjects[$iExp]);
						break;
					}
				}
				if (!$bFoundMatch)
				{
					$aObjDesc = array();
					foreach($aTargetAttributes as $iCol => $sAttCode)
					{
						$aObjDesc[$sAttCode] = $oObj->Get($sAttCode);
					}
					$aErrors_Unexpected[get_class($oObj).'::'.$oObj->GetKey()] = $aObjDesc;
				}
			}

			// Display the current status
			//
			$aErrors = array();
			if (count($aErrors_Unexpected) > 0) {
				$aErrors[] = "Unexpected objects found in iTop DB after step $iRow (starting at 0):\n".print_r($aErrors_Unexpected, true);
			}
			if (count($aExpectedObjects) > 0) {
				$aErrors[] = "Expected objects NOT found in iTop DB after step $iRow (starting at 0)\n".print_r($aExpectedObjects, true);
			}
			if (count($aErrors) > 0) {
				$sAdditionalInfo = (isset($sResultsViewable)) ? $sResultsViewable : "";
				static::fail(implode("\n", $aErrors) . "\n $sAdditionalInfo");
			} else {
				static::assertTrue(true);
			}

			// If not on the final row, run a data exchange sequence
			//
			if (array_key_exists($iRow, $aSourceData))
			{
				$aToBeLoaded = $aSourceData[$iRow];

				// First line
				$sCsvData = implode(';', $aSourceAttributes)."\n";

				$sTextQualifier = '"';

				foreach($aToBeLoaded as $aDataRow)
				{
					$aFinalData = array();
					foreach($aDataRow as $iCol => $value)
					{
						$sAttCode = $aSourceAttributes[$iCol];
						$sRawValue = $aPrefixes[$sAttCode].$value;

						$sFrom = array("\r\n", $sTextQualifier);
						$sTo = array("\n", $sTextQualifier.$sTextQualifier);
						$sCSVValue = $sTextQualifier.str_replace($sFrom, $sTo, (string)$sRawValue).$sTextQualifier;

						$aFinalData[] = $sCSVValue;
					}
					$sCsvData .= implode(';', $aFinalData)."\n";
				}
				$sCSVTmpFile = tempnam(sys_get_temp_dir(), "CSV");
				file_put_contents($sCSVTmpFile, $sCsvData);

				$aParams = array(
					'csvfile' => $sCSVTmpFile,
					'data_source_id' => $iDataSourceId,
					'separator' => ';',
					'simulate' => 0,
					'output' => 'details',
				);
				list($iRetCode, $aOutputLines) = static::ExecSynchroImport($aParams, $bSynchroByHttp);

				unlink($sCSVTmpFile);

				// Report the load results
				//
				if (strlen($sCsvData) > 5000)
				{
					$sCsvDataViewable = 'INPUT TOO LONG TO BE DISPLAYED ('.strlen($sCsvData).")\n".substr($sCsvData, 0, 500)."\n... TO BE CONTINUED";
				}
				else
				{
					$sCsvDataViewable = $sCsvData;
				}
				echo "Input Data:\n";
				echo $sCsvDataViewable;
				echo "\n";

				$sResultsViewable = '|   '.implode("\n|   ", $aOutputLines);

				echo "Results:\n";
				echo $sResultsViewable;
				echo "\n";

				if ($iRetCode != 0)
				{
					static::fail("Execution of synchro_import failing with code '$iRetCode', see error.log for more details");
				}

				if (stripos($sResultsViewable, 'exception') !== false)
				{
					self::fail('Encountered an Exception during the last import/synchro');
				}

				$aKeys = ["creation", "update", "deletion"];
				foreach ($aKeys as $sKey){
					$this->assertStringContainsString("$sKey errors: 0", $sResultsViewable, "step $iRow : below res should contain '$sKey errors: 0': " . $sResultsViewable);
				}

				//N°3805 : potential javascript returned like
				/*
				        Please wait...
	var aListJsFiles = [];
                $(document).ready(function () {
                            setTimeout(function () {
                                    }, 50);
                    });
				 */
				$sLastExpectedLine = "#Replica disappeared, no action taken: 0";
				$aSplittedRes = explode($sLastExpectedLine, $sResultsViewable);
				$this->assertNotFalse($aSplittedRes);
				if (count($aSplittedRes)>1){
					$sPotentialIssuesWithWebApplication = $aSplittedRes[1];
					$this->assertEquals("", $sPotentialIssuesWithWebApplication, 'when failed it means data synchro result is polluted with some web application stuff like html or js');
				}
			}
		}

		return $oDataSource;
	}

	public function testDataSynchroByCli_DBObjectUseCase(){
		/**
		 * <class id="Event" _delta="define">
		 * <!-- Generated by toolkit/export-class-to-meta.php -->
		 * <parent>DBObject</parent>
		 * <properties>
		 * <category>core/cmdb,view_in_gui</category>
		 * </properties>
		 * <fields>
		 * <field id="message" xsi:type="AttributeText"/>
		 * <field id="date" xsi:type="AttributeDateTime"/>
		 * <field id="userinfo" xsi:type="AttributeString"/>
		 * </fields>
		 * </class>
		 */

		$DBObjectClass = EventWithTitleAsReconciliationKey::class;
		$oEventNotification = new EventWithTitleAsReconciliationKey();
		$this->assertTrue(is_a($oEventNotification, DBObject::class));
		$this->assertFalse(is_a($oEventNotification, CMDBObject::class));

		foreach (['A', 'C'] as $sKey) {
			$oEventA = new EventWithTitleAsReconciliationKey();
			$oEventA->Set('title', "title_$sKey");
			$oEventA->Set('message', "message_$sKey");
			$oEventA->Set('userinfo', "userinfo_$sKey");
			$oEventA->DBWrite();
		}

		$aDbObjectSyncroUsecase = [
			'desc' => 'Load EventNotification (DBObject)',
			'target_class' => $DBObjectClass,
			'source_properties' => [
				'full_load_periodicity' => 3600, // should be ignored in this case
				'reconciliation_policy' => 'use_attributes',
				'action_on_zero' => 'create',
				'action_on_one' => 'update',
				'action_on_multiple' => 'error',
				'delete_policy' => 'delete',
				'delete_policy_update' => '',
				'delete_policy_retention' => 0,
			],
			'source_data' => [
				['primary_key', 'title', 'message', 'date','userinfo'],
				[
					['A', 'title_A', 'message_A', 'userinfo_AAA'],
					['B', 'title_B', 'message_B', 'userinfo_B'],
				],
			],
			'target_data' => [
				['title'], //columns
				[
					// Initial state
				],
				[
					['title_A'], //expected values
					['title_B'], //expected values
				],
			],
			'attributes' => [
				'title' => [
					'do_reconcile' => true,
					'do_update' => true,
					'automatic_prefix' => true, // unique id (for unit testing)
				],
				'message' => [
					'do_reconcile' => false,
					'do_update' => false,
				],
				'userinfo' => [
					'do_reconcile' => false,
					'do_update' => true,
				],
			],
			'bSynchroByHttp' => false
		];

		$this->RunDataSynchroTest($aDbObjectSyncroUsecase);
	}
}
