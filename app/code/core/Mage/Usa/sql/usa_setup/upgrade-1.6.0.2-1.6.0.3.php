<?php

/**
 * Maho
 *
 * @package    Mage_Usa
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/** @var Mage_Core_Model_Resource_Setup $this */

$days = Mage::app()->getLocale()->getTranslationList('days');
$days = array_keys($days['format']['wide']);
foreach ($days as $key => $value) {
    $days[$key] = ucfirst($value);
}

$select = $this->getConnection()
    ->select()
    ->from($this->getTable('core/config_data'), ['config_id', 'value'])
    ->where('path = ?', 'carriers/dhl/shipment_days')
    ->orWhere('path = ?', 'carriers/dhl/intl_shipment_days');

foreach ($this->getConnection()->fetchAll($select) as $configRow) {
    $row = ['value' => implode(',', array_intersect_key($days, array_flip(explode(',', $configRow['value']))))];
    $this->getConnection()->update(
        $this->getTable('core/config_data'),
        $row,
        [
            'config_id = ?' => $configRow['config_id'],
        ],
    );
}
