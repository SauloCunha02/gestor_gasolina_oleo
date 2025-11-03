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
        executarQuery("DELETE FROM trocas_oleo WHERE id = :id", [':id' => $id]);
        header('Location: listar.php?msg=excluido');
        exit();
    } catch (Exception $e) {
        $erro = 'Erro ao excluir!';
    }
}

// Buscar todas as trocas
$db = getConnection();
$sql = "SELECT * FROM trocas_oleo ORDER BY data_troca DESC, id DESC";
$trocas =$db->query($sql)->fetchAll();

// Calcular estatísticas
$totalGasto = 0;
$kmAnterior = null;
$intervalos = [];

foreach ($trocas as $troca) {
    $totalGasto +=$troca['valor_pago'];
    
    // Calcular intervalo entre trocas
    if ($kmAnterior !== null) {
        $intervalo = $kmAnterior - $troca['km_troca'];
        if ($intervalo > 0) {
            $intervalos[] = $intervalo;
        }
    }
    $kmAnterior =$troca['km_troca'];
}

$intervaloMedio = count($intervalos) > 0 ? array_sum($intervalos) / count($intervalos) : 0;
$valorMedio = count($trocas) > 0 ? $totalGasto / count($trocas) : 0;

// Próxima troca estimada
$ultimaTroca = count($trocas) > 0 ? $trocas[0] : null;
$proximaTrocaKm =$ultimaTroca && $intervaloMedio > 0 
    ? $ultimaTroca['km_troca'] + $intervaloMedio 
    : null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Trocas de Óleo</title>
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
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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

        .stat-box.warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
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
            <h1>🛢️ Lista de Trocas de Óleo</h1>
            <div class="header-buttons">
                <a href="../index.php" class="btn btn-back">← Dashboard</a>
                <a href="cadastrar.php" class="btn btn-new">➕ Nova Troca</a>
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
                <h3>Total de Trocas</h3>
                <div class="value"><?php echo count($trocas); ?></div>
            </div>
            <div class="stat-box">
                <h3>Total Gasto</h3>
                <div class="value">R$ <?php echo number_format($totalGasto, 2, ',', '.'); ?></div>
            </div>
            <div class="stat-box">
                <h3>Valor Médio</h3>
                <div class="value">R$ <?php echo number_format($valorMedio, 2, ',', '.'); ?></div>
            </div>
            <div class="stat-box">
                <h3>Intervalo Médio</h3>
                <div class="value"><?php echo number_format($intervaloMedio, 0, '', '.'); ?> km</div>
            </div>
            <?php if ($proximaTrocaKm): ?>
            <div class="stat-box warning">
                <h3>Próxima Troca</h3>
                <div class="value"><?php echo number_format($proximaTrocaKm, 0, '', '.'); ?> km</div>
            </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <?php if (count($trocas) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>KM</th>
                            <th>Valor</th>
                            <th>Tipo de Óleo</th>
                            <th>KM desde última</th>
                            <th>Observação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $kmAnt = null;
                        foreach ($trocas as $troca): 
                            $kmDesdeUltima = null;
                            if ($kmAnt !== null) {
                                $kmDesdeUltima = $kmAnt - $troca['km_troca'];
                            }
                            $kmAnt =$troca['km_troca'];
                        ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($troca['data_troca'])); ?></td>
                                <td><?php echo number_format($troca['km_troca'], 0, '', '.'); ?> km</td>
                                <td><strong>R$ <?php echo number_format($troca['valor_pago'], 2, ',', '.'); ?></strong></td>
                                <td>
                                    <?php 
                                    echo $troca['tipo_oleo'] 
                                        ? htmlspecialchars($troca['tipo_oleo']) 
                                        : '<span style="color: #999;">-</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    echo $kmDesdeUltima !== null 
                                        ? number_format($kmDesdeUltima, 0, '', '.') . ' km' 
                                        : '<span style="color: #999;">-</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($troca['observacao']) {
                                        echo htmlspecialchars(substr($troca['observacao'], 0, 30));
                                        if (strlen($troca['observacao']) > 30) echo '...';
                                    } else {
                                        echo '<span style="color: #999;">-</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="?excluir=<?php echo $troca['id']; ?>" 
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
                    <div class="icon">🛢️</div>
                    <h3>Nenhuma troca de óleo registrada</h3>
                    <p>Cadastre a primeira troca de óleo para começar!</p>
                    <br>
                    <a href="cadastrar.php" class="btn btn-new">➕ Cadastrar Agora</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
