<?php

/**
 * Maho
 *
 * @package    Mage_Core
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Core_Model_File_Validator_StreamWrapper
{
    /**
     * Allowed stream wrappers
     *
     * @var array
     */
    protected $_allowedStreamWrappers = [];

    /**
     * Mage_Core_Model_File_Validator_StreamWrapper constructor.
     *
     * @param array $allowedStreamWrappers
     */
    public function __construct($allowedStreamWrappers = [])
    {
        $this->_allowedStreamWrappers = $allowedStreamWrappers;
    }

    /**
     * Validation callback for checking is stream wrapper allowed
     *
     * @param  string $filePath Path to file
     * @return bool
     */
    public function validate($filePath)
    {
        if (($pos = strpos($filePath, '://')) > 0) {
            $wrapper = substr($filePath, 0, $pos);
            if (!in_array($wrapper, $this->_allowedStreamWrappers)) {
                return false;
            }
        }
        return true;
    }
}
