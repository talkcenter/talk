<?php

namespace Talk\Group;

use Talk\Foundation\AbstractValidator;

class GroupValidator extends AbstractValidator
{
    protected $rules = [
        'name_singular' => ['required'],
        'name_plural' => ['required']
    ];
}
