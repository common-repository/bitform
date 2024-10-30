<?php

namespace BitForm\Controller;

use BitForm\Common\JsonResponse;
use BitForm\Common\Permission;

abstract class AbstractController
{

    const API_NAMESPACE = 'bitform';

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'registerRoutes'));
    }

    abstract public function registerRoutes();

    public function defaultPermissionCheck()
    {
        return Permission::hasAllCapabilities();
    }

    public function anonymous()
    {
        return true;
    }

    protected function registerRoute($endpoint, $method, $callback, $permission = 'defaultPermissionCheck', $args = array())
    {
        $options = array(
            'methods' => $method,
            'callback' => array($this, $callback),
            'permission_callback' => is_array($permission) ? $permission : array($this, $permission),
            'args' => $args,
        );
        register_rest_route(self::API_NAMESPACE, $endpoint, $options);
    }

    protected function getPathVariable($request, $name)
    {
        return $request->get_url_params()[$name];
    }

    protected function getPathId($request)
    {
        return $this->getPathVariable($request, 'id');
    }

    protected function ok($data = null)
    {
        return new \WP_REST_Response($data, 200);
    }

    protected function json($data)
    {
        return new JsonResponse($data, 200);
    }

    protected function page($list, $total = null)
    {
        return $this->ok(array(
            'list' => $list,
            'total' => $total
        ));
    }

    protected function badRequest($msg, $data = null)
    {
        return new \WP_REST_Response(array(
            'message' => $msg,
            'data' => $data
        ), 400);
    }

    protected function getPageParams()
    {
        return array(
            'pageNumber' => array(
                'description'       => __('Current page of the collection.'),
                'type'              => 'integer',
                'default'           => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
                'minimum'           => 1,
            ),
            'pageSize' => array(
                'description'       => __('Maximum number of items to be returned in result set.'),
                'type'              => 'integer',
                'default'           => 20,
                'minimum'           => 1,
                'maximum'           => 1000,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ),
        );
    }
}
