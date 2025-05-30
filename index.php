<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Tratar requisições OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once 'database.php';

$database = new Database();
$conexao = $database->conectar();
$database->criarTabelaSeNaoExistir();

$metodo = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? $_GET['id'] : null;

switch($metodo) {
    case 'GET':
        if ($id) {
            obterTarefa($conexao, $id);
        } else {
            obterTodasTarefas($conexao);
        }
        break;
        
    case 'POST':
        criarTarefa($conexao);
        break;
        
    case 'PUT':
        if ($id) {
            atualizarTarefa($conexao, $id);
        } else {
            http_response_code(400);
            echo json_encode(['erro' => 'ID necessário para PUT']);
        }
        break;
        
    case 'DELETE':
        if ($id) {
            deletarTarefa($conexao, $id);
        } else {
            http_response_code(400);
            echo json_encode(['erro' => 'ID necessário para DELETE']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['erro' => 'Método não permitido']);
}

function obterTodasTarefas($conexao) {
    $query = "SELECT * FROM tarefas ORDER BY criada_em DESC";
    $stmt = $conexao->prepare($query);
    $stmt->execute();
    
    $tarefas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        'sucesso' => true, 
        'dados' => $tarefas,
        'total' => count($tarefas)
    ]);
}

function obterTarefa($conexao, $id) {
    $query = "SELECT * FROM tarefas WHERE id = ?";
    $stmt = $conexao->prepare($query);
    $stmt->execute([$id]);
    
    $tarefa = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tarefa) {
        echo json_encode(['sucesso' => true, 'dados' => $tarefa]);
    } else {
        http_response_code(404);
        echo json_encode(['erro' => 'Tarefa não encontrada']);
    }
}

function criarTarefa($conexao) {
    $dados = json_decode(file_get_contents("php://input"), true);
    
    if (empty($dados['titulo'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'Título é obrigatório']);
        return;
    }
    
    $query = "INSERT INTO tarefas (titulo, descricao) VALUES (?, ?)";
    $stmt = $conexao->prepare($query);
    
    if ($stmt->execute([$dados['titulo'], $dados['descricao'] ?? ''])) {
        $id = $conexao->lastInsertId();
        http_response_code(201);
        echo json_encode([
            'sucesso' => true, 
            'mensagem' => 'Tarefa criada com sucesso',
            'id' => $id
        ]);
    }
}

function atualizarTarefa($conexao, $id) {
    $dados = json_decode(file_get_contents("php://input"), true);
    
    $query = "UPDATE tarefas SET titulo = ?, descricao = ?, concluida = ? WHERE id = ?";
    $stmt = $conexao->prepare($query);
    
    if ($stmt->execute([
        $dados['titulo'] ?? '', 
        $dados['descricao'] ?? '',
        $dados['concluida'] ?? false, 
        $id
    ])) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Tarefa atualizada']);
    }
}

function deletarTarefa($conexao, $id) {
    $query = "DELETE FROM tarefas WHERE id = ?";
    $stmt = $conexao->prepare($query);
    
    if ($stmt->execute([$id])) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Tarefa deletada']);
    }
}
?>