<?php

namespace BitForm\Repository;

use BitForm\Context;

class EntryDataRepository extends AbstractRepository
{

    public function __construct()
    {
        parent::__construct('entry_data');
    }

    protected function getCreateTableSql()
    {
        $charsetCollate = $this->getCharsetCollate();
        $sql = "CREATE TABLE $this->tableName (
      `entry_id` INT UNSIGNED NOT NULL,
      `item_id` VARCHAR(100) NOT NULL,
      `value` VARCHAR(2000) NOT NULL,
      PRIMARY KEY (`entry_id`, `item_id`)
    ) $charsetCollate;";
        return $sql;
    }

    public function createByEntryData($entryId, $data)
    {
        if (!count($data)) {
            return;
        }
        $values = array();
        $placeholders = array();
        foreach ($data as $itemId => $value) {
            $placeholders[] = "(%d, %s, %s)";
            array_push($values, $entryId, $itemId, $value);
        }
        $v = $this->prepare(implode(', ', $placeholders), $values);
        $sql = "INSERT INTO $this->tableName (`entry_id`, `item_id`, `value`) VALUES $v ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);";
        return $this->query($sql);
    }

    public function findByEntryId($entryId)
    {
        $sql = "SELECT * FROM $this->tableName WHERE `entry_id` = %d";
        $sql = $this->prepare($sql, $entryId);
        return $this->find($sql);
    }

    public function findByEntryIdIn($entryIds)
    {
        $ids = $this->prepareIds($entryIds);
        if (!$ids) {
            return array();
        }
        $sql = "SELECT * FROM $this->tableName WHERE `entry_id` IN ($ids)";
        return $this->find($sql);
    }

    public function countByFormItemData($formId, $itemId, $data)
    {
        $entryDataTable = $this->tableName;
        $entryTable = Context::$entryRepository->getTableName();
        $sql = "SELECT COUNT(*) FROM $entryDataTable JOIN $entryTable ON $entryTable.`id` = $entryDataTable.`entry_id` 
                WHERE $entryTable.`form_id` = %d 
                AND $entryDataTable.`item_id` = %s 
                AND $entryDataTable.`value` = %s";
        $sql = $this->prepare($sql, $formId, $itemId, $data);
        return (int) $this->findVar($sql);
    }
}
