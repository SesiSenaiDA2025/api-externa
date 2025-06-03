<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Configuração MySQL do Railway
    $host = getenv('MYSQLHOST');
    $database = getenv('MYSQL_DATABASE');
    $username = getenv('MYSQLUSER');
    $password = getenv('MYSQLPASSWORD');
    $port_db = getenv('MYSQLPORT') ?: 3306;
    
    if (!$host) {
        throw new Exception('Variáveis MySQL não encontradas');
    }
    
    // Conectar
    $dsn = "mysql:host=$host;port=$port_db;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    // Criar tabela
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
        $stmt->execute(['🎉 Railway + MySQL funcionando!', 'Sua API está online na nuvem!']);
        $stmt->execute(['✅ Primeiro teste', 'Banco de dados conectado com sucesso']);
    }
    
    // API REST
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT * FROM tarefas ORDER BY criada_em DESC");
        $tarefas = $stmt->fetchAll();
        
        echo json_encode([
            'sucesso' => true,
            'dados' => $tarefas,
            'total' => count($tarefas),
            'servidor' => 'Railway',
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
    }
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input['titulo']) {
            throw new Exception('Título é obrigatório');
        }
        
        $stmt = $pdo->prepare("INSERT INTO tarefas (titulo, descricao) VALUES (?, ?)");
        $stmt->execute([$input['titulo'], $input['descricao'] ?? '']);
        
        echo json_encode([
            'sucesso' => true,
            'id' => $pdo->lastInsertId(),
            'mensagem' => 'Tarefa criada!'
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>