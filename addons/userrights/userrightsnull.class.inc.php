<?php

/**
 * UserRightsNull
 * User management Module - say Yeah! to everything
 *
 * @deprecated 3.3.0
 * @copyright   Copyright (C) 2010-2024 Combodo SAS
 * @license     http://opensource.org/licenses/AGPL-3.0
 */
DeprecatedCallsLog::NotifyDeprecatedPhpMethod('use file addons/userrights/userrightsprofile.class.inc.php instead (param in configuration file)');
class UserRightsNull extends UserRightsAddOnAPI
{
	// Installation: create the very first user
	public function CreateAdministrator($sAdminUser, $sAdminPwd, $sLanguage = 'EN US')
	{
		return true;
	}

	public function IsAdministrator($oUser)
	{
		return true;
	}

	public function IsPortalUser($oUser)
	{
		return true;
	}

	public function Init()
	{
		return true;
	}

	public function GetSelectFilter($oUser, $sClass, $aSettings = array())
	{
		$oNullFilter  = new DBObjectSearch($sClass);
		return $oNullFilter;
	}

	public function IsActionAllowed($oUser, $sClass, $iActionCode, $oInstanceSet = null)
	{
		return UR_ALLOWED_YES;
	}

	public function IsStimulusAllowed($oUser, $sClass, $sStimulusCode, $oInstanceSet = null)
	{
		return UR_ALLOWED_YES;
	}

	public function IsActionAllowedOnAttribute($oUser, $sClass, $sAttCode, $iActionCode, $oInstanceSet = null)
	{
		return UR_ALLOWED_YES;
	}

	public function FlushPrivileges()
	{
	}
}

UserRights::SelectModule('UserRightsNull');

?>
