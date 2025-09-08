<?php
/*
 * @copyright   Copyright (C) 2010-2024 Combodo SAS
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

use Combodo\iTop\Application\UI\Base\Component\FieldBadge\FieldBadgeUIBlockFactory;
use Combodo\iTop\Application\UI\Links\Set\BlockLinkSetDisplayAsProperty;
use Combodo\iTop\Application\WebPage\WebPage;
use Combodo\iTop\Form\Field\LabelField;
use Combodo\iTop\Form\Field\TextAreaField;
use Combodo\iTop\Form\Form;
use Combodo\iTop\Form\Validator\CustomRegexpValidator;
use Combodo\iTop\Renderer\BlockRenderer;
use Combodo\iTop\Renderer\Console\ConsoleBlockRenderer;
use Combodo\iTop\Service\Links\LinkSetModel;

require_once('MyHelpers.class.inc.php');
require_once('htmlsanitizer.class.inc.php');
require_once('customfieldshandler.class.inc.php');
require_once('datetimeformat.class.inc.php');

require_once(APPROOT.'/sources/Core/Orm/ormDocument.php');
require_once(APPROOT.'/sources/Core/Orm/ormStopWatch.php');
require_once(APPROOT.'/sources/Core/Orm/ormPassword.php');
require_once(APPROOT.'/sources/Core/Orm/ormCaseLog.php');
require_once(APPROOT.'/sources/Core/Orm/ormLinkSet.php');
require_once(APPROOT.'/sources/Core/Orm/ormSet.php');
require_once(APPROOT.'/sources/Core/Orm/ormTagSet.php');
require_once(APPROOT.'/sources/Core/Orm/ormCustomFieldsValue.php');


require_once(APPROOT.'/sources/Core/AttributeDefinition/MissingColumnException.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/iAttributeNoGroupBy.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeDefinition.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeDashboard.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeLinkedSet.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeLinkedSetIndirect.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeDBFieldVoid.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeDBField.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeInteger.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeObjectKey.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributePercentage.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeDecimal.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeBoolean.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeString.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeClass.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeClassState.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeApplicationLanguage.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeFinalClass.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributePassword.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeEncryptedString.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeText.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeLongText.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeCaseLog.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeHTML.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeEmailAddress.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeIPAddress.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributePhoneNumber.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeOQL.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeTemplateString.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeTemplateText.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeTemplateHTML.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeEnum.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeMetaEnum.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeDateTime.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeDuration.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeDate.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeDeadline.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeExternalKey.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeHierarchicalKey.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeExternalField.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeURL.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeBlob.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeImage.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeStopWatch.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeSubItem.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeOneWayPassword.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeTable.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributePropertySet.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeSet.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeEnumSet.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeClassAttCodeSet.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeQueryAttCodeSet.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeTagSet.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeFriendlyName.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeRedundancySettings.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeObsolescenceDate.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeCustomFields.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeArchiveFlag.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeArchiveDate.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeObsolescenceFlag.php');
require_once(APPROOT.'/sources/Core/AttributeDefinition/AttributeObsolescenceDate.php');



// Indexed array having two dimensions

// The PHP value is a hash array, it is stored as a TEXT column
