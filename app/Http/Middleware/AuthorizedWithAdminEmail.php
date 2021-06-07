<?php

/**
 * Base - Any modification needs to be approved, except the space inside the block of TODO
 */

namespace App\Http\Middleware;

class AuthorizedWithAdminEmail extends AuthorizedWithUserEmail
{
    use AdminMiddlewareTrait;
}