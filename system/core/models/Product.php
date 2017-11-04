<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\core\models;

use gplcart\core\Model,
    gplcart\core\Cache,
    gplcart\core\Config,
    gplcart\core\Hook;
use gplcart\core\traits\Image as ImageTrait,
    gplcart\core\traits\Alias as AliasTrait;
use gplcart\core\models\Sku as SkuModel,
    gplcart\core\models\File as FileModel,
    gplcart\core\models\Alias as AliasModel,
    gplcart\core\models\Price as PriceModel,
    gplcart\core\models\Search as SearchModel,
    gplcart\core\models\Language as LanguageModel,
    gplcart\core\models\PriceRule as PriceRuleModel,
    gplcart\core\models\ProductField as ProductFieldModel;
use gplcart\core\helpers\Request as RequestHelper;

/**
 * Manages basic behaviors and data related to products
 */
class Product extends Model
{

    use ImageTrait,
        AliasTrait;

    /**
     * Cache instance
     * @var \gplcart\core\Cache $cache
     */
    protected $cache;

    /**
     * Product field model instance
     * @var \gplcart\core\models\ProductField $product_field
     */
    protected $product_field;

    /**
     * Price model instance
     * @var \gplcart\core\models\Price $price
     */
    protected $price;

    /**
     * Language model instance
     * @var \gplcart\core\models\Language $language
     */
    protected $language;

    /**
     * Price rule model instance
     * @var \gplcart\core\models\PriceRule $pricerule
     */
    protected $pricerule;

    /**
     * SKU model instance
     * @var \gplcart\core\models\Sku $sku
     */
    protected $sku;

    /**
     * Search module instance
     * @var \gplcart\core\models\Search $search
     */
    protected $search;

    /**
     * Alias model instance
     * @var \gplcart\core\models\Alias $alias
     */
    protected $alias;

    /**
     * File model instance
     * @var \gplcart\core\models\File $file
     */
    protected $file;

    /**
     * Request class instance
     * @var \gplcart\core\helpers\Request $request
     */
    protected $request;

    /**
     * @param Config $config
     * @param Hook $hook
     * @param Cache $cache
     * @param LanguageModel $language
     * @param AliasModel $alias
     * @param FileModel $file
     * @param PriceModel $price
     * @param PriceRuleModel $pricerule
     * @param SkuModel $sku
     * @param SearchModel $search
     * @param ProductFieldModel $product_field
     * @param RequestHelper $request
     */
    public function __construct(Config $config, Hook $hook, Cache $cache, LanguageModel $language,
            AliasModel $alias, FileModel $file, PriceModel $price, PriceRuleModel $pricerule,
            SkuModel $sku, SearchModel $search, ProductFieldModel $product_field,
            RequestHelper $request)
    {
        parent::__construct($config, $hook);

        $this->sku = $sku;
        $this->file = $file;
        $this->alias = $alias;
        $this->price = $price;
        $this->cache = $cache;
        $this->search = $search;
        $this->request = $request;
        $this->language = $language;
        $this->pricerule = $pricerule;
        $this->product_field = $product_field;
    }

    /**
     * Adds a product
     * @param array $data
     * @return integer
     */
    public function add(array $data)
    {
        $result = null;
        $this->hook->attach('product.add.before', $data, $result, $this);

        if (isset($result)) {
            return (int) $result;
        }

        $data['created'] = $data['modified'] = GC_TIME;

        $data += array(
            'currency' => $this->config->get('currency', 'USD')
        );

        $this->setPrice($data);

        $result = $data['product_id'] = $this->db->insert('product', $data);

        $this->setTranslationTrait($this->db, $data, 'product', false);
        $this->setImagesTrait($this->file, $data, 'product');

        $this->setSku($data, false);
        $this->setSkuCombinations($data, false);
        $this->setOptions($data, false);
        $this->setAttributes($data, false);
        $this->setAliasTrait($this->alias, $data, 'product', false);
        $this->setRelated($data, false);

        $this->search->index('product', $data);

        $this->hook->attach('product.add.after', $data, $result, $this);
        return (int) $result;
    }

