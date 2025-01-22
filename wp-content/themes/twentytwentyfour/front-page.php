<?php
// Configuração do banco de dados
$host = "mysql16-farm2.uni5.net";
$usuario = "areadeprojetos11";
$senha = "z3k2s2";
$banco = "areadeprojetos11";

// Conexão com o banco de dados
$conexao = new mysqli($host, $usuario, $senha, $banco);

// Verifica a conexão
if ($conexao->connect_error) {
    die("Erro na conexão: " . $conexao->connect_error);
}

// Define o número de postagens por página
$postagens_por_pagina = 9;

// Obtém o número da página atual (padrão é 1)
$pagina_atual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;

// Calcula o índice inicial para a consulta SQL
$inicio = ($pagina_atual - 1) * $postagens_por_pagina;

// Recebe os dados do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['titulo'], $_POST['descricao'])) {
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];

    // Manipula o upload de imagem (opcional)
    $imagem_nome = null;
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $imagem_nome = basename($_FILES['imagem']['name']);
        $imagem_caminho = "uploads/" . $imagem_nome;

        // Certifique-se de que a pasta "uploads" existe e tem permissão de escrita
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }

        move_uploaded_file($_FILES['imagem']['tmp_name'], $imagem_caminho);
    }

    // Prepara a consulta SQL para inserir postagem
    $sql = "INSERT INTO Postagens (titulo, descricao, imagem) VALUES (?, ?, ?)";
    $stmt = $conexao->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sss", $titulo, $descricao, $imagem_nome);
        $stmt->execute();
        $stmt->close();
    }
}

// Exclusão de postagens
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    $sql_delete = "DELETE FROM Postagens WHERE id = ?";
    $stmt_delete = $conexao->prepare($sql_delete);

    if ($stmt_delete) {
        $stmt_delete->bind_param("i", $delete_id);
        $stmt_delete->execute();
        $stmt_delete->close();
    }
}

// Consulta para contar o total de postagens
$sql_contagem = "SELECT COUNT(*) AS total FROM Postagens";
$resultado_contagem = $conexao->query($sql_contagem);
$total_postagens = $resultado_contagem->fetch_assoc()['total'];

// Calcula o número total de páginas
$total_paginas = ceil($total_postagens / $postagens_por_pagina);

// Consulta para buscar as postagens da página atual
$sql = "SELECT id, titulo, descricao, imagem FROM Postagens ORDER BY id DESC LIMIT ?, ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("ii", $inicio, $postagens_por_pagina);
$stmt->execute();
$resultado = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Postagens</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">
    <h1 class="mb-4">Postagens</h1>
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="titulo" class="form-label">Título</label>
            <input type="text" id="titulo" name="titulo" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="descricao" class="form-label">Descrição</label>
            <textarea id="descricao" name="descricao" class="form-control" rows="4" required></textarea>
        </div>
        <div class="mb-3">
            <label for="imagem" class="form-label">Imagem</label>
            <input type="file" id="imagem" name="imagem" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Publicar</button>
    </form>

    <?php if ($resultado->num_rows > 0): ?>
        <?php while ($row = $resultado->fetch_assoc()): ?>
            <div class="postagem card mb-4" style="max-width: 600px; margin: auto;">
                <div class="card-header">
                    <strong><?= htmlspecialchars($row['titulo']) ?></strong>
                </div>
                <div class="card-body">
                    <p class="card-text"><?= nl2br(htmlspecialchars($row['descricao'])) ?></p>
                    <?php if (!empty($row['imagem'])): ?>
                        <img src="uploads/<?= htmlspecialchars($row['imagem']) ?>" alt="<?= htmlspecialchars($row['titulo']) ?>" class="img-fluid">
                    <?php endif; ?>
                </div>
                <div class="card-footer text-muted">
                    <form method="POST" style="display: inline-block;">
                        <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Deletar</button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>

    <!-- Paginação -->
    <nav aria-label="Navegação de páginas" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <li class="page-item <?= $i == $pagina_atual ? 'active' : '' ?>">
                    <a class="page-link" href="?pagina=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</body>
</html>
