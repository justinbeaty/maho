<?php

/**
 * Maho
 *
 * @package    Varien_Convert
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Varien_Convert_Adapter_Soap extends Varien_Convert_Adapter_Abstract
{
    #[\Override]
    public function load()
    {
        return $this;
    }

    #[\Override]
    public function save()
    {
        return $this;
    }
}
