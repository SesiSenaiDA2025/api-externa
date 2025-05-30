<?php
class Database {
    private $host;
    private $nome_bd;
    private $usuario;
    private $senha;
    private $conexao;

    public function __construct() {
        // Railway fornece essas variáveis automaticamente
        $this->host = $_ENV['MYSQLHOST'] ?? 'localhost';
        $this->nome_bd = $_ENV['MYSQLDATABASE'] ?? 'railway';
        $this->usuario = $_ENV['MYSQLUSER'] ?? 'root';
        $this->senha = $_ENV['MYSQLPASSWORD'] ?? '';
    }

    public function conectar() {
        try {
            $this->conexao = new PDO(
                "mysql:host={$this->host};dbname={$this->nome_bd}",
                $this->usuario,
                $this->senha
            );
            $this->conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->conexao;
        } catch(PDOException $e) {
            die(json_encode(['erro' => 'Conexão falhou: ' . $e->getMessage()]));
        }
    }

    public function criarTabelaSeNaoExistir() {
        $sql = "CREATE TABLE IF NOT EXISTS tarefas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            titulo VARCHAR(200) NOT NULL,
            descricao TEXT,
            concluida BOOLEAN DEFAULT FALSE,
            criada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->conexao->exec($sql);
        
        // Inserir dados iniciais se a tabela estiver vazia
        $stmt = $this->conexao->query("SELECT COUNT(*) FROM tarefas");
        if ($stmt->fetchColumn() == 0) {
            $stmt = $this->conexao->prepare("INSERT INTO tarefas (titulo, descricao) VALUES (?, ?)");
            $stmt->execute(['Estudar APIs', 'Aprender a criar e hospedar APIs REST']);
            $stmt->execute(['Fazer exercício', 'Consumir API hospedada em uma aplicação']);
            $stmt->execute(['Deploy completo', 'API funcionando na nuvem!']);
        }
    }
}
?>