<?php

namespace app\modules\geo\services;

/**
 * Thrown when a distance cannot be computed. The message is the Google status or a short code; callers show a
 * neutral text to the user and the details are written to the "geo" log category.
 */
class DistanceUnavailableException extends \RuntimeException
{
    /** @var string Google status or short code, for logs and support (not shown to riders) */
    public $reason;

    // [doc:start] __construct - Construct
    public function __construct($reason)
    {
        $this->reason = $reason;
        parent::__construct('We could not calculate the distance right now. Please try again in a few minutes.');
    }
    // [doc:end] __construct
}
