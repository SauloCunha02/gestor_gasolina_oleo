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
$sql = "SELECT * FROM trocas_oleo 
        WHERE data_troca BETWEEN :inicio AND :fim 
        ORDER BY data_troca DESC";
$trocas = executarQuery($sql, [
    ':inicio' => $dataInicio,
    ':fim' => $dataFim
])->fetchAll();

// Calcular estatísticas
$totalGasto = 0;
$kmInicial = null;
$kmFinal = null;
$intervalos = [];
$kmAnt = null;

foreach ($trocas as $index => $troca) {
    $totalGasto += $troca['valor_pago'];
    
    if ($index === 0) {
        $kmFinal =$troca['km_troca'];
    }
    $kmInicial = $troca['km_troca'];
    
    if ($kmAnt !== null) {
        $intervalo = $kmAnt - $troca['km_troca'];
        if ($intervalo > 0) {
            $intervalos[] = $intervalo;
        }
    }
    $kmAnt = $troca['km_troca'];
}

$intervaloMedio = count($intervalos) > 0 ? array_sum($intervalos) / count($intervalos) : 0;
$valorMedio = count($trocas) > 0 ? $totalGasto / count($trocas) : 0;

// Tipos de óleo mais usados
$sqlTipos = "SELECT tipo_oleo, COUNT(*) as qtd 
             FROM trocas_oleo 
             WHERE data_troca BETWEEN :inicio AND :fim 
               AND tipo_oleo IS NOT NULL 
               AND tipo_oleo != ''
             GROUP BY tipo_oleo 
             ORDER BY qtd DESC";
$tiposOleo = executarQuery($sqlTipos, [
    ':inicio' => $dataInicio,
    ':fim' => $dataFim
])->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Trocas de Óleo</title>
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
            background: #f59e0b;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-filter:hover {
            background: #d97706;
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

        .cards-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
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

        .tipo-list {
            list-style: none;
            padding: 0;
        }

        .tipo-list li {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .tipo-list li:last-child {
            border-bottom: none;
        }

        .tipo-badge {
            background: #fef3c7;
            color: #92400e;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
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

            .cards-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1>📊 Relatório de Trocas de Óleo</h1>
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
                <div class="value">R$ <?php echo number_format($totalGasto, 2, ',', '.'); ?></div>
            </div>

            <div class="stat-card">
                <div class="icon">🛢️</div>
                <h3>Trocas Realizadas</h3>
                <div class="value"><?php echo count($trocas); ?></div>
            </div>

            <div class="stat-card">
                <div class="icon">📊</div>
                <h3>Valor Médio</h3>
                <div class="value">R$ <?php echo number_format($valorMedio, 2, ',', '.'); ?></div>
            </div>

            <div class="stat-card">
                <div class="icon">🛣️</div>
                <h3>Intervalo Médio</h3>
                <div class="value"><?php echo number_format($intervaloMedio, 0, '', '.'); ?> km</div>
            </div>
        </div>

        <div class="cards-row">
            <div class="report-card">
                <h2>Detalhamento do Período</h2>
                <p style="color: #666; margin-bottom: 20px;">
                    Período: <?php echo date('d/m/Y', strtotime($dataInicio)); ?> até <?php echo date('d/m/Y', strtotime($dataFim)); ?>
                </p>

                <?php if (count($trocas) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>KM</th>
                                <th>Valor</th>
                                <th>Tipo</th>
                                <th>KM desde última</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $kmAnt = null;
                            foreach ($trocas as $troca): 
                                $kmDesdeUltima = null;
                                if ($kmAnt !== null) {
                                    $kmDesdeUltima =$kmAnt - $troca['km_troca'];
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
                                            : '-';
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        echo $kmDesdeUltima !== null 
                                            ? number_format($kmDesdeUltima, 0, '', '.') . ' km' 
                                            : '-';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 40px;">
                        Nenhum registro encontrado no período selecionado.
                    </p>
                <?php endif; ?>
            </div>

            <div class="report-card">
                <h2>Tipos de Óleo Usados</h2>
                <?php if (count($tiposOleo) > 0): ?>
                    <ul class="tipo-list">
                        <?php foreach ($tiposOleo as $tipo): ?>
                            <li>
                                <span><?php echo htmlspecialchars($tipo['tipo_oleo']); ?></span>
                                <span class="tipo-badge"><?php echo $tipo['qtd']; ?>x</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p style="color: #999; text-align: center; padding: 20px;">
                        Nenhum tipo registrado
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (count($trocas) > 0): ?>
        <div class="report-card">
            <h2>Resumo Geral</h2>
            <table>
                <tr>
                    <td><strong>Total de Trocas:</strong></td>
                    <td><?php echo count($trocas); ?> trocas</td>
                </tr>
                <tr>
                    <td><strong>Total Gasto:</strong></td>
                    <td>R$ <?php echo number_format($totalGasto, 2, ',', '.'); ?></td>
                </tr>
                <tr>
                    <td><strong>Valor Médio por Troca:</strong></td>
                    <td>R$ <?php echo number_format($valorMedio, 2, ',', '.'); ?></td>
                </tr>
                <tr>
                    <td><strong>Intervalo Médio entre Trocas:</strong></td>
                    <td><?php echo number_format($intervaloMedio, 0, '', '.'); ?> km</td>
                </tr>
                <?php if (count($trocas) >= 2): ?>
                <tr>
                    <td><strong>Primeira Troca do Período:</strong></td>
                    <td><?php echo date('d/m/Y', strtotime($trocas[count($trocas)-1]['data_troca'])); ?> (<?php echo number_format($trocas[count($trocas)-1]['km_troca'], 0, '', '.'); ?> km)</td>
                </tr>
                <tr>
                    <td><strong>Última Troca do Período:</strong></td>
                    <td><?php echo date('d/m/Y', strtotime($trocas[0]['data_troca'])); ?> (<?php echo number_format($trocas[0]['km_troca'], 0, '', '.'); ?> km)</td>
                </tr>
                <?php endif; ?>
            </table>

            <button onclick="window.print()" class="btn-print">🖨️ Imprimir Relatório</button>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>

