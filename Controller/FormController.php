<?php

namespace BitForm\Controller;

use BitForm\Context;
use BitForm\Constant\FormMetaConstants;
use BitForm\Utils\JsonUtils;

class FormController extends AbstractController
{

    private $formRepository;
    private $formMetaRepository;

    public function __construct()
    {
        parent::__construct();
        $this->formRepository = Context::$formRepository;
        $this->formMetaRepository = Context::$formMetaRepository;
    }

    public function registerRoutes()
    {
        $this->registerRoute('/forms', 'GET', 'getForms');
        $this->registerRoute('/forms', 'POST', 'postForms');
        $this->registerRoute('/forms/(?P<id>\d+)', 'GET', 'getForm');
        $this->registerRoute('/forms/(?P<id>\d+)', 'PUT', 'putForm');
        $this->registerRoute('/forms/(?P<id>\d+)', 'POST', 'copyForm');
        $this->registerRoute('/forms/(?P<id>\d+)', 'DELETE', 'deleteForm', 'defaultPermissionCheck', array(
            'force' => array(
                'description' => __('Whether to bypass trash and force deletion.'),
                'type'        => 'boolean',
                'default'     => false,
            ),
        ));
        $this->registerRoute('/forms/(?P<id>\d+)/setting', 'GET', 'getFormSetting');
        $this->registerRoute('/forms/(?P<id>\d+)/setting', 'PUT', 'putFormSetting');
    }

    public function getForms($request)
    {
        $data = $this->formRepository->findForms();
        return $this->ok($data);
    }

    public function postForms($request)
    {
        $jsonArr = $request->get_json_params();
        $data = $this->formRepository->createByJson($jsonArr);
        return $this->ok($data);
    }

    public function getForm($request)
    {
        $id = $this->getPathId($request);
        $data = $this->formRepository->findById($id);
        return $this->json($data['json']);
    }

    public function putForm($request)
    {
        $id = $this->getPathId($request);
        $jsonArr = $request->get_json_params();
        $jsonStr = $request->get_body();
        $this->formRepository->updateByJson($id, $jsonArr, $jsonStr);
        return $this->json($jsonStr);
    }

    public function copyForm($request)
    {
        $id = $this->getPathId($request);
        $form = $this->formRepository->findById($id);
        $jsonArr = JsonUtils::parse($form['json'], true);
        $data = $this->formRepository->createByJson($jsonArr);
        return $this->ok($data);
    }

    public function deleteForm($request)
    {
        $id = $this->getPathId($request);
        $force = $request->get_param('force');
        $data = $this->formRepository->deleteById($id, $force);
        return $this->ok($data);
    }

    public function getFormSetting($request)
    {
        $id = $this->getPathId($request);
        $scope = $request->get_param('scope');
        $meta = FormMetaConstants::PREFIXES[$scope];
        if (!$meta) {
            $msg = __('Invalid scope', 'bitform');
            return $this->badRequest($msg);
        }
        $config = $this->formRepository->findConfigById($id);
        if ($config === null) {
            $msg = __('Invalid form', 'bitform');
            return $this->badRequest($msg);
        }
        $configArr = FormMetaConstants::parseConfig($config, $meta);
        $data = $this->formMetaRepository->findAllByFormIdAndScope($id, $scope);
        $data['config'] = $configArr;
        $data['oid'] = $id;
        return $this->ok($data);
    }

    public function putFormSetting($request)
    {
        $id = (int) $this->getPathId($request);
        $scope = $request->get_param('scope');
        $meta = FormMetaConstants::PREFIXES[$scope];
        if (!$meta) {
            $msg = __('Invalid scope', 'bitform');
            return $this->badRequest($msg);
        }
        $config = $this->formRepository->findConfigById($id);
        if ($config === null) {
            $msg = __('Invalid form', 'bitform');
            return $this->badRequest($msg);
        }

        $jsonArr = $request->get_json_params();
        $detail = $jsonArr['detail'];
        $updatedDetail = [];
        foreach ($meta as $v) {
            $name = $v['name'];
            $key = "$scope.$name";
            $value = $detail[$name];
            if ($value !== null) {
                $updatedDetail[$key] = $value;
            }
        }
        $this->formMetaRepository->createOrUpdate($id, $updatedDetail);

        $configArr = $jsonArr['config'];
        $updatedConfig = FormMetaConstants::updateConfig($config, $meta, $configArr);
        $this->formRepository->updateByConfig($id, $updatedConfig);
    }
}
