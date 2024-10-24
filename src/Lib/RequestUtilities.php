<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Lib;

use Generator;
use Symfony\Component\HttpFoundation\Request;

class RequestUtilities
{
    /**
     * @param   Request  $request
     *
     * @return Generator
     */
    public static function parseRequestGenerator(Request $request): Generator
    {
        if ($request->isMethod('GET')) {
            foreach ($request->query->all() as $key => $value) {
                yield $key => $value;
            }
        } elseif ($request->isMethod('POST')) {
            foreach ($request->request->all() as $key => $value) {
                yield $key => $value;
            }
        } else {
            // If it is neither a GET or a POST then yield nothing
            yield;
        }
    }

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