    /**
     * Updates a product
     * @param integer $product_id
     * @param array $data
     * @return boolean
     */
    public function update($product_id, array $data)
    {
        $result = null;
        $this->hook->attach('product.update.before', $product_id, $data, $result, $this);

        if (isset($result)) {
            return (bool) $result;
        }

        $data['modified'] = GC_TIME;
        $data['product_id'] = $product_id;

        $this->setPrice($data);

        $conditions = array('product_id' => $product_id);
        $updated = $this->db->update('product', $data, $conditions);

        $updated += (int) $this->setSku($data);
        $updated += (int) $this->setTranslationTrait($this->db, $data, 'product');
        $updated += (int) $this->setImagesTrait($this->file, $data, 'product');
        $updated += (int) $this->setAliasTrait($this->alias, $data, 'product');
        $updated += (int) $this->setSkuCombinations($data);
        $updated += (int) $this->setOptions($data);
        $updated += (int) $this->setAttributes($data);
        $updated += (int) $this->setRelated($data);

        $result = $updated > 0;

        if ($result) {
            $this->search->index('product', $product_id);
            $this->cache->clear("product.$product_id.", array('pattern' => '*'));
        }

        $this->hook->attach('product.update.after', $product_id, $data, $result, $this);
        return (bool) $result;
    }

    /**
     * Converts a price to minor units
     * @param array $data
     */
    protected function setPrice(array &$data)
    {
        if (!empty($data['price']) && !empty($data['currency'])) {
            $data['price'] = $this->price->amount($data['price'], $data['currency']);
        }
    }

    /**
     * Deletes and/or adds related products
     * @param array $data
     * @param boolean $update
     * @return boolean
     */
    public function setRelated(array $data, $update = true)
    {
        if (empty($data['form']) && empty($data['related'])) {
            return false;
        }

        $product_id = $data['product_id'];

        if ($update) {
            $this->db->delete('product_related', array('product_id' => $product_id));
            $this->db->delete('product_related', array('related_product_id' => $product_id));
        }

        if (empty($data['related'])) {
            return false;
        }

        foreach ((array) $data['related'] as $id) {
            $this->db->insert('product_related', array('product_id' => $product_id, 'related_product_id' => $id));
            $this->db->insert('product_related', array('product_id' => $id, 'related_product_id' => $product_id));
        }

        return true;
    }

    /**
     * Generate a product SKU
     * @param array $data
     * @return string
     */
    public function generateSku(array $data)
    {
        $data += array('placeholders' => $this->sku->getPatternPlaceholders());
        return $this->sku->generate($this->sku->getPattern(), $data);
    }

    /**
     * Loads a product from the database
     * @param integer $product_id
     * @param array $options
     * @return array
     */
    public function get($product_id, array $options = array())
    {
        $result = &gplcart_static(gplcart_array_hash(array("product.get.$product_id" => $options)));

        if (isset($result)) {
            return $result;
        }

        $this->hook->attach('product.get.before', $product_id, $options, $result, $this);

        if (empty($product_id)) {
            return $result = array();
        }

        if (isset($result)) {
            return $result;
        }

        $options += array('language' => null);
        $list = $this->getList(array('product_id' => $product_id));

        if (empty($list)) {
            return $result = array();
        }

        $result = reset($list);

        $this->attachFields($result);
        $this->attachSku($result);
        $this->attachImagesTrait($this->file, $result, 'product', $options['language']);
        $this->attachTranslationTrait($this->db, $result, 'product', $options['language']);

        $this->hook->attach('product.get.after', $product_id, $options, $result, $this);

        return $result;
    }

