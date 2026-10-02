<?php
require_once 'config.php';
verificarLogin();

$mensagem = $tipoMensagem = "";

if(isset($_GET['excluir'])){
    try{
        $conn->prepare("DELETE FROM produtos WHERE id =?")
        ->execute([$_GET['excluir']]);
        $mensagem = "Produto excluído com sucesso";
        $tipoMensagem = "sucesso";

    }catch (PDOException $e){
        $mensagem = "Erro ao excluir: ". $e->getMessage();
        $tipoMensagem = 'erro';
    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $id = $_POST['id'] ?? 0;


    $data = [
        'codigo' => ($_POST['codigo']?? ''),
        'produto' => ($_POST['produto']?? ''),
        'fabricante' => ($_POST['fabricante']?? ''),
        'categoria' => ($_POST['categoria']?? ''),
        'preco' => ($_POST['preco']?? 0),
        'estoque_atual' => ($_POST['estoque_atual']?? 0),
        'estoque_minimo' => ($_POST['estoque_minimo']?? 0),
        'ativo' => ($_POST['ativo']?? 1),
    ];


    if(!$data['codigo'] || !$data['produto'] || $data['preco'] <=0){
        $mensagem = "Preencha todos os campos obrigatórios";
        $tipoMensagem = 'erro';
    }else{
        if($id > 0){
            $sql = "UPDATE produtos SET
                        codigo=:codigo,
                        produto=:produto,
                        fabricante=:fabricante,
                        categoria=:categoria,
                        preco=:preco,
                        estoque_atual=:estoque_atual,
                        estoque_minimo=:estoque_minimo,
                        ativo=:ativo
                    WHERE id =:id";
            $data['id']= $id;
        }else{
            echo "aqui";
            $sql = "INSERT INTO produtos (
            codigo, 
            produto, 
            fabricante, 
            preco, 
            categoria,
            estoque_atual, 
            estoque_minimo, 
            ativo) 
            VALUES (
            :codigo, 
            :produto, 
            :fabricante, 
            :preco, 
            :categoria,
            :estoque_atual, 
            :estoque_minimo, 
            :ativo)";
        }
        try {
        $conn->prepare($sql)->execute($data);
        $mensagem = $id > 0 ? 'Produto atualizado' : 'Produto cadastrado';
        $tipoMensagem = 'sucesso';
        } catch (PDOException $e) {
            $mensagem = "Erro: ". $e->getMessage();
            $tipoMensagem = 'erro';
        }
    }
}

$produtoEditar = null;
if(isset($_GET['editar'])){
    $stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $produtoEditar = $stmt->fetch();
}

$busca = ($_GET['busca'] ?? '');
$sql = "SELECT * FROM produtos WHERE ativo = 1" . ($busca ? " AND (produto LIKE :b OR codigo LIKE :b OR categoria LIKE :b)" : "").
        " ORDER BY produto";
$stmt = $conn->prepare($sql);
$stmt->execute($busca ? ['b' => "%$busca%"]:[]);
$produtos = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de produtos - Pet Shop</title>
</head>
<body>
    <h1>Cadastro de produtos - Pet Shop</h1>
    <p><a href="index.php">Voltar</a></p>
    <hr>

    <?php if ($mensagem): ?>
        <p style="color: <?= $tipoMensagem === 'sucesso' ? 'green' : 'red'?>">
            <?= ($mensagem) ?>
        </p>
    <?php endif; ?>

    <h2><?= $produtoEditar? 'Editar' : 'Novo' ?> Produto</h2>
    <form method="POST">
        <input type="hidden" name="id" value="<?= $produtoEditar['id'] ?? 0?>">


        <label> Código: * <input type="text" name="codigo" required
        value="<?= ($produtoEditar['codigo']) ?? '' ?>">
        </label><br>

        <label> Produto: * <input type="text" name="produto" size="40" required
        value="<?= ($produtoEditar['produto']) ?? '' ?>">
        </label><br>
        
        <label> Fabricante:  <input type="text" name="fabricante" 
        value="<?= ($produtoEditar['fabricante']) ?? '' ?>">
        </label><br>

        <label> Preço: * <input type="number" name="preco" step="0.01" required
        value="<?= ($produtoEditar['preco']) ?? '' ?>">
        </label><br>

        <label> Estoque atual:  <input type="number" min="0" name="estoque_atual" required
        value="<?= ($produtoEditar['estoque_atual']) ?? 0 ?>">
        </label><br>

        <label> Estoque mínimo:  <input type="number" min="0" name="estoque_minimo" required
        value="<?= ($produtoEditar['estoque_minimo']) ?? 0 ?>">
        </label><br>


        <label>Status (Ativo):
            <select name = "ativo">
                <option value="1"<?= ($produtoEditar['ativo'] ?? 1) == 1 ? 'selected' : ''?>>Ativo</option>
                <option value="0"<?= ($produtoEditar['ativo'] ?? 1) == 0 ? 'selected' : ''?>>Inativo</option>
            </select>
        </label><br>

        <label> Categoria:  <input type="text" name="categoria" size="40"
        value="<?= ($produtoEditar['categoria']) ?? '' ?>">
        </label><br>


        <button type="submit">Salvar</button>
        <?php if($produtoEditar): ?><a href="produtos.php"><button type="button">Cancelar</button><?php endif; ?>
    </form>
    
    <hr>

    <h2>Lista de produtos</h2>
    <form method="GET">
        <label>Buscar
            <input type="text" name="busca" value="<?= ($busca)?>"
                    placeholder="Produto, código ou categoria...">
        </label>
        <button type="submit">Buscar</button>
        <?php if ($busca): ?><a href="produtos.php">Limpar</a><?php endif; ?>
    </form><br>

    <?php if($produtos): ?>
        <table border:1 cellpadding="5" cellspacing="0">
            <tr>
                <th>Código</th>
                <th>Produto</th>
                <th>Fabricante</th>
                <th>Categoria</th>
                <th>Preço</th>
                <th>Estoque atual</th>
                <th>Estoque mínimo</th>
                <th>Status</th>

            </tr>
            <?php foreach($produtos as $p): ?>
                <tr>
                    <td><?= ($p['codigo']) ?></td>
                    <td><?= ($p['produto']) ?></td>
                    <td><?= ($p['fabricante']) ?></td>
                    <td><?= ($p['categoria']) ?></td>
                    <td><?= ($p['preco']) ?></td>
                    <td><?= ($p['estoque_atual']) ?></td>
                    <td><?= ($p['estoque_minimo']) ?></td>
                    <td><?= ($p['ativo']) ?></td>

                <td>
                    <a href="produtos.php?editar=<?= $p['id'] ?>">Editar</a>
                    <a href="produtos.php?excluir=<?= $p['id'] ?>"
                     onclick="return confirm('Deseja excluir?')">Excluir</a>
                </td>
                </tr>
                <?php endforeach;?>
            
        </table>
        <?php else: ?>
            <p>Nenhum produto cadastrado.</p>
        <?php endif; ?>    
</body>
</html>