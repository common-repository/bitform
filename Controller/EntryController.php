<?php

namespace BitForm\Controller;

use BitForm\Context;
use BitForm\Utils\StringUtils;

class EntryController extends AbstractController
{

    private $entryValidator;
    private $entryRepository;
    private $entryNoteRepository;

    public function __construct()
    {
        parent::__construct();
        $this->entryValidator = Context::$entryValidator;
        $this->entryRepository = Context::$entryRepository;
        $this->entryNoteRepository = Context::$entryNoteRepository;
    }

    public function registerRoutes()
    {
        $this->registerRoute('/forms/(?P<formId>\d+)/entries', 'GET', 'getEntries', 'defaultPermissionCheck', $this->getPageParams());
        $this->registerRoute('/forms/(?P<formId>\d+)/entries/search', 'POST', 'searchEntries');
        $this->registerRoute('/forms/(?P<formId>\d+)/entries', 'POST', 'postEntries');
        $this->registerRoute('/forms/(?P<formId>\d+)/entries', 'DELETE', 'deleteEntries');
        $this->registerRoute('/forms/(?P<formId>\d+)/entries/(?P<entryId>\d+)', 'GET', 'getEntry');
        $this->registerRoute('/forms/(?P<formId>\d+)/entries/(?P<entryId>\d+)', 'PUT', 'putEntry');
        $this->registerRoute('/forms/(?P<formId>\d+)/entries/(?P<entryId>\d+)/notes', 'GET', 'getEntryNotes');
        $this->registerRoute('/forms/(?P<formId>\d+)/entries/(?P<entryId>\d+)/notes', 'POST', 'postEntryNotes');
        $this->registerRoute('/forms/(?P<formId>\d+)/entries/(?P<entryId>\d+)/notes', 'DELETE', 'deleteEntryNotes');
    }

    public function getEntries($request)
    {
        $formId = $request->get_param('formId');
        $pageNumber = $request->get_param('pageNumber');
        $pageSize = $request->get_param('pageSize');
        $entries = $this->entryRepository->findByFormId($formId, $pageNumber, $pageSize);
        $total = $this->entryRepository->countByFormId($formId);
        return $this->page($entries, $total);
    }

    public function searchEntries($request)
    {
        $formId = $this->getPathVariable($request, 'formId');
        $search = $request->get_json_params();
        $entries = $this->entryRepository->findByFormId($formId, $search['pageNumber'], $search['pageSize'], $search['sort']);
        $total = $this->entryRepository->countByFormId($formId);
        return $this->page($entries, $total);
    }

    public function postEntries($request)
    {
        $formId = $this->getPathVariable($request, 'formId');
        $entries = $request->get_json_params();
        $body = $request->get_body();
        $isMulti = StringUtils::isJsonArray($body);
        if (!$isMulti) {
            $entries = [$entries];
        }
        $invalid = $this->entryValidator->validateEntries($formId, $entries);
        if ($invalid) {
            $msg = __('Data submit failed', 'bitform');
            return $this->badRequest($msg, $invalid);
        }
        $entryIds = [];
        foreach ($entries as $entry) {
            $entryId = $this->entryRepository->createByFormData($formId, $entry);
            $entryIds[] = $entryId;
        }
        return $this->ok($isMulti ? $entryIds : $entryIds[0]);
    }

    public function deleteEntries($request)
    {
        $ids = $request->get_param('ids');
        $entryIds = explode(',', $ids);
        $rows = $this->entryRepository->deleteByIds($entryIds);
        return $this->ok($rows);
    }

    public function getEntry($request)
    {
        $entryId = $this->getPathVariable($request, 'entryId');
        $row = $this->entryRepository->findEntryDataById($entryId);
        return $this->ok($row);
    }

    public function putEntry($request)
    {
        $formId = $this->getPathVariable($request, 'formId');
        $entryId = $this->getPathVariable($request, 'entryId');
        $data = $request->get_json_params();
        $invalid = $this->entryValidator->validateEntry($formId, $data);
        if ($invalid) {
            $msg = __('Data submit failed', 'bitform');
            return $this->badRequest($msg, $invalid);
        }
        $row = $this->entryRepository->updateByEntryData($entryId, $data);
        return $this->ok($row);
    }

    public function getEntryNotes($request)
    {
        $entryId = $request->get_param('entryId');
        $notes = $this->entryNoteRepository->findByEntryId($entryId);
        return $this->ok($notes);
    }

    public function postEntryNotes($request)
    {
        $entryId = $request->get_param('entryId');
        $note = $request->get_param('note');
        $noteId = $this->entryNoteRepository->createByEntryNote($entryId, $note);
        return $this->ok($noteId);
    }

    public function deleteEntryNotes($request)
    {
        $ids = $request->get_param('ids');
        $noteIds = explode(',', $ids);
        $rows = $this->entryNoteRepository->deleteByIds($noteIds);
        return $this->ok($rows);
    }
}
