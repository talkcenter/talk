<?php

namespace Talk\Foundation\ErrorHandling;

use Throwable;

interface Reporter
{
    /**
     * 向后端报告 Talkcenter 无法处理的错误。
     *
     * @param Throwable $error
     * @return void
     */
    public function report(Throwable $error);
}
