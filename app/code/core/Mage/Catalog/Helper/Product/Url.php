<?php

/**
 * Maho
 *
 * @package    Mage_Catalog
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2022-2024 The OpenMage Contributors (https://openmage.org)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Catalog_Helper_Product_Url extends Mage_Core_Helper_Url
{
    protected $_moduleName = 'Mage_Catalog';

    /**
     * Symbol convert table
     *
     * @var array
     */
    protected $_convertTable = ['&amp;' => 'and', '@' => 'at', '©' => 'c', '®' => 'r', '™' => 'tm'];

    /**
     * Check additional instruction for conversion table in configuration
     */
    public function __construct()
    {
        $convertNode = Mage::getConfig()->getNode('default/url/convert');
        if ($convertNode) {
            foreach ($convertNode->children() as $node) {
                $this->_convertTable[(string) $node->from] = (string) $node->to;
            }
        }
    }

    /**
     * Get chars conversion table
     *
     * @return array
     */
    public function getConvertTable()
    {
        return $this->_convertTable;
    }

    /**
     * Process string based on conversion table
     *
     * @param   string $string
     * @param   null|string $locale
     * @param   bool $lower
     * @return  string
     */
    public function format($string, $locale = null, $lower = true)
    {
        if ($string === null) {
            return '';
        }

        $string = strtr($string, $this->getConvertTable());

        if (!empty($locale)) {
            $opts = transliterator_list_ids();
            $code = str_replace('_', '-', strtolower($locale)) . '_Latn/BGN';
            if (in_array($code, $opts)) {
                return transliterator_transliterate($code . '; Any-Latin; Latin-ASCII; [^\u001F-\u007f] remove' . ($lower ? '; Lower()' : ''), $string);
            }
        }

        return transliterator_transliterate('Any-Latin; Latin-ASCII; [^\u001F-\u007f] remove' . ($lower ? '; Lower()' : ''), $string);
    }
}
