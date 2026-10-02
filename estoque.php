<?php
require_once 'config.php';
verificarLogin();

$mensagem = $tipoMensagem = $alertaEstoque = "";

if($_SERVER['REQUEST_METHOD'] === "POST"){
    $pid = (int)($_POST['produto_id'] ?? 0);
    $tipo = $_POST['tipo']?? "";
    $qtd = (int)($_POST['quantidade'] ?? 0);
    $data = $_POST['data'] ?? "";

    if(!$pid || !$tipo || !$data || $qtd < 0){
        $mensagem = "Preencha todos os campos";
        $tipoMensagem = 'erro';
    }else{
        $stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ?");
        $stmt->execute([$pid]);
        if(!($prod = $stmt->fetch())){
            $mensagem = "Produto não encontrado";
            $tipoMensagem = 'erro';
        }else{
            $ant = $prod['estoque_atual'];
            $novo = $tipo === 'ENTRADA' ? $ant + $qtd : $ant - $qtd;
            if($novo < 0){
                $mensagem = "Estoque insuficiente";
                $tipoMensagem = 'erro';
            }else{
                try{
                $conn->beginTransaction();
                $conn->prepare("INSERT INTO movimentacoes (tipo, data, quantidade, saldo_anterior, usuarios_id, produtos_id) 
                VALUES (?, ?, ?, ?, ?, ?)")->execute([$tipo ===  'Entrada' ?1:2, $data, $qtd, $ant, $_SESSION['usuario_id'], $pid]);
                $conn-> prepare("UPDATE produtos SET estoque_atual = ? WHERE id = ?")->execute([$novo, $pid]);
                $conn->commit();
                $mensagem = "Movimentação registrada com sucesso";
                $tipoMensagem = 'sucesso';
                if($tipo === 'SAIDA' && $novo <= $prod['estoque_minimo']){
                    $alertaEstoque = "ALERTA DE ESTOQUE BAIXO! O produto {$prod["produto"]} está com estoque baixo! Atual: 
                    {$novo}, Mínimo {$prod['estoque_minimo']}. ";
                }
                }catch(PDOException $e){
                    if($conn->inTransaction()) $conn -> rollBack();
                    $mensagem = "Erro ao registrar movimentação: " . $e->getMessage();
                    $tipoMensagem = 'erro';
                }
            }
        }
    }
}
$produtos = $conn->query("SELECT * FROM produtos WHERE ativo = 1 ORDER BY produto")->fetchAll();
$movimentacoes = $conn->query("SELECT m.*,
    (SELECT p.produto FROM produtos p WHERE p.id = m.produtos_id) AS produto_produto,
    (SELECT u.nome FROM usuarios u WHERE u.id = m.produtos_id) AS usuario_nome 
        FROM movimentacoes m
        ORDER BY m.id DESC;")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de estoque</title>
</head>
<body>
    <h1>Gestão de estoque</h1>
    <p><a href="index.php">Voltar</a></p>
    <hr>
    
        <?php if ($mensagem): ?>
            <p style="color: <?= $tipoMensagem === 'sucesso' ? 'green' : 'red' ?>">
                <?= htmlspecialchars($mensagem) ?>
            </p>
            <hr>
        <?php endif;?>

        <?php if ($alertaEstoque): ?>
            <p style="color: gold">
                <?= htmlspecialchars($alertaEstoque) ?>
            </p>
            <hr>
        <?php endif;?>

        <h2>Nova movimentação de estoque</h2>
        <form method="post">
            <label>Produto:
                <select name="produto_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach($produtos as $p): ?>
                        <option value="<?= $p['id'] ?>">
                            <?= htmlspecialchars($p['produto']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <br>
            <label>Tipo:
                        <input type="radio" name="tipo" value="ENTRADA" required> ENTRADA
                        <input type="radio" name="tipo" value="SAIDA" required> SAIDA
            </label>
            <br>
            <label>Quantidade:
                <input type="number" name="quantidade" min='1' required>
            </label>
            <br>
            <label>Data:
                <input type="date" name="data" value="<?= date ('Y-m-d') ?>"required>
            </label>
            <br>
            <br>
            <button type="submit">Registrar movimentações</button>
        </form>

        <h2>Lista de produtos</h2>
        <?php if($produtos): ?>
            <table border="5">
                <tr>
                    <th>Código</th>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Preço</th>
                    <th>Estoque atual</th>
                    <th>Estoque mínimo</th>
                    <th>Aviso</th>
                </tr>
                <?php foreach($produtos as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['codigo']) ?></td>
                        <td><?= htmlspecialchars($p['produto']) ?></td>
                        <td><?= htmlspecialchars($p['categoria'] ?? "") ?></td>
                        <td>R$<?= number_format($p['preco'], 2,',','.') ?></td>
                        <td><?= $p['estoque_atual'] ?></td>
                        <td><?= $p['estoque_minimo'] ?></td>
                        <td>
                            <?= $p['estoque_atual'] <$p['estoque_minimo'] ?
                            '<strong>AVISO: estoque baixo</strong>' : 'Normal';?>
                        </td>
                    </tr>
                    <?php endforeach;?>
            </table>
            <?php else: ?> <p>Nenhum produto cadastrado</p><?php endif;?>

            <hr>

            <h2>Histórico de movimentações</h2>
            <?php if($movimentacoes): ?>
                <table border="5">
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Produtos</th>
                        <th>Tipo</th>
                        <th>Quantidade</th>
                        <th>Saldo anterior</th>
                        <th>Usuário</th>
                    </tr>
                    <?php foreach($movimentacoes as $m): ?>
                        <tr>
                            <td><?= $m['id'] ?></td>
                            <td><?= date('d/m/Y', strtotime($m['data'])) ?></td>
                            <td><?= htmlspecialchars($m['produto_produto']) ?></td>
                            <td><?= $m['tipo'] == 1 ? 'ENTRADA' : 'SAIDA' ?></td>
                            <td><?= $m['quantidade']?></td>
                            <td><?= $m['saldo_anterior']?></td>
                            <td><?= htmlspecialchars($m['usuario_nome'])?></td>
                        </tr>
                    <?php endforeach;?>
                </table>
                        <?php else:?> <p>Nenhuma movimentação registrada.</p><?php endif; ?>
</body>
</html>
