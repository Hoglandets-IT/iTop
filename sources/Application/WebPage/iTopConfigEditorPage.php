<?php

namespace Combodo\iTop\Application\WebPage;
use Dict;
use utils;
use Combodo\iTop\Application\UI\Base\Component\Alert\Alert;
use Combodo\iTop\Application\UI\Base\Component\Alert\AlertUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Button\ButtonUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Form\Form;
use Combodo\iTop\Application\UI\Base\Component\Html\Html;
use Combodo\iTop\Application\UI\Base\Component\Input\InputUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Title\TitleUIBlockFactory;
use Combodo\iTop\Application\WebPage\iTopWebPage;
use Combodo\iTop\Config\Validator\iTopConfigAstValidator;
use Combodo\iTop\Config\Validator\iTopConfigSyntaxValidator;

class iTopConfigEditorPage extends iTopWebPage
{
	public function __construct()
	{
		parent::__construct(Dict::S('modules-config-edit-title'));
		$this->set_base(utils::GetAbsoluteUrlAppRoot().'pages/');
		$sAceDir = 'node_modules/ace-builds/src-min/';
		$this->LinkScriptFromAppRoot($sAceDir.'ace.js');
		$this->LinkScriptFromAppRoot($sAceDir.'mode-php.js');
		$this->LinkScriptFromAppRoot($sAceDir.'theme-eclipse.js');
		$this->LinkScriptFromAppRoot($sAceDir.'ext-searchbox.js');

		$this->AddUiBlock(TitleUIBlockFactory::MakeForPage(Dict::S('config-edit-title')));
	}

	public function AddAlertFromException(\Exception $e)
	{
		switch ($e->getCode()) {
			case CONFIG_WARNING:
				$oAlert = AlertUIBlockFactory::MakeForWarning('', $e->getMessage());
				break;
			case CONFIG_INFO:
				$oAlert = AlertUIBlockFactory::MakeForInformation('', $e->getMessage());
				break;
			case CONFIG_ERROR:
			default:
				$oAlert = AlertUIBlockFactory::MakeForDanger('', $e->getMessage());
		}
		$this->AddUiBlock($oAlert);
	}

	public function AddConfigScripts() {
		$this->add_script(<<<JS
var EditorUtils = (function() {
	var STORAGE_RANGE_KEY = 'cfgEditorRange';
	var STORAGE_LINE_KEY = 'cfgEditorFirstline';
	var _editorSavedRange = null;
	var _editorSavedFirstLine = null;
	
	var saveEditorDisplay = function(editor) {
		_initObjectValues(editor);
		_persistObjectValues();
	};
	
	var _initObjectValues = function(editor) {
		_editorSavedRange = editor.getSelectionRange();
		_editorSavedFirstLine = editor.renderer.getFirstVisibleRow();
	};
	
	var _persistObjectValues = function() {
		sessionStorage.setItem(EditorUtils.STORAGE_RANGE_KEY, JSON.stringify(_editorSavedRange));
		sessionStorage.setItem(EditorUtils.STORAGE_LINE_KEY, _editorSavedFirstLine);
	};
	
	var restoreEditorDisplay = function(editor) {
		_restoreObjectValues();
		_setEditorDisplay(editor);
	};
	
	var _restoreObjectValues = function() {
		if ((sessionStorage.getItem(STORAGE_RANGE_KEY) == null) 
			|| (sessionStorage.getItem(STORAGE_LINE_KEY) == null)) {
			return;
		}
		
		_editorSavedRange = JSON.parse(sessionStorage.getItem(EditorUtils.STORAGE_RANGE_KEY));
		_editorSavedFirstLine = sessionStorage.getItem(EditorUtils.STORAGE_LINE_KEY);
		sessionStorage.removeItem(STORAGE_RANGE_KEY);
		sessionStorage.removeItem(STORAGE_LINE_KEY);
	};
	
	var _setEditorDisplay = function(editor) {
		if ((_editorSavedRange == null) || (_editorSavedFirstLine == null)) {
			return;
		}

		editor.selection.setRange(_editorSavedRange);
		editor.renderer.scrollToRow(_editorSavedFirstLine);
	};
	
	var getEditorForm = function(editor) {
        var editorContainer = $(editor.container);
        return editorContainer.closest("form");
	};
	
	var updateConfigEditorButtonState = function(editor) {
	    var isSameContent = (editor.getValue() == $('#prev_config').val());
	    var hasNoError = $.isEmptyObject(editor.getSession().getAnnotations());
	    $('#cancel_button').prop('disabled', isSameContent);
	    $('#submit_button').prop('disabled', isSameContent || !hasNoError);
	};
	
	return {
		STORAGE_RANGE_KEY: STORAGE_RANGE_KEY,
		STORAGE_LINE_KEY : STORAGE_LINE_KEY,
		saveEditorDisplay : saveEditorDisplay,
		restoreEditorDisplay : restoreEditorDisplay,
		getEditorForm : getEditorForm,
		updateConfigEditorButtonState : updateConfigEditorButtonState
	};
})();
JS
		);
		$this->add_ready_script(<<<'JS'
var editor = ace.edit("new_config");

var configurationSource = $('input[name="new_config"]');
editor.getSession().setValue(configurationSource.val());

editor.getSession().on('change', function()
{
  configurationSource.val(editor.getSession().getValue());
  EditorUtils.updateConfigEditorButtonState(editor);
});
editor.getSession().on("changeAnnotation", function()
{
  EditorUtils.updateConfigEditorButtonState(editor);
});

editor.setTheme("ace/theme/eclipse");
editor.getSession().setMode("ace/mode/php");
editor.commands.addCommand({
    name: 'save',
    bindKey: {win: "Ctrl-S", "mac": "Cmd-S"},
    exec: function(editor) {
        var editorForm = EditorUtils.getEditorForm(editor);
        var submitButton = $('#submit_button');
        
        if (submitButton.is(":enabled")) {
            editorForm.trigger('submit');
        }
    }
});


var editorForm = EditorUtils.getEditorForm(editor);
editorForm.on('submit', function() {
	EditorUtils.saveEditorDisplay(editor);
});


EditorUtils.restoreEditorDisplay(editor);
editor.focus();
JS
		);

		$sConfirmCancel = addslashes(Dict::S('config-confirm-cancel'));
		$this->add_script(<<<JS
function ResetConfig()
{
	$("#operation").attr('value', 'revert');
	if (confirm('$sConfirmCancel'))
	{
		$('input[name="new_config"]').val(prevConfig.val());
		return true;
	}
	return false;
}
JS
		);
	}

}