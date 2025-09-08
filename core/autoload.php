<?php
// Copyright (C) 2024 Combodo SAS
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

MetaModel::IncludeModule('application/transaction.class.inc.php');
MetaModel::IncludeModule('application/menunode.class.inc.php');
MetaModel::IncludeModule('application/user.preferences.class.inc.php');
MetaModel::IncludeModule('application/user.dashboard.class.inc.php');
MetaModel::IncludeModule('application/audit.rule.class.inc.php');
MetaModel::IncludeModule('application/audit.domain.class.inc.php');
MetaModel::IncludeModule('application/query.class.inc.php');
MetaModel::IncludeModule('setup/moduleinstallation.class.inc.php');


MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/Event/Event.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/Event/EventNotification.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/Event/EventNotificationEmail.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/Event/EventIssue.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/Event/EventWebService.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/Event/EventRestService.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/Event/EventLoginUsage.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/Event/EventOnObject.php');

MetaModel::IncludeModule(APPROOT.'/sources/Core/DataModel/AsyncTask/ExecAsyncTask.php');
MetaModel::IncludeModule(APPROOT.'/sources/Core/DataModel/AsyncTask/AsyncTask.php');
MetaModel::IncludeModule(APPROOT.'/sources/Core/DataModel/AsyncTask/AsyncSendEmail.php');
MetaModel::IncludeModule(APPROOT.'/sources/Core/DataModel/AsyncTask/ExecAsyncTask.php');
MetaModel::IncludeModule(APPROOT.'/sources/Core/DataModel/AsyncTask/AsyncSendNewsroom.php');

MetaModel::IncludeModule(APPROOT.'/core/email.class.inc.php');

MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/TriggerAndAction/Action.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/TriggerAndAction/ActionNotification.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/TriggerAndAction/ActionEmail.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/TriggerAndAction/lnkTriggerAction.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/TriggerAndAction/Trigger.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/TriggerAndAction/TriggerOnAttributeBlobDownload.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/TriggerAndAction/TriggerOnObject.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/TriggerAndAction/TriggerOnObjectCreate.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/TriggerAndAction/TriggerOnObjectDelete.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/TriggerAndAction/TriggerOnObjectMention.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/TriggerAndAction/TriggerOnObjectUpdate.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/TriggerAndAction/TriggerOnPortalUpdate.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/TriggerAndAction/TriggerOnStateChange.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/TriggerAndAction/TriggerOnStateEnter.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/TriggerAndAction/TriggerOnStateLeave.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/DataModel/TriggerAndAction/TriggerOnThresholdReached.php');

//MetaModel::IncludeModule('core/bulkexport.class.inc.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/BulkExport/BulkExport.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/BulkExport/BulkExportException.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/BulkExport/BulkExportMissingParameterException.php');
MetaModel::IncludeModule(APPROOT.'/sources/Application/BulkExport/BulkExportResultGC.php');

MetaModel::IncludeModule('core/ownershiplock.class.inc.php');
MetaModel::IncludeModule(APPROOT.'/sources/Core/DataModel/TagSetFieldData.php');
MetaModel::IncludeModule('synchro/synchrodatasource.class.inc.php');
MetaModel::IncludeModule(APPROOT.'/sources/Core/DataModel/BackgroundTask.php');
MetaModel::IncludeModule('core/inlineimage.class.inc.php');
//MetaModel::IncludeModule('core/counter.class.inc.php');
MetaModel::IncludeModule(APPROOT.'/sources/Core/DataModel/KeyValueStore.php');
MetaModel::IncludeModule(APPROOT.'/sources/Core/DataModel/TemporaryObjectDescriptor.php');

MetaModel::IncludeModule('webservices/webservices.basic.php');

//MetaModel::IncludeModule('addons', 'user rights', 'addons/userrights/userrightsprofile.class.inc.php');
