<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a CarbonListing is asked to transition to a status that is
 * not allowed from its current status (per the state-machine table in
 * CarbonListing::ALLOWED_TRANSITIONS), or when the saving listener detects
 * a direct status assignment that bypasses transitionTo() with an
 * invalid pairing.
 */
class InvalidStateTransition extends RuntimeException
{
}
