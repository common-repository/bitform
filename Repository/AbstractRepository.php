<?php

namespace BitForm\Repository;

use BitForm\Exception\PersistenceException;
use BitForm\Utils\StringUtils;

abstract class AbstractRepository
{

    protected $wpdb;
    protected $tableName;

    public function __construct($tableName)
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tableName = $wpdb->prefix . 'bitform_' . $tableName;
    }

    protected function getCharsetCollate()
    {
        return $this->wpdb->get_charset_collate();
    }

    protected abstract function getCreateTableSql();

    protected function prepare($query, ...$args)
    {
        return $this->wpdb->prepare($query, ...$args);
    }

    protected function prepareIds($ids)
    {
        $nums = StringUtils::parseIds($ids);
        $escNums = array_map('esc_sql', $nums);
        return implode(',', $escNums);
    }

    protected function query($query)
    {
        return $this->wpdb->query($query);
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function createTable()
    {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $sql = $this->getCreateTableSql();
        dbDelta($sql);
    }

    public function dropTable()
    {
        $this->query("DROP TABLE IF EXISTS `$this->tableName`");
    }

    public function create($data)
    {
        $this->wpdb->insert($this->tableName, $data);
        if ($this->wpdb->last_error) {
            throw new PersistenceException($this->wpdb->last_error);
        }
        return $this->wpdb->insert_id;
    }

    public function update($id, $data)
    {
        $rows = $this->wpdb->update($this->tableName, $data, array(
            'id' => $id
        ));
        if ($this->wpdb->last_error) {
            throw new PersistenceException($this->wpdb->last_error);
        }
        return $rows;
    }

    public function findById($id)
    {
        $sql = "SELECT * FROM $this->tableName WHERE `id` = %d";
        $sql = $this->wpdb->prepare($sql, $id);
        return $this->wpdb->get_row($sql, ARRAY_A);
    }

    public function findVarById($var, $id)
    {
        $sql = "SELECT `$var` FROM $this->tableName WHERE `id` = %d";
        $sql = $this->wpdb->prepare($sql, $id);
        return $this->wpdb->get_var($sql);
    }

    public function find($sql)
    {
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function findVar($sql)
    {
        return $this->wpdb->get_var($sql);
    }

    public function findRow($sql)
    {
        return $this->wpdb->get_row($sql, ARRAY_A);
    }

    public function existsById($id)
    {
        return $this->findVarById('id', $id) !== null;
    }
}
