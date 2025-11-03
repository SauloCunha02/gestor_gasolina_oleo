<?php
session_start();

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: ../login.php');
    exit();
}

require_once '../config/database.php';

// Excluir registro
if (isset($_GET['excluir'])) {
    $id = (int)$_GET['excluir'];
    try {
        executarQuery("DELETE FROM abastecimentos WHERE id = :id", [':id' => $id]);
        header('Location: listar.php?msg=excluido');
        exit();
    } catch (Exception $e) {
        $erro = 'Erro ao excluir!';
    }
}

// Buscar todos abastecimentos
$db = getConnection();
$sql = "SELECT * FROM abastecimentos ORDER BY data_abastecimento DESC, id DESC";
$abastecimentos =$db->query($sql)->fetchAll();

// Calcular estatísticas
$totalGasto = 0;
$totalLitros = 0;
$kmAnterior = null;
$consumos = [];

foreach ($abastecimentos as $abast) {
    $totalGasto +=$abast['valor_pago'];
    if ($abast['litros']) {
        $totalLitros +=$abast['litros'];
    }
    
    // Calcular consumo
    if ($kmAnterior !== null && $abast['litros']) {
        $kmRodados =$kmAnterior - $abast['km_atual'];
        if ($kmRodados > 0) {
            $consumo =$kmRodados / $abast['litros'];
            $consumos[] = $consumo;
        }
    }
    $kmAnterior =$abast['km_atual'];
}

$consumoMedio = count($consumos) > 0 ? array_sum($consumos) / count($consumos) : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Abastecimentos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
        }

        .header-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .btn-new {
            background: #10b981;
            color: white;
        }

        .btn-new:hover {
            background: #059669;
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-box h3 {
            color: #666;
            font-size: 13px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .stat-box .value {
            color: #333;
            font-size: 24px;
            font-weight: 700;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f9fafb;
        }

        th {
            padding: 15px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            border-bottom: 2px solid #e5e7eb;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        tr:hover {
            background: #f9fafb;
        }

        .btn-delete {
            padding: 6px 12px;
            background: #dc2626;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 12px;
            transition: background 0.3s;
        }

        .btn-delete:hover {
            background: #b91c1c;
        }

        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: #999;
        }

        .empty-state .icon {
            font-size: 60px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1>⛽ Lista de Abastecimentos</h1>
            <div class="header-buttons">
                <a href="../index.php" class="btn btn-back">← Dashboard</a>
                <a href="cadastrar.php" class="btn btn-new">➕ Novo</a>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'excluido'): ?>
            <div class="alert alert-success">
                ✅ Registro excluído com sucesso!
            </div>
        <?php endif; ?>

        <div class="stats-row">
            <div class="stat-box">
                <h3>Total de Registros</h3>
                <div class="value"><?php echo count($abastecimentos); ?></div>
            </div>
            <div class="stat-box">
                <h3>Total Gasto</h3>
                <div class="value">R$ <?php echo number_format($totalGasto, 2, ',', '.'); ?></div>
            </div>
            <div class="stat-box">
                <h3>Total de Litros</h3>
                <div class="value"><?php echo number_format($totalLitros, 2, ',', '.'); ?> L</div>
            </div>
            <div class="stat-box">
                <h3>Consumo Médio</h3>
                <div class="value"><?php echo number_format($consumoMedio, 1, ',', '.'); ?> km/L</div>
            </div>
        </div>

        <div class="card">
            <?php if (count($abastecimentos) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>KM</th>
                            <th>Valor</th>
                            <th>Litros</th>
                            <th>R$/Litro</th>
                            <th>Observação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($abastecimentos as $abast): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($abast['data_abastecimento'])); ?></td>
                                <td><?php echo number_format($abast['km_atual'], 0, '', '.'); ?> km</td>
                                <td><strong>R$ <?php echo number_format($abast['valor_pago'], 2, ',', '.'); ?></strong></td>
                                <td>
                                    <?php 
                                    if ($abast['litros']) {
                                        echo number_format($abast['litros'], 2, ',', '.') . ' L';
                                    } else {
                                        echo '<span style="color: #999;">-</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($abast['litros']) {
                                        $precoPorLitro = $abast['valor_pago'] / $abast['litros'];
                                        echo 'R$' . number_format($precoPorLitro, 2, ',', '.');
                                    } else {
                                        echo '<span style="color: #999;">-</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($abast['observacao']) {
                                        echo htmlspecialchars(substr($abast['observacao'], 0, 30));
                                        if (strlen($abast['observacao']) > 30) echo '...';
                                    } else {
                                        echo '<span style="color: #999;">-</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="?excluir=<?php echo $abast['id']; ?>" 
                                       class="btn-delete"
                                       onclick="return confirm('Deseja realmente excluir este registro?')">
                                        🗑️ Excluir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="icon">⛽</div>
                    <h3>Nenhum abastecimento registrado</h3>
                    <p>Cadastre seu primeiro abastecimento para começar!</p>
                    <br>
                    <a href="cadastrar.php" class="btn btn-new">➕ Cadastrar Agora</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
