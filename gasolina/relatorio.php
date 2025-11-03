<?php
session_start();

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: ../login.php');
    exit();
}

require_once '../config/database.php';

$db = getConnection();

// Filtros
$dataInicio =$_GET['data_inicio'] ?? date('Y-m-01');
$dataFim =$_GET['data_fim'] ?? date('Y-m-d');

// Buscar dados filtrados
$sql = "SELECT * FROM abastecimentos 
        WHERE data_abastecimento BETWEEN :inicio AND :fim 
        ORDER BY data_abastecimento DESC";
$abastecimentos = executarQuery($sql, [
    ':inicio' => $dataInicio,
    ':fim' => $dataFim
])->fetchAll();

// Calcular estatísticas
$totalGasto = 0;
$totalLitros = 0;
$kmInicial = null;
$kmFinal = null;

foreach ($abastecimentos as $index => $abast) {
    $totalGasto +=$abast['valor_pago'];
    if ($abast['litros']) {
        $totalLitros +=$abast['litros'];
    }
    
    if ($index === 0) {
        $kmFinal =$abast['km_atual'];
    }
    $kmInicial =$abast['km_atual'];
}

$kmRodados =$kmFinal - $kmInicial;
$consumoMedio =$totalLitros > 0 ? $kmRodados / $totalLitros : 0;
$custoKm =$kmRodados > 0 ? $totalGasto / $kmRodados : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Abastecimentos</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
        }

        .btn-back {
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            transition: background 0.3s;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .filter-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .filter-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
        }

        .btn-filter {
            padding: 11px 25px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-filter:hover {
            background: #5568d3;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-card .icon {
            font-size: 35px;
            margin-bottom: 10px;
        }

        .stat-card h3 {
            color: #666;
            font-size: 13px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .stat-card .value {
            color: #333;
            font-size: 26px;
            font-weight: 700;
        }

        .report-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .report-card h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f9fafb;
        }

        th {
            padding: 12px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .btn-print {
            margin-top: 20px;
            padding: 12px 30px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-print:hover {
            background: #059669;
        }

        @media print {
            .header, .filter-card, .btn-back, .btn-print {
                display: none;
            }
            
            body {
                background: white;
            }
        }

        @media (max-width: 768px) {
            .filter-row {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1>📊 Relatório de Abastecimentos</h1>
            <a href="../index.php" class="btn-back">← Dashboard</a>
        </div>
    </header>

    <div class="container">
        <div class="filter-card">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="form-group">
                        <label>Data Início</label>
                        <input type="date" name="data_inicio" value="<?php echo $dataInicio; ?>">
                    </div>
                    <div class="form-group">
                        <label>Data Fim</label>
                        <input type="date" name="data_fim" value="<?php echo $dataFim; ?>">
                    </div>
                    <button type="submit" class="btn-filter">🔍 Filtrar</button>
                </div>
            </form>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">💰</div>
                <h3>Total Gasto</h3>
                
                <div class="value">R<?php echo number_format($totalGasto, 2, ',', '.'); ?></div>
            </div>

            <div class="stat-card">
                <div class="icon">⛽</div>
                <h3>Total de Litros</h3>
                <div class="value"><?php echo number_format($totalLitros, 2, ',', '.'); ?> L</div>
            </div>

            <div class="stat-card">
                <div class="icon">🛣️</div>
                <h3>KM Rodados</h3>
                <div class="value"><?php echo number_format($kmRodados, 0, '', '.'); ?> km</div>
            </div>

            <div class="stat-card">
                <div class="icon">📈</div>
                <h3>Consumo Médio</h3>
                <div class="value"><?php echo number_format($consumoMedio, 1, ',', '.'); ?> km/L</div>
            </div>

            <div class="stat-card">
                <div class="icon">💵</div>
                <h3>Custo por KM</h3>
                <div class="value">R$ <?php echo number_format($custoKm, 2, ',', '.'); ?></div>
            </div>

            <div class="stat-card">
                <div class="icon">📊</div>
                <h3>Abastecimentos</h3>
                <div class="value"><?php echo count($abastecimentos); ?></div>
            </div>
        </div>

        <div class="report-card">
            <h2>Detalhamento do Período</h2>
            <p style="color: #666; margin-bottom: 20px;">
                Período: <?php echo date('d/m/Y', strtotime($dataInicio)); ?> até <?php echo date('d/m/Y', strtotime($dataFim)); ?>
            </p>

            <?php if (count($abastecimentos) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>KM</th>
                            <th>Valor</th>
                            <th>Litros</th>
                            <th>R$/Litro</th>
                            <th>KM Rodados</th>
                            <th>Consumo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $kmAnterior = null;
                        foreach ($abastecimentos as $abast): 
                            $kmRodadosItem = null;
                            $consumoItem = null;
                            
                            if ($kmAnterior !== null) {
                                $$kmRodadosItem =$kmAnterior - $abast['km_atual'];
                                if ($abast['litros'] && $kmRodadosItem > 0) {
                                    $consumoItem =$kmRodadosItem / $abast['litros'];
                                }
                            }
                            $kmAnterior = $abast['km_atual'];
                        ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($abast['data_abastecimento'])); ?></td>
                                <td><?php echo number_format($abast['km_atual'], 0, '', '.'); ?> km</td>
                                <td><strong>R$ <?php echo number_format($abast['valor_pago'], 2, ',', '.'); ?></strong></td>
                                <td>
                                    <?php 
                                    echo $abast['litros'] 
                                        ? number_format($abast['litros'], 2, ',', '.') . ' L' 
                                        : '-';
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($abast['litros']) {
                                        echo 'R$$' . number_format($abast['valor_pago'] / $abast['litros'], 2, ',', '.');
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    echo $kmRodadosItem !== null 
                                        ? number_format($kmRodadosItem, 0, '', '.') . ' km' 
                                        : '-';
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    echo $consumoItem !== null 
                                        ? number_format($consumoItem, 1, ',', '.') . ' km/L' 
                                        : '-';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <button onclick="window.print()" class="btn-print">🖨️ Imprimir Relatório</button>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">
                    Nenhum registro encontrado no período selecionado.
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
