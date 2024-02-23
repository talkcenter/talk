<?php

namespace Talk\Api;

use Talk\Foundation\AbstractValidator;

class ForgotPasswordValidator extends AbstractValidator
{
    /**
     * {@inheritdoc}
     */
    protected $rules = [
        'email' => ['required', 'email']
    ];
}
