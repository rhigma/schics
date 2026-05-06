<?php
require_once __DIR__ . '/auth.php';
schics_logout();
header('Location: index.php');
