<?php

namespace Combodo\iTop\Config\Validator;

class iTopConfigValidator {
	const CONFIG_ERROR = 0;
	const CONFIG_WARNING = 1;
	const CONFIG_INFO = 2;

	/**
	 * @param $sRawConfig
	 *
	 * @throws \Exception
	 */
	public function Validate($sRawConfig)
	{
		$oiTopConfigValidator = new iTopConfigAstValidator();
		$oiTopConfigValidator->Validate($sRawConfig);

		/// 2 - only after we are sure that there is no malicious code, we can perform a syntax check!
		$oiTopConfigValidator = new iTopConfigSyntaxValidator();
		$oiTopConfigValidator->Validate($sRawConfig);
	}

	function DBPasswordIsOk($sPassword)
	{
		$bIsWindows = (array_key_exists('WINDIR', $_SERVER) || array_key_exists('windir', $_SERVER));

		if ($bIsWindows && (preg_match("/[%!\"]/U", $sPassword) !== 0)) {
			return false;
		}

		return true;
	}
}