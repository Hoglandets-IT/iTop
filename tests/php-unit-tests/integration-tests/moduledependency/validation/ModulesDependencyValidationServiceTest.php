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

namespace Combodo\iTop\Test\UnitTest\ModuleDependency\Validation;

use Combodo\iTop\Setup\ModuleDependency\ModuleDependency;
use Combodo\iTop\Setup\ModuleDependency\Validation\ModulesDependencyValidationService;
use Combodo\iTop\Setup\ModuleDependency\Validation\XmlModule;
use Combodo\iTop\Test\UnitTest\ItopTestCase;

class ModulesDependencyValidationServiceTest extends ItopTestCase {
	private array $aFilesToRemove = [];

	protected function setUp(): void
	{
		parent::setUp();
		$this->RequireOnceItopFile('setup/moduledependency/validation/modulesdependencyvalidationservice.php');
		if (substr(PHP_OS, 0, 3) == 'WIN') {
			$this->markTestSkipped("run under Linux only");
		}
	}

	protected function tearDown(): void
	{
		parent::tearDown();
		foreach ($this->aFilesToRemove as $sTmpFile){
			@unlink($sTmpFile);
		}
		ModulesDependencyValidationService::SetInstance(null);
	}

	/**
	 * Module dependency validation: make sure dependencies are correct toward classes/interfaces coming from PHP/Xml datamodel files
	 */
	public function testReadModuleFileData()
	{
		ModulesDependencyValidationService::GetInstance()->FetchAllDependenciesViaModulesFiles();

		ModulesDependencyValidationService::GetInstance()->FetchAllDependenciesViaDM();

		$aErrors=[];
		/** @var XmlModule $oXmlModule */
		foreach (ModulesDependencyValidationService::GetInstance()->aModules as $sModuleName => $oXmlModule) {
			$aCurrentDeps = ModulesDependencyValidationService::GetInstance()::$aModulesDataByModuleName[$sModuleName][2]['dependencies'] ?? [];
			$aModuleErrors=[];
			foreach ($oXmlModule->aDependencyModulesNames as $sDepModuleName => $oXmlModule2){
				$sXmlUIDs = implode('|', $oXmlModule->aXMlMetaInfosByModuleNames[$sDepModuleName]);
				$bResolved=false;
				foreach ($aCurrentDeps as $sDepString){
					$oModuleDependency = new ModuleDependency($sDepString);

					if (in_array($sDepModuleName, $oModuleDependency->GetPotentialPrerequisiteModuleNames())) {
						$bResolved=true;
						break;
					}

					if (false !== strpos($sDepModuleName, '|')){
						$aDepModules = explode('|', $sDepModuleName);
						foreach ($aDepModules as $sDepModule){
							$sDepModule = trim($sDepModule);
							if (in_array($sDepModule, $oModuleDependency->GetPotentialPrerequisiteModuleNames())) {
								$bResolved=true;
								break;
							}
						}

						if ($bResolved){
							break;
						}
					}

					foreach ($oModuleDependency->GetPotentialPrerequisiteModuleNames() as $sPotentialDepModuleName){
						/** @var XmlModule $oXmlModule2 */
						$oXmlModule2 = ModulesDependencyValidationService::GetInstance()->aModules[$sPotentialDepModuleName]??null;

						if (! is_null($oXmlModule2) && $oXmlModule2->Depends($sDepModuleName)){
							$bResolved=true;
							break;
						}
					}

					if ($bResolved) {
						break;
					}
				}

				if (! $bResolved){
					$aModuleErrors []= "$sModuleName depends on $sDepModuleName but missing in module dependencies: " . implode(' & ', $aCurrentDeps) . ". ($sXmlUIDs)";
				}
			}

			if (count($aModuleErrors)){
				$aErrors[$sModuleName]=$aModuleErrors;
			}
		}

		$this->assertEquals(0, count($aErrors), var_export($aErrors, true));

	}

	public function testListDeclaredFullnameClassesFromPhpFile()
	{
		$aExpected = [
			'CMDBChangeOp',
			'CMDBChangeOpCreate',
			'CMDBChangeOpDelete',
			'CMDBChangeOpSetAttribute',
			'CMDBChangeOpSetAttributeScalar',
			'CMDBChangeOpSetAttributeTagSet',
			'CMDBChangeOpSetAttributeURL',
			'CMDBChangeOpSetAttributeBlob',
			'CMDBChangeOpSetAttributeOneWayPassword',
			'CMDBChangeOpSetAttributeEncrypted',
			'CMDBChangeOpSetAttributeText',
			'CMDBChangeOpSetAttributeLongText',
			'CMDBChangeOpSetAttributeHTML',
			'CMDBChangeOpSetAttributeCaseLog',
			'CMDBChangeOpPlugin',
			'CMDBChangeOpSetAttributeLinksAddRemove',
			'CMDBChangeOpSetAttributeLinksTune',
			'CMDBChangeOpSetAttributeCustomFields',
			'iCMDBChangeOp',
		];
		$this->assertEquals($aExpected, ModulesDependencyValidationService::GetInstance()->ListDeclaredFullnameClassesFromPhpFile(APPROOT . 'core/cmdbchangeop.class.inc.php'));
	}

