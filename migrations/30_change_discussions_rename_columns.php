<?php

use Talk\Database\Migration;

return Migration::renameColumns('discussions', [
    'comments_count' => 'comment_count',
    'participants_count' => 'participant_count',
    'number_index' => 'post_number_index',
    'start_time' => 'created_at',
    'start_user_id' => 'user_id',
    'start_post_id' => 'first_post_id',
    'last_time' => 'last_posted_at',
    'last_user_id' => 'last_posted_user_id',
    'hide_time' => 'hidden_at',
    'hide_user_id' => 'hidden_user_id'
]);
