<?php
/**
 * Maho
 *
 * @category   Unserialize
 * @package    Unserialize_Reader
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Class Unserialize_Reader_Int
 */
class Unserialize_Reader_Bool
{
    /**
     * @var int
     */
    protected $_status;

    /**
     * @var string|int
     */
    protected $_value;

    public const READING_VALUE = 1;

    /**
     * @param string $char
     * @param string $prevChar
     * @return int|null
     */
    public function read($char, $prevChar)
    {
        if ($prevChar == Unserialize_Parser::SYMBOL_COLON) {
            $this->_value .= $char;
            $this->_status = self::READING_VALUE;
            return null;
        }

        if ($this->_status == self::READING_VALUE) {
            if ($char !== Unserialize_Parser::SYMBOL_SEMICOLON) {
                $this->_value .= $char;
            } else {
                return (bool)$this->_value;
            }
        }
        return null;
    }
}