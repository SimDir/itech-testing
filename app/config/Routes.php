<?php defined('ROOT') OR die('No direct script access.');

return array(
    '^report/([-_a-zA-Z0-9]+).php' => 'report/get/$1',
    '^report/([-_a-zA-Z0-9]+).html' => 'report/get/$1',
);
