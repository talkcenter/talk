<?php

namespace Talk\Post;

use Talk\Foundation\AbstractValidator;

class PostValidator extends AbstractValidator
{
    protected $rules = [
        'content' => [
            'required',
            'max:65535'
        ]
    ];
}
