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

/**
 * Convert dry run validator
 *
 * Insert where you want to step profile execution if dry run flag is set
 *
 * @package    Varien_Convert
 */
class Varien_Convert_Validator_Dryrun extends Varien_Convert_Validator_Abstract
{
    #[\Override]
    public function validate()
    {
        if ($this->getVar('dry_run') || $this->getProfile()->getDryRun()) {
            $this->addException('Dry run set, stopping execution', Varien_Convert_Exception::FATAL);
        }
        return $this;
    }
}
