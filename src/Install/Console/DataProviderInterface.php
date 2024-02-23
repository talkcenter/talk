<?php

namespace Talk\Install\Console;

use Talk\Install\Installation;

interface DataProviderInterface
{
    public function configure(Installation $installation): Installation;
}
