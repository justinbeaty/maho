<?php

/**
 * Maho
 *
 * @package    Mage_Core
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2020-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Core_Model_Resource_Type_Db_Mysqli extends Mage_Core_Model_Resource_Type_Db
{
    /**
     * Get Connection
     *
     * @param array $config
     * @return Varien_Db_Adapter_Mysqli
     */
    public function getConnection($config)
    {
        $configArr = (array) $config;
        $configArr['profiler'] = !empty($configArr['profiler']) && $configArr['profiler'] !== 'false';

        $conn = new Varien_Db_Adapter_Mysqli($configArr);

        if (!empty($configArr['initStatements']) && $conn) {
            $conn->query($configArr['initStatements']);
        }

        return $conn;
    }
}
