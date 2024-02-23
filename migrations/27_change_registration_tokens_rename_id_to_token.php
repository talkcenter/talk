<?php

use Talk\Database\Migration;

return Migration::renameColumn('registration_tokens', 'id', 'token');
