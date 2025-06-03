<?php
declare(strict_types=1);

namespace Combodo\iTop\Service\Export;

use BulkExport;
use Combodo\iTop\Application\TwigBase\Controller\Controller;
use Combodo\iTop\Application\UI\Base\Component\Field\FieldUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Form\Form;
use Combodo\iTop\Application\UI\Base\Component\Form\FormUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Input\InputUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Input\Select\SelectOptionUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Input\Select\SelectUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Panel\PanelUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Layout\MultiColumn\Column\ColumnUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Layout\MultiColumn\MultiColumnUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Layout\UIContentBlockUIBlockFactory;
use Combodo\iTop\Application\WebPage\AjaxPage;
use Combodo\iTop\Application\WebPage\WebPage;
use Combodo\iTop\Service\Router\Router;
use Dict;
use Exception;
use MetaModel;
use UserRights;
use utils;

/**
 *
 */
class ExportController extends Controller
{
    public const ROUTE_NAMESPACE = 'export';
    /**
     * @return AjaxPage
     * @throws \ApplicationException
     * @throws \CoreException
     * @throws \OQLException
     */
    public static function OperationChooseGlobalParams()
    {
        $sFormat = utils::ReadParam('format', '');

        $sExportBtnLabel = json_encode(Dict::S('UI:Button:Export'));
        $sJSTitle = json_encode(utils::EscapeHtml(utils::ReadParam('dialog_title', '', false, 'raw_data')));
        $oP = new AjaxPage($sJSTitle);
        $oP->add('<div id="interactive_export_dlg">');
        $oP->add_ready_script(
            <<<EOF
		$('#interactive_export_dlg').dialog({
			autoOpen: true,
			modal: true,
			width: '80%',
			height: 'auto',
			maxHeight: $(window).height() - 50,
			title: $sJSTitle,
			close: function() { $('#export-form').attr('data-state', 'cancelled'); $(this).remove(); },
			buttons: [
				{text: $sExportBtnLabel, id: 'export-dlg-submit', click: function() {} }
			]
		});
			
		setTimeout(function() { $('#interactive_export_dlg').dialog('option', { position: { my: "center", at: "center", of: window }}); $('#export-btn').hide(); ExportImpactButton('#export-dlg-submit'); }, 100);
EOF
        );

        $oForm = self::GetFormWithHiddenParams($sFormat, $oP);
        /*
        A AJOUTER PEUT-ETRE PLUS TARD
				sHtmlForm += '<tr><td>'+this.options.labels.title+'</td><td><input name="title" value="'+this.options.labels.untitled+'" style="width: 20em;"/></td></tr>';
				sHtmlForm += '<tr><td>'+this.options.labels.comments+'</td><td><textarea style="width: 20em; height:5em;" name="comments"/></textarea></td></tr>';
*/
        /* first select params specific to the export format  */
        $oExporter = BulkExport::FindExporter($sFormat);
        if ($oExporter === null) {
            $aSupportedFormats = BulkExport::FindSupportedFormats();
            $oP->add("Invalid output format: '$sFormat'. The supported formats are: ".implode(', ', array_keys($aSupportedFormats)));
            $oP->add('</div>');
            return $oP;
        }
            $UIContentBlock = UIContentBlockUIBlockFactory::MakeStandard('form_part_'.$sFormat)->AddCSSClass('form_part');
            $oForm->AddSubBlock($UIContentBlock);
            $UIContentBlock->AddSubBlock($oExporter->GetFormPart($oP, $sFormat.'_options'));

        $aSelectedClasses = utils::ReadParam('list_classes', '', false, utils::ENUM_SANITIZATION_FILTER_RAW_DATA);

        $oPanel = PanelUIBlockFactory::MakeNeutral(Dict::S('UI:Export:Class:SelectedClasses'));
        $oForm->AddSubBlock($oPanel);
        $oMulticolumn = MultiColumnUIBlockFactory::MakeStandard('selected_classes');
        $oPanel->AddSubBlock($oMulticolumn);
        $oMulticolumn->AddCSSClass('ibo-multi-column--export');
        $oColumn1 = ColumnUIBlockFactory::MakeStandard();
        $oMulticolumn->AddColumn($oColumn1);
        $oColumn2 = ColumnUIBlockFactory::MakeStandard();
        $oMulticolumn->AddColumn($oColumn2);
        foreach ($aSelectedClasses as $i => $sClass) {
            $oBlock = FieldUIBlockFactory::MakeStandard(MetaModel::GetName($sClass)) ;
            $oValue = SelectUIBlockFactory::MakeForSelect($sClass);
            $oValue->AddOption(SelectOptionUIBlockFactory::MakeForSelectOption('standard', Dict::S('UI:Export:Class:Standard'), true));
            $oValue->AddOption(SelectOptionUIBlockFactory::MakeForSelectOption('user', Dict::S('UI:Export:Class:User'), false));
            $oValue->AddOption(SelectOptionUIBlockFactory::MakeForSelectOption('custom', Dict::S('UI:Export:Class:Custom'), false));
            $oBlock->AddSubBlock($oValue);
            if ($i%2 == 0) {
                $oColumn1->AddSubBlock($oBlock);
            } else {
                $oColumn2->AddSubBlock($oBlock);
            }
        }

        $oP->add('</div>');


        return $oP;
    }

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///
///
/// /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   /* function DisplayForm(WebPage $oP, $sAction = '', $sExpression = '', $sFormat = null)
    {
        $oExportSearch = null;
        $oP->add_script(DateTimeFormat::GetJSSQLToCustomFormat());
        $sJSDefaultDateTimeFormat = json_encode((string)AttributeDateTime::GetFormat());
        $oP->add_script(
            <<<EOF
function FormatDatesInPreview(sRadioSelector, sPreviewSelector)
{
	if ($('#'+sRadioSelector+'_date_time_format_default').prop('checked'))
	{
		sPHPFormat = $sJSDefaultDateTimeFormat;
	}
	else
	{
		sPHPFormat = $('#'+sRadioSelector+'_custom_date_time_format').val();
	}
	$('#interactive_fields_'+sPreviewSelector+' .user-formatted-date-time').each(function() {
		var val = $(this).attr('data-date');
		var sDisplay = DateTimeFormatFromPHP(val, sPHPFormat);
		$(this).html(sDisplay);
	});
	$('#interactive_fields_'+sPreviewSelector+' .user-formatted-date').each(function() {
		var val = $(this).attr('data-date');
		var sDisplay = DateFormatFromPHP(val, sPHPFormat);
		$(this).html(sDisplay);
	});
}
EOF
        );
        $oP->LinkScriptFromAppRoot('js/tabularfieldsselector.js');
        $oP->LinkScriptFromAppRoot('js/jquery.dragtable.js');
        $oP->LinkStylesheetFromAppRoot('css/dragtable.css');

  /*      $oForm = FormUIBlockFactory::MakeStandard("export-form");
        $oForm->SetAction($sAction);
        $oForm->AddDataAttribute("state", "not-yet-started");
        $oP->AddSubBlock($oForm);*

        $bExpressionIsValid = true;
        $sExpressionError = '';
        if ($sExpression === null)  {
            $bExpressionIsValid = false;
        } else if ($sExpression !== '') {
            try {
                $oExportSearch = DBObjectSearch::FromOQL($sExpression);
                $oExportSearch->UpdateContextFromUser();
            }
            catch (OQLException $e) {
                $bExpressionIsValid = false;
                $sExpressionError = $e->getMessage();
            }
        }

        if (!$bExpressionIsValid) {
            DisplayExpressionForm($oP, $sAction, $sExpression, $sExpressionError,$oForm);

            return;
        }


        $oForm->AddSubBlock(InputUIBlockFactory::MakeForHidden("expression", $sExpression));
        $oExportSearch = DBObjectSearch::FromOQL($sExpression);
        $oExportSearch->UpdateContextFromUser();

        $aFormPartsByFormat = array();
        $aAllFormParts = array();

        // One specific format was chosen
        $oSelect = InputUIBlockFactory::MakeForHidden("format", utils::EscapeHtml($sFormat));
        $oForm->AddSubBlock($oSelect);

     /*   $oExporter = BulkExport::FindExporter($sFormat, $oExportSearch);
        $aParts = $oExporter->EnumFormParts();
        foreach ($aParts as $sPartId => $void) {
            $aAllFormParts[$sPartId] = $oExporter;
        }
        $aFormPartsByFormat[$sFormat] = array_keys($aAllFormParts);

        foreach ($aAllFormParts as $sPartId => $oExport) {
            $UIContentBlock = UIContentBlockUIBlockFactory::MakeStandard('form_part_'.$sPartId)->AddCSSClass('form_part');
            $oForm->AddSubBlock($UIContentBlock);
            $UIContentBlock->AddSubBlock($oExport->GetFormPart($oP, $sPartId));
        }*
        //end of form
        $oBlockExport = UIContentBlockUIBlockFactory::MakeStandard("export-feedback")->SetIsHidden(true);
        $oBlockExport->AddSubBlock(new Html('<p class="export-message" style="text-align:center;">'.Dict::S('ExcelExport:PreparingExport').'</p>'));
        $oBlockExport->AddSubBlock(new Html('<div class="export-progress-bar" style="max-width:30em; margin-left:auto;margin-right:auto;"><div class="export-progress-message" style="text-align:center;"></div></div>'));
        $oP->AddSubBlock($oBlockExport);
        if ($sFormat == null) {//if it's global export
            $oP->AddSubBlock(ButtonUIBlockFactory::MakeForPrimaryAction('export', Dict::S('UI:Button:Export'), 'export', false, 'export-btn'));
        }
        $oBlockResult = UIContentBlockUIBlockFactory::MakeStandard("export_text_result")->SetIsHidden(true);
        $oBlockResult->AddSubBlock(new Html(Dict::S('Core:BulkExport:ExportResult')));

        $oTextArea = new TextArea('export_content', '', 'export_content');
        $oTextArea->AddCSSClass('ibo-input-text--export');
        $oBlockResult->AddSubBlock($oTextArea);
        $oP->AddSubBlock($oBlockResult);

        $sJSParts = json_encode($aFormPartsByFormat);
        $oP->add_ready_script(
            <<<EOF
window.aFormParts = $sJSParts;
$('#format_selector').on('change init', function() {
	ExportToggleFormat($(this).val());
}).trigger('init');
		
$('.export-progress-bar').progressbar({
	 value: 0,
	 change: function() {
		$('.export-progress-message').text( $(this).progressbar( "value" ) + "%" );
	 },
	 complete: function() {
		 $('.export-progress-message').text( '100 %' );
	 }
});

ExportInitButton('#export-btn');

EOF
        );

    }
/*_export_dlg: function(sTitle, sSubmitUrl, sOperation)
			{
				var sId = this.element.attr('id');
				var me = this;
				var oPositions = {};
				for(k in this.aNodes)
				{
					oPositions[this.aNodes[k].id] = {x: this.aNodes[k].x, y: this.aNodes[k].y };
				}
				var sHtmlForm = '<div id="GraphExportDlg'+this.element.attr('id')+'"><form id="graph_'+this.element.attr('id')+'_export_dlg" target="_blank" action="'+sSubmitUrl+'" method="post">';
				sHtmlForm += '<input type="hidden" name="g" value="'+this.options.grouping_threshold+'">';
				sHtmlForm += '<input type="hidden" name="context_key" value="'+this.options.context_key+'">';
				sHtmlForm += '<input type="hidden" name="transaction_id" value="'+this.options.transaction_id+'">';
				$('#'+sId+'_contexts').multiselect('getChecked').each(function() {
					sHtmlForm += '<input type="hidden" name="contexts['+$(this).val()+']" value="'+me.options.additional_contexts[$(this).val()].oql+'">';
				});

				sHtmlForm += '<input type="hidden" name="positions" value="">';
				for(k in this.options.excluded_classes)
				{
					sHtmlForm += '<input type="hidden" name="excluded_classes[]" value="'+this.options.excluded_classes[k]+'">';
				}
				for(var k1 in this.options.sources)
				{
					for(var k2 in this.options.sources[k1])
					{
						sHtmlForm += '<input type="hidden" name="sources['+k1+'][]" value="'+this.options.sources[k1][k2]+'">';
					}
				}
				for(var k1 in this.options.excluded)
				{
					for(var k2 in this.options.excluded[k1])
					{
						sHtmlForm += '<input type="hidden" name="excluded['+k1+'][]" value="'+this.options.excluded[k1][k2]+'">';
					}
				}
				if (sOperation == 'attachment')
				{
					sHtmlForm += '<input type="hidden" name="obj_class" value="'+this.options.export_as_attachment.obj_class+'">';
					sHtmlForm += '<input type="hidden" name="obj_key" value="'+this.options.export_as_attachment.obj_key+'">';
				}
				sHtmlForm += '<table>';
				sHtmlForm += '<tr><td>'+this.options.page_format.label+'</td><td><select name="p">';
				for(k in this.options.page_format.values)
				{
					var sSelected = (k == this.options.page_format['default']) ? ' selected' : '';
					sHtmlForm += '<option value="'+k+'"'+sSelected+'>'+this.options.page_format.values[k]+'</option>';
				}
				sHtmlForm += '</select></td></tr>';
				sHtmlForm += '<tr><td>'+this.options.page_orientation.label+'</td><td><select name="o">';
				for(k in this.options.page_orientation.values)
				{
					var sSelected = (k == this.options.page_orientation['default']) ? ' selected' : '';
					sHtmlForm += '<option value="'+k+'"'+sSelected+'>'+this.options.page_orientation.values[k]+'</option>';
				}
				sHtmlForm += '</select></td></tr>';
				sHtmlForm += '<tr><td>'+this.options.labels.title+'</td><td><input name="title" value="'+this.options.labels.untitled+'" style="width: 20em;"/></td></tr>';
				sHtmlForm += '<tr><td>'+this.options.labels.comments+'</td><td><textarea style="width: 20em; height:5em;" name="comments"/></textarea></td></tr>';
				sHtmlForm += '<tr><td colspan=2><input type="checkbox" checked id="include_list_checkbox" name="include_list" value="1"><label for="include_list_checkbox">&nbsp;'+this.options.labels.include_list+'</label></td></tr>';
				sHtmlForm += '<table>';
				sHtmlForm += '</form></div>';

				$('body').append(sHtmlForm);
				$('#graph_'+this.element.attr('id')+'_export_dlg input[name="positions"]').val(JSON.stringify(oPositions));
				var me = this;
				if (sOperation == 'attachment')
				{
					$('#GraphExportDlg'+this.element.attr('id')+' form').on('submit', function() { return me._on_export_as_attachment(); });
				}
				$('#GraphExportDlg'+this.element.attr('id')).dialog({
					width: 'auto',
					modal: true,
					title: sTitle,
					close: function() { $(this).remove(); },
					buttons: [
						{text: this.options.labels['cancel'], click: function() { $(this).dialog('close');} },
						{text: this.options.labels['export'], click: function() { $('#graph_'+me.element.attr('id')+'_export_dlg').submit(); $(this).dialog('close');} },
					]
				});
			},
*/
    /*
    private function SelectColumns($sOQL): array
    {
        $sWidgetId = 'tabular_fields_selector';
        $oSearch = DBObjectSearch::FromOQL($sOQL);
        $oSet = new DBObjectSet($oSearch);
        $aSelectedClasses = $oSearch->GetSelectedClasses();
        $aAuthorizedClasses = array();
        foreach($aSelectedClasses as $sAlias => $sClassName)
        {
            if (UserRights::IsActionAllowed($sClassName, UR_ACTION_BULK_READ, $oSet) != UR_ALLOWED_NO)
            {
                $aAuthorizedClasses[$sAlias] = $sClassName;
            }
        }
        $aAllFieldsByAlias = array();
        $aAllAttCodes = array();
        foreach($aAuthorizedClasses as $sAlias => $sClass)
        {
            $aAllFields = array();
            if (count($aAuthorizedClasses) > 1 )
            {
                $sShortAlias = $sAlias.'.';
            }
            else
            {
                $sShortAlias = '';
            }
            if ($this->IsExportableField($sClass, 'id'))
            {
                $sFriendlyNameAttCode = MetaModel::GetFriendlyNameAttributeCode($sClass);
                if (is_null($sFriendlyNameAttCode))
                {
                    // The friendly name is made of several attribute
                    $aSubAttr = array(
                        array('attcodeex' => 'id', 'code' => $sShortAlias.'id', 'unique_label' => $sShortAlias.Dict::S('UI:CSVImport:idField'), 'label' => $sShortAlias.'id'),
                        array('attcodeex' => 'friendlyname', 'code' => $sShortAlias.'friendlyname', 'unique_label' => $sShortAlias.Dict::S('Core:FriendlyName-Label'), 'label' => $sShortAlias.Dict::S('Core:FriendlyName-Label')),
                    );
                }
                else
                {
                    // The friendly name has no added value
                    $aSubAttr = array();
                }
                $aAllFields[] = array('attcodeex' => 'id', 'code' => $sShortAlias.'id', 'unique_label' => $sShortAlias.Dict::S('UI:CSVImport:idField'), 'label' => Dict::S('UI:CSVImport:idField'), 'subattr' => $aSubAttr);
            }
            foreach(MetaModel::ListAttributeDefs($sClass) as $sAttCode => $oAttDef)
            {
                if($this->IsSubAttribute($sClass, $sAttCode, $oAttDef)) continue;

                if ($this->IsExportableField($sClass, $sAttCode, $oAttDef))
                {
                    $sShortLabel = $oAttDef->GetLabel();
                    $sLabel = $sShortAlias.$oAttDef->GetLabel();
                    $aSubAttr = $this->GetSubAttributes($sClass, $sAttCode, $oAttDef);
                    $aValidSubAttr = array();
                    foreach($aSubAttr as $aSubAttDef)
                    {
                        $aValidSubAttr[] = array('attcodeex' => $aSubAttDef['code'], 'code' => $sShortAlias.$aSubAttDef['code'], 'label' => $aSubAttDef['label'], 'unique_label' => $sShortAlias.$aSubAttDef['unique_label']);
                    }
                    $aAllFields[] = array('attcodeex' => $sAttCode, 'code' => $sShortAlias.$sAttCode, 'label' => $sShortLabel, 'unique_label' => $sLabel, 'subattr' => $aValidSubAttr);
                }
            }
            usort($aAllFields,  array(get_class($this), 'SortOnLabel'));
            if (count($aAuthorizedClasses) > 1)
            {
                $sKey = MetaModel::GetName($sClass).' ('.$sAlias.')';
            }
            else
            {
                $sKey = MetaModel::GetName($sClass);
            }
            $aAllFieldsByAlias[$sKey] = $aAllFields;

            foreach ($aAllFields as $aFieldSpec)
            {
                $sAttCode = $aFieldSpec['attcodeex'];
                if (count($aFieldSpec['subattr']) > 0)
                {
                    foreach ($aFieldSpec['subattr'] as $aSubFieldSpec)
                    {
                        $aAllAttCodes[$sAlias][] = $aSubFieldSpec['attcodeex'];
                    }
                }
                else
                {
                    $aAllAttCodes[$sAlias][] = $sAttCode;
                }
            }
        }

        $JSAllFields = json_encode($aAllFieldsByAlias);

        // First, fetch only the ids - the rest will be fetched by an object reload
        $oSet = new DBObjectSet($oSearch);
        $iCount = $oSet->Count();

        foreach ($oSearch->GetSelectedClasses() as $sAlias => $sClass)
        {
            $aColumns[$sAlias] = array();
        }
        $oSet->OptimizeColumnLoad($aColumns);
        $iPreviewLimit = 3;
        $oSet->SetLimit($iPreviewLimit);
        $aSampleData = array();
        while($aRow = $oSet->FetchAssoc())
        {
            $aSampleRow = array();
            foreach($aAuthorizedClasses as $sAlias => $sClass)
            {
                if (count($aAuthorizedClasses) > 1) {
                    $sShortAlias = $sAlias.'.';
                } else {
                    $sShortAlias = '';
                }
                if (isset($aAllAttCodes[$sAlias])) {
                    foreach ($aAllAttCodes[$sAlias] as $sAttCodeEx) {
                        $oObj = $aRow[$sAlias];
                        $aSampleRow[$sShortAlias.$sAttCodeEx] = $oObj ? $this->GetSampleData($oObj, $sAttCodeEx) : '';
                    }
                }
            }
            $aSampleData[] = $aSampleRow;
        }
        $sJSSampleData = json_encode($aSampleData);
        $aLabels = array(
            'preview_header' => Dict::S('Core:BulkExport:DragAndDropHelp'),
            'empty_preview' => Dict::S('Core:BulkExport:EmptyPreview'),
            'columns_order' => Dict::S('Core:BulkExport:ColumnsOrder'),
            'columns_selection' => Dict::S('Core:BulkExport:AvailableColumnsFrom_Class'),
            'check_all' => Dict::S('Core:BulkExport:CheckAll'),
            'uncheck_all' => Dict::S('Core:BulkExport:UncheckAll'),
            'no_field_selected' => Dict::S('Core:BulkExport:NoFieldSelected'),
        );
        $sJSLabels = json_encode($aLabels);
        $oP->add_ready_script(
            <<<EOF
$('#$sWidgetId').tabularfieldsselector({fields: $JSAllFields, value_holder: '#tabular_fields', advanced_holder: '#tabular_advanced', sample_data: $sJSSampleData, total_count: $iCount, preview_limit: $iPreviewLimit, labels: $sJSLabels });
EOF
        );
        $oUIContentBlock = UIContentBlockUIBlockFactory::MakeStandard($sWidgetId);
        $oUIContentBlock->AddCSSClass('ibo-tabularbulkexport');

        return $oUIContentBlock;
    }

    public static function operationGeneratePdf()
    {
            require_once(APPROOT.'core/simplegraph.class.inc.php');
            require_once(APPROOT.'core/relationgraph.class.inc.php');
            require_once(APPROOT.'core/displayablegraph.class.inc.php');
            $sRelation = utils::ReadParam('relation', 'impacts');
            $sDirection = utils::ReadParam('direction', 'down');

            $iGroupingThreshold = utils::ReadParam('g', 5, false, 'integer');
            $sPageFormat = utils::ReadParam('p', 'A4');
            $sPageOrientation = utils::ReadParam('o', 'L');
            $sTitle = utils::ReadParam('title', '', false, 'raw_data');
            $sPositions = utils::ReadParam('positions', null, false, 'raw_data');
            $aExcludedClasses = utils::ReadParam('excluded_classes', array(), false, 'raw_data');
            $bIncludeList = (bool)utils::ReadParam('include_list', false);
            $sComments = utils::ReadParam('comments', '', false, 'raw_data');
            $aContexts = utils::ReadParam('contexts', array(), false, 'raw_data');
            $sContextKey = utils::ReadParam('context_key', '', false, 'raw_data');
            $aPositions = null;
            if ($sPositions != null) {
                $aPositions = json_decode($sPositions, true);
            }

            // Get the list of source objects
            $aSources = utils::ReadParam('sources', array(), false, 'raw_data');
            $aSourceObjects = array();
            foreach ($aSources as $sClass => $aIDs) {
                $oSearch = new DBObjectSearch($sClass);
                $oSearch->AddCondition('id', $aIDs, 'IN');
                $oSet = new DBObjectSet($oSearch);
                while ($oObj = $oSet->Fetch()) {
                    $aSourceObjects[] = $oObj;
                }
            }
            $sSourceClass = '*';
            if (count($aSourceObjects) == 1) {
                $sSourceClass = get_class($aSourceObjects[0]);
            }

            // Get the list of excluded objects
            $aExcluded = utils::ReadParam('excluded', array(), false, 'raw_data');
            $aExcludedObjects = array();
            foreach ($aExcluded as $sClass => $aIDs) {
                $oSearch = new DBObjectSearch($sClass);
                $oSearch->AddCondition('id', $aIDs, 'IN');
                $oSet = new DBObjectSet($oSearch);
                while ($oObj = $oSet->Fetch()) {
                    $aExcludedObjects[] = $oObj;
                }
            }

            $iMaxRecursionDepth = MetaModel::GetConfig()->Get('relations_max_depth');
            if ($sDirection == 'up') {
                $oRelGraph = MetaModel::GetRelatedObjectsUp($sRelation, $aSourceObjects, $iMaxRecursionDepth, true, $aContexts);
            } else {
                $oRelGraph = MetaModel::GetRelatedObjectsDown($sRelation, $aSourceObjects, $iMaxRecursionDepth, true, $aExcludedObjects, $aContexts);
            }

            // Remove excluded classes from the graph
            if (count($aExcludedClasses) > 0) {
                $oIterator = new RelationTypeIterator($oRelGraph, 'Node');
                foreach ($oIterator as $oNode) {
                    $oObj = $oNode->GetProperty('object');
                    if ($oObj && in_array(get_class($oObj), $aExcludedClasses)) {
                        $oRelGraph->FilterNode($oNode);
                    }
                }
            }

            $oPage = new PDFPage($sTitle, $sPageFormat, $sPageOrientation);
            $oPage->SetContentDisposition('attachment', $sTitle.'.pdf');

            $oGraph = DisplayableGraph::FromRelationGraph($oRelGraph, $iGroupingThreshold, ($sDirection == 'down'), true);
            $oGraph->InitFromGraphviz();
            if ($aPositions != null) {
                $oGraph->UpdatePositions($aPositions);
            }

            $aGroups = array();
            $oIterator = new RelationTypeIterator($oGraph, 'Node');
            foreach ($oIterator as $oNode) {
                if ($oNode instanceof DisplayableGroupNode) {
                    $aGroups[$oNode->GetProperty('group_index')] = $oNode->GetObjects();
                }
            }
            // First page is the graph
            $oGraph->RenderAsPDF($oPage, $sComments, $sContextKey);

            if ($bIncludeList) {
                // Then the lists of objects (one table per finalclass)
                $aResults = array();
                $oIterator = new RelationTypeIterator($oRelGraph, 'Node');
                foreach ($oIterator as $oNode) {
                    $oObj = $oNode->GetProperty('object'); // Some nodes (Redundancy Nodes and Group) do not contain an object
                    if ($oObj) {
                        $sObjClass = get_class($oObj);
                        if (!array_key_exists($sObjClass, $aResults)) {
                            $aResults[$sObjClass] = array();
                        }
                        $aResults[$sObjClass][] = $oObj;
                    }
                }

                $oPage->get_tcpdf()->AddPage();
                $oPage->get_tcpdf()->SetFontSize(10); // Reset the font size to its default
                $oPage->AddSubBlock(TitleUIBlockFactory::MakeNeutral(Dict::S('UI:RelationshipList')));
                $iLoopTimeLimit = MetaModel::GetConfig()->Get('max_execution_time_per_loop');
                foreach ($aResults as $sListClass => $aObjects) {
                    set_time_limit($iLoopTimeLimit * count($aObjects));
                    $oSet = CMDBObjectSet::FromArray($sListClass, $aObjects);
                    $oSet->SetShowObsoleteData(utils::ShowObsoleteData());
                    $oTitle = new Html(Dict::Format('UI:Search:Count_ObjectsOf_Class_Found', $oSet->Count(), Metamodel::GetName($sListClass)));
                    $oPage->AddSubBlock(TitleUIBlockFactory::MakeStandard($oTitle, 2));
                    $oPage->AddSubBlock(cmdbAbstractObject::GetDataTableFromDBObjectSet($oSet, array('table_id' => $sSourceClass.'_'.$sRelation.'_'.$sDirection.'_'.$sListClass)));
                }

                // Then the content of the groups (one table per group)
                if (count($aGroups) > 0) {
                    $oPage->get_tcpdf()->AddPage();
                    $oPage->AddSubBlock(TitleUIBlockFactory::MakeNeutral(Dict::S('UI:RelationGroups')));
                    foreach ($aGroups as $idx => $aObjects) {
                        set_time_limit($iLoopTimeLimit * count($aObjects));
                        $sListClass = get_class(current($aObjects));
                        $oSet = CMDBObjectSet::FromArray($sListClass, $aObjects);
                        $sIconUrl = MetaModel::GetClassIcon($sListClass, false);
                        $sIconUrl = str_replace(utils::GetAbsoluteUrlModulesRoot(), APPROOT.'env-'.utils::GetCurrentEnvironment().'/', $sIconUrl);
                        $oTitle = new Html("<img src=\"$sIconUrl\" style=\"vertical-align:middle;width: 24px; height: 24px;\"/> ".Dict::Format('UI:RelationGroupNumber_N', (1 + $idx)), Metamodel::GetName($sListClass));
                        $oPage->AddSubBlock(TitleUIBlockFactory::MakeStandard($oTitle, 2));
                        $oPage->AddSubBlock(cmdbAbstractObject::GetDataTableFromDBObjectSet($oSet));

                    }
                }
            }
    }*/
    public static function OperationSelectColumns()
    {
		//select all the fields for the 'customs' classes

	    $sJSTitle = json_encode(utils::EscapeHtml(utils::ReadParam('dialog_title', '', false, 'raw_data')));
	    $oP = new AjaxPage($sJSTitle);
        $sFormat = utils::ReadParam('format', '');
        $oForm = self::GetFormWithHiddenParams($sFormat, $oP);

        $oExporter = BulkExport::FindExporter($sFormat);
        if ($oExporter === null) {
            $aSupportedFormats = BulkExport::FindSupportedFormats();
            $oP->add("Invalid output format: '$sFormat'. The supported formats are: ".implode(', ', array_keys($aSupportedFormats)));
            $oP->add('</div>');
            return $oP;
        }
        $oExporter->ReadParameters();
        foreach ($oExporter->GetStatusInfo() as $sKey => $sValue) {
            $oForm->AddSubBlock(InputUIBlockFactory::MakeForHidden($sKey, $sValue));
        }


        $sWidgetId = 'tabular_fields_selector';
	    $aClassesChoice = [];
        $aClasses = utils::ReadParam('list_classes', '', false, utils::ENUM_SANITIZATION_FILTER_CLASS);
        foreach($aClasses as $sClassName)
        {
	        $sSelection = utils::ReadParam($sClassName, '', false, utils::ENUM_SANITIZATION_FILTER_STRING);
	        $aClassesChoice[$sClassName] = $sSelection;
        }

        $aAllFieldsByAlias = array();
        $aAllAttCodes = array();
       /* foreach($aAuthorizedClasses as $sAlias => $sClass)
        {
            $aAllFields = array();
            if (count($aAuthorizedClasses) > 1 )
            {
                $sShortAlias = $sAlias.'.';
            }
            else
            {
                $sShortAlias = '';
            }
            if ($this->IsExportableField($sClass, 'id'))
            {
                $sFriendlyNameAttCode = MetaModel::GetFriendlyNameAttributeCode($sClass);
                if (is_null($sFriendlyNameAttCode))
                {
                    // The friendly name is made of several attribute
                    $aSubAttr = array(
                        array('attcodeex' => 'id', 'code' => $sShortAlias.'id', 'unique_label' => $sShortAlias.Dict::S('UI:CSVImport:idField'), 'label' => $sShortAlias.'id'),
                        array('attcodeex' => 'friendlyname', 'code' => $sShortAlias.'friendlyname', 'unique_label' => $sShortAlias.Dict::S('Core:FriendlyName-Label'), 'label' => $sShortAlias.Dict::S('Core:FriendlyName-Label')),
                    );
                }
                else
                {
                    // The friendly name has no added value
                    $aSubAttr = array();
                }
                $aAllFields[] = array('attcodeex' => 'id', 'code' => $sShortAlias.'id', 'unique_label' => $sShortAlias.Dict::S('UI:CSVImport:idField'), 'label' => Dict::S('UI:CSVImport:idField'), 'subattr' => $aSubAttr);
            }
            foreach(MetaModel::ListAttributeDefs($sClass) as $sAttCode => $oAttDef)
            {
                if($this->IsSubAttribute($sClass, $sAttCode, $oAttDef)) continue;

                if ($this->IsExportableField($sClass, $sAttCode, $oAttDef))
                {
                    $sShortLabel = $oAttDef->GetLabel();
                    $sLabel = $sShortAlias.$oAttDef->GetLabel();
                    $aSubAttr = $this->GetSubAttributes($sClass, $sAttCode, $oAttDef);
                    $aValidSubAttr = array();
                    foreach($aSubAttr as $aSubAttDef)
                    {
                        $aValidSubAttr[] = array('attcodeex' => $aSubAttDef['code'], 'code' => $sShortAlias.$aSubAttDef['code'], 'label' => $aSubAttDef['label'], 'unique_label' => $sShortAlias.$aSubAttDef['unique_label']);
                    }
                    $aAllFields[] = array('attcodeex' => $sAttCode, 'code' => $sShortAlias.$sAttCode, 'label' => $sShortLabel, 'unique_label' => $sLabel, 'subattr' => $aValidSubAttr);
                }
            }
            usort($aAllFields,  array(get_class($this), 'SortOnLabel'));
            if (count($aAuthorizedClasses) > 1)
            {
                $sKey = MetaModel::GetName($sClass).' ('.$sAlias.')';
            }
            else
            {
                $sKey = MetaModel::GetName($sClass);
            }
            $aAllFieldsByAlias[$sKey] = $aAllFields;

            foreach ($aAllFields as $aFieldSpec)
            {
                $sAttCode = $aFieldSpec['attcodeex'];
                if (count($aFieldSpec['subattr']) > 0)
                {
                    foreach ($aFieldSpec['subattr'] as $aSubFieldSpec)
                    {
                        $aAllAttCodes[$sAlias][] = $aSubFieldSpec['attcodeex'];
                    }
                }
                else
                {
                    $aAllAttCodes[$sAlias][] = $sAttCode;
                }
            }
        }

        $JSAllFields = json_encode($aAllFieldsByAlias);

        // First, fetch only the ids - the rest will be fetched by an object reload
        $oSearch = new DBObjectSearch($sSelectedClass);
        $oSet = new CMDBObjectSet($oSearch);
        $iCount = $oSet->Count();

        foreach ($oSearch->GetSelectedClasses() as $sAlias => $sClass)
        {
            $aColumns[$sAlias] = array();
        }
        $oSet->OptimizeColumnLoad($aColumns);
        $iPreviewLimit = 3;
        $oSet->SetLimit($iPreviewLimit);
        $aSampleData = array();
        while($aRow = $oSet->FetchAssoc())
        {
            $aSampleRow = array();
            foreach($aAuthorizedClasses as $sAlias => $sClass)
            {
                if (count($aAuthorizedClasses) > 1) {
                    $sShortAlias = $sAlias.'.';
                } else {
                    $sShortAlias = '';
                }
                if (isset($aAllAttCodes[$sAlias])) {
                    foreach ($aAllAttCodes[$sAlias] as $sAttCodeEx) {
                        $oObj = $aRow[$sAlias];
                        $aSampleRow[$sShortAlias.$sAttCodeEx] = $oObj ? $this->GetSampleData($oObj, $sAttCodeEx) : '';
                    }
                }
            }
            $aSampleData[] = $aSampleRow;
        }
        $sJSSampleData = json_encode($aSampleData);
        $aLabels = array(
            'preview_header' => Dict::S('Core:BulkExport:DragAndDropHelp'),
            'empty_preview' => Dict::S('Core:BulkExport:EmptyPreview'),
            'columns_order' => Dict::S('Core:BulkExport:ColumnsOrder'),
            'columns_selection' => Dict::S('Core:BulkExport:AvailableColumnsFrom_Class'),
            'check_all' => Dict::S('Core:BulkExport:CheckAll'),
            'uncheck_all' => Dict::S('Core:BulkExport:UncheckAll'),
            'no_field_selected' => Dict::S('Core:BulkExport:NoFieldSelected'),
        );
        $sJSLabels = json_encode($aLabels);
        $oP->add_ready_script(
            <<<EOF
$('#$sWidgetId').tabularfieldsselector({fields: $JSAllFields, value_holder: '#tabular_fields', advanced_holder: '#tabular_advanced', sample_data: $sJSSampleData, total_count: $iCount, preview_limit: $iPreviewLimit, labels: $sJSLabels });
EOF
        );
        $oUIContentBlock = UIContentBlockUIBlockFactory::MakeStandard($sWidgetId);
        $oUIContentBlock->AddCSSClass('ibo-tabularbulkexport');
*/
        return $oP;
    }

    /**
     * @param mixed $sFormat
     * @param AjaxPage $oP
     * @return Form
     * @throws Exception
     */
    public static function GetFormWithHiddenParams(mixed $sFormat, AjaxPage $oP): Form
    {
        $oForm = FormUIBlockFactory::MakeStandard("export-form");
        // $oForm->SetAction(utils::GetAbsoluteUrlAppRoot().'webservices/export-v2.php');
        $oForm->SetAction(Router::GetInstance()->GenerateUrl('export.select_columns', ['format' => $sFormat]));
        $oForm->AddDataAttribute("state", "not-yet-started");
        $oP->AddSubBlock($oForm);
        $oForm->AddSubBlock(InputUIBlockFactory::MakeForHidden('format', $sFormat));

        //Add params coming from the screen
        $iTransactionId = isset($aExtraParams['transaction_id']) ? $aExtraParams['transaction_id'] : utils::GetNewTransactionId();
        $oP->SetTransactionId($iTransactionId);
        $oForm->AddSubBlock(InputUIBlockFactory::MakeForHidden('transaction_id', $iTransactionId));

        $oForm->AddSubBlock(InputUIBlockFactory::MakeForHidden('context_key', utils::ReadParam('context_key', '', false, 'raw_data')));
        $oForm->AddSubBlock(InputUIBlockFactory::MakeForHidden('g', utils::ReadParam('g', '')));
        $aContexts = utils::ReadParam('contexts', '');
        foreach ($aContexts as $sContext) {
            $oForm->AddSubBlock(InputUIBlockFactory::MakeForHidden('contexts', $sContext));
        }
        $aExcludedClasses = utils::ReadParam('excluded_classes', '');
        foreach ($aExcludedClasses as $sExcludedClass) {
            $oForm->AddSubBlock(InputUIBlockFactory::MakeForHidden('excluded_classes', $sExcludedClass));
        }
        $aSources = utils::ReadParam('sources', '');
        foreach ($aSources as $sKey => $aSource) {
            foreach ($aSource as $sSource) {
                $oForm->AddSubBlock(InputUIBlockFactory::MakeForHidden('sources[' . $sKey . ']', $sSource));
            }
        }
        $aExcludeds = utils::ReadParam('excluded', '');
        foreach ($aExcludeds as $sKey => $aExcluded) {
            foreach ($aExcluded as $sExcluded) {
                $oForm->AddSubBlock(InputUIBlockFactory::MakeForHidden('excluded[' . $sKey . ']', $sExcluded));
            }
        }
        $aSelectedClasses = utils::ReadParam('list_classes', '', false, utils::ENUM_SANITIZATION_FILTER_RAW_DATA);
        foreach ($aSelectedClasses as $sSelectedClass) {
            $oForm->AddSubBlock(InputUIBlockFactory::MakeForHidden('list_classes', $sSelectedClass));
        }
        return $oForm;
    }
}