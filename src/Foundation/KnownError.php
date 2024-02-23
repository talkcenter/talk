<?php

namespace Talk\Foundation;

/**
 * An exception that has a well-known meaning in a Talk application.
 *
 * We use these exceptions as a mechanism to quickly bubble up errors from the
 * domain layer. Using the {@see \Talk\Foundation\ErrorHandling\Registry},
 * these will then be mapped to error "types" and, if relevant, HTTP statuses.
 *
 * Exceptions implementing this interface can implement their own logic to
 * determine their own type (usually a hardcoded value).
 */
interface KnownError
{
    /**
     * Determine the exception's type.
     *
     * This should be a short, precise identifier for the error that can be
     * exposed to users as an error code. Furthermore, it can be used to find
     * appropriate error messages in translations or views to render pretty
     * error pages.
     *
     * Different exception classes are allowed to return the same status code,
     * e.g. when they have similar semantic meaning to the end user, but are
     * thrown by different subsystems.
     *
     * @return string
     */
    public function getType(): string;
}
