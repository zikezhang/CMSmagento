<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_CatalogIndex
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Reindexer resource model
 *
 * @category    Mage
 * @package     Mage_CatalogIndex
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_CatalogIndex_Model_Resource_Indexer extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Enter description here ...
     *
     * @var unknown
     */
    protected $_insertData       = array();

    /**
     * Enter description here ...
     *
     * @var unknown
     */
    protected $_tableFields      = array();

    /**
     * Enter description here ...
     *
     * @var unknown
     */
    protected $_attributeCache   = array();

    /**
     * Enter description here ...
     *
     */
    protected function _construct()
    {
        $this->_init('catalog/product', 'entity_id');
    }

    /**
     * Enter description here ...
     *
     * @param unknown_type $id
     * @return unknown
     */
    protected function _loadAttribute($id)
    {
        if (!isset($this->_attributeCache[$id])) {
            $this->_attributeCache[$id] = Mage::getModel('eav/entity_attribute')->load($id);
        }

        return $this->_attributeCache[$id];
    }
  
    /**
     * Reindex attributes data
     *
     * @param array $products
     * @param array $attributeIds
     * @param mixed $store
     * @param int|null $forcedId
     * @param string $table
     * @param bool $storeIsWebsite
     * @return Mage_CatalogIndex_Model_Resource_Indexer
     */
    public function reindexAttributes($products, $attributeIds, $store, $forcedId = null, $table = 'catalogindex/eav',
        $storeIsWebsite = false)
    {
        $storeField = 'store_id';
        $websiteId = null;
        if ($storeIsWebsite) {
            $storeField = 'website_id';
            if ($store instanceof Mage_Core_Model_Store) {
                $websiteId = $store->getWebsiteId();
            } else {
                $websiteId = Mage::app()->getStore($store)->getWebsiteId();
            }
        }

        $this->_beginInsert($table, array('entity_id', 'attribute_id', 'value', $storeField));

        $products = Mage::getSingleton('catalogindex/retreiver')->assignProductTypes($products);

        if (is_null($forcedId)) {
            foreach ($products as $type=>$typeIds) {
                $retreiver = Mage::getSingleton('catalogindex/retreiver')->getRetreiver($type);
                if ($retreiver->areChildrenIndexable(Mage_CatalogIndex_Model_Retreiver::CHILDREN_FOR_ATTRIBUTES)) {
                    foreach ($typeIds as $id) {
                        $children = $retreiver->getChildProductIds($store, $id);
                        if ($children) {
                            $this->reindexAttributes($children, $attributeIds, $store, $id, $table, $storeIsWebsite);
                        }
                    }
                }
            }
        }

        $attributeIndex = $this->getProductData($products, $attributeIds, $store);
        foreach ($attributeIndex as $index) {
            $type = $index['type_id'];
            $id = (is_null($forcedId) ? $index['entity_id'] : $forcedId);

            if ($id && $index['attribute_id'] && isset($index['value'])) {
                $attribute = $this->_loadAttribute($index['attribute_id']);
                if ($attribute->getFrontendInput() == 'multiselect') {
                    $index['value'] = explode(',', $index['value']);
                }

                if (is_array($index['value'])) {
                    foreach ($index['value'] as $value) {
                        $this->_insert($table, array(
                            $id,
                            $index['attribute_id'],
                            $value,
                            (is_null($websiteId) ? $store->getId() : $websiteId)
                        ));
                    }
                } else {
                    $this->_insert($table, array(
                        $id,
                        $index['attribute_id'],
                        $index['value'],
                        (is_null($websiteId) ? $store->getId() : $websiteId)
                    ));
                }
            }
        }

        $this->_commitInsert($table);
        return $this;
    }
  
    /**
     * Get data for products
     *
     * @param array $products
     * @param array $attributeIds
     * @param Mage_Core_Model_Store $store
     * @return array
     */
    public function getProductData($products, $attributeIds, $store)
    {
        $result = array();
        foreach ($products as $type=>$typeIds) {
            $retreiver = Mage::getSingleton('catalogindex/retreiver')->getRetreiver($type);
            $byType = $retreiver->getAttributeData($typeIds, $attributeIds, $store);
            if ($byType) {
                $result = array_merge($result, $byType);
            }
        }
        return $result;
    }

    /**
     * Prepare base information for data insert
     *
     * @param string $table
     * @param array $fields
     * @return Mage_CatalogIndex_Model_Resource_Indexer
     */
    protected function _beginInsert($table, $fields)
    {
        $this->_tableFields[$table] = $fields;
        return $this;
    }

    /**
     * Put data into table
     *
     * @param string $table
     * @param bool $forced
     * @return Mage_CatalogIndex_Model_Resource_Indexer
     */
    protected function _commitInsert($table, $forced = true)
    {
        if (isset($this->_insertData[$table]) && count($this->_insertData[$table]) && ($forced || count($this->_insertData[$table]) >= 100)) {
            $query = 'REPLACE INTO ' . $this->getTable($table) . ' (' . implode(', ', $this->_tableFields[$table]) . ') VALUES ';
            $separator = '';
            foreach ($this->_insertData[$table] as $row) {
                $rowString = $this->_getWriteAdapter()->quoteInto('(?)', $row);
                $query .= $separator . $rowString;
                $separator = ', ';
            }
            $this->_getWriteAdapter()->query($query);
            $this->_insertData[$table] = array();
        }
        return $this;
    }

    /**
     * Insert data to table
     *
     * @param string $table
     * @param array $data
     * @return Mage_CatalogIndex_Model_Resource_Indexer
     */
    protected function _insert($table, $data)
    {
        $this->_insertData[$table][] = $data;
        $this->_commitInsert($table, false);
        return $this;
    }
  
}
