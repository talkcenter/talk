<?php

namespace Talk\Install;

interface ReversibleStep extends Step
{
    public function revert();
}
