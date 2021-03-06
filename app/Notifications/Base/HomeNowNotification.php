<?php

/**
 * Base - Any modification needs to be approved, except the space inside the block of TODO
 */

namespace App\Notifications\Base;

use App\Utils\ClientSettings\Traits\HomeIndependentClientTrait;

abstract class HomeNowNotification extends NowNotification
{
    use HomeIndependentClientTrait;
}