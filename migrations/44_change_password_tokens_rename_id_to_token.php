<?php

use Talk\Database\Migration;

return Migration::renameColumn('password_tokens', 'id', 'token');