    /**
     * Returns a product by the SKU
     * @param string $sku
     * @param integer $store_id
     * @param string|null $language
     * @return array
     * @todo Reuse getList(), but tune up its query (LENGTH)
     */
    public function getBySku($sku, $store_id, $language = null)
    {
        $product = &gplcart_static("product.get.sku.$sku.$store_id.$language");

        if (isset($product)) {
            return $product;
        }

        if (!isset($language)) {
            $language = $this->language->getLangcode();
        }

        $sql = 'SELECT p.*, COALESCE(NULLIF(pt.title, ""), p.title) AS title,'
                . ' ps.sku, ps.price, ps.stock, ps.file_id'
                . ' FROM product p'
                . ' LEFT JOIN product_sku ps ON(p.product_id=ps.product_id)'
                . ' LEFT JOIN product_translation pt ON(p.product_id=pt.product_id'
                . ' AND pt.language=:language)'
                . ' WHERE ps.sku=:sku AND ps.store_id=:store_id';

        $conditions = array(
            'sku' => $sku,
            'language' => $language,
            'store_id' => $store_id
        );

        $product = $this->db->fetch($sql, $conditions);
        $this->attachImagesTrait($this->file, $product, 'product', $language);

        return $product;
    }

    /**
     * Adds fields to the product
     * @param array $product
     * @return array
     */
    protected function attachFields(array &$product)
    {
        if (empty($product)) {
            return $product;
        }

        $product['field'] = $this->product_field->getList($product['product_id']);

        if (empty($product['field']['option'])) {
            return $product;
        }

        foreach ($product['field']['option'] as &$field_values) {
            $field_values = array_unique($field_values);
        }

        return $product;
    }

    /**
     * Adds option combinations to the product
     * @param array $product
     * @return array
     */
    protected function attachSku(array &$product)
    {
        if (empty($product)) {
            return $product;
        }

        $product['default_field_values'] = array();
        $codes = (array) $this->sku->getList(array('product_id' => $product['product_id']));

        foreach ($codes as $code) {

            if ($code['combination_id'] === '') {
                $product['sku'] = $code['sku'];
                $product['price'] = $code['price'];
                $product['stock'] = $code['stock'];
                continue;
            }

            $product['combination'][$code['combination_id']] = $code;

            if (!empty($code['is_default'])) {
                $product['default_field_values'] = $code['fields'];
            }
        }

        return $product;
    }

    /**
     * Deletes a product
     * @param integer $product_id
     * @param bool $check
     * @return boolean
     */
    public function delete($product_id, $check = true)
    {
        $result = null;
        $this->hook->attach('product.delete.before', $product_id, $check, $result, $this);

        if (isset($result)) {
            return (bool) $result;
        }

        if ($check && !$this->canDelete($product_id)) {
            return false;
        }

        $conditions = array('product_id' => $product_id);
        $conditions2 = array('id_key' => 'product_id', 'id_value' => $product_id);

        $result = (bool) $this->db->delete('product', $conditions);

        if ($result) {

            $this->db->delete('cart', $conditions);
            $this->db->delete('review', $conditions);
            $this->db->delete('rating', $conditions);
            $this->db->delete('wishlist', $conditions);
            $this->db->delete('product_sku', $conditions);
            $this->db->delete('rating_user', $conditions);
            $this->db->delete('product_field', $conditions);
            $this->db->delete('product_translation', $conditions);
            $this->db->delete('file', $conditions2);
            $this->db->delete('alias', $conditions2);
            $this->db->delete('search_index', $conditions2);

            $sql = 'DELETE ci'
                    . ' FROM collection_item ci'
                    . ' INNER JOIN collection c ON(ci.collection_id = c.collection_id)'
                    . ' WHERE c.type = ? AND ci.value = ?';

            $this->db->run($sql, array('product', $product_id));
        }

        $this->hook->attach('product.delete.after', $product_id, $check, $result, $this);
        return (bool) $result;
    }

    /**
     * Whether the product can be deleted
     * @param integer $product_id
     * @return boolean
     */
    public function canDelete($product_id)
    {
        $sql = 'SELECT cart_id'
                . ' FROM cart'
                . ' WHERE product_id=? AND order_id > 0';

        $result = $this->db->fetchColumn($sql, array($product_id));
        return empty($result);
    }

