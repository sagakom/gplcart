<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\core\handlers\validator\elements;

use gplcart\core\handlers\validator\Element as ElementValidator;

/**
 * Methods to validate single elements
 */
class Common extends ElementValidator
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Validates field/value is not empty
     * @param array $submitted
     * @param array $options
     * @return boolean
     */
    public function required(array $submitted, array $options)
    {
        $value = gplcart_array_get_value($submitted, $options['field']);

        if (empty($value)) {
            return $this->setErrorRequired($options['field'], $options['label']);
        }
        return true;
    }

    /**
     * Validates field/value is numeric
     * @param array $submitted
     * @param array $options
     * @return boolean
     */
    public function numeric(array $submitted, array $options)
    {
        $value = gplcart_array_get_value($submitted, $options['field']);

        if (is_numeric($value)) {
            return true;
        }
        return $this->setErrorNumeric($options['field'], $options['label']);
    }

    /**
     * Validates field/value length is in range
     * @param array $submitted
     * @param array $options
     * @return boolean
     */
    public function length(array $submitted, array $options)
    {
        $value = gplcart_array_get_value($submitted, $options['field']);
        $length = mb_strlen($value);

        list($min, $max) = $options['arguments'] + array(1, 255);

        if ($min <= $length && $length <= $max) {
            return true;
        }
        return $this->setErrorLengthRange($options['field'], $options['label'], $min, $max);
    }

    /**
     * Validates field/value matches a regexp pattern
     * @param array $submitted
     * @param array $options
     * @return boolean
     */
    public function regexp(array $submitted, array $options)
    {
        $value = gplcart_array_get_value($submitted, $options['field']);
        if (empty($options['arguments']) || preg_match(reset($options['arguments']), $value) !== 1) {
            return $this->setErrorInvalidValue($options['field'], $options['label']);
        }
        return true;
    }

}
