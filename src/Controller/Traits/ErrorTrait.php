<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Controller\Traits;

use Symfony\Component\HttpFoundation\Request;

trait ErrorTrait
{
    /**
     * @param   Request  $request
     *
     * @return string[]
     */
    public function parseError(Request $request): array
    {
        // Do not throw if errors are suppressed by @ operator
        // error_reporting() value for suppressed errors changed in PHP 8.0.0
        $error = '';
        $error_description = '';
        if ($request->query->has('error')) {
            $error = (string)$request->query->get('error');
        }

        if ($request->query->has('error_description')) {
            $error_description = (string)$request->query->get('error_description');
        }

        return [
            $error,
            $error_description,
        ];
    }
}
