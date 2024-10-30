<?php

namespace BitForm;

use BitForm\Common\Permission;
use BitForm\Repository\FormRepository;
use BitForm\Repository\FormMetaRepository;
use BitForm\Repository\EntryDataRepository;
use BitForm\Repository\EntryNoteRepository;
use BitForm\Repository\EntryRepository;
use BitForm\Common\Notification;
use BitForm\Controller\EntryValidator;
use BitForm\Controller\FormController;
use BitForm\Controller\EntryController;
use BitForm\Controller\FileController;
use BitForm\Controller\PublicController;

class Context
{
    public static $formRepository;
    public static $formMetaRepository;
    public static $entryDataRepository;
    public static $entryNoteRepository;
    public static $entryRepository;
    public static $entryValidator;
    public static $notification;
    public static $formController;
    public static $entryController;
    public static $fileController;
    public static $publicController;

    private static function initRepositories()
    {
        self::$formRepository = new FormRepository();
        self::$formMetaRepository = new FormMetaRepository();
        self::$entryDataRepository = new EntryDataRepository();
        self::$entryNoteRepository = new EntryNoteRepository();
        self::$entryRepository = new EntryRepository();
    }

    public static function init()
    {
        self::initRepositories();
        self::$notification = new Notification();
        self::$entryValidator = new EntryValidator();
        self::$formController = new FormController();
        self::$entryController = new EntryController();
        self::$fileController = new FileController();
        self::$publicController = new PublicController();
    }

    public static function install()
    {
        Permission::install();
        self::initRepositories();
        self::$formRepository->createTable();
        self::$formMetaRepository->createTable();
        self::$entryDataRepository->createTable();
        self::$entryNoteRepository->createTable();
        self::$entryRepository->createTable();
    }

    public static function uninstall()
    {
        Permission::uninstall();
        self::initRepositories();
        self::$formRepository->dropTable();
        self::$formMetaRepository->dropTable();
        self::$entryDataRepository->dropTable();
        self::$entryNoteRepository->dropTable();
        self::$entryRepository->dropTable();
    }
}
