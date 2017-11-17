<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\core\models;

use gplcart\core\Hook,
    gplcart\core\Database;
use gplcart\core\models\Condition as ConditionModel;

/**
 * Manages basic behaviors and data related to triggers
 */
class Trigger
{

    /**
     * Database class instance
     * @var \gplcart\core\Database $db
     */
    protected $db;

    /**
     * Hook class instance
     * @var \gplcart\core\Hook $hook
     */
    protected $hook;

    /**
     * Condition model instance
     * @var \gplcart\core\models\Condition $condition
     */
    protected $condition;

    /**
     * @param Hook $hook
     * @param Database $db
     * @param ConditionModel $condition
     */
    public function __construct(Hook $hook, Database $db, ConditionModel $condition)
    {
        $this->db = $db;
        $this->hook = $hook;
        $this->condition = $condition;
    }

    /**
     * Returns an array of triggers or counts them
     * @param array $data
     * @return array|integer
     */
    public function getList(array $data = array())
    {
        $triggers = &gplcart_static(gplcart_array_hash(array('trigger.list' => $data)));

        if (isset($triggers)) {
            return $triggers;
        }

        $sql = 'SELECT *';

        if (!empty($data['count'])) {
            $sql = 'SELECT COUNT(trigger_id)';
        }

        $sql .= ' FROM triggers WHERE trigger_id > 0';

        $where = array();

        if (isset($data['status'])) {
            $sql .= ' AND status = ?';
            $where[] = (int) $data['status'];
        }

        if (isset($data['store_id'])) {
            $sql .= ' AND store_id = ?';
            $where[] = (int) $data['store_id'];
        }

        if (isset($data['weight'])) {
            $sql .= ' AND weight = ?';
            $where[] = (int) $data['weight'];
        }

        if (isset($data['name'])) {
            $sql .= ' AND name LIKE ?';
            $where[] = "%{$data['name']}%";
        }

        $allowed_order = array('asc', 'desc');
        $allowed_sort = array('name', 'status', 'store_id');

        if ((isset($data['sort']) && in_array($data['sort'], $allowed_sort))//
                && (isset($data['order']) && in_array($data['order'], $allowed_order))) {
            $sql .= " ORDER BY {$data['sort']} {$data['order']}";
        } else {
            $sql .= " ORDER BY weight ASC";
        }

        if (!empty($data['limit'])) {
            $sql .= ' LIMIT ' . implode(',', array_map('intval', $data['limit']));
        }

        if (!empty($data['count'])) {
            return (int) $this->db->fetchColumn($sql, $where);
        }

        $results = $this->db->fetchAll($sql, $where);

        $triggers = array();
        foreach ($results as $trigger) {

            $trigger['database'] = true;
            $trigger['data'] = unserialize($trigger['data']);

            if (!empty($trigger['data']['conditions'])) {
                gplcart_array_sort($trigger['data']['conditions']);
            }

            $triggers[$trigger['trigger_id']] = $trigger;
        }

        $this->hook->attach('trigger.list', $triggers, $this);
        return $triggers;
    }

    /**
     * Adds a trigger
     * @param array $data
     * @return integer
     */
    public function add(array $data)
    {
        $result = null;
        $this->hook->attach('trigger.add.before', $data, $result, $this);

        if (isset($result)) {
            return (int) $result;
        }

        $result = $this->db->insert('triggers', $data);
        $this->hook->attach('trigger.add.after', $data, $result, $this);
        return (int) $result;
    }

    /**
     * Load a trigger
     * @param integer $trigger_id
     * @return array
     */
    public function get($trigger_id)
    {
        $result = null;
        $this->hook->attach('trigger.get.before', $trigger_id, $result, $this);

        if (isset($result)) {
            return $result;
        }

        $sql = 'SELECT * FROM triggers WHERE trigger_id=?';
        $result = $this->db->fetch($sql, array($trigger_id), array('unserialize' => 'data'));
        $this->hook->attach('trigger.get.after', $trigger_id, $result, $this);
        return $result;
    }

    /**
     * Deletes a trigger
     * @param integer $trigger_id
     * @return boolean
     */
    public function delete($trigger_id)
    {
        $result = null;
        $this->hook->attach('trigger.delete.before', $trigger_id, $result, $this);

        if (isset($result)) {
            return (bool) $result;
        }

        $result = (bool) $this->db->delete('triggers', array('trigger_id' => $trigger_id));
        $this->hook->attach('trigger.delete.after', $trigger_id, $result, $this);
        return (bool) $result;
    }

    /**
     * Updates a trigger
     * @param integer $trigger_id
     * @param array $data
     * @return boolean
     */
    public function update($trigger_id, array $data)
    {
        $result = null;
        $this->hook->attach('trigger.update.before', $trigger_id, $data, $result, $this);

        if (isset($result)) {
            return (bool) $result;
        }

        $result = (bool) $this->db->update('triggers', $data, array('trigger_id' => $trigger_id));
        $this->hook->attach('trigger.update.after', $trigger_id, $data, $result, $this);
        return (bool) $result;
    }

    /**
     * Returns an array of triggered trigger IDs for the given context
     * @param array $options
     * @param array $data
     * @return array
     */
    public function getTriggered(array $options = array(), array $data = array())
    {
        $options += array('status' => 1);
        $triggers = (array) $this->getList($options);

        if (empty($triggers)) {
            return array();
        }

        $triggered = array();
        foreach ($triggers as $trigger) {
            if ($this->condition->isMet($trigger, $data)) {
                $triggered[] = $trigger['trigger_id'];
            }
        }

        return $triggered;
    }

}
