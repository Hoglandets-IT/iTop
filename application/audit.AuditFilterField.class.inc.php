<?php
// Copyright (C) 2010-2024 Combodo SAS
//
//   This file is part of iTop.
//
//   iTop is free software; you can redistribute it and/or modify	
//   it under the terms of the GNU Affero General Public License as published by
//   the Free Software Foundation, either version 3 of the License, or
//   (at your option) any later version.
//
//   iTop is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU Affero General Public License for more details.
//
//   You should have received a copy of the GNU Affero General Public License
//   along with iTop. If not, see <http://www.gnu.org/licenses/>
use Combodo\iTop\Application\UI\Base\Component\Alert\AlertUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Html\Html;


/**
 * This class allow to define placeholder to be used in the audit "rule" and audit "category" OQL queries.
 *
 * @copyright   Copyright (C) 2010-2025 Combodo SAS
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

class AuditFilterField extends cmdbAbstractObject
{
    public static function Init()
    {
        $aParams = array
        (
            "category"            => "application,grant_by_profile",
            "key_type"            => "autoincrement",
            "name_attcode"        => "placeholder",
            "state_attcode"       => "",
            "reconc_keys"         => array('placeholder'),
            "db_table"            => "priv_auditfilterfield",
            "db_key_field"        => "id",
            "db_finalclass_field" => "",
            'style'               => new ormStyle(null, null, null, null, null, '../images/icons/icons8-audit-filtre.svg'),
        );
        MetaModel::Init_Params($aParams);
        MetaModel::Init_AddAttribute(new AttributeString("placeholder", array("allowed_values" => null, "sql" => "placeholder", "default_value" => "", "is_null_allowed" => false, "depends_on" => array(), "validation_pattern"=>'^\w+$')));
        MetaModel::Init_AddAttribute(new AttributeString("label", array("allowed_values" => null, "sql" => "label", "default_value" => "", "is_null_allowed" => false, "depends_on" => array())));
        MetaModel::Init_AddAttribute(new AttributeOQL("oql", array("allowed_values" => null, "sql" => "oql", "default_value" => "", "is_null_allowed" => true, "depends_on" => array())));
        MetaModel::Init_AddAttribute(new AttributeString("values", array("allowed_values" => null, "sql" => "values", "default_value" => "", "is_null_allowed" => true, "depends_on" => array())));

        // Display lists
        MetaModel::Init_SetZListItems('details', array('label', 'placeholder', 'oql', 'values')); // Attributes to be displayed for the complete details
        MetaModel::Init_SetZListItems('list', array('label', 'placeholder', 'oql','values')); // Attributes to be displayed for a list
        // Search criteria
        MetaModel::Init_SetZListItems('standard_search', array('label', 'placeholder')); // Criteria of the std search form
        MetaModel::Init_SetZListItems('default_search', array('label', 'placeholder')); // Criteria of the advanced search form
    }
    /**
     * @param WebPage $oPage
     * @return void
     * @throws ConfigException
     * @throws CoreException
     */
    public static function DisplayListOfFields(WebPage $oPage): void
    {
        $sOQL = 'SELECT AuditFilterField';
        $oSearch = DBObjectSearch::FromOQL($sOQL);
        $oAuditFilterSet = new DBObjectSet($oSearch, array(), array());
        if ($oAuditFilterSet->Count()>0) {
            $sHtml = '<ul style="list-style: unset; padding-left: 20px;">';
            while ($oAuditFilter = $oAuditFilterSet->Fetch()) {
                $sHtml .= '<li><i>:' . $oAuditFilter->Get('placeholder') . '</i> for ' .  $oAuditFilter->Get('label') . '</li>';
            }
            $sHtml .= '</ul>';
            $oPage->AddUiBlock(AlertUIBlockFactory::MakeForInformation('In OQL query, you can use this placeholders:', '')->AddSubBlock(new Html($sHtml)));
        }
    }
}