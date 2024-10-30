<?php

namespace BitForm\Common;

class JsonResponse extends \WP_REST_Response
{

    public static function serve($served, $response, $request, $server)
    {
        if ($response instanceof self) {
            echo $response->get_data();
            return true;
        }
        return $served;
    }
}
