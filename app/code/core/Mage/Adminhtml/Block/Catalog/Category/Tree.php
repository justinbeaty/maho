<?php

/**
 * Maho
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2021-2024 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Categories tree block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 */
class Mage_Adminhtml_Block_Catalog_Category_Tree extends Mage_Adminhtml_Block_Catalog_Category_Abstract
{
    protected $_withProductCount;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('catalog/category/tree.phtml');
        $this->_withProductCount = true;
    }

    #[\Override]
    protected function _prepareLayout()
    {
        $addUrl = $this->getUrl('*/*/add', [
            '_current' => true,
            '_query' => false,
            'id' => null,
        ]);

        $this->setChild(
            'add_sub_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData([
                    'label'     => Mage::helper('catalog')->__('Add Subcategory'),
                    'onclick'   => "addNew('$addUrl', false)",
                    'class'     => 'add',
                    'id'        => 'add_subcategory_button',
                    'disabled'  =>  !$this->canAddSubCategory(),
                ]),
        );

        $this->setChild(
            'add_root_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData([
                    'label'     => Mage::helper('catalog')->__('Add Root Category'),
                    'onclick'   => "addNew('$addUrl', true)",
                    'class'     => 'add' . ($this->canAddRootCategory() ? '' : ' no-display'),
                    'id'        => 'add_root_category_button',
                ]),
        );

        $this->setChild(
            'store_switcher',
            $this->getLayout()->createBlock('adminhtml/store_switcher')
                ->setSwitchUrl($this->getUrl('*/*/*', ['_current' => true, '_query' => false, 'store' => null]))
                ->setTemplate('store/switcher/enhanced.phtml'),
        );
        return parent::_prepareLayout();
    }

    /**
     * @return int
     */
    protected function _getDefaultStoreId()
    {
        return Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID;
    }

    /**
     * Load category collection with product count
     *
     * @return Mage_Catalog_Model_Resource_Category_Collection
     */
    public function getCategoryCollection()
    {
        $storeId = $this->getRequest()->getParam('store', $this->_getDefaultStoreId());
        $collection = $this->getData('category_collection');
        if (is_null($collection)) {
            /** @var Mage_Catalog_Model_Resource_Category_Collection $collection */
            $collection = Mage::getModel('catalog/category')->getCollection();

            $collection->addAttributeToSelect('name')
                ->addAttributeToSelect('is_active')
                ->setProductStoreId($storeId)
                ->setLoadProductCount($this->_withProductCount)
                ->setStoreId($storeId);

            $this->setData('category_collection', $collection);
        }
        return $collection;
    }

    /**
     * @return string
     */
    public function getAddRootButtonHtml()
    {
        return $this->getChildHtml('add_root_button');
    }

    /**
     * @return string
     */
    public function getAddSubButtonHtml()
    {
        return $this->getChildHtml('add_sub_button');
    }

    /**
     * @return string
     */
    public function getStoreSwitcherHtml()
    {
        return $this->getChildHtml('store_switcher');
    }

    /**
     * Returns URL for loading tree
     *
     * @param bool $expanded
     * @return string
     */
    public function getLoadTreeUrl($expanded = null)
    {
        $params = ['_current' => true, 'id' => null, 'store' => null];
        if ($expanded == true) {
            $params['expand_all'] = true;
        }
        return $this->getUrl('*/*/categoriesJson', $params);
    }

    /**
     * @return string
     */
    public function getNodesUrl()
    {
        return $this->getUrl('*/catalog_category/jsonTree');
    }

    /**
     * @return string
     */
    public function getSwitchTreeUrl()
    {
        return $this->getUrl(
            '*/catalog_category/tree',
            ['_current' => true, 'store' => null, '_query' => false, 'id' => null, 'parent' => null],
        );
    }

    /**
     * @return string
     */
    public function getMoveUrl()
    {
        return $this->getUrl('*/catalog_category/move', ['store' => $this->getRequest()->getParam('store')]);
    }

    /**
     * Get category children as array
     *
     * @param Mage_Catalog_Model_Category|int|string $parentNodeCategory
     * @return array
     */
    public function getTree($parentNodeCategory = null)
    {
        $rootArray = $this->_getNodeJson($this->getRoot($parentNodeCategory));
        return $rootArray['children'] ?? [];
    }

    /**
     * Get category children as JSON
     *
     * @param Mage_Catalog_Model_Category|int|string $parentNodeCategory
     * @return string
     */
    public function getTreeJson($parentNodeCategory = null)
    {
        $rootArray = $this->_getNodeJson($this->getRoot($parentNodeCategory));
        return Mage::helper('core')->jsonEncode($rootArray['children'] ?? []);
    }

    /**
     * Get JSON of a tree node or an associative array
     *
     */
    public function getNodeJson(Varien_Data_Tree_Node|array $node, int $level = 0): array
    {
        return $this->_getNodeJson($node, $level);
    }

    /**
     * Get JSON of a tree node or an associative array
     *
     * @param Varien_Data_Tree_Node|array $node
     * @param int $level
     * @return array
     */
    protected function _getNodeJson($node, $level = 0)
    {
        // create a node from data array
        if (is_array($node)) {
            $node = new Varien_Data_Tree_Node($node, 'entity_id', new Varien_Data_Tree());
        }

        $item = [];
        $item['text'] = $this->buildNodeName($node);

        $rootForStores = in_array($node->getEntityId(), $this->getRootIds());

        $item['id']  = (int) $node->getId();
        $item['type'] = 'folder';

        $item['store']  = (int) $this->getStore()->getId();
        $item['path'] = $node->getData('path');

        $item['cls'] = 'folder ' . ($node->getIsActive() ? 'active-category' : 'no-active-category');

        $allowMove = (bool) $this->_isCategoryMoveable($node);
        $item['allowDrop'] = $allowMove;

        // disallow drag if it's first level and category is root of a store
        $item['allowDrag'] = $allowMove && !($node->getLevel() == 1 && $rootForStores);

        if ($node->hasChildren() || $level < $this->getRecursionLevel() || $this->getRecursionLevel() === 0) {
            $item['children'] = [];
            foreach ($node->getChildren() as $child) {
                $item['children'][] = $this->_getNodeJson($child, $level + 1);
            }
        }

        $isParent = $this->_isParentSelectedCategory($node);

        if ($isParent || $node->getLevel() < 2) {
            $item['expanded'] = true;
        }

        return $item;
    }

    /**
     * Get root category information
     */
    public function getRootTreeParameters(): array
    {
        $root = $this->getRoot();
        return [
            'data' => $this->getTree(),
            'parameters' => [
                'text'         => $this->buildNodeName($root),
                'allowDrag'    => false,
                'allowDrop'    => (bool) $root->getIsVisible(),
                'id'           => (int) $root->getId(),
                'store_id'     => (int) $this->getStore()->getId(),
                'category_id'  => (int) $this->getCategory()->getId(),
                'root_visible' => (bool) $root->getIsVisible(),
                'can_add_root' => (bool) $this->canAddRootCategory(),
                'expanded'     => $this->getRecursionLevel() === 0,
            ],
        ];
    }

    /**
     * Get category name
     *
     * @param Varien_Object $node
     * @return string
     */
    public function buildNodeName($node)
    {
        $result = $this->escapeHtml($node->getName());
        if ($this->_withProductCount) {
            $result .= ' (' . $node->getProductCount() . ')';
        }
        return $result;
    }

    /**
     * Check if the node can be moved
     *
     * @param Varien_Object $node
     * @return bool
     */
    protected function _isCategoryMoveable($node)
    {
        $options = new Varien_Object([
            'is_moveable' => true,
            'category' => $node,
        ]);

        Mage::dispatchEvent(
            'adminhtml_catalog_category_tree_is_moveable',
            ['options' => $options],
        );

        return $options->getIsMoveable();
    }

    /**
     * Check if the node contains children categories that are selected
     *
     * @param Varien_Object $node
     * @return bool
     */
    protected function _isParentSelectedCategory($node)
    {
        if ($node && $this->getCategory()) {
            $pathIds = $this->getCategory()->getPathIds();
            if (in_array($node->getId(), $pathIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if page loaded by outside link to category edit
     *
     * @return bool
     */
    public function isClearEdit()
    {
        return (bool) $this->getRequest()->getParam('clear');
    }

    /**
     * Check availability of adding root category
     *
     * @return bool
     */
    public function canAddRootCategory()
    {
        if ($this->getStore()->getId() !== 0) {
            return false;
        }

        $options = new Varien_Object(['is_allow' => true]);
        Mage::dispatchEvent('adminhtml_catalog_category_tree_can_add_root_category', [
            'category' => $this->getCategory(),
            'options'  => $options,
            'store'    => $this->getStore()->getId(),
        ]);

        return $options->getIsAllow();
    }

    /**
     * Check availability of adding sub category
     *
     * @return bool
     */
    public function canAddSubCategory()
    {
        $options = new Varien_Object(['is_allow' => true]);
        Mage::dispatchEvent('adminhtml_catalog_category_tree_can_add_sub_category', [
            'category' => $this->getCategory(),
            'options'  => $options,
            'store'    => $this->getStore()->getId(),
        ]);

        return $options->getIsAllow();
    }
}
