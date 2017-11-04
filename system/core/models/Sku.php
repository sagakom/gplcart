<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\core\models;

use gplcart\core\Model,
    gplcart\core\Config,
    gplcart\core\Hook;
use gplcart\core\models\Language as LanguageModel;

/**
 * Manages basic behaviors and data related to product SKU
 */
class Sku extends Model
{

    /**
     * Language model instance
     * @var \gplcart\core\models\Language $language
     */
    protected $language;

    /**
     * @param Config $config
     * @param Hook $hook
     * @param LanguageModel $language
     */
    public function __construct(Config $config, Hook $hook, LanguageModel $language)
    {
        parent::__construct($config, $hook);

        $this->language = $language;
    }

    /**
     * Returns an array of SKUs or counts them
     * @param array $data
     * @return array|integer
     */
    public function getList(array $data = array())
    {
        $sql = 'SELECT *';

        if (!empty($data['count'])) {
            $sql = 'SELECT COUNT(product_sku_id)';
        }

        $sql .= ' FROM product_sku WHERE product_sku_id > 0';

        $where = array();

        if (isset($data['sku'])) {
            $sql .= ' AND sku LIKE ?';
            $where[] = "%{$data['sku']}%";
        }

        if (isset($data['product_id'])) {
            $sql .= ' AND product_id=?';
            $where[] = (int) $data['product_id'];
        }

        if (isset($data['combination_id'])) {
            $sql .= ' AND combination_id=?';
            $where[] = $data['combination_id'];
        }

        if (isset($data['status'])) {
            $sql .= ' AND status=?';
            $where[] = (int) $data['status'];
        }

        if (isset($data['store_id'])) {
            $sql .= ' AND store_id=?';
            $where[] = (int) $data['store_id'];
        }

        $sql .= " ORDER BY sku ASC";

        if (!empty($data['limit'])) {
            $sql .= ' LIMIT ' . implode(',', array_map('intval', $data['limit']));
        }

        if (!empty($data['count'])) {
            return (int) $this->db->fetchColumn($sql, $where);
        }

        $results = $this->db->fetchAll($sql, $where, array('index' => 'product_sku_id'));

        foreach ($results as &$result) {
            $result['fields'] = $this->getFieldValues($result['combination_id']);
        }

        $this->hook->attach('sku.list', $results, $this);
        return $results;
    }

    /**
     * Adds a SKU
     * @param array $data
     * @return integer
     */
    public function add(array $data)
    {
        $result = null;
        $this->hook->attach('sku.add.before', $data, $result, $this);

        if (isset($result)) {
            return (int) $result;
        }

        $result = $this->db->insert('product_sku', $data);
        $this->hook->attach('sku.add.after', $data, $result, $this);
        return (int) $result;
    }

    /**
     * Deletes a product SKU
     * @param integer $product_id
     * @param array $options
     * @return boolean
     */
    public function delete($product_id, array $options = array())
    {
        $result = null;
        $this->hook->attach('sku.delete.before', $product_id, $options, $result, $this);

        if (isset($result)) {
            return (bool) $result;
        }

        $sql = 'DELETE FROM product_sku WHERE product_id=?';

        if (!empty($options['combinations'])) {
            $sql .= ' AND LENGTH(combination_id) > 0';
        }

        if (!empty($options['base'])) {
            $sql .= ' AND LENGTH(combination_id) = 0';
        }

        $result = (bool) $this->db->run($sql, array($product_id))->rowCount();
        $this->hook->attach('sku.delete.after', $product_id, $options, $result, $this);
        return (bool) $result;
    }

    /**
     * Generates a SKU
     * @param string $pattern
     * @param array $options
     * @return string
     */
    public function generate($pattern, array $options = array())
    {
        $options += array('store_id' => null, 'placeholders' => array());

        $result = null;
        $this->hook->attach('sku.generate.before', $pattern, $options, $result);

        if (isset($result)) {
            return (string) $result;
        }

        $sku = $pattern;
        if (!empty($options['placeholders'])) {
            $sku = gplcart_string_replace($pattern, $options['placeholders'], $options);
        }

        $result = $this->getUnique(mb_strimwidth($sku, 0, 200, ''), $options['store_id']);
        $this->hook->attach('sku.generate.after', $pattern, $options, $result);
        return $result;
    }

    /**
     * Returns a product SKU pattern
     * @return string
     */
    public function getPattern()
    {
        return $this->config->get("product_sku_pattern", 'PRODUCT-%i');
    }

    /**
     * Returns an array of placeholders for a product SKU pattern
     * @return array
     */
    public function getPatternPlaceholders()
    {
        return $this->config->get('product_sku_placeholder', array('%i' => 'product_id'));
    }

