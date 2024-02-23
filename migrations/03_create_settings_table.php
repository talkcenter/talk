<?php

use Talk\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable(
    'settings',
    function (Blueprint $table) {
        $table->string('key', 100)->primary();
        $table->binary('value')->nullable();
    }
);
