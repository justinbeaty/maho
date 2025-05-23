<?php

/**
 * Maho
 *
 * @package    Mage_Eav
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Eav_Model_Attribute_Data_Multiline extends Mage_Eav_Model_Attribute_Data_Text
{
    /**
     * Extract data from request and return value
     *
     * @return array|string
     */
    #[\Override]
    public function extractValue(Zend_Controller_Request_Http $request)
    {
        $value = $this->_getRequestValue($request);
        if (!is_array($value)) {
            $value = false;
        } else {
            $value = array_map([$this, '_applyInputFilter'], $value);
        }
        return $value;
    }

    /**
     * Validate data
     * Return true or array of errors
     *
     * @param array|string $value
     * @return bool|array
     */
    #[\Override]
    public function validateValue($value)
    {
        $errors     = [];
        $attribute  = $this->getAttribute();

        if ($value === false) {
            // try to load original value and validate it
            $value = $this->getEntity()->getDataUsingMethod($attribute->getAttributeCode());
            if (!is_array($value)) {
                $value = explode("\n", $value);
            }
        }

        if (!is_array($value)) {
            $value = [$value];
        }
        for ($i = 0; $i < $attribute->getMultilineCount(); $i++) {
            if (!isset($value[$i])) {
                $value[$i] = null;
            }
            // validate first line
            if ($i == 0) {
                $result = parent::validateValue($value[$i]);
                if ($result !== true) {
                    $errors = $result;
                }
            } else {
                if (!empty($value[$i])) {
                    $result = parent::validateValue($value[$i]);
                    if ($result !== true) {
                        $errors = array_merge($errors, $result);
                    }
                }
            }
        }

        if (count($errors) == 0) {
            return true;
        }
        return $errors;
    }

    /**
     * Export attribute value to entity model
     */
    #[\Override]
    public function compactValue($value)
    {
        if (is_array($value)) {
            $value = trim(implode("\n", $value));
        }
        return parent::compactValue($value);
    }

    /**
     * Restore attribute value from SESSION to entity model
     *
     * @param array|string $value
     * @return Mage_Eav_Model_Attribute_Data_Text
     */
    #[\Override]
    public function restoreValue($value)
    {
        return $this->compactValue($value);
    }

    /**
     * Return formatted attribute value from entity model
     *
     * @param string $format
     * @return string|array
     * @throws Mage_Core_Exception
     */
    #[\Override]
    public function outputValue($format = Mage_Eav_Model_Attribute_Data::OUTPUT_FORMAT_TEXT)
    {
        $values = $this->getEntity()->getData($this->getAttribute()->getAttributeCode());
        if (!is_array($values)) {
            $values = explode("\n", (string) $values);
        }
        $values = array_map([$this, '_applyOutputFilter'], $values);
        $output = match ($format) {
            Mage_Eav_Model_Attribute_Data::OUTPUT_FORMAT_ARRAY => $values,
            Mage_Eav_Model_Attribute_Data::OUTPUT_FORMAT_HTML => implode('<br />', $values),
            Mage_Eav_Model_Attribute_Data::OUTPUT_FORMAT_ONELINE => implode(' ', $values),
            default => implode("\n", $values),
        };
        return $output;
    }
}
