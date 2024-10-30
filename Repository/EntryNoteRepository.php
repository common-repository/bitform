<?php

namespace BitForm\Repository;

use BitForm\Utils\DateTimeUtils;

class EntryNoteRepository extends AbstractRepository
{

    public function __construct()
    {
        parent::__construct('entry_note');
    }

    protected function getCreateTableSql()
    {
        $charsetCollate = $this->getCharsetCollate();
        $sql = "CREATE TABLE $this->tableName (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `entry_id` INT UNSIGNED NOT NULL,
      `content` VARCHAR(2000) NOT NULL,
      `created_by` INT UNSIGNED NULL DEFAULT NULL,
      `created_at` DATETIME NOT NULL,
      PRIMARY KEY (`id`),
      INDEX `entry_id_index`(`entry_id`)
    ) $charsetCollate;";
        return $sql;
    }

    public function createByEntryNote($entryId, $note)
    {
        $now = DateTimeUtils::currentDateTime();
        $entry = array(
            'entry_id' => $entryId,
            'content' => $note,
            'created_by' => get_current_user_id(),
            'created_at' => $now
        );
        return $this->create($entry);
    }

    public function findByEntryId($entryId)
    {
        $usersTable = $this->wpdb->users;
        $sql = "SELECT n.`id`, n.`content`, n.`created_at` AS `createdAt`, u.`display_name` AS `user` 
                FROM $this->tableName n LEFT JOIN $usersTable u ON n.`created_by` = u.`ID` 
                WHERE n.`entry_id` = %d ORDER BY n.`id` DESC";
        $sql = $this->prepare($sql, $entryId);
        return $this->find($sql);
    }

    public function deleteByIds($ids)
    {
        $ids = $this->prepareIds($ids);
        if (!$ids) {
            return;
        }
        return $this->query("DELETE FROM $this->tableName WHERE `id` IN ($ids)");
    }
}
