<?php
require_once __DIR__ . '/../system/auth.php';

pata_logout();

header('Location: /login.php?logged_out=1');
exit;