    /**
     * Calculates the product price
     * @param array $product
     * @return array
     */
    public function calculate(array $product)
    {
        return $this->pricerule->calculate($product['price'], $product);
    }

    /**
     * Returns an array of weight measurement units
     * @return array
     */
    public function getWeightUnits()
    {
        return array(
            'g' => $this->language->text('Gram'),
            'kg' => $this->language->text('Kilogram'),
            'lb' => $this->language->text('Pound'),
            'oz' => $this->language->text('Ounce'),
        );
    }

    /**
     * Returns an array of size measurement units
     * @return array
     */
    public function getSizeUnits()
    {
        return array(
            'in' => $this->language->text('Inch'),
            'mm' => $this->language->text('Millimeter'),
            'cm' => $this->language->text('Centimetre')
        );
    }

    /**
     * Returns an array of related products
     * @param array $options
     * @return array
     */
    public function getRelated(array $options)
    {
        $options += array('load' => false);

        if (empty($options['product_id'])) {
            return array();
        }

        $sql = 'SELECT related_product_id FROM product_related WHERE product_id=?';

        if (!empty($options['limit'])) {
            $sql .= ' LIMIT ' . implode(',', array_map('intval', $options['limit']));
        }

        $list = $this->db->fetchColumnAll($sql, array($options['product_id']));

        if (!empty($list) && $options['load']) {
            $options['product_id'] = $list;
            $list = $this->getList($options);
        }

        return (array) $list;
    }

    /**
     * Returns an array of products or counts them
     * @param array $data
     * @return array|integer
     */
    public function getList(array $data = array())
    {
        $sql = 'SELECT p.*, a.alias, COALESCE(NULLIF(pt.title, ""), p.title) AS title,'
                . 'pt.language, ps.sku, ps.price, ps.stock, ps.file_id, u.role_id';

        if (!empty($data['count'])) {
            $sql = 'SELECT COUNT(p.product_id)';
        }

        $sql .= ' FROM product p'
                . ' LEFT JOIN product_translation pt ON(p.product_id = pt.product_id AND pt.language=?)'
                . ' LEFT JOIN alias a ON(a.id_key=? AND a.id_value=p.product_id)'
                . ' LEFT JOIN user u ON(u.user_id=p.user_id)'
                . ' LEFT JOIN product_sku ps ON(p.product_id = ps.product_id AND LENGTH(ps.combination_id) = 0)';

        $language = $this->language->getLangcode();
        $where = array($language, 'product_id');

        if (!empty($data['product_id'])) {
            settype($data['product_id'], 'array');
            $placeholders = rtrim(str_repeat('?,', count($data['product_id'])), ',');
            $sql .= " WHERE p.product_id IN($placeholders)";
            $where = array_merge($where, $data['product_id']);
        } else {
            $sql .= ' WHERE p.product_id > 0';
        }

        if (isset($data['title'])) {
            $sql .= ' AND (p.title LIKE ? OR (pt.title LIKE ? AND pt.language=?))';
            $where[] = "%{$data['title']}%";
            $where[] = "%{$data['title']}%";
            $where[] = $language;
        }

        if (isset($data['language'])) {
            $sql .= ' AND pt.language = ?';
            $where[] = $data['language'];
        }

        if (isset($data['sku'])) {
            $sql .= ' AND ps.sku=?';
            $where[] = $data['sku'];
        }

        if (isset($data['sku_like'])) {
            $sql .= ' AND ps.sku LIKE ?';
            $where[] = "%{$data['sku_like']}%";
        }

        if (isset($data['price']) && isset($data['currency'])) {
            $sql .= ' AND ps.price = ?';
            $where[] = $this->price->amount((int) $data['price'], $data['currency']);
        }

        if (isset($data['currency'])) {
            $sql .= ' AND p.currency = ?';
            $where[] = $data['currency'];
        }

        if (isset($data['stock'])) {
            $sql .= ' AND ps.stock = ?';
            $where[] = (int) $data['stock'];
        }

        if (isset($data['category_id'])) {
            $sql .= ' AND p.category_id = ?';
            $where[] = (int) $data['category_id'];
        }

        if (isset($data['status'])) {
            $sql .= ' AND p.status = ?';
            $where[] = (int) $data['status'];
        }

        if (isset($data['store_id'])) {
            $sql .= ' AND p.store_id = ?';
            $where[] = (int) $data['store_id'];
        }

        if (empty($data['count'])) {
            $sql .= ' GROUP BY p.product_id,'
                    // Additional group by to prevent errors wnen sql_mode=only_full_group_by
                    . 'a.alias, pt.title, ps.sku, ps.price, ps.stock, ps.file_id';
        }

        $allowed_order = array('asc', 'desc');

        $allowed_sort = array(
            'title' => 'p.title', 'sku' => 'ps.sku', 'sku_like' => 'ps.sku', 'price' => 'ps.price',
            'currency' => 'p.currency', 'stock' => 'ps.stock',
            'status' => 'p.status', 'store_id' => 'p.store_id',
            'product_id' => 'p.product_id'
        );

        if (isset($data['sort']) && isset($allowed_sort[$data['sort']])//
                && isset($data['order']) && in_array($data['order'], $allowed_order)) {
            $sql .= " ORDER BY {$allowed_sort[$data['sort']]} {$data['order']}";
        } else {
            $sql .= " ORDER BY p.modified DESC";
        }

        if (!empty($data['limit'])) {
            $sql .= ' LIMIT ' . implode(',', array_map('intval', $data['limit']));
        }

        if (!empty($data['count'])) {
            return (int) $this->db->fetchColumn($sql, $where);
        }

        $list = $this->db->fetchAll($sql, $where, array('index' => 'product_id'));

        $this->hook->attach('product.list', $list, $this);
        return $list;
    }

