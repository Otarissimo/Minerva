<?php
require_once 'classes/Horario.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

echo json_encode((new Horario())->getStatus(), JSON_UNESCAPED_UNICODE);
