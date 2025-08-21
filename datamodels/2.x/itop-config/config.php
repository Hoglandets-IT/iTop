<?php
/*
 * @copyright   Copyright (C) 2010-2024 Combodo SAS
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

use Combodo\iTop\Application\UI\Base\Component\Alert\AlertUIBlockFactory;
use Combodo\iTop\Application\WebPage\iTopConfigEditorPage;
use Combodo\iTop\Config\Validator\iTopConfigValidator;

require_once(APPROOT.'application/startup.inc.php');

/////////////////////////////////////////////////////////////////////
// Main program
//
LoginWebPage::DoLogin(); // Check user rights and prompt if needed
ApplicationMenu::CheckMenuIdEnabled('ConfigEditor');


$oP = new iTopConfigEditorPage();


try {
	$sOperation = utils::ReadParam('operation', '');


	if (MetaModel::GetConfig()->Get('demo_mode')) {
		throw new Exception(Dict::S('config-not-allowed-in-demo'), iTopConfigValidator::CONFIG_INFO);
	}

	if (MetaModel::GetModuleSetting('itop-config', 'config_editor', '') == 'disabled') {
		throw new Exception(Dict::S('config-interactive-not-allowed'), iTopConfigValidator::CONFIG_WARNING);
	}

	$sConfigFile = APPROOT.'conf/'.utils::GetCurrentEnvironment().'/config-itop.php';

	$sConfigContent = file_get_contents($sConfigFile);
	$sConfigChecksum = md5($sConfigContent);
	$sConfig = str_replace("\r\n", "\n", $sConfigContent);
	$sOriginalConfig = $sConfig;

	if (!empty($sOperation)) {
		$sConfig = utils::ReadParam('new_config', '', false, 'raw_data');
	}

	try {
		if ($sOperation == 'revert') {
			throw new Exception(Dict::S('config-reverted'), iTopConfigValidator::CONFIG_WARNING);
		}

		if ($sOperation == 'save') {
			$sTransactionId = utils::ReadParam('transaction_id', '', false, 'transaction_id');
			if (!utils::IsTransactionValid($sTransactionId, true)) {
				throw new Exception(Dict::S('config-error-transaction'), iTopConfigValidator::CONFIG_ERROR);
			}

			$sChecksum = utils::ReadParam('checksum');
			if ($sChecksum !== $sConfigChecksum) {
				throw new Exception(Dict::S('config-error-file-changed'), iTopConfigValidator::CONFIG_ERROR);
			}

			if ($sConfig === $sOriginalConfig) {
				throw new Exception(Dict::S('config-no-change'), iTopConfigValidator::CONFIG_INFO);
			}
			$oValidator = new iTopConfigValidator();
			$oValidator->Validate($sConfig);// throws exceptions

			@chmod($sConfigFile, 0770); // Allow overwriting the file
			$sTmpFile = tempnam(SetupUtils::GetTmpDir(), 'itop-cfg-');
			// Don't write the file as-is since it would allow to inject any kind of PHP code.
			// Instead, write the interpreted version of the file
			// Note:
			// The actual raw PHP code will anyhow be interpreted exactly twice: once in TestConfig() above
			// and a second time during the load of the Config object below.
			// If you are really concerned about an iTop administrator crafting some malicious
			// PHP code inside the config file, then turn off the interactive configuration
			// editor by adding the configuration parameter:
			// 'itop-config' => array(
			//     'config_editor' => 'disabled',
			// )
			file_put_contents($sTmpFile, $sConfig);
			$oTempConfig = new Config($sTmpFile, true);
			$oTempConfig->WriteToFile($sConfigFile);
			@unlink($sTmpFile);
			@chmod($sConfigFile, 0440); // Read-only

			if ($oValidator->DBPasswordIsOk($oTempConfig->Get('db_pwd'))) {
				$oAlert = AlertUIBlockFactory::MakeForSuccess('', Dict::S('config-saved'));
			} else {
				$oAlert = AlertUIBlockFactory::MakeForInformation('', Dict::S('config-saved-warning-db-password'));
			}
			$oP->AddUiBlock($oAlert);

			$oP->CheckAsyncTasksRetryConfig($oTempConfig);

			// Read the config from disk after save
			$sConfigContent = file_get_contents($sConfigFile);
			$sConfigChecksum = md5($sConfigContent);
			$sConfig = str_replace("\r\n", "\n", $sConfigContent);
			$sOriginalConfig = $sConfig;
		}
	}
	catch (Exception $e) {
		$oP->AddAlertFromException($e);
	}

	$oP->AddEditor($sOriginalConfig, $sOriginalConfig);


} catch (Exception $e) {
	$oP->AddAlertFromException($e);
}

$oP->output();