	public function testListDeclaredFullnameClassesFromAutoloadFile()
	{
		$aExpected = [
			'Combodo\iTop\OAuthClient\Controller\AjaxOauthClientController',
			'Combodo\iTop\OAuthClient\Controller\OAuthClientController',
			'Combodo\iTop\OAuthClient\Service\ApplicationUIExtension',
			'Combodo\iTop\OAuthClient\Service\PopupMenuExtension',
		];
		$this->assertEquals($aExpected, ModulesDependencyValidationService::GetInstance()->ListDeclaredFullnameClassesFromPhpFile(APPROOT . 'datamodels/2.x/itop-oauth-client/vendor/autoload.php'));
	}

	public function testListDeclaredFullnameClassesFromAutoloadFile_itopfence()
	{
		$sContent=<<<PHP
<?php

// autoload_classmap.php @generated by Composer

\$vendorDir = dirname(__DIR__);
\$baseDir = dirname(\$vendorDir);

return array(
    'Attribute' => \$vendorDir . '/symfony/polyfill-php80/Resources/stubs/Attribute.php',
    'Combodo\\iTop\\Fence\\Checker\\IpRangeChecker' => \$baseDir . '/src/Checker/IpRangeChecker.php',
    'Combodo\\iTop\\Fence\\Checker\\LoginFailedListener' => \$baseDir . '/src/Checker/LoginFailedListener.php',
);
PHP;
		$sDir = sys_get_temp_dir().'/'.uniqid();
		mkdir($sDir);
		$sComposerDir = $sDir.'/composer';
		mkdir($sComposerDir);
		$sPath = "$sComposerDir/autoload_classmap.php";
		file_put_contents($sPath, $sContent);

		$aExpected = [
			'Combodo\iTop\\Fence\Checker\IpRangeChecker',
			'Combodo\iTop\\Fence\Checker\LoginFailedListener',
		];
		$aRes = ModulesDependencyValidationService::GetInstance()->ListDeclaredFullnameClassesFromAutoloadFile("$sDir/autoload.php");
		@unlink($sPath);
		rmdir($sComposerDir);
		rmdir($sDir);
		$this->assertEquals($aExpected, $aRes);
	}

	public function testReadModuleMetaInfo()
	{
		$aExpected = [
			APPROOT . 'datamodels/2.x/itop-portal-base/module.itop-portal-base.php',
			'itop-portal-base/3.3.0',
			[
				'label' => 'Portal Development Library',
		        'category' => 'Portal',
		        'dependencies' => [ 'itop-attachments/3.2.1' ],
		        'mandatory' => true,
		        'visible' => false,
		        'datamodel' => [ 'portal/vendor/autoload.php' ],
		        'webservice' => [],
		        'dictionary' => [],
		        'data.struct' => [],
		        'data.sample' => [],
		        'doc.manual_setup' => '',
		        'doc.more_information' => '',
		        'settings' => [],
				'module_file_path' => APPROOT . 'datamodels/2.x/itop-portal-base/module.itop-portal-base.php'
			],
		];
		$this->assertEquals($aExpected, ModulesDependencyValidationService::GetInstance()->GetModuleMetainfo('itop-portal-base'));
	}

	public function testGetFirstFoundDepsUID() {
		$sOutput=<<<TXT
/var/www/html/Professional-3.2.1-16428/web/datamodels/2.x/authent-token/src/Hook/MyAccountSectionTabContentExtension.php:Combodo\iTop\MyAccount\Hook\iMyAccountTabContentExtension
TXT;

		$this->assertEquals('Combodo\iTop\MyAccount\Hook\iMyAccountTabContentExtension', ModulesDependencyValidationService::GetInstance()->GetFirstFoundDepsUID($sOutput));


		$sOutput=<<<TXT
/var/www/html/Professional-3.2.1-16428/web/datamodels/2.x/authent-token/src/Hook/MyAccountSectionTabContentExtension.php:Combodo\iTop\MyAccount\Hook\iMyAccountTabContentExtension
/var/www/html/Professional-3.2.1-16428/web/datamodels/2.x/authent-token/src/Hook/MyAccountSectionTabContentExtension2.php:Combodo\iTop\MyAccount\Hook\iMyAccountTabContentExtension2
/var/www/html/Professional-3.2.1-16428/web/datamodels/2.x/authent-token/src/Hook/MyAccountSectionTabContentExtension3.php:Combodo\iTop\MyAccount\Hook\iMyAccountTabContentExtension3
TXT;

		$this->assertEquals('Combodo\iTop\MyAccount\Hook\iMyAccountTabContentExtension', ModulesDependencyValidationService::GetInstance()->GetFirstFoundDepsUID($sOutput));
	}
}


