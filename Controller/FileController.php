<?php

namespace BitForm\Controller;

use BitForm\Context;
use BitForm\Utils\FileUtils;

class FileController extends AbstractController
{

    private $mediaController;
    private $formRepository;

    public function __construct()
    {
        $this->mediaController = new \WP_REST_Attachments_Controller('attachment');
        $this->formRepository = Context::$formRepository;
        parent::__construct();
    }

    public function registerRoutes()
    {
        register_rest_route(self::API_NAMESPACE, '/forms/media', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'getMedia'),
                'permission_callback' => array($this->mediaController, 'get_items_permissions_check'),
                'args'                => $this->mediaController->get_collection_params(),
            ),
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'postMedia'),
                'permission_callback' => array($this->mediaController, 'create_item_permissions_check'),
                'args'                => $this->mediaController->get_endpoint_args_for_item_schema(\WP_REST_Server::CREATABLE),
            ),
            'schema' => array($this->mediaController, 'get_public_item_schema'),
        ));
        register_rest_route(self::API_NAMESPACE, '/forms/media/(?P<id>[\d]+)', array(
            array(
                'methods'             => \WP_REST_Server::DELETABLE,
                'callback'            => array($this->mediaController, 'delete_item'),
                'permission_callback' => array($this->mediaController, 'delete_item_permissions_check'),
                'args'                => array(
                    'force' => array(
                        'description' => __('Whether to bypass trash and force deletion.'),
                        'type'        => 'boolean',
                        'default'     => true,
                    ),
                ),
            ),
            'schema' => array($this->mediaController, 'get_public_item_schema'),
        ));
        $this->registerRoute('/forms/(?P<id>\d+)/files', 'POST', 'postFiles');
    }

    private function parseAttachment($attachment)
    {
        return array(
            'id' => $attachment['id'],
            'type' => strtoupper($attachment['media_type']),
            'url' => $attachment['source_url']
        );
    }

    public function getMedia($request)
    {
        $response = $this->mediaController->get_items($request);
        $attachments = $response->get_data();
        $data = array();
        foreach ($attachments as $attachment) {
            $data[] = $this->parseAttachment($attachment);
        }
        $response->set_data($data);
        return $response;
    }

    public function postMedia($request)
    {
        $response = $this->mediaController->create_item($request);
        $attachment = $response->get_data();
        $data = $this->parseAttachment($attachment);
        $response->set_data($data);
        return $response;
    }

    public function postFiles($request)
    {
        $formId = $this->getPathId($request);
        $isValid = $this->formRepository->existsById($formId);
        if (!$isValid) {
            $msg = __('Invalid form', 'bitform');
            return $this->badRequest($msg);
        }
        $files = $request->get_file_params();
        $file = $files['file'];
        $originFileName = $file['name'];
        $extension = FileUtils::getExtension($originFileName);
        if (!$extension) {
            $msg = __('Invalid file type', 'bitform');
            return $this->badRequest($msg);
        }
        $fileDir = FileUtils::getFormDir($formId);
        $newFileName = FileUtils::getRandomFileName() . '.' . $extension;
        move_uploaded_file($file['tmp_name'], $fileDir['basedir'] . $newFileName);
        return $newFileName;
    }
}
