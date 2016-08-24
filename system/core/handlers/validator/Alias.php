<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace core\handlers\validator;

use core\models\Alias as ModelsAlias;
use core\models\Language as ModelsLanguage;

/**
 * Provides methods to validate url alias data
 */
class Alias
{

    /**
     * Language model instance
     * @var \core\models\Language $language
     */
    protected $language;

    /**
     * Alias model instance
     * @var \core\models\Alias $alias
     */
    protected $alias;

    /**
     * Constructor
     * @param ModelsLanguage $language
     * @param ModelsAlias $alias
     */
    public function __construct(ModelsLanguage $language, ModelsAlias $alias)
    {
        $this->alias = $alias;
        $this->language = $language;
    }

    /**
     * Checks if an alias exists in the database
     * @param string $alias
     * @param array $options
     * @return boolean|string
     */
    public function unique($alias, array $options = array())
    {
        if (!isset($alias) || $alias === '') {
            return true;
        }

        if (isset($options['data']['alias']) && ($options['data']['alias'] === $alias)) {
            return true;
        }
        
        $exists = $this->alias->exists($alias);

        if (empty($exists)) {
            return true;
        }

        return $this->language->text('URL alias already exists');
    }

}
