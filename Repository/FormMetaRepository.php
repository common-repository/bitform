<?php

namespace BitForm\Repository;

use BitForm\Utils\JsonUtils;

class FormMetaRepository extends AbstractRepository
{

    public function __construct()
    {
        parent::__construct('form_meta');
    }

    protected function getCreateTableSql()
    {
        $charsetCollate = $this->getCharsetCollate();
        $sql = "CREATE TABLE $this->tableName (
      `form_id` INT UNSIGNED NOT NULL,
      `key` VARCHAR(100) NOT NULL,
      `value` LONGTEXT NOT NULL,
      PRIMARY KEY  (`form_id`, `key`)
    ) $charsetCollate;";
        return $sql;
    }

    public function createOrUpdate($formId, $data)
    {
        if (!count($data)) {
            return;
        }
        $values = array();
        $placeholders = array();
        foreach ($data as $key => $value) {
            $placeholders[] = "(%d, %s, %s)";
            $setting = is_array($value) ? JsonUtils::stringify($value) : $value;
            array_push($values, $formId, $key, $setting);
        }
        $v = $this->prepare(implode(', ', $placeholders), $values);
        $sql = "INSERT INTO $this->tableName (`form_id`, `key`, `value`) VALUES $v ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);";
        return $this->query($sql);
    }

    public function findAllByFormIdAndScope($formId, $scope)
    {
        $sql = "SELECT * FROM $this->tableName WHERE `form_id` = %d AND `key` LIKE %s";
        $sql = $this->prepare($sql, $formId, $scope . '%');
        $rows = $this->find($sql);
        $formMeta = [];
        foreach ($rows as $row) {
            $formMeta[$row['key']] = $row['value'];
        }
        return $formMeta;
    }
}
