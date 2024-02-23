<?php

namespace Talk\Discussion;

use Talk\Foundation\AbstractValidator;

class DiscussionValidator extends AbstractValidator
{
    protected $rules = [
        'title' => [
            'required',
            'min:3',
            'max:80'
        ]
    ];
}
