<?php defined('ROOT') OR die('No direct script access.');

return array(
    '^report/([-_a-zA-Z0-9]+).php' => 'geport/get/$1',
    '^report/([-_a-zA-Z0-9]+).html' => 'geport/get/$1',
);
