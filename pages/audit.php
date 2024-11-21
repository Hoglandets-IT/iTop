<?php
/*
 * @copyright   Copyright (C) 2010-2024 Combodo SAS
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

use Combodo\iTop\Application\UI\Base\Component\Alert\AlertUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Button\ButtonUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Dashlet\DashletContainer;
use Combodo\iTop\Application\UI\Base\Component\Dashlet\DashletFactory;
use Combodo\iTop\Application\UI\Base\Component\DataTable\DataTableUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Field\FieldUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Html\Html;
use Combodo\iTop\Application\UI\Base\Component\Input\InputUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Input\Select\SelectOptionUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Input\Select\SelectUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Panel\Panel;
use Combodo\iTop\Application\UI\Base\Component\Panel\PanelUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Text\Text;
use Combodo\iTop\Application\UI\Base\Component\Title\TitleUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Layout\Dashboard\DashboardColumn;
use Combodo\iTop\Application\UI\Base\Layout\Dashboard\DashboardRow;
use Combodo\iTop\Application\UI\Base\Layout\UIContentBlockUIBlockFactory;
use Combodo\iTop\Application\WebPage\CSVPage;
use Combodo\iTop\Application\WebPage\ErrorPage;
use Combodo\iTop\Application\WebPage\iTopWebPage;
use Combodo\iTop\Core\MetaModel\FriendlyNameType;
use Combodo\iTop\Form\Field\SelectObjectField;

/**
 * Adds the context parameters to the audit rule query
 *
 * @param DBSearch $oFilter
 * @param ApplicationContext $oAppContext
 *
 * @throws \CoreException
 * @throws \CoreWarning
 * @throws \Exception
 */
function FilterByContext(DBSearch &$oFilter, ApplicationContext $oAppContext)
{
	$sObjClass = $oFilter->GetClass();		
	$aContextParams = $oAppContext->GetNames();
	$aCallSpec = array($sObjClass, 'MapContextParam');
	if (is_callable($aCallSpec))
	{
		foreach($aContextParams as $sParamName)
		{
			$sValue = $oAppContext->GetCurrentValue($sParamName, null);
			if ($sValue != null)
			{
				$sAttCode = call_user_func($aCallSpec, $sParamName); // Returns null when there is no mapping for this parameter
				if ( ($sAttCode != null) && MetaModel::IsValidAttCode($sObjClass, $sAttCode))
				{
					// Check if the condition points to a hierarchical key
                    $bConditionAdded = false;
					if ($sAttCode == 'id')
					{
						// Filtering on the objects themselves
						$sHierarchicalKeyCode = MetaModel::IsHierarchicalClass($sObjClass);
						
						if ($sHierarchicalKeyCode !== false)
						{
							$oRootFilter = new DBObjectSearch($sObjClass);
							$oRootFilter->AddCondition($sAttCode, $sValue);
							$oFilter->AddCondition_PointingTo($oRootFilter, $sHierarchicalKeyCode, TREE_OPERATOR_BELOW); // Use the 'below' operator by default
							$bConditionAdded = true;
						}
					}
					else
					{
						$oAttDef = MetaModel::GetAttributeDef($sObjClass, $sAttCode);
						$bConditionAdded = false;
						if ($oAttDef->IsExternalKey())
						{
							$sHierarchicalKeyCode = MetaModel::IsHierarchicalClass($oAttDef->GetTargetClass());
							
							if ($sHierarchicalKeyCode !== false)
							{
								$oRootFilter = new DBObjectSearch($oAttDef->GetTargetClass());
								$oRootFilter->AddCondition('id', $sValue);
								$oHKFilter = new DBObjectSearch($oAttDef->GetTargetClass());
								$oHKFilter->AddCondition_PointingTo($oRootFilter, $sHierarchicalKeyCode, TREE_OPERATOR_BELOW); // Use the 'below' operator by default
								$oFilter->AddCondition_PointingTo($oHKFilter, $sAttCode);
								$bConditionAdded = true;
							}
						}
					}
					if (!$bConditionAdded)
					{
						$oFilter->AddCondition($sAttCode, $sValue);
					}
				}
			}
		}
	}
}

/**
 * @param int $iRuleId Audit rule ID
 * @param DBObjectSearch $oDefinitionFilter Created from the audit category's OQL
 * @param ApplicationContext $oAppContext
 *
 * @return mixed
 * @throws \ArchivedObjectException
 * @throws \CoreException
 * @throws \OQLException
 */
function GetRuleResultFilter($iRuleId, $oDefinitionFilter, $oAppContext, $aParams  = [])
{
	$oRule = MetaModel::GetObject('AuditRule', $iRuleId);
	$sOql = $oRule->Get('query');
	$oRuleFilter = DBObjectSearch::FromOQL($sOql, $aParams);
	$oRuleFilter->UpdateContextFromUser();
	FilterByContext($oRuleFilter, $oAppContext); // Not needed since this filter is a subset of the definition filter, but may speedup things

	if ($oRule->Get('valid_flag') == 'false')
	{
		// The query returns directly the invalid elements
		$oFilter = $oRuleFilter->Intersect($oDefinitionFilter);
	}
	else
	{
		// The query returns only the valid elements, all the others are invalid
		// Warning : we're generating a `WHERE ID IN`... query, and this could be very slow if there are lots of id !
      	$aValidRows = $oRuleFilter->ToDataArray(array('id'));
		$aValidIds = array();
		foreach($aValidRows as $aRow)
		{
			$aValidIds[] = $aRow['id'];
		}
		/** @var \DBObjectSearch $oFilter */
		$oFilter = $oDefinitionFilter->DeepClone();
		if (count($aValidIds) > 0)
		{
			$aInDefSet = array();
			foreach($oDefinitionFilter->ToDataArray(array('id')) as $aRow)
			{
				$aInDefSet[] = $aRow['id'];
			}
			$aInvalids = array_diff($aInDefSet, $aValidIds);
			if (count($aInvalids) > 0)
			{
				$oFilter->AddConditionForInOperatorUsingParam('id', $aInvalids, true);
			}
			else
			{
				$oFilter->AddCondition('id', 0, '=');
			}
		}
	}
	return $oFilter;
}

