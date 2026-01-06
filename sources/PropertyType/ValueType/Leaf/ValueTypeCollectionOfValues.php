<?php

/*
 * @copyright   Copyright (C) 2010-2026 Combodo SAS
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\PropertyType\ValueType\Leaf;

use Combodo\iTop\DesignElement;
use Combodo\iTop\Forms\Block\Base\ChoiceFormBlock;
use Combodo\iTop\Forms\Block\Base\FormBlock;
use Combodo\iTop\PropertyType\ValueType\Branch\AbstractBranchValueType;
use Combodo\iTop\PropertyType\ValueType\Leaf\AbstractLeafValueType;
use Combodo\iTop\PropertyType\ValueType\ValueTypeFactory;

class ValueTypeCollectionOfValues extends AbstractLeafValueType
{
	private string $sFormBlockClass;

	public function GetFormBlockClass(): string
	{
		return $this->sFormBlockClass;
	}

	public function InitFromDomNode(DesignElement $oDomNode, ?AbstractBranchValueType $oParent = null): void
	{
		$oNode = $oDomNode->GetUniqueElement('value-type');
		$oRealValueType = ValueTypeFactory::GetInstance()->CreateValueTypeFromDomNode($oNode, $oParent);
		$this->sFormBlockClass = $oRealValueType->getFormBlockClass();

		if (is_a($this->sFormBlockClass, ChoiceFormBlock::class, true)) {
			$this->aFormBlockOptionsForPHP['multiple'] = 'true';
		}

		parent::InitFromDomNode($oDomNode, $oParent);
	}
}
