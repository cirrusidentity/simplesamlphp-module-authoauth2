<?php

declare(strict_types=1);

namespace SimpleSAML\Module\authoauth2\Codebooks;

enum Oauth2ErrorsEnum: string
{
    case AccessDenied = 'access_denied';
    case ConsentRequired = 'consent_required';
    case InvalidScope = 'invalid_scope';
    case UserCancelledAuthorize = 'user_cancelled_authorize';
    case UserCancelledLogin = 'user_cancelled_login';
    case UserDenied = 'user_denied';
}
