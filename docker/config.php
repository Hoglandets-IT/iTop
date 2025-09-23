<?php

$MySettings = array(
    'app_root_url' => $_ENV['ITOP_URL'] ?? "",
    'behind_reverse_proxy' => $_ENV['BEHIND_REVERSE_PROXY'] === 'true',

    'db_host'    => $_ENV['DB_HOSTNAME'] ?? "mysql",
	'db_name'    => $_ENV['DB_ENV_MYSQL_DATABASE'] ?? "itop",
	'db_user'    => $_ENV['DB_ENV_MYSQL_USER'] ?? "itop",
	'db_pwd'     => $_ENV['DB_ENV_MYSQL_PASSWORD'] ?? "itop"
);

?>