function MakeSelectField($oPage, string $sLabel, string $sFieldName, string $sOql, string $sCurrentValue)
{
    $oSearch = DBObjectSearch::FromOQL($sOql);
    $oAllowedValues = new DBObjectSet($oSearch);
    $oAllowedValues->SetShowObsoleteData(utils::ShowObsoleteData());
    $iMaxComboLength = MetaModel::GetConfig()->Get('max_combo_length');

    $bIsAutocomplete = $oAllowedValues->CountExceeds($iMaxComboLength);
    $sWrapperCssClass = $bIsAutocomplete ? 'ibo-input-select-autocomplete-wrapper' : 'ibo-input-select-wrapper';
    $sHTMLValue = "<div class=\"field_input_zone\">";

    // We just need to compare the number of entries with MaxComboLength, so no need to get the real count.
    if (!$bIsAutocomplete) {
        // Discrete list of values, use a SELECT or RADIO buttons depending on the config
        $sHelpText = ''; 
        $aOptions = [];
        $aOptions['value'] = "";
        $aOptions['label'] = Dict::S('UI:SelectOne');

        $oAllowedValues->Rewind();
        $sClassAllowed = $oAllowedValues->GetClass();
        $bAddingValue = false;

        $aFieldsToLoad = [];

        $aComplementAttributeSpec = MetaModel::GetNameSpec($oAllowedValues->GetClass(), FriendlyNameType::COMPLEMENTARY);
        $sFormatAdditionalField = $aComplementAttributeSpec[0];
        $aAdditionalField = $aComplementAttributeSpec[1];

        if (count($aAdditionalField) > 0) {
            $bAddingValue = true;
            $aFieldsToLoad[$sClassAllowed] = $aAdditionalField;
        }
        $sObjectImageAttCode = MetaModel::GetImageAttributeCode($sClassAllowed);
        if (!empty($sObjectImageAttCode)) {
            $aFieldsToLoad[$sClassAllowed][] = $sObjectImageAttCode;
        }
        $aFieldsToLoad[$sClassAllowed][] = 'friendlyname';
        $oAllowedValues->OptimizeColumnLoad($aFieldsToLoad);

        $oSelect = SelectUIBlockFactory::MakeForSelect($sFieldName, $sFieldName);
        $oSelect->AddCSSClass('ibo-input-field-wrapper');
        
        while ($oChoiceItem = $oAllowedValues->Fetch()) {

            $sOptionName = utils::HtmlEntityDecode($oChoiceItem->GetName());
            
            if ($bAddingValue) {
                $aArguments = [];
                foreach ($aAdditionalField as $sAdditionalField) {
                    array_push($aArguments, $oAllowedValues->Get($sAdditionalField));
                }
                $sOptionName.='<br><i>'.utils::HtmlEntities(vsprintf($sFormatAdditionalField, $aArguments)).'</i>'; ;
            }
            if (!empty($sObjectImageAttCode)) {
                // Try to retrieve image for contact
                /** @var \ormDocument $oImage */
                $oImage = $oAllowedValues->Get($sObjectImageAttCode);
                if (!$oImage->IsEmpty()) {
                    $sPicturepictureUrl = $oImage->GetDisplayURL($sClassAllowed, $oChoiceItem->GetKey(), $sObjectImageAttCode);
                    $sOptionName.=' <span class="ibo-input-select--autocomplete-item-image" style="background-image: url('.$sPicturepictureUrl.');"></span>';
                } else {
                    $sInitials = utils::FormatInitialsForMedallion(utils::ToAcronym($oChoiceItem->Get('friendlyname')));
                    $sOptionName.=' <span class="ibo-input-select--autocomplete-item-image" ">'.$sInitials.'</span>';
                }
            }
            $oOption = SelectOptionUIBlockFactory::MakeForSelectOption($oChoiceItem->GetKey(), $sOptionName, ($sCurrentValue == $oChoiceItem->GetKey()));
            $oSelect->AddOption($oOption);
        }
        $sInputType = CmdbAbstractObject::ENUM_INPUT_TYPE_DROPDOWN_DECORATED;
        
        $sJsonOptions = str_replace("'", "\'", str_replace('\\', '\\\\', json_encode($aOptions)));
        $oPage->add_ready_script(
            <<<JS
        let select$sFieldName = $('#$sFieldName').selectize({
                    plugins:['custom_itop', 'selectize-plugin-a11y'],                  
                });
JS
        );
        return $oSelect;
    }
    else
    {
        // Too many choices, use an autocomplete
        // Check that the given value is allowed
        $oSearch = $oAllowedValues->GetFilter();
        $oSearch->AddCondition('id', $sCurrentValue);
        $oSet = new DBObjectSet($oSearch);
        $sClass = $oSet->GetClass();
        if ($oSet->Count() == 0)
        {
            $sCurrentValue = null;
        }

        if (is_null($sCurrentValue) || ($sCurrentValue == 0)) // Null values are displayed as ''
        {
            $sDisplayValue = '';
        } else {
            $sDisplayValue = MetaModel::GetObject($sClass, $sCurrentValue)->GetName();
        }
        $iMinChars = MetaModel::GetConfig()->Get('min_autocomplete_chars'); //@@@ $this->oAttDef->GetMinAutoCompleteChars();

        // the input for the auto-complete
        $sInputType = CmdbAbstractObject::ENUM_INPUT_TYPE_AUTOCOMPLETE;
        $sHTMLValue .= "<input class=\"field_autocomplete ibo-input ibo-input-select ibo-input-select-autocomplete\" type=\"text\"  id=\"label_$sFieldName\" value=\"$sDisplayValue\" placeholder='...'/>";

        // another hidden input to store & pass the object's Id
        $sHTMLValue .= "<input type=\"hidden\" id=\"$sFieldName\" name=\"{$sFieldName}\" value=\"".utils::HtmlEntities($sCurrentValue)."\" />\n";

         // Scripts to start the autocomplete and bind some events to it
        $oPage->add_ready_script(
            <<<JS
	   
		var hasFocus = 0;
		var cache = {};
		$('#label_$sFieldName').data('selected_value', $('#label_$sFieldName').val());
		$('#label_$sFieldName').attr('title', $('#label_$sFieldName').val());
		$('#label_$sFieldName').autocomplete({
				source: function (request, response) {
					term = request.term.toLowerCase().latinise().replace(/[\u0300-\u036f]/g, "");

					if (term in cache) {
						response(cache[term]);
						return;
					}
					if (term.indexOf(this.previous) >= 0 && cache[this.previous] != null && cache[this.previous].length < 120) {
						//we have already all the possibility in cache
						var data = [];
						$.each(cache[this.previous], function (key, value) {
							if (value.label.toLowerCase().latinise().replace(/[\u0300-\u036f]/g, "").indexOf(term) >= 0) {
								data.push(value);
							}
						});
						cache[term] = data;
						response(data);
					} else {
						$.post({
							url: GetAbsoluteUrlAppRoot()+'pages/ajax.render.php',
							dataType: "json",
							data: {
								q: request.term,
								operation: 'ac_extkey',
								sTargetClass: '$sClass',
								sFilter: '$sOql',
								bSearchMode: true,
								sOutputFormat: 'json',
								json: function () {
									return '';
								}
							},
							success: function (data) {
								cache[term] = data;
								response(data);
							}
						});

					}
				},
				autoFocus: true,
				minLength: $iMinChars,
				focus: function (event, ui) {
					return false;
				},
				select: function (event, ui) {
					$('#$sFieldName').val(ui.item.value);
					let labelValue = $('<div>').html(ui.item.label).text();
					$('#label_$sFieldName').val(labelValue);
					$('#label_$sFieldName').data('selected_value', labelValue);
					$('#label_$sFieldName').attr('title',labelValue);
					return false;
				},
				open: function (event, ui) {
					// dialog tries to move above every .ui-front with _moveToTop(), we want to be above our parent dialog
					var dialog = $(this).closest('.ui-dialog');
					if (dialog.length > 0) {
						$('.ui-autocomplete.ui-front').css('z-index', parseInt(dialog.css("z-index"))+1);
					}
				   // UpdateDropdownPosition = function (oControlElem, oDropdownElem) {
                        // First fix width to ensure it's not too long
                        const fControlWidth = $(this).outerWidth();
                        $('.ui-autocomplete.selectize-dropdown:visible').css('width', fControlWidth);
                
                        // Then, fix height / position to ensure it's within the viewport
                        const fWindowHeight = window.innerHeight;
                
                        const fControlTopY = $(this).offset().top;
                        const fControlHeight = $(this).outerHeight();
                
                        const fDropdownTopY = $('.ui-autocomplete.selectize-dropdown:visible').offset().top;
                        // This one is "let" as it might be updated if necessary
                        let fDropdownHeight = $('.ui-autocomplete.selectize-dropdown:visible').outerHeight();
                        const fDropdownBottomY = fDropdownTopY + fDropdownHeight;
                
                        if (fDropdownBottomY > fWindowHeight) {
                            // Set dropdown max-height to 1/3 of the screen, this way we are sure the dropdown will fit in either the top / bottom half of the screen
                            $('.ui-autocomplete.selectize-dropdown:visible').css('max-height', '30vh');
                            fDropdownHeight = $('.ui-autocomplete.selectize-dropdown:visible').outerHeight();
                
                            // Position dropdown above input if not enough space on the bottom part of the screen
                            if ((fDropdownTopY / fWindowHeight) > 0.6) {
                                $('.ui-autocomplete.selectize-dropdown:visible').css('top', fDropdownTopY - fDropdownHeight - fControlHeight);
                            }
                        }
                 //   this.ManageScroll = function () {
                        if ($('#label_$sFieldName').scrollParent()[0].tagName != 'HTML') {
                            $('#label_$sFieldName').scrollParent().on(['scroll.$sFieldName', 'resize.$sFieldName'].join(" "), function () {
                                setTimeout(function () {
                                    me.ManageScrollInElement();
                                }, 50);
                            });
                            if ($('#label_$sFieldName').scrollParent().scrollParent()[0].tagName != 'HTML') {
                                $('#label_$sFieldName').scrollParent().scrollParent().on(['scroll.$sFieldName', 'resize.$sFieldName'].join(" "), function () {
                                    setTimeout(function () {
                                        me.ManageScrollInElement();
                                    }, 50);
                                });
                            }
                        }
				},
				close: function (event, ui) {
                    if ($('#label_$sFieldName').scrollParent()[0].tagName != 'HTML') {
                        $('#label_$sFieldName').scrollParent().off('scroll.$sFieldName');
                        $('#label_$sFieldName').scrollParent().off('resize.$sFieldName');
                        if ($('#label_$sFieldName').scrollParent().scrollParent()[0].tagName != 'HTML') {
                            $('#label_$sFieldName').scrollParent().scrollParent().off('scroll.$sFieldName');
                            $('#label_$sFieldName').scrollParent().scrollParent().off('resize.$sFieldName');
                        }
                    }
				}
			})
		.autocomplete("instance")._renderItem = function (ul, item) {
			$(ul).addClass('selectize-dropdown');
			let term = this.term.replace("/([\^\$\(\)\[\]\{\}\*\.\+\?\|\\])/gi", "\\$1");
			let val = '';
			if (item.initials != undefined) {
				if (item.picture_url != undefined) {
					val = '<span class="ibo-input-select--autocomplete-item-image" style="background-image: url('+item.picture_url+');">'+item.initials+'</span>';
				} else {
					val = '<span class="ibo-input-select--autocomplete-item-image");">'+item.initials+'</span>';
				}
			}
			val = val+'<div class="ibo-input-select--autocomplete-item-txt" title="'+item.label+'">';
			if (item.obsolescence_flag == '1') {
				val = val+' <span class="object-ref-icon text_decoration"><span class="fas fa-eye-slash object-obsolete fa-1x fa-fw"></span></span>';
			}
			let labelValue = item.label.replace(new RegExp("(?![^&;]+;)(?!<[^<>]*)("+term+")(?![^<>]*>)(?![^&;]+;)", "gi"), "<strong>$1</strong>");
			val = val+labelValue;
			if (item.additional_field != undefined) {
				val = val+'<br><i>'+item.additional_field+'</i>';
			}
			val = val+'</div>';
			return $("<li>")
				.append("<div data-selectable=\"\" class=\"ibo-input-select--autocomplete-item\">"+val+"</div>")
				.appendTo(ul);
		};

		$('#label_$sFieldName').on('focus', function () {
			// track whether the field has focus, we shouldn't process any
			// results if the field no longer has focus
			hasFocus++;
		}).on('blur', function () {
			hasFocus = 0;
			if ($('#label_$sFieldName').val().length == 0) {
                $('#$sFieldName').val('');
                $('#label_$sFieldName').val('');
                $('#label_$sFieldName').data('selected_value', '');
			} else {
				$('#label_$sFieldName').val($('#label_$sFieldName').data('selected_value'));
			}
		}).on('click',
			function () {
				hasFocus++;
				$('#label_$sFieldName').autocomplete("search");
			}).on('keyup',function () {
			if ($('#label_$sFieldName').val().length == 0) {
				if (!$('#label_$sFieldName').parent().find('.ibo-input-select--action-button--clear').hasClass('ibo-is-hidden')) {
					$('#label_$sFieldName').parent().find('.ibo-input-select--action-button--clear').addClass('ibo-is-hidden');
				}
			} else {
				if ($('#label_$sFieldName').parent().find('.ibo-input-select--action-button--clear').hasClass('ibo-is-hidden')) {
					$('#label_$sFieldName').parent().find('.ibo-input-select--action-button--clear').removeClass('ibo-is-hidden');
				}
			}
		});

		var iPaddingRight = $('#$sFieldName').parent().find('.ibo-input-select--action-buttons')[0].childElementCount * 20+15;
		$('#$sFieldName').parent().find('.ibo-input-select').css('padding-right', iPaddingRight);
        
        
        
		if ($('#ac_dlg_{$sFieldName}').length == 0)
		{
			$('body').append('<div id="ac_dlg_{$sFieldName}"></div>');
		}
JS
        );
        $sHTMLValue .= "<div class=\"ibo-input-select--action-buttons\">";
        $sHTMLValue .= "<a href=\"#\" class=\"ibo-input-select--action-button ibo-input-select--action-button--clear ibo-is-hidden\"  id=\"mini_clear_{$sFieldName}\" onClick=\"$('#$sFieldName').val('');$('#label_$sFieldName').val('');		$('#label_$sFieldName').data('selected_value', '');\" data-tooltip-content='".Dict::S('UI:Button:Clear')."'><i class=\"fas fa-times\"></i></a>";
    }
 /*   if ($bExtensions && MetaModel::IsHierarchicalClass($this->sTargetClass) !== false) {
        $sHTMLValue .= "<a href=\"#\" class=\"ibo-input-select--action-button ibo-input-select--action-button--hierarchy\" id=\"mini_tree_{$this->iId}\" onClick=\"oACWidget_{$this->iId}.HKDisplay();\" data-tooltip-content='".Dict::S('UI:Button:SearchInHierarchy')."'><i class=\"fas fa-sitemap\"></i></a>";
        $oPage->add_ready_script(
            <<<JS
			if ($('#ac_tree_{$sFieldName}').length == 0)
			{
				$('body').append('<div id="ac_tree_{$sFieldName}"></div>');
			}		
JS
        );
    }
    if ($oAllowedValues->CountExceeds($iMaxComboLength)) {
        $sHTMLValue .= "	<a href=\"#\" class=\"ibo-input-select--action-button ibo-input-select--action-button--search\"  id=\"mini_search_{$this->iId}\" onClick=\"oACWidget_{$this->iId}.Search();\" data-tooltip-content='".Dict::S('UI:Button:Search')."'><i class=\"fas fa-search\"></i></a>";
    }*/
    $sHTMLValue .= "</div>";
    $sHTMLValue .= "</div>";

    return new Html( $sHTMLValue);
}
try
{
	require_once('../approot.inc.php');
	require_once(APPROOT.'/application/application.inc.php');
	require_once(APPROOT.'/application/startup.inc.php');
	IssueLog::Trace('----- Request: '.utils::GetRequestUri(), LogChannels::WEB_REQUEST);

	$bSelectionAuditRulesByDefault = utils::GetConfig()->Get('audit.enable_selection_landing_page');
	$operation = utils::ReadParam('operation', $bSelectionAuditRulesByDefault ? 'selection' : 'audit');
    $aAuditFilter = utils::GetConfig()->Get('audit.filter');
    if ($aAuditFilter == null){
        $aAuditFilter = [];
    }

	$oAppContext = new ApplicationContext();
	
	require_once(APPROOT.'/application/loginwebpage.class.inc.php');
	LoginWebPage::DoLogin(); // Check user rights and prompt if needed
	
	$oP = new iTopWebPage(Dict::S('UI:Audit:Title'));

	switch($operation)
	{
		case 'csv':
		$oP->DisableBreadCrumb();
		// Big result sets cause long OQL that cannot be passed (serialized) as a GET parameter
		// Therefore we don't use the standard "search_oql" operation of UI.php to display the CSV
		$iCategory = utils::ReadParam('category', '');
		$iRuleIndex = utils::ReadParam('rule', 0);
	
		$oAuditCategory = MetaModel::GetObject('AuditCategory', $iCategory);
		$oDefinitionFilter = DBObjectSearch::FromOQL($oAuditCategory->Get('definition_set'));
		$oDefinitionFilter->UpdateContextFromUser();
		FilterByContext($oDefinitionFilter, $oAppContext);
		$oDefinitionSet = new CMDBObjectSet($oDefinitionFilter);
		$oFilter = GetRuleResultFilter($iRuleIndex, $oDefinitionFilter, $oAppContext);
		$oErrorObjectSet = new CMDBObjectSet($oFilter);
		$oAuditRule = MetaModel::GetObject('AuditRule', $iRuleIndex);
		$sFileName = utils::ReadParam('filename', null, true, 'string');
		$bAdvanced = utils::ReadParam('advanced', false);
		$sAdvanced = $bAdvanced ? '&advanced=1' : '';
		
		if ($sFileName != null)
		{
			$oP = new CSVPage("iTop - Export");
			$sCharset = MetaModel::GetConfig()->Get('csv_file_default_charset');
			$sCSVData = cmdbAbstractObject::GetSetAsCSV($oErrorObjectSet, array('localize_values' => true, 'fields_advanced' => $bAdvanced), $sCharset);
			if ($sCharset == 'UTF-8')
			{
				$sOutputData = UTF8_BOM.$sCSVData;
			}
			else
			{
				$sOutputData = $sCSVData;
			}
			if ($sFileName == '')
			{
				// Plain text => Firefox will NOT propose to download the file
				$oP->add_header("Content-type: text/plain; charset=$sCharset");
			}
			else
			{
				$sCSVName = basename($sFileName); // pseudo sanitization, just in case
				// Force the name of the downloaded file, since windows gives precedence to the extension over of the mime type
				$oP->add_header("Content-disposition: attachment; filename=\"$sCSVName\"");
				$oP->add_header("Content-type: text/csv; charset=$sCharset");
			}
			$oP->add($sOutputData);
			$oP->TrashUnexpectedOutput();
			$oP->output();
			exit;
		} else {
			$sTitle = Dict::S('UI:Audit:AuditErrors');
			$oP->SetBreadCrumbEntry('ui-tool-auditerrors', $sTitle, '', '', 'fas fa-stethoscope', iTopWebPage::ENUM_BREADCRUMB_ENTRY_ICON_TYPE_CSS_CLASSES);

			$oBackButton = ButtonUIBlockFactory::MakeIconLink('fas fa-chevron-left', Dict::S('UI:Audit:InteractiveAudit:Back'), "./audit.php?".$oAppContext->GetForLink());
			$oP->AddUiBlock($oBackButton);
			$oP->AddUiBlock(TitleUIBlockFactory::MakeForPage($sTitle.$oAuditRule->Get('description')));

			$sBlockId = 'audit_errors';
			$oP->p("<div id=\"$sBlockId\">");
			$oBlock = DisplayBlock::FromObjectSet($oErrorObjectSet, 'csv', array('show_obsolete_data' => true));
			$oBlock->Display($oP, 1);
			$oP->p("</div>");
			// Adjust the size of the Textarea containing the CSV to fit almost all the remaining space
			$oP->add_ready_script(" $('#1>textarea').height(400);"); // adjust the size of the block			
			$sExportUrl = utils::GetAbsoluteUrlAppRoot()."pages/audit.php?operation=csv&category=".$oAuditCategory->GetKey()."&rule=".$oAuditRule->GetKey();
			$oDownloadButton = ButtonUIBlockFactory::MakeForAlternativePrimaryAction('fas fa-chevron-left', Dict::S('UI:Audit:InteractiveAudit:Back'), "./audit.php?".$oAppContext->GetForLink());

			$oP->add_ready_script("$('a[href*=\"webservices/export.php?expression=\"]').attr('href', '".$sExportUrl."&filename=audit.csv".$sAdvanced."');");
			$oP->add_ready_script("$('#1 :checkbox').removeAttr('onclick').on('click', function() { var sAdvanced = ''; if (this.checked) sAdvanced = '&advanced=1'; window.location.href='$sExportUrl'+sAdvanced; } );");
		}
		break;
						
		case 'errors':
			$sTitle = Dict::S('UI:Audit:AuditErrors');
			$iCategory = utils::ReadParam('category', '');
			$iRuleIndex = utils::ReadParam('rule', 0);

			$oAuditCategory = MetaModel::GetObject('AuditCategory', $iCategory);
			$oDefinitionFilter = DBObjectSearch::FromOQL($oAuditCategory->Get('definition_set'));
			$oDefinitionFilter->UpdateContextFromUser();
			FilterByContext($oDefinitionFilter, $oAppContext);
			$oDefinitionSet = new CMDBObjectSet($oDefinitionFilter);
			$oFilter = GetRuleResultFilter($iRuleIndex, $oDefinitionFilter, $oAppContext);
			$oErrorObjectSet = new CMDBObjectSet($oFilter);
			$oAuditRule = MetaModel::GetObject('AuditRule', $iRuleIndex);
			$sDescription = get_class($oAuditRule).": ".$oAuditRule->GetName();
			$oP->SetBreadCrumbEntry('ui-tool-auditerrors', $sTitle, $sDescription, '', 'fas fa-stethoscope', iTopWebPage::ENUM_BREADCRUMB_ENTRY_ICON_TYPE_CSS_CLASSES);

			$oBackButton = ButtonUIBlockFactory::MakeIconLink('fas fa-chevron-left', Dict::S('UI:Audit:Interactive:Button:Back'), "./audit.php?".$oAppContext->GetForLink());
			$oP->AddUiBlock($oBackButton);
			$oP->AddUiBlock(TitleUIBlockFactory::MakeForPage($sTitle.$oAuditRule->Get('description')));
			$sBlockId = 'audit_errors';
			$oP->p("<div id=\"$sBlockId\">");
			$oBlock = DisplayBlock::FromObjectSet($oErrorObjectSet, 'list', array('show_obsolete_data' => true));
			$oBlock->Display($oP, 1);
			$oP->p("</div>");
			$sExportUrl = utils::GetAbsoluteUrlAppRoot()."pages/audit.php?operation=csv&category=".$oAuditCategory->GetKey()."&rule=".$oAuditRule->GetKey();
			$oP->add_ready_script("$('a[href*=\"pages/UI.php?operation=search\"]').attr('href', '".$sExportUrl."')");
			break;

		case 'selection':
			$oP->SetBreadCrumbEntry('ui-tool-auditselection', Dict::S('UI:Audit:Interactive:Selection:BreadCrumb'), Dict::S('UI:Audit:Interactive:Selection:BreadCrumb+'), '', 'fas fa-stethoscope', iTopWebPage::ENUM_BREADCRUMB_ENTRY_ICON_TYPE_CSS_CLASSES);
			if (UserRights::IsActionAllowed('AuditCategory', UR_ACTION_MODIFY)) {
				$oButton = ButtonUIBlockFactory::MakeLinkNeutral(utils::GetAbsoluteUrlAppRoot()."pages/UI.php?c[menu]=AuditCategories", Dict::S('UI:Audit:Interactive:Button:Configuration'), 'fas fa-wrench');
				$oP->AddUiBlock($oButton);
			}
			$oP->AddUiBlock(TitleUIBlockFactory::MakeForPage(Dict::S('UI:Audit:Interactive:Selection:Title')));

            if($aAuditFilter !=[] ){
                $oPanel = PanelUIBlockFactory::MakeNeutral('',Dict::S('UI:Audit:Interactive:Selection:SubTitleParams'));
                $oP->AddUiBlock($oPanel);
                foreach ($aAuditFilter as $sFieldName => $aFieldParam) {

                    $oBlock = FieldUIBlockFactory::MakeStandard($aFieldParam['label']);
                    $oBlock->SetAttLabel($aFieldParam['label'])
                        ->AddDataAttribute("input-id", $sFieldName)
                        ->AddDataAttribute("input-type", 'input-type');
                    $oValue = UIContentBlockUIBlockFactory::MakeStandard("", ["form-field-content", "ibo-input-field-wrapper"]);

                    $sCurrentValue = utils::ReadParam($sFieldName, '');

                    if (array_key_exists('oql', $aFieldParam) && utils::IsNotNullOrEmptyString($aFieldParam['oql'])) {
                        $oValue->AddSubBlock(MakeSelectField( $oP, $aFieldParam['label'],  $sFieldName,  $aFieldParam['oql'],  $sCurrentValue));
                    } else {//this is a list of values
                        $aListValues = $aFieldParam['values'];
                        $oSelect = SelectUIBlockFactory::MakeForSelect($sFieldName, $sFieldName);
                        $oSelect->AddCSSClass('ibo-input-field-wrapper');

                        foreach($aListValues as $sKey => $sValue) {
                            $oSelect->AddOption(SelectOptionUIBlockFactory::MakeForSelectOption($sKey, $sValue, ($sCurrentValue == $sKey)));
                        }

                        $oValue->AddSubBlock($oSelect);
                    }
                    $oBlock->AddSubBlock($oValue);
                    $oPanel->AddSubBlock($oBlock);
                 }

            }
            $oP->AddUiBlock(TitleUIBlockFactory::MakeNeutral(Dict::S('UI:Audit:Interactive:Selection:SubTitle'),2));

			// Header block to select all audit categories
			$oCategoriesSet = new DBObjectSet(new DBObjectSearch('AuditCategory'));
			$iCategoryCount = $oCategoriesSet->Count();

			$oDashboardRow = new DashboardRow();
			$oDashboardRow->AddCSSClass('ibo-audit--dashboard');
			$oDashboardColumn = new DashboardColumn(false, true);
			$oDashboardRow->AddDashboardColumn($oDashboardColumn);
			$oAllCategoriesDashlet = new DashletContainer();

            $sDomainUrl = utils::GetAbsoluteUrlAppRoot()."pages/audit.php?operation=audit";
            if($aAuditFilter !=[] ) {
                //modif URLLink In order to send params
                $sGetParams = '';
                foreach ($aAuditFilter as $sFieldName => $aFieldParam) {
                    $sGetParams .= $sFieldName."=$('[name=$sFieldName]').val();";
                    $sDomainUrl .= "&".$sFieldName."='+$sFieldName+'";
                }
                $sDomainUrl = 'javascript:'.$sGetParams.' window.location = \''.$sDomainUrl.'\'';
            }

			$oAllCategoriesDashlet
				->AddCSSClasses(['ibo-dashlet--is-inline', 'ibo-dashlet-badge'])
				->AddSubBlock(DashletFactory::MakeForDashletBadge(
					utils::GetAbsoluteUrlAppRoot().'images/icons/icons8-audit.svg',
                    $sDomainUrl,
					$iCategoryCount,
					Dict::S('UI:Audit:Interactive:Selection:BadgeAll')
				));
			$oDashboardColumn->AddUIBlock($oAllCategoriesDashlet);
			$oP->AddUiBlock($oDashboardRow);

			// Three column layout to display all available audit domains
			$oDashboardRow = new DashboardRow();
			$oDashboardRow
				->AddCSSClass('ibo-audit--dashboard')
				->AddDashboardColumn(new DashboardColumn(false, true))
				->AddDashboardColumn(new DashboardColumn(false, true))
				->AddDashboardColumn(new DashboardColumn(false, true));

			// Fetch all audit domains with at least on linked category
			$oDomainSet = new DBObjectSet(DBObjectSearch::FromOQL("SELECT AuditDomain AS domain JOIN lnkAuditCategoryToAuditDomain AS lnk ON lnk.domain_id = domain.id"));
			$oDomainSet->SetOrderBy(array('name' => true));
			$iDomainCnt = 0;
			/** @var AuditDomain $oAuditDomain */
			while($oAuditDomain = $oDomainSet->Fetch()) {
				$sDomainUrl = utils::GetAbsoluteUrlAppRoot()."pages/audit.php?operation=audit&domain=".$oAuditDomain->GetKey();
				$sIconUrl = utils::GetAbsoluteUrlAppRoot().'images/icons/icons8-puzzle.svg';
					/** @var \ormDocument $oImage */
				$oImage = $oAuditDomain->Get('icon');
				if (!$oImage->IsEmpty()) {
					$sIconUrl = $oImage->GetDisplayURL(get_class($oAuditDomain), $oAuditDomain->GetKey(), 'icon');
				}
				$iCategoryCount = $oAuditDomain->Get('categories_list')->Count();

                if($aAuditFilter !=[] ) {
                     //modif URLLink In order to send params
                    $sGetParams = '';
                    foreach ($aAuditFilter as $sFieldName => $aFieldParam) {
                        $sGetParams .= $sFieldName."=$('[name=$sFieldName]').val();";
                        $sDomainUrl .= "&".$sFieldName."='+$sFieldName+'";
                    }
                    $sDomainUrl = 'javascript:'.$sGetParams.' window.location = \''.$sDomainUrl.'\'';
                }

				$oDomainBlock = DashletFactory::MakeForDashletBadge($sIconUrl, $sDomainUrl, $iCategoryCount, $oAuditDomain->Get('name'));
				$oDomainDashlet = new DashletContainer();
				$oDomainDashlet->AddSubBlock($oDomainBlock)->AddCSSClasses(['ibo-dashlet--is-inline', 'ibo-dashlet-badge']);
				$oDashboardRow->GetSubBlocks()[$iDomainCnt % 3]->AddUIBlock($oDomainDashlet); // ;
				$iDomainCnt++;
                IssueLog::Error('domaine numero'.$iDomainCnt);
			}
            $oP->AddUiBlock($oDashboardRow);

			break;

		case 'audit':
		default:
			$sDomainKey = utils::ReadParam('domain', '');
			$sCategories = utils::ReadParam('categories', '', false, utils::ENUM_SANITIZATION_FILTER_STRING);  // May contain commas
			// Default case, full audit
			$oCategoriesSet = new DBObjectSet(new DBObjectSearch('AuditCategory'));
			$sTitle = Dict::S('UI:Audit:Interactive:All:Title');
			$sSubTitle = Dict::S('UI:Audit:Interactive:All:SubTitle');
			$sBreadCrumbLabel = Dict::S('UI:Audit:Interactive:All:BreadCrumb');
			$sBreadCrumbTooltip = Dict::S('UI:Audit:Interactive:All:BreadCrumb+');

			if (!empty($sCategories)) {  // Case with a set of categories
				$oCategoriesSet = new DBObjectSet(DBObjectSearch::FromOQL("SELECT AuditCategory WHERE id IN (:categories)", array('categories' => explode(',', $sCategories))));
				$sCategories = implode(", ", $oCategoriesSet->GetColumnAsArray('name'));
				$oCategoriesSet->Rewind();
				$sTitle = Dict::Format('UI:Audit:Interactive:Categories:Title', $sCategories);
				$sSubTitle = Dict::Format('UI:Audit:Interactive:Categories:SubTitle', $oCategoriesSet->Count());
				$sBreadCrumbLabel = Dict::Format('UI:Audit:Interactive:Categories:BreadCrumb', $oCategoriesSet->Count());
				$sBreadCrumbTooltip = Dict::Format('UI:Audit:Interactive:Categories:BreadCrumb+', $sCategories);

			} elseif (!empty($sDomainKey)) {  // Case with a single Domain
				$oAuditDomain = MetaModel::GetObject('AuditDomain', $sDomainKey);
				$oCategoriesSet = new DBObjectSet(DBObjectSearch::FromOQL("SELECT AuditCategory AS c JOIN lnkAuditCategoryToAuditDomain AS lnk ON lnk.category_id = c.id WHERE lnk.domain_id = :domain", array('domain' => $oAuditDomain->GetKey())));
				$sDomainName = $oAuditDomain->GetName();
				$sTitle = Dict::Format('UI:Audit:Interactive:Domain:Title', $sDomainName);
				$sSubTitle = Dict::Format('UI:Audit:Interactive:Domain:SubTitle', $sDomainName);
				$sBreadCrumbLabel = Dict::Format('UI:Audit:Interactive:Domain:BreadCrumb', $sDomainName);
				$sBreadCrumbTooltip = Dict::Format('UI:Audit:Interactive:Domain:BreadCrumb+', $sDomainName);
			}

			$oP->SetBreadCrumbEntry('ui-tool-audit', $sBreadCrumbLabel, $sBreadCrumbTooltip, '', 'fas fa-stethoscope', iTopWebPage::ENUM_BREADCRUMB_ENTRY_ICON_TYPE_CSS_CLASSES);
			$oBackButton = ButtonUIBlockFactory::MakeLinkNeutral("./audit.php?".$oAppContext->GetForLink(), Dict::S('UI:Audit:Interactive:Button:Back'), 'fas fa-chevron-left');
			$oP->AddUiBlock($oBackButton);
			$oP->AddUiBlock(TitleUIBlockFactory::MakeForPage($sTitle));


            $aFilterParams = [];
            if($aAuditFilter !=[] ){
                $oPanel = PanelUIBlockFactory::MakeNeutral('',Dict::S('UI:Audit:Interactive:FilterList'));
                $oP->AddUiBlock($oPanel);

                foreach ($aAuditFilter as $sFieldName => $aFieldParam) {
                    $sCurrentValue = utils::ReadParam($sFieldName, '');
                    $aFilterParams[$sFieldName] = $sCurrentValue;
                    IssueLog::Error($sFieldName.':'.$sCurrentValue);
                    $sName = '';
                    if (array_key_exists('oql', $aFieldParam) && utils::IsNotNullOrEmptyString($aFieldParam['oql'])) {
                         $oSearch = new DBObjectSet(DBObjectSearch::FromOQL($aFieldParam['oql']));
                         $sClass = $oSearch->GetClass();
                        $oObject = MetaModel::GetObject($sClass, $sCurrentValue);
                        $sName = $oObject->GetName();
                    } else {//this is a list of values
                        $sName = $aFieldParam['values'][$sCurrentValue];
                    }

                    $sFilterText .= '<li>'.$aFieldParam['label'].': '.$sName.'</li>';
                }
                $oPanel->AddSubBlock(new Html($sFilterText.'</ul>'));
            }
        $oP->AddUiBlock(new Html('<br>'));

			$oP->AddUiBlock(new Text($sSubTitle));

			$oTotalBlock = DashletFactory::MakeForDashletBadge(utils::GetAbsoluteUrlAppRoot().'images/icons/icons8-audit.svg', '#', 0, Dict::S('UI:Audit:Dashboard:ObjectsAudited'));
			$oErrorBlock = DashletFactory::MakeForDashletBadge(utils::GetAbsoluteUrlAppRoot().'images/icons/icons8-delete.svg', '#', 0, Dict::S('UI:Audit:Dashboard:ObjectsInError'));
			$oWorkingBlock = DashletFactory::MakeForDashletBadge(utils::GetAbsoluteUrlAppRoot().'images/icons/icons8-checkmark.svg', '#', 0, Dict::S('UI:Audit:Dashboard:ObjectsValidated'));

			$aCSSClasses = ['ibo-dashlet--is-inline', 'ibo-dashlet-badge'];

			$oDashletContainerTotal = new DashletContainer();
			$oDashletContainerError = new DashletContainer();
			$oDashletContainerWorking = new DashletContainer();

			$oDashletContainerTotal->AddSubBlock($oTotalBlock)->AddCSSClasses($aCSSClasses);
			$oDashletContainerError->AddSubBlock($oErrorBlock)->AddCSSClasses($aCSSClasses);
			$oDashletContainerWorking->AddSubBlock($oWorkingBlock)->AddCSSClasses($aCSSClasses);

			$oDashboardRow = new DashboardRow();

			$oDashboardColumnTotal = new DashboardColumn(false, true);
			$oDashboardColumnError = new DashboardColumn(false, true);
			$oDashboardColumnWorking = new DashboardColumn(false, true);

			$oDashboardColumnTotal->AddUIBlock($oDashletContainerTotal);
			$oDashboardColumnError->AddUIBlock($oDashletContainerError);
			$oDashboardColumnWorking->AddUIBlock($oDashletContainerWorking);

			$oDashboardRow->AddDashboardColumn($oDashboardColumnTotal);
			$oDashboardRow->AddDashboardColumn($oDashboardColumnError);
			$oDashboardRow->AddDashboardColumn($oDashboardColumnWorking);

			$oDashboardRow->AddCSSClass('ibo-audit--dashboard');

			$oP->AddUiBlock($oDashboardRow);

			$aAuditCategoryPanels = [];
			/** @var AuditCategory $oAuditCategory */
			while ($oAuditCategory = $oCategoriesSet->fetch()) {
				$oAuditCategoryPanelBlock = new Panel($oAuditCategory->GetName());
				$oAuditCategoryPanelBlock->SetIsCollapsible(true);
				// Create toolbar and add it to panel
				$oToolbar = \Combodo\iTop\Application\UI\Base\Component\Toolbar\ToolbarUIBlockFactory::MakeForButton();
				$oAuditCategoryPanelBlock->AddToolbarBlock($oToolbar);
				// Add a button in the above toolbar
				$sAuditCategoryClass = get_class($oAuditCategory);
				if (UserRights::IsActionAllowed($sAuditCategoryClass, UR_ACTION_READ)) {
					$oToolbar->AddSubBlock(ButtonUIBlockFactory::MakeIconLink('fas fa-wrench fa-lg', Dict::S('UI:Audit:ViewRules'), ApplicationContext::MakeObjectUrl($sAuditCategoryClass, $oAuditCategory->GetKey()).'&#ObjectProperties=tab_ClassAuditCategoryAttributerules_list'),);
				}
				$aResults = array();
				try {
					$iCount = 0;
                    IssueLog::Error('$aFilterParams'.json_encode($aFilterParams));
					$oDefinitionFilter = DBObjectSearch::FromOQL($oAuditCategory->Get('definition_set'),$aFilterParams);
					$oDefinitionFilter->UpdateContextFromUser();
					FilterByContext($oDefinitionFilter, $oAppContext);

					$aObjectsWithErrors = array();
					if (!empty($currentOrganization)) {
						if (MetaModel::IsValidFilterCode($oDefinitionFilter->GetClass(), 'org_id')) {
							$oDefinitionFilter->AddCondition('org_id', $currentOrganization, '=');
						}
					}
                    IssueLog::Error('Filtre: '.$oDefinitionFilter->ToOQL(true));
					$oDefinitionSet = new CMDBObjectSet($oDefinitionFilter);
					$iCount = $oDefinitionSet->Count();
					$oRulesFilter = new DBObjectSearch('AuditRule');
					$oRulesFilter->AddCondition('category_id', $oAuditCategory->GetKey(), '=');
                    foreach ($aFilterParams as $sFieldName => $sCurrentValue) {
                        $oRulesFilter->AddInternalParam($sFieldName, $sCurrentValue);
                    }

                    IssueLog::Error('2Filtre: '.$oRulesFilter->ToOQL(true));
					$oRulesSet = new DBObjectSet($oRulesFilter);
					while ($oAuditRule = $oRulesSet->fetch()) {
						$aRow = array();
						$aRow['description'] = $oAuditRule->GetName();
						if ($iCount == 0) {
							// nothing to check, really !
							$aRow['nb_errors'] = "<a href=\"audit.php?operation=errors&category=".$oAuditCategory->GetKey()."&rule=".$oAuditRule->GetKey()."\">0</a>";
							$aRow['percent_ok'] = '100.00';
							$aRow['class'] = $oAuditCategory->GetReportColor($iCount, 0);
						} else {
							try {
								$oFilter = GetRuleResultFilter($oAuditRule->GetKey(), $oDefinitionFilter, $oAppContext, $aFilterParams);
                                IssueLog::Error('3Filtre: '.$oFilter->ToOQL(true));
							    $aErrors = $oFilter->SelectAttributeToArray('id');
								$iErrorsCount = count($aErrors);
								foreach ($aErrors as $aErrorRow) {
									$aObjectsWithErrors[$aErrorRow['id']] = true;
								}
								$aRow['nb_errors'] = ($iErrorsCount == 0) ? '0' : "<a href=\"?operation=errors&category=".$oAuditCategory->GetKey()."&rule=".$oAuditRule->GetKey()."&".$oAppContext->GetForLink()."\">$iErrorsCount</a> <a href=\"?operation=csv&category=".$oAuditCategory->GetKey()."&rule=".$oAuditRule->GetKey()."&".$oAppContext->GetForLink()."\"><img src=\"" . utils::GetAbsoluteUrlAppRoot() . "images/icons/icons8-export-csv.svg\" class=\"ibo-audit--audit-line--csv-download\"></a>";
								$aRow['percent_ok'] = sprintf('%.2f', 100.0 * (($iCount - $iErrorsCount) / $iCount));
								$aRow['class'] = $oAuditCategory->GetReportColor($iCount, $iErrorsCount);
							}
							catch (Exception $e) {
								$aRow['nb_errors'] = Dict::S('UI:Audit:OqlError');
								$aRow['percent_ok'] = Dict::S('UI:Audit:Error:ValueNA');
								$aRow['class'] = 'red';
								$sMessage = Dict::Format('UI:Audit:ErrorIn_Rule_Reason', $oAuditRule->GetHyperlink(), $e->getMessage());

								$oErrorAlert = AlertUIBlockFactory::MakeForFailure(Dict::S('UI:Audit:ErrorIn_Rule'), $sMessage);
								$oErrorAlert->AddCSSClass('ibo-audit--error-alert');
								$oP->AddUiBlock($oErrorAlert);
							}
						}
						$aResults[] = $aRow;
					}
					$iTotalErrors = count($aObjectsWithErrors);
					$sOverallPercentOk = ($iCount == 0) ? '100.00' : sprintf('%.2f', 100.0 * (($iCount - $iTotalErrors) / $iCount));
					$sClass = $oAuditCategory->GetReportColor($iCount, $iTotalErrors);

					$oTotalBlock->SetCount((int)$oTotalBlock->GetCount() + ($iCount));
					$oErrorBlock->SetCount((int)$oErrorBlock->GetCount() + $iTotalErrors);
					$oWorkingBlock->SetCount((int)$oWorkingBlock->GetCount() + ($iCount - $iTotalErrors));
					$oAuditCategoryPanelBlock->SetSubTitle(Dict::Format('UI:Audit:AuditCategory:Subtitle', $iTotalErrors, $iCount, $sOverallPercentOk));

				}
			catch(Exception $e)
			{
				$sMessage = Dict::Format('UI:Audit:ErrorIn_Category_Reason', $oAuditCategory->GetHyperlink(), utils::HtmlEntities($e->getMessage()));
				$oErrorAlert = AlertUIBlockFactory::MakeForFailure(Dict::S('UI:Audit:ErrorIn_Category'), $sMessage);
				$oErrorAlert->AddCSSClass('ibo-audit--error-alert');
				$oP->AddUiBlock($oErrorAlert);
				continue;
			}

			$oAuditCategoryPanelBlock->SetColorFromColorSemantic($sClass);
			$oAuditCategoryPanelBlock->AddCSSClass('ibo-audit--audit-category--panel');
			$aData = [];
			foreach($aResults as $aRow)
			{
				$aData[] = array(
					'audit_rule' => $aRow['description'],
					'nb_err' => $aRow['nb_errors'],
					'percentage_ok' => $aRow['percent_ok'],
					'@class' => 'ibo-is-'.$aRow['class'].'',
				);
			}

			$aAttribs = array(
				'audit_rule' => array('label' => Dict::S('UI:Audit:HeaderAuditRule'), 'description' => Dict::S('UI:Audit:HeaderAuditRule')),
				'nb_err' => array('label' => Dict::S('UI:Audit:HeaderNbErrors'), 'description' => Dict::S('UI:Audit:HeaderNbErrors')),
				'percentage_ok' => array('label' => Dict::S('UI:Audit:PercentageOk'), 'description' => Dict::S('UI:Audit:PercentageOk')),
			);

			$oAttachmentTableBlock = DataTableUIBlockFactory::MakeForStaticData('', $aAttribs, $aData, null, [], "", array('pageLength' => -1));
			$oAuditCategoryPanelBlock->AddSubBlock($oAttachmentTableBlock);
			$aAuditCategoryPanels[] = $oAuditCategoryPanelBlock;
		}
		foreach ($aAuditCategoryPanels as $oAuditCategoryPanel) {
			$oP->AddUiBlock($oAuditCategoryPanel);
		}
	}
	$oP->output();
}
catch(CoreException $e)
{
	require_once(APPROOT.'/setup/setuppage.class.inc.php');
	$oP = new ErrorPage(Dict::S('UI:PageTitle:FatalError'));
	$oP->add("<h1>".Dict::S('UI:FatalErrorMessage')."</h1>\n");	
	$oP->error(Dict::Format('UI:Error_Details', $e->getHtmlDesc()));	
	$oP->output();

	if (MetaModel::IsLogEnabledIssue())
	{
		if (MetaModel::IsValidClass('EventIssue'))
		{
			$oLog = new EventIssue();

			$oLog->Set('message', $e->getMessage());
			$oLog->Set('userinfo', '');
			$oLog->Set('issue', $e->GetIssue());
			$oLog->Set('impact', 'Page could not be displayed');
			$oLog->Set('callstack', $e->getTrace());
			$oLog->Set('data', $e->getContextData());
			$oLog->DBInsertNoReload();
		}

		IssueLog::Error($e->getMessage());
	}

	// For debugging only
	//throw $e;
}
catch(Exception $e)
{
	require_once(APPROOT.'/setup/setuppage.class.inc.php');
	$oP = new ErrorPage(Dict::S('UI:PageTitle:FatalError'));
	$oP->add("<h1>".Dict::S('UI:FatalErrorMessage')."</h1>\n");	
	$oP->error(Dict::Format('UI:Error_Details', $e->getMessage()));	
	$oP->output();

	if (MetaModel::IsLogEnabledIssue())
	{
		if (MetaModel::IsValidClass('EventIssue'))
		{
			$oLog = new EventIssue();

			$oLog->Set('message', $e->getMessage());
			$oLog->Set('userinfo', '');
			$oLog->Set('issue', 'PHP Exception');
			$oLog->Set('impact', 'Page could not be displayed');
			$oLog->Set('callstack', $e->getTrace());
			$oLog->Set('data', array());
			$oLog->DBInsertNoReload();
		}

		IssueLog::Error($e->getMessage());
	}
}
