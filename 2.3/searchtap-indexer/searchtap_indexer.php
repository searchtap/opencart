<?php

$indexer = getopt("i:")["i"];

define("ST_INDEXER", $indexer);
$cli_action = 'module/searchtap';
require_once('cli_dispatch.php');