<?php
/*
 * @copyright   Copyright (C) 2010-2024 Combodo SAS
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

use Combodo\iTop\Application\UI\Base\Component\Alert\Alert;
use Combodo\iTop\Application\UI\Base\Component\Alert\AlertUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Button\ButtonUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Form\Form;
use Combodo\iTop\Application\UI\Base\Component\Html\Html;
use Combodo\iTop\Application\UI\Base\Component\Input\InputUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Title\TitleUIBlockFactory;
use Combodo\iTop\Application\WebPage\iTopConfigEditorPage;
use Combodo\iTop\Application\WebPage\iTopWebPage;
use Combodo\iTop\Config\Validator\iTopConfigAstValidator;
use Combodo\iTop\Config\Validator\iTopConfigSyntaxValidator;

require_once(APPROOT.'application/startup.inc.php');

const CONFIG_ERROR = 0;
const CONFIG_WARNING = 1;
const CONFIG_INFO = 2;



function CheckAsyncTasksRetryConfig(Config $oTempConfig, iTopWebPage $oP)
{
	$iWarnings = 0;
	foreach (get_declared_classes() as $sPHPClass) {
		$oRefClass = new ReflectionClass($sPHPClass);
		if ($oRefClass->isSubclassOf('AsyncTask') && !$oRefClass->isAbstract()) {
			$aMessages = AsyncTask::CheckRetryConfig($oTempConfig, $oRefClass->getName());

			if (count($aMessages) !== 0) {
				foreach ($aMessages as $sMessage) {
					$oAlert = AlertUIBlockFactory::MakeForWarning('', $sMessage);
					$oP->AddUiBlock($oAlert);
					$iWarnings++;
				}
			}
		}
	}

	return $iWarnings;
}

/////////////////////////////////////////////////////////////////////
// Main program
//
LoginWebPage::DoLogin(); // Check user rights and prompt if needed
ApplicationMenu::CheckMenuIdEnabled('ConfigEditor');

//$sOperation = utils::ReadParam('operation', 'menu');
//$oAppContext = new ApplicationContext();

$oP = new iTopConfigEditorPage();


try {
	$sOperation = utils::ReadParam('operation', '');
	$iEditorTopMargin = 2;
	if (UserRights::IsAdministrator() && ExecutionKPI::IsEnabled()) {
		$iEditorTopMargin += 6;
	}

	if (MetaModel::GetConfig()->Get('demo_mode')) {
		throw new Exception(Dict::S('config-not-allowed-in-demo'), CONFIG_INFO);
	}

	if (MetaModel::GetModuleSetting('itop-config', 'config_editor', '') == 'disabled') {
		throw new Exception(Dict::S('config-interactive-not-allowed'), CONFIG_WARNING);
	}

	$sConfigFile = APPROOT.'conf/'.utils::GetCurrentEnvironment().'/config-itop.php';

	$iEditorTopMargin += 9;
	$sConfigContent = file_get_contents($sConfigFile);
	$sConfigChecksum = md5($sConfigContent);
	$sConfig = str_replace("\r\n", "\n", $sConfigContent);
	$sOriginalConfig = $sConfig;

	if (!empty($sOperation)) {
		$iEditorTopMargin += 5;
		$sConfig = utils::ReadParam('new_config', '', false, 'raw_data');
	}

	try {
		if ($sOperation == 'revert') {
			throw new Exception(Dict::S('config-reverted'), CONFIG_WARNING);
		}

		if ($sOperation == 'save') {
			$sTransactionId = utils::ReadParam('transaction_id', '', false, 'transaction_id');
			if (!utils::IsTransactionValid($sTransactionId, true)) {
				throw new Exception(Dict::S('config-error-transaction'), CONFIG_ERROR);
			}

			$sChecksum = utils::ReadParam('checksum');
			if ($sChecksum !== $sConfigChecksum) {
				throw new Exception(Dict::S('config-error-file-changed'), CONFIG_ERROR);
			}

			if ($sConfig === $sOriginalConfig) {
				throw new Exception(Dict::S('config-no-change'), CONFIG_INFO);
			}
			Config::Validate($sConfig); // throws exceptions

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

			if ($oTempConfig->DBPasswordInNewConfigIsOk()) {
				$oAlert = AlertUIBlockFactory::MakeForSuccess('', Dict::S('config-saved'));
			} else {
				$oAlert = AlertUIBlockFactory::MakeForInformation('', Dict::S('config-saved-warning-db-password'));
			}
			$oP->AddUiBlock($oAlert);

			$iWarnings = CheckAsyncTasksRetryConfig($oTempConfig, $oP);

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

	// (remove EscapeHtml)  N°5914 - Wrong encoding in modules configuration editor
	$oP->AddUiBlock(new Html('<p>'.Dict::S('config-edit-intro').'</p>'));

	$oForm = new Form();
	$oForm->AddSubBlock(InputUIBlockFactory::MakeForHidden('operation', 'save', 'operation'));
	$oForm->AddSubBlock(InputUIBlockFactory::MakeForHidden('transaction_id', utils::GetNewTransactionId()));
	$oForm->AddSubBlock(InputUIBlockFactory::MakeForHidden('checksum', $sConfigChecksum));

	//--- Cancel button
	$oCancelButton = ButtonUIBlockFactory::MakeForCancel(Dict::S('config-cancel'), 'cancel_button', null, true, 'cancel_button');
	$oCancelButton->SetOnClickJsCode("return ResetConfig();");
	$oForm->AddSubBlock($oCancelButton);

	//--- Submit button
	$oSubmitButton = ButtonUIBlockFactory::MakeForPrimaryAction(Dict::S('config-apply'), null, Dict::S('config-apply'), true, 'submit_button');
	$oForm->AddSubBlock($oSubmitButton);

	//--- Config editor
	$oForm->AddSubBlock(InputUIBlockFactory::MakeForHidden('prev_config', $sOriginalConfig, 'prev_config'));
	$oForm->AddSubBlock(InputUIBlockFactory::MakeForHidden('new_config', $sOriginalConfig));
	$oForm->AddHtml("<div id =\"new_config\" style=\"position: absolute; top: ".$iEditorTopMargin."em; bottom: 0; left: 5px; right: 5px;\"></div>");
	$oP->AddUiBlock($oForm);

	$oP->AddConfigScripts();
} catch (Exception $e) {
	$oAlert = $oP->AddAlertFromException($e);
}

$oP->output();
