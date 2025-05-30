<?php
// Teste simples - Railway
header('Content-Type: application/json');

// Teste 1: API funcionando
echo json_encode([
    'status' => 'API funcionando!',
    'timestamp' => date('Y-m-d H:i:s'),
    'servidor' => 'Railway',
    'variáveis_mysql' => [
        'MYSQLHOST' => getenv('MYSQLHOST') ?: 'não encontrada',
        'MYSQLDATABASE' => getenv('MYSQLDATABASE') ?: 'não encontrada', 
        'MYSQLUSER' => getenv('MYSQLUSER') ?: 'não encontrada',
        'senha_existe' => getenv('MYSQLPASSWORD') ? 'sim' : 'não'
    ]
]);
?>