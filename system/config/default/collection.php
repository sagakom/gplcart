<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */
return array(
    'product' => array(
        'title' => /* @text */'Product',
        'id_key' => 'product_id',
        'handlers' => array(
            'list' => array('gplcart\\core\\models\\Product', 'getList'),
            'validate' => array('gplcart\\core\\handlers\\validator\\components\\CollectionItem', 'validateProductCollectionItem'),
        ),
        'template' => array(
            'item' => 'product/item/grid',
            'list' => 'collection/list/product'
        ),
    ),
    'file' => array(
        'title' => /* @text */'File',
        'id_key' => 'file_id',
        'handlers' => array(
            'list' => array('gplcart\\core\\models\\File', 'getList'),
            'validate' => array('gplcart\\core\\handlers\\validator\\components\\CollectionItem', 'validateFileCollectionItem'),
        ),
        'template' => array(
            'item' => 'collection/item/file',
            'list' => 'collection/list/file'
        )
    ),
    'page' => array(
        'title' => /* @text */'Page',
        'id_key' => 'page_id',
        'handlers' => array(
            'list' => array('gplcart\\core\\models\\Page', 'getList'),
            'validate' => array('gplcart\\core\\handlers\\validator\\components\\CollectionItem', 'validatePageCollectionItem'),
        ),
        'template' => array(
            'item' => 'collection/item/page',
            'list' => 'collection/list/page'
        )
    )
);
