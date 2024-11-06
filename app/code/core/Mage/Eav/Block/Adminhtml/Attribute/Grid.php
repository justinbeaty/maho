<?php
/**
 * Maho
 *
 * @category   Mage
 * @package    Mage_Eav
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * @category   Mage
 * @package    Mage_Eav
 */
class Mage_Eav_Block_Adminhtml_Attribute_Grid extends Mage_Eav_Block_Adminhtml_Attribute_Grid_Abstract
{
    /**
     * @inheritDoc
     */
    #[\Override]
    protected function _prepareCollection()
    {
        /** @var Mage_Eav_Model_Entity_Type $entityType */
        $entityType = Mage::registry('entity_type');

        $hiddenAttributes = Mage::helper('eav')->getHiddenAttributes($entityType->getEntityTypeCode());

        /** @var Mage_Eav_Model_Resource_Entity_Attribute_Collection $collection */
        $collection = Mage::getResourceModel($entityType->getEntityAttributeCollection());
        $collection->setEntityTypeFilter($entityType->getEntityTypeId())
            ->addVisibleFilter()
            ->setNotCodeFilter($hiddenAttributes);

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
}
