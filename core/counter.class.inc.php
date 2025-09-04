<?php
/**
 * Copyright (C) 2013-2024 Combodo SAS
 *
 * This file is part of iTop.
 *
 * iTop is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * iTop is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 */


/**
 * Persistent classes for a CMDB
 *
 * @copyright   Copyright (C) 2010-2024 Combodo SAS
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

class KeyValueStore extends DBObject
{
	public static function Init()
	{
		$aParams = array(
			'category' => '',
			'key_type' => 'autoincrement',
			'name_attcode' => array('key_name'),
			'state_attcode' => '',
			'reconc_keys' => array(''),
			'db_table' => 'key_value_store',
			'db_key_field' => 'id',
			'db_finalclass_field' => '',
			'indexes' => array (
				array (
					0 => 'key_name',
					1 => 'namespace',
				),
			),);
		MetaModel::Init_Params($aParams);
		MetaModel::Init_InheritAttributes();
		MetaModel::Init_AddAttribute(new AttributeString("namespace", array("allowed_values"=>null, "sql"=>'namespace', "default_value"=>null, "is_null_allowed"=>true, "depends_on"=>array(), "always_load_in_tables"=>false)));
		MetaModel::Init_AddAttribute(new AttributeString("key_name", array("allowed_values"=>null, "sql"=>'key_name', "default_value"=>'', "is_null_allowed"=>false, "depends_on"=>array(), "always_load_in_tables"=>false)));
		MetaModel::Init_AddAttribute(new AttributeString("value", array("allowed_values"=>null, "sql"=>'value', "default_value"=>'0', "is_null_allowed"=>false, "depends_on"=>array(), "always_load_in_tables"=>false)));

		MetaModel::Init_SetZListItems('details', array (
			0 => 'key_name',
			1 => 'value',
			2 => 'namespace',
		));
		MetaModel::Init_SetZListItems('standard_search', array (
			0 => 'key_name',
			1 => 'value',
			2 => 'namespace',
		));
		MetaModel::Init_SetZListItems('list', array (
			0 => 'key_name',
			1 => 'value',
			2 => 'namespace',
		));
		;
	}


}