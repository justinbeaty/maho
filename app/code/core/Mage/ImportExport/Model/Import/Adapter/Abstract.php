<?php

/**
 * Maho
 *
 * @package    Mage_ImportExport
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

abstract class Mage_ImportExport_Model_Import_Adapter_Abstract implements SeekableIterator
{
    /**
     * Column names array.
     *
     * @var array
     */
    protected $_colNames;

    /**
     * Quantity of columns in first (column names) row.
     *
     * @var int
     */
    protected $_colQuantity;

    /**
     * Current row.
     *
     * @var array
     */
    protected $_currentRow = null;

    /**
     * Current row number.
     *
     * @var int
     */
    protected $_currentKey = null;

    /**
     * Source file path.
     *
     * @var string
     */
    protected $_source;

    /**
     * Adapter object constructor.
     *
     * @param string $source Source file path.
     * @throws Mage_Core_Exception
     */
    final public function __construct($source)
    {
        register_shutdown_function([$this, 'destruct']);

        if (!is_string($source)) {
            Mage::throwException(Mage::helper('importexport')->__('Source file path must be a string'));
        }
        if (!is_readable($source)) {
            Mage::throwException(Mage::helper('importexport')->__('%s file does not exists or is not readable', $source));
        }
        $this->_source = $source;

        $this->_init();

        // validate column names consistency
        if (is_array($this->_colNames) && !empty($this->_colNames)) {
            $this->_colQuantity = count($this->_colNames);

            if (count(array_unique($this->_colNames)) != $this->_colQuantity) {
                Mage::throwException(Mage::helper('importexport')->__('Column names have duplicates'));
            }
        } else {
            Mage::throwException(Mage::helper('importexport')->__('Column names is empty or is not an array'));
        }
    }

    /**
     * Destruct method on shutdown
     */
    public function destruct() {}

    /**
     * Method called as last step of object instance creation. Can be overridden in child classes.
     *
     * @return Mage_ImportExport_Model_Import_Adapter_Abstract
     */
    protected function _init()
    {
        return $this;
    }

    /**
     * Return the current element.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    #[\Override]
    public function current()
    {
        return array_combine(
            $this->_colNames,
            count($this->_currentRow) != $this->_colQuantity
                    ? array_pad($this->_currentRow, $this->_colQuantity, '')
                    : $this->_currentRow,
        );
    }

    /**
     * Column names getter.
     *
     * @return array
     */
    public function getColNames()
    {
        return $this->_colNames;
    }

    /**
     * Return the key of the current element.
     *
     * @return int More than 0 integer on success, integer 0 on failure.
     */
    #[\ReturnTypeWillChange]
    #[\Override]
    public function key()
    {
        return $this->_currentKey;
    }

    /**
     * Seeks to a position.
     *
     * @param int $position The position to seek to.
     */
    #[\ReturnTypeWillChange]
    #[\Override]
    public function seek($position)
    {
        Mage::throwException(Mage::helper('importexport')->__('Not implemented yet'));
    }

    /**
     * Checks if current position is valid.
     *
     * @return bool Returns true on success or false on failure.
     */
    #[\ReturnTypeWillChange]
    #[\Override]
    public function valid()
    {
        return !empty($this->_currentRow);
    }

    /**
     * Check source file for validity.
     *
     * @return Mage_ImportExport_Model_Import_Adapter_Abstract
     */
    public function validateSource()
    {
        return $this;
    }

    /**
     * Get the source path
     *
     * @return string
     */
    public function getSource()
    {
        return $this->_source;
    }
}