    /**
     * Saves a product to cookie
     * @param integer $product_id
     * @param integer $limit
     * @param integer $lifespan
     * @return array
     */
    public function setViewed($product_id, $limit, $lifespan)
    {
        $existing = $this->getViewed($limit);

        if (in_array($product_id, $existing)) {
            return $existing;
        }

        array_unshift($existing, $product_id);
        $saved = array_unique($existing);

        $this->controlViewedLimit($saved, $limit);

        $this->request->setCookie('viewed_products', implode('|', $saved), $lifespan);
        return $saved;
    }

    /**
     * Returns an array of recently viewed product IDs
     * @param integer|null $limit
     * @return array
     */
    public function getViewed($limit = null)
    {
        $cookie = $this->request->cookie('viewed_products', '', 'string');
        $products = array_filter(explode('|', $cookie), 'is_numeric');
        $this->controlViewedLimit($products, $limit);

        return $products;
    }

    /**
     * Reduces an array of recently viewed products
     * If the limit is set to X and > 0 it removes all but first X items in the array
     * @param array $items
     * @param integer $limit
     * @return array
     */
    protected function controlViewedLimit(array &$items, $limit)
    {
        if (empty($limit)) {
            return $items;
        }

        return array_slice($items, 0, $limit + 1);
    }

    /**
     * Deletes and/or adds a new base SKU
     * @param array $data
     * @param boolean $update
     * @return bool
     */
    protected function setSku(array &$data, $update = true)
    {
        if (empty($data['form']) && empty($data['sku'])) {
            return false;
        }

        if ($update) {
            $this->sku->delete($data['product_id'], array('base' => true));
            return (bool) $this->sku->add($data);
        }

        if (empty($data['sku'])) {
            $data['sku'] = $this->generateSku($data);
        }

        return (bool) $this->sku->add($data);
    }

