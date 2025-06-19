<?php

namespace Combodo\iTop\SessionTracker;

interface iSessionHandlerExtension {
	public function __construct();

	/**
	 * Called by SessionHandler to change data stored in iTop session files
	 * @param array $aJson: previous data stored in session file
	 * @param array $aData: usual session data see @SessionHandler
	 *
	 * @return void
	 */
	public function CompleteSessionData(array $aJson, array &$aData) : void;
}