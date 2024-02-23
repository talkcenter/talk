<?php

use Talk\Database\Migration;

return Migration::renameColumn('email_tokens', 'id', 'token');
