<?php

namespace BitForm\Repository;

use BitForm\Context;
use BitForm\Utils\DateTimeUtils;
use BitForm\Utils\JsonUtils;

class FormRepository extends AbstractRepository
{

    public function __construct()
    {
        parent::__construct('forms');
    }

    protected function getCreateTableSql()
    {
        $charsetCollate = $this->getCharsetCollate();
        $sql = "CREATE TABLE $this->tableName (
      `id` INT NOT NULL AUTO_INCREMENT,
      `name` VARCHAR(255) NOT NULL,
      `title` VARCHAR(255) NOT NULL,
      `config` INT NOT NULL DEFAULT '1',
      `json` LONGTEXT NOT NULL,
      `created_by` INT NOT NULL,
      `modified_by` INT NULL DEFAULT NULL,
      `created_at` DATETIME NOT NULL,
      `modified_at` DATETIME DEFAULT NULL,
      `active` tinyint(1) NOT NULL DEFAULT '1',
      PRIMARY KEY (`id`)
    ) $charsetCollate;";
        return $sql;
    }

    public function createByJson($jsonArr)
    {
        $now = DateTimeUtils::currentDateTime();
        $data = array(
            'name' => $jsonArr['id'],
            'title' => $jsonArr['title'],
            'json' => '',
            'created_by' => get_current_user_id(),
            'created_at' => $now
        );
        $id = $this->create($data);
        $jsonArr['oid'] = $id;
        $this->update($id, array('json' => JsonUtils::stringify($jsonArr)));
        return $jsonArr;
    }

    public function updateByActive($id, $active)
    {
        $now = DateTimeUtils::currentDateTime();
        $data = array(
            'modified_by' => get_current_user_id(),
            'modified_at' => $now,
            'active' => $active
        );
        return $this->update($id, $data);
    }

    public function deleteById($id, $force)
    {
        $id = $this->prepare('%d', $id);
        if (!$force) {
            return $this->updateByActive($id, 0);
        }
        $formTable = $this->tableName;
        $formMetaTable = Context::$formMetaRepository->getTableName();
        $entryTable = Context::$entryRepository->getTableName();
        $entryDataTable = Context::$entryDataRepository->getTableName();
        $entryNoteTable = Context::$entryNoteRepository->getTableName();
        $this->query("DELETE $entryDataTable FROM $entryDataTable JOIN $entryTable ON $entryTable.`id` = $entryDataTable.`entry_id` WHERE $entryTable.`form_id` = $id");
        $this->query("DELETE $entryNoteTable FROM $entryNoteTable JOIN $entryTable ON $entryTable.`id` = $entryNoteTable.`entry_id` WHERE $entryTable.`form_id` = $id");
        $this->query("DELETE FROM $entryTable WHERE `form_id` = $id");
        $this->query("DELETE FROM $formMetaTable WHERE `form_id` = $id");
        return $this->query("DELETE FROM $formTable WHERE `id` = $id");
    }

    public function updateByJson($id, $jsonArr, $jsonStr)
    {
        $now = DateTimeUtils::currentDateTime();
        $data = array(
            'name' => $jsonArr['id'],
            'title' => $jsonArr['title'],
            'json' => $jsonStr,
            'modified_by' => get_current_user_id(),
            'modified_at' => $now
        );
        return $this->update($id, $data);
    }

    public function updateByConfig($id, $config)
    {
        $data = array(
            'config' => $config,
            'modified_by' => get_current_user_id(),
            'modified_at' => DateTimeUtils::currentDateTime()
        );
        return $this->update($id, $data);
    }

    public function findAllFormTitles($active = 1)
    {
        $active = $active ? 1 : 0;
        $sql = "SELECT forms.id, forms.title FROM $this->tableName forms WHERE forms.active = $active ORDER BY forms.id DESC";
        return $this->find($sql);
    }

    public function findForms($active = 1, $asc = false)
    {
        $active = $active ? 1 : 0;
        $sort = $asc ? 'ASC' : 'DESC';
        $today = DateTimeUtils::getDate() . ' 00:00:00';
        $tommorow = DateTimeUtils::getDate(1) . ' 00:00:00';
        $entriesTableName = Context::$entryRepository->getTableName();
        $sql = "SELECT forms.id, forms.title, COALESCE(t1.c, 0) AS today, COALESCE(t2.c, 0) AS total FROM $this->tableName forms 
        LEFT JOIN ( SELECT form_id, COUNT(*) AS c FROM $entriesTableName WHERE created_at > '$today' AND created_at < '$tommorow' AND active = 1 GROUP BY form_id ) t1 ON forms.id = t1.form_id 
        LEFT JOIN ( SELECT form_id, COUNT(*) AS c FROM $entriesTableName WHERE active = 1 GROUP BY form_id ) t2 ON forms.id = t2.form_id 
        WHERE forms.active = $active ORDER BY forms.id $sort";
        return $this->find($sql);
    }

    public function findConfigById($id)
    {
        $config = $this->findVarById('config', $id);
        return $config === null ? null : (int) $config;
    }
}
