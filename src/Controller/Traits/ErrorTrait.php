<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Controller\Traits;

use Symfony\Component\HttpFoundation\Request;
use SimpleSAML\Module\authoauth2\Lib\RequestUtilities;

trait ErrorTrait
{
    /**
     * @param   Request  $request
     *
     * @return string[]
     */
    public function parseError(Request $request): array
    {
        $requestParams = RequestUtilities::getRequestParams($request);
        // Do not throw if errors are suppressed by @ operator
        // error_reporting() value for suppressed errors changed in PHP 8.0.0
        $error = '';
        $error_description = '';
        if (isset($requestParams['error'])) {
            $error = (string)$requestParams['error'];
        }

        if (isset($requestParams['error_description'])) {
            $error_description = (string)$requestParams['error_description'];
        }

        return [
            $error,
            $error_description,
        ];
    }
}