    /**
     * Returns a unique SKU for the given store ID
     * @param string $sku
     * @param integer|null $store_id
     * @return string
     */
    public function getUnique($sku, $store_id)
    {
        $existing = $this->get($sku, $store_id);

        if (empty($existing)) {
            return $sku;
        }

        $counter = 1;

        do {
            $modified = $sku . '-' . $counter;
            $counter++;
        } while ($this->get($modified, $store_id));

        return $modified;
    }

    /**
     * Returns an array of field value IDs from a combination ID
     * @param string $combination_id
     * @return array
     */
    public function getFieldValues($combination_id)
    {
        $field_value_ids = explode('_', substr($combination_id, strpos($combination_id, '-') + 1));
        sort($field_value_ids);

        return $field_value_ids;
    }

    /**
     * Creates a field combination id from an array of field value ids
     * @param array $field_value_ids
     * @param null|integer $product_id
     * @return string
     */
    public function getCombinationId(array $field_value_ids, $product_id = null)
    {
        sort($field_value_ids);
        $combination_id = implode('_', $field_value_ids);

        return empty($product_id) ? $combination_id : "$product_id-$combination_id";
    }

    /**
     * Returns an array of data when selecting sku combinations
     * @param array $product
     * @param array $field_value_ids
     * @return array
     */
    public function selectCombination(array $product, array $field_value_ids)
    {
        $result = array();
        $this->hook->attach('sku.select.combination.before', $product, $field_value_ids, $result, $this);

        if (!empty($result)) {
            return (array) $result;
        }

        $access = !empty($product['stock']) || empty($product['subtract']);

        $result = array(
            'modal' => '',
            'severity' => '',
            'cart_access' => $access,
            'combination' => array(),
            'sku' => $product['sku'],
            'price' => $product['price'],
            'currency' => $product['currency'],
            'message' => $access ? '' : $this->language->text('Out of stock')
        );

        if (empty($field_value_ids)) {
            $this->hook->attach('sku.select.combination.after', $product, $field_value_ids, $result, $this);
            return (array) $result;
        }

        if (empty($product['status'])) {
            $result['severity'] = 'danger';
            $result['message'] = $this->language->text('Unavailable');

            $this->hook->attach('sku.select.combination.after', $product, $field_value_ids, $result, $this);
            return (array) $result;
        }

        $combination_id = $this->getCombinationId($field_value_ids, $product['product_id']);

        if (empty($product['combination'][$combination_id]['status'])) {

            $result['not_matched'] = true;
            $result['cart_access'] = false;

            $result['severity'] = 'danger';
            $result['message'] = $this->language->text('Unavailable');
            $result['related'] = $this->getRelatedFieldValues($product, $field_value_ids);

            $this->hook->attach('sku.select.combination.after', $product, $field_value_ids, $result, $this);
            return (array) $result;
        }

        $result['combination'] = $product['combination'][$combination_id];
        $result['combination']['currency'] = $product['currency'];

        $result['sku'] = $result['combination']['sku'];
        $result['price'] = $result['combination']['price'];

        if (empty($result['combination']['stock']) && $product['subtract']) {
            $result['cart_access'] = false;
            $result['severity'] = 'warning';
            $result['message'] = $this->language->text('Out of stock');
        }

        $this->hook->attach('sku.select.combination.after', $product, $field_value_ids, $result, $this);
        return (array) $result;
    }

    /**
     * Returns an array of related fields value IDs
     * @todo Rethink this. It should return all possible combinations
     * @param array $product
     * @param array $ids
     * @return array
     */
    protected function getRelatedFieldValues(array $product, array $ids)
    {
        $related = array();
        foreach ($product['combination'] as $combination) {
            if (array_intersect($ids, $combination['fields'])) {
                $related += $combination['fields'];
            }
        }

        return $related;
    }

    /**
     * Loads a SKU
     * @param string $sku
     * @param integer|null $store_id
     * @return array
     */
    public function get($sku, $store_id = null)
    {
        $result = null;
        $this->hook->attach('sku.get.before', $sku, $store_id, $result, $this);

        if (isset($result)) {
            return $result;
        }

        $conditions = array($sku);
        $sql = 'SELECT * FROM product_sku WHERE sku=?';

        if (isset($store_id)) {
            $sql .= ' AND store_id=?';
            $conditions[] = $store_id;
        }

        $result = $this->db->fetch($sql, $conditions);
        $this->hook->attach('sku.get.after', $sku, $store_id, $result, $this);
        return $result;
    }

}
