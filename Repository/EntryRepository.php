<?php

namespace BitForm\Repository;

use BitForm\Context;
use BitForm\Utils\DateTimeUtils;

class EntryRepository extends AbstractRepository
{
    private $entryDataRepository;

    public function __construct()
    {
        parent::__construct('entries');
        $this->entryDataRepository = Context::$entryDataRepository;
    }

    protected function getCreateTableSql()
    {
        $charsetCollate = $this->getCharsetCollate();
        $sql = "CREATE TABLE $this->tableName (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `form_id` INT UNSIGNED NOT NULL,
      `created_by` INT UNSIGNED NULL DEFAULT NULL,
      `modified_by` INT UNSIGNED NULL DEFAULT NULL,
      `created_at` DATETIME NOT NULL,
      `modified_at` DATETIME DEFAULT NULL,
      `active` tinyint(1) NOT NULL DEFAULT '1',
      PRIMARY KEY (`id`),
      INDEX `form_id_index`(`form_id`)
    ) $charsetCollate;";
        return $sql;
    }

    public function createByFormData($formId, $data)
    {
        $now = DateTimeUtils::currentDateTime();
        $entry = array(
            'form_id' => $formId,
            'created_by' => get_current_user_id(),
            'created_at' => $now
        );
        $entryId = $this->create($entry);
        $this->entryDataRepository->createByEntryData($entryId, $data);
        return $entryId;
    }

    public function updateByEntryData($entryId, $data)
    {
        $audit = array(
            'modified_by' => get_current_user_id(),
            'modified_at' => DateTimeUtils::currentDateTime()
        );
        $this->update($entryId, $audit);
        return $this->entryDataRepository->createByEntryData($entryId, $data);
    }

    public function findEntryDataById($entryId)
    {
        $entry = null;
        $row = $this->findById($entryId);
        if ($row !== null) {
            $entry = [
                'id' => $row['id'],
                'createdAt' => $row['created_at'],
                'modifiedAt' => $row['modified_at'],
            ];
            $data = $this->entryDataRepository->findByEntryId($entryId);
            foreach ($data as $v) {
                $key = $v['item_id'];
                $value = $v['value'];
                $entry[$key] = $value;
            }
        }
        return $entry;
    }

    public function findByFormId($formId, $pageNumber, $pageSize, $sort = [])
    {
        $sortField = 'id';
        $sortOrder = 'DESC';
        if (in_array($sort['field'], ['createdAt', 'modifiedAt'])) {
            $sortField = $sort['field'];
            if ($sort['order'] === 'ascend') {
                $sortOrder = 'ASC';
            }
        }
        $sql = "SELECT `id`, `created_at` AS `createdAt`, `modified_at` AS `modifiedAt` FROM $this->tableName WHERE `form_id` = %d ORDER BY `$sortField` $sortOrder LIMIT %d OFFSET %d";
        $sql = $this->prepare($sql, $formId, $pageSize, ($pageNumber - 1) * $pageSize);
        $entries = $this->find($sql);
        $ids = [];
        $entryMap = [];
        foreach ($entries as &$entry) {
            $entryId = $entry['id'];
            $ids[] = $entryId;
            $entryMap[$entryId] = &$entry;
        }
        $data = $this->entryDataRepository->findByEntryIdIn($ids);
        foreach ($data as $v) {
            $entryId = $v['entry_id'];
            $key = $v['item_id'];
            $value = $v['value'];
            $entryMap[$entryId][$key] = $value;
        }
        return $entries;
    }

    public function countByFormId($formId)
    {
        $sql = "SELECT COUNT(*) FROM $this->tableName WHERE `form_id` = %d";
        $sql = $this->prepare($sql, $formId);
        return (int) $this->findVar($sql);
    }

    public function countByFormIdAndCreatedAt($formId, $start, $end)
    {
        $sql = "SELECT COUNT(*) FROM $this->tableName WHERE `form_id` = %d AND `active` = 1";
        $time = [];
        if ($start) {
            $sql .= " AND `created_at` >= %s";
            $time[] = $start;
        }
        if ($end) {
            $sql .= " AND `created_at` < %s";
            $time[] = $end;
        }
        $sql = $this->prepare($sql, $formId, ...$time);
        return (int) $this->findVar($sql);
    }

    public function deleteByIds($ids)
    {
        $ids = $this->prepareIds($ids);
        if (!$ids) {
            return;
        }
        $entryDataTable = $this->entryDataRepository->tableName;
        $this->query("DELETE FROM $entryDataTable WHERE `entry_id` IN ($ids)");
        return $this->query("DELETE FROM $this->tableName WHERE `id` IN ($ids)");
    }
}
