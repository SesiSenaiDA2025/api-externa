<?php
// API de Tarefas - Railway Compatible
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Configuração do banco Railway
$host = $_ENV['MYSQLHOST'] ?? 'localhost';
$port = $_ENV['MYSQLPORT'] ?? 3306;
$database = $_ENV['MYSQLDATABASE'] ?? 'railway';
$username = $_ENV['MYSQLUSER'] ?? 'root';
$password = $_ENV['MYSQLPASSWORD'] ?? '';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'erro' => 'Conexão falhou',
        'mensagem' => $e->getMessage(),
        'debug' => [
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'user' => $username
        ]
    ]);
    exit;
}

// Criar tabela
$createTable = "CREATE TABLE IF NOT EXISTS tarefas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    concluida BOOLEAN DEFAULT FALSE,
    criada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

try {
    $pdo->exec($createTable);
    
    // Dados iniciais
    $count = $pdo->query("SELECT COUNT(*) as total FROM tarefas")->fetch();
    if ($count['total'] == 0) {
        $stmt = $pdo->prepare("INSERT INTO tarefas (titulo, descricao) VALUES (?, ?)");
        $stmt->execute(['🚀 Railway funcionando!', 'API hospedada com sucesso']);
        $stmt->execute(['📚 Testar API', 'Usar todas as funcionalidades']);
        $stmt->execute(['🎯 Deploy completo', 'Railway + MySQL funcionando']);
    }
} catch(PDOException $e) {
    // Continuar mesmo se der erro
}

// Roteamento
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch($method) {
    case 'GET':
        if ($id) {
            obterTarefa($pdo, $id);
        } else {
            listarTarefas($pdo);
        }
        break;
        
    case 'POST':
        criarTarefa($pdo);
        break;
        
    case 'PUT':
        if ($id) {
            atualizarTarefa($pdo, $id);
        } else {
            resposta(400, ['erro' => 'ID necessário']);
        }
        break;
        
    case 'DELETE':
        if ($id) {
            deletarTarefa($pdo, $id);
        } else {
            resposta(400, ['erro' => 'ID necessário']);
        }
        break;
        
    default:
        resposta(405, ['erro' => 'Método não permitido']);
}

function listarTarefas($pdo) {
    $stmt = $pdo->query("SELECT * FROM tarefas ORDER BY criada_em DESC");
    $tarefas = $stmt->fetchAll();
    
    foreach ($tarefas as &$tarefa) {
        $tarefa['concluida'] = (bool)$tarefa['concluida'];
        $tarefa['id'] = (int)$tarefa['id'];
    }
    
    resposta(200, [
        'sucesso' => true,
        'dados' => $tarefas,
        'total' => count($tarefas),
        'servidor' => 'Railway 🚂',
        'status' => 'Funcionando perfeitamente!'
    ]);
}

function obterTarefa($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM tarefas WHERE id = ?");
    $stmt->execute([$id]);
    $tarefa = $stmt->fetch();
    
    if ($tarefa) {
        $tarefa['concluida'] = (bool)$tarefa['concluida'];
        resposta(200, ['sucesso' => true, 'dados' => $tarefa]);
    } else {
        resposta(404, ['erro' => 'Tarefa não encontrada']);
    }
}

function criarTarefa($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty(trim($input['titulo'] ?? ''))) {
        resposta(400, ['erro' => 'Título obrigatório']);
        return;
    }
    
    $stmt = $pdo->prepare("INSERT INTO tarefas (titulo, descricao) VALUES (?, ?)");
    if ($stmt->execute([trim($input['titulo']), trim($input['descricao'] ?? '')])) {
        resposta(201, [
            'sucesso' => true,
            'mensagem' => 'Tarefa criada!',
            'id' => (int)$pdo->lastInsertId()
        ]);
    } else {
        resposta(500, ['erro' => 'Erro ao criar']);
    }
}

function atualizarTarefa($pdo, $id) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $pdo->prepare("SELECT * FROM tarefas WHERE id = ?");
    $stmt->execute([$id]);
    $atual = $stmt->fetch();
    
    if (!$atual) {
        resposta(404, ['erro' => 'Not found']);
        return;
    }
    
    $titulo = trim($input['titulo'] ?? $atual['titulo']);
    $descricao = trim($input['descricao'] ?? $atual['descricao']);
    $concluida = isset($input['concluida']) ? (bool)$input['concluida'] : (bool)$atual['concluida'];
    
    $stmt = $pdo->prepare("UPDATE tarefas SET titulo = ?, descricao = ?, concluida = ? WHERE id = ?");
    if ($stmt->execute([$titulo, $descricao, $concluida, $id])) {
        resposta(200, ['sucesso' => true, 'mensagem' => 'Atualizada!']);
    } else {
        resposta(500, ['erro' => 'Erro ao atualizar']);
    }
}

function deletarTarefa($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM tarefas WHERE id = ?");
    if ($stmt->execute([$id])) {
        resposta(200, ['sucesso' => true, 'mensagem' => 'Deletada!']);
    } else {
        resposta(500, ['erro' => 'Erro ao deletar']);
    }
}

function resposta($codigo, $dados) {
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}
?>