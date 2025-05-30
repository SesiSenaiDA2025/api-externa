<?php
// API REST - Railway com porta dinâmica
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuração de porta para Railway
$port = getenv('PORT') ?: $_ENV['PORT'] ?? 8080;

// Se for primeira execução, mostrar informações de debug
if (!isset($_SERVER['HTTP_HOST']) || $_SERVER['REQUEST_URI'] === '/debug') {
    echo json_encode([
        'status' => '🚂 Railway API Online!',
        'timestamp' => date('Y-m-d H:i:s'),
        'porta_configurada' => $port,
        'porta_servidor' => $_SERVER['SERVER_PORT'] ?? 'não detectada',
        'host' => $_SERVER['HTTP_HOST'] ?? 'localhost',
        'metodo' => $_SERVER['REQUEST_METHOD'],
        'uri' => $_SERVER['REQUEST_URI'],
        'mysql_vars' => [
            'MYSQLHOST' => getenv('MYSQLHOST') ? '✅ OK' : '❌ Não encontrada',
            'MYSQLDATABASE' => getenv('MYSQLDATABASE') ? '✅ OK' : '❌ Não encontrada',
            'MYSQLUSER' => getenv('MYSQLUSER') ? '✅ OK' : '❌ Não encontrada',
            'MYSQLPASSWORD' => getenv('MYSQLPASSWORD') ? '✅ OK' : '❌ Não encontrada'
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Teste básico de funcionamento
try {
    // Configuração MySQL
    $host = getenv('MYSQLHOST') ?: 'localhost';
    $port_db = getenv('MYSQLPORT') ?: 3306;
    $database = getenv('MYSQLDATABASE') ?: 'railway';
    $username = getenv('MYSQLUSER') ?: 'root';
    $password = getenv('MYSQLPASSWORD') ?: '';
    
    if (!$host || $host === 'localhost') {
        echo json_encode([
            'status' => '⚠️ Variáveis MySQL não conectadas',
            'acao_necessaria' => 'Conectar MySQL no Railway Dashboard',
            'variaveis_encontradas' => [
                'MYSQLHOST' => $host,
                'MYSQLDATABASE' => $database,
                'MYSQLUSER' => $username
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // Tentar conectar
    $dsn = "mysql:host=$host;port=$port_db;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    // Criar tabela se não existir
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tarefas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            titulo VARCHAR(255) NOT NULL,
            descricao TEXT,
            concluida TINYINT(1) DEFAULT 0,
            criada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Inserir dados de teste se vazio
    $count = $pdo->query("SELECT COUNT(*) as total FROM tarefas")->fetch();
    if ($count['total'] == 0) {
        $stmt = $pdo->prepare("INSERT INTO tarefas (titulo, descricao) VALUES (?, ?)");
        $stmt->execute(['🎉 Railway funcionando!', 'API + MySQL conectados com sucesso']);
        $stmt->execute(['✅ Teste de conexão', 'Banco de dados operacional']);
    }
    
    // Listar tarefas
    $stmt = $pdo->query("SELECT * FROM tarefas ORDER BY criada_em DESC");
    $tarefas = $stmt->fetchAll();
    
    echo json_encode([
        'sucesso' => true,
        'mensagem' => '🚂 API Railway + MySQL funcionando!',
        'servidor' => [
            'host' => $_SERVER['HTTP_HOST'],
            'porta' => $port,
            'timestamp' => date('Y-m-d H:i:s')
        ],
        'banco' => [
            'host' => $host,
            'database' => $database,
            'status' => 'conectado'
        ],
        'dados' => $tarefas,
        'total_tarefas' => count($tarefas)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage(),
        'codigo' => $e->getCode(),
        'detalhes' => [
            'arquivo' => basename($e->getFile()),
            'linha' => $e->getLine()
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>