    /**
     * Deletes and/or adds product combinations
     * @param array $data
     * @param boolean $update
     * @return boolean
     */
    protected function setSkuCombinations(array $data, $update = true)
    {
        if (empty($data['form']) && empty($data['combination'])) {
            return false;
        }

        if ($update) {
            $this->sku->delete($data['product_id'], array('combinations' => true));
        }

        return $this->addSkuCombinations($data);
    }

    /**
     * Add SKUs for an array of product combinations
     * @param array $data
     * @return boolean
     */
    protected function addSkuCombinations(array $data)
    {
        if (empty($data['combination'])) {
            return false;
        }

        foreach ($data['combination'] as $combination) {

            if (empty($combination['fields'])) {
                continue;
            }

            if (!empty($combination['price'])) {
                $combination['price'] = $this->price->amount($combination['price'], $data['currency']);
            }

            $this->setCombinationFileId($combination, $data);

            $combination['store_id'] = $data['store_id'];
            $combination['product_id'] = $data['product_id'];
            $combination['combination_id'] = $this->sku->getCombinationId($combination['fields'], $data['product_id']);

            if (empty($combination['sku'])) {
                $combination['sku'] = $this->sku->generate($data['sku'], array('store_id' => $data['store_id']));
            }

            $this->sku->add($combination);
        }

        return true;
    }

    /**
     * Adds a file ID from uploaded images to the combination item
     * @param array $combination
     * @param array $data
     * @return array
     */
    protected function setCombinationFileId(array &$combination, array $data)
    {
        foreach ($data['images'] as $image) {
            if ($image['path'] === $combination['path']) {
                $combination['file_id'] = $image['file_id'];
            }
        }

        return $combination;
    }

    /**
     * Deletes and/or adds product option fields
     * @param array $data
     * @param boolean $update
     * @return boolean
     */
    protected function setOptions(array $data, $update = true)
    {
        if (empty($data['form']) && empty($data['combination'])) {
            return false;
        }

        if ($update) {
            $this->product_field->delete('option', $data['product_id']);
        }

        return $this->addOptions($data);
    }

    /**
     * Deletes and/or adds product attribute fields
     * @param array $data
     * @param boolean $update
     * @return boolean
     */
    protected function setAttributes(array $data, $update = true)
    {
        if (empty($data['form']) && empty($data['field']['attribute'])) {
            return false;
        }

        if ($update) {
            $this->product_field->delete('attribute', $data['product_id']);
        }

        return $this->addAttributes($data);
    }

    /**
     * Adds multiple options
     * @param array $data
     * @return boolean
     */
    protected function addOptions(array $data)
    {
        if (empty($data['combination'])) {
            return false;
        }

        foreach ($data['combination'] as $combination) {

            if (empty($combination['fields'])) {
                continue;
            }

            foreach ($combination['fields'] as $field_id => $field_value_id) {

                $options = array(
                    'type' => 'option',
                    'field_id' => $field_id,
                    'product_id' => $data['product_id'],
                    'field_value_id' => $field_value_id
                );

                $this->product_field->add($options);
            }
        }

        return true;
    }

    /**
     * Adds multiple attributes
     * @param array $data
     * @return boolean
     */
    protected function addAttributes(array $data)
    {
        if (empty($data['field']['attribute'])) {
            return false;
        }

        foreach ($data['field']['attribute'] as $field_id => $field_value_ids) {
            foreach ((array) $field_value_ids as $field_value_id) {

                $options = array(
                    'type' => 'attribute',
                    'field_id' => $field_id,
                    'product_id' => $data['product_id'],
                    'field_value_id' => $field_value_id
                );

                $this->product_field->add($options);
            }
        }

        return true;
    }

    /**
     * Returns a relative/absolute path for uploaded images
     * @param boolean $absolute
     * @return string
     */
    public function getImagePath($absolute = false)
    {
        $dirname = $this->config->get('product_image_dirname', 'product');

        if ($absolute) {
            return gplcart_path_absolute($dirname, GC_DIR_IMAGE);
        }

        return trim(substr(GC_DIR_IMAGE, strlen(GC_DIR_FILE)), '\\/') . "/$dirname";
    }

}
