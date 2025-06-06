<?php

/**
 * Maho
 *
 * @package    Mage_CatalogIndex
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_CatalogIndex_Model_Catalog_Index_Flag extends Mage_Core_Model_Flag
{
    protected $_flagCode = 'catalogindex';

    public const STATE_QUEUED = 1;
    public const STATE_RUNNING = 2;

    /**
     * @return Mage_Core_Model_Flag
     */
    #[\Override]
    protected function _beforeSave()
    {
        switch ($this->getState()) {
            case self::STATE_QUEUED:
                $this->setFlagData($this->getQueueInfo());
                break;

            case self::STATE_RUNNING:
                $this->setFlagData(getmypid());
                break;

            default:
                break;
        }

        return parent::_beforeSave();
    }
}
