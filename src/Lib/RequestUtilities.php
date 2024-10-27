<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Lib;

use Symfony\Component\HttpFoundation\Request;

class RequestUtilities
{
    /**
     * @param   Request  $request
     *
     * @return array
     */
    public static function getRequestParams(Request $request): array
    {
        $params = [];
        if ($request->isMethod('GET')) {
            $params = $request->query->all();
        } elseif ($request->isMethod('POST')) {
            $params = $request->request->all();
        }

        return $params;
    }
}
