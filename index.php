<?php
session_start();

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

$db = getConnection();

// Buscar dados de abastecimentos
$sqlGasolina = "SELECT * FROM abastecimentos ORDER BY data_abastecimento DESC LIMIT 10";
$abastecimentos =$db->query($sqlGasolina)->fetchAll();

$sqlGasolinaTotal = "SELECT SUM(valor_pago) as total, SUM(litros) as litros FROM abastecimentos";
$totalGasolina =$db->query($sqlGasolinaTotal)->fetch();

// Buscar dados de trocas de óleo
$sqlOleo = "SELECT * FROM trocas_oleo ORDER BY data_troca DESC LIMIT 10";
$trocasOleo =$db->query($sqlOleo)->fetchAll();

$sqlOleoTotal = "SELECT SUM(valor_pago) as total FROM trocas_oleo";
$totalOleo =$db->query($sqlOleoTotal)->fetch();

// CALCULAR ESTATÍSTICAS AVANÇADAS
$totalGeral = ($totalGasolina['total'] ?? 0) + ($totalOleo['total'] ?? 0);

// Consumo médio
$consumoMedio = 0;
if (count($abastecimentos) >= 2) {
    $kmAnterior = null;
    $consumos = [];
    foreach ($abastecimentos as $abast) {
        if ($kmAnterior !== null && $abast['litros']) {
            $kmRodados =$kmAnterior - $abast['km_atual'];
            if ($kmRodados > 0) {
                $consumos[] = $kmRodados /$abast['litros'];
            }
        }
        $kmAnterior =$abast['km_atual'];
    }
    if (count($consumos) > 0) {
        $consumoMedio = array_sum($consumos) / count($consumos);
    }
}

// KM total rodado
$kmTotal = 0;
if (count($abastecimentos) >= 2) {
    $kmTotal = $abastecimentos[0]['km_atual'] - $abastecimentos[count($abastecimentos)-1]['km_atual'];
}

// Custo por KM
$custoPorKm =$kmTotal > 0 ? $totalGeral /$kmTotal : 0;

// Último abastecimento
$ultimoAbastecimento = count($abastecimentos) > 0 ? $abastecimentos[0] : null;
$diasDesdeUltimo = null;
if ($ultimoAbastecimento) {
    $dataUltimo = new DateTime($ultimoAbastecimento['data_abastecimento']);
    $hoje = new DateTime();
    $diasDesdeUltimo = $hoje->diff($dataUltimo)->days;
}

// Última troca de óleo
$ultimaTrocaOleo = count($trocasOleo) > 0 ? $trocasOleo[0] : null;
$kmDesdeUltimaTroca = null;
$alertaTrocaOleo = false;
if ($ultimaTrocaOleo && $ultimoAbastecimento) {
    $kmDesdeUltimaTroca =$ultimoAbastecimento['km_atual'] - $ultimaTrocaOleo['km_troca'];
    // Alerta se passou de 1000km desde última troca
    if ($kmDesdeUltimaTroca > 1000) {
        $alertaTrocaOleo = true;
    }
}

// Preço médio por litro
$precoMedioLitro = 0;
$totalLitrosComValor = 0;
$somaPrecos = 0;
foreach($abastecimentos as $abast) {
    if ($abast['litros'] > 0) {
        $$somaPrecos += ($abast['valor_pago'] / $abast['litros']);
        $totalLitrosComValor++;
    }
}
if ($totalLitrosComValor > 0) {
    $precoMedioLitro =$somaPrecos / $totalLitrosComValor;
}

// Gastos mensais para gráfico
$sqlMensal = "SELECT 
    DATE_FORMAT(data_abastecimento, '%Y-%m') as mes,
    SUM(valor_pago) as total
    FROM abastecimentos 
    WHERE data_abastecimento >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY mes 
    ORDER BY mes";
$gastosMensais =$db->query($sqlMensal)->fetchAll();

$meses = [];
$valores = [];
foreach ($gastosMensais as $gasto) {
    $data = DateTime::createFromFormat('Y-m', $gasto['mes']);
    $meses[] = $data->format('M/Y');
    $valores[] = (float)$gasto['total'];
}

// Consumo mensal para gráfico
$sqlConsumoMensal = "SELECT 
    DATE_FORMAT(data_abastecimento, '%Y-%m') as mes,
    AVG(km_atual) as km_medio
    FROM abastecimentos 
    WHERE data_abastecimento >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY mes 
    ORDER BY mes";
$consumoMensal = $db->query($sqlConsumoMensal)->fetchAll();

// Combinar todos os registros
$todosRegistros = [];
foreach ($abastecimentos as $abast) {
    $todosRegistros[] = [
        'tipo' => 'gasolina',
        'data' => $abast['data_abastecimento'],
        'descricao' => 'Abastecimento - ' . number_format($abast['km_atual'], 0, '', '.') . ' km',
        'valor' => $abast['valor_pago'],
        'icone' => '⛽'
    ];
}
foreach ($trocasOleo as $troca) {
    $todosRegistros[] = [
        'tipo' => 'oleo',
        'data' => $troca['data_troca'],
        'descricao' => 'Troca de Óleo - ' . number_format($troca['km_troca'], 0, '', '.') . ' km',
        'valor' => $troca['valor_pago'],
        'icone' => '🛢️'
    ];
}

// Ordenar por data
usort($todosRegistros, function($a, $b) {
    return strtotime($b['data']) - strtotime($a['data']);
});
$todosRegistros = array_slice($todosRegistros, 0, 10);

// Gastos mês atual vs anterior
$mesAtual = date('Y-m');
$mesAnterior = date('Y-m', strtotime('-1 month'));

$sqlMesAtual = "SELECT SUM(valor_pago) as total FROM abastecimentos WHERE DATE_FORMAT(data_abastecimento, '%Y-%m') = '$mesAtual'";
$gastoMesAtual = $db->query($sqlMesAtual)->fetch()['total'] ?? 0;

$sqlMesAnterior = "SELECT SUM(valor_pago) as total FROM abastecimentos WHERE DATE_FORMAT(data_abastecimento, '%Y-%m') = '$mesAnterior'";
$gastoMesAnterior =$db->query($sqlMesAnterior)->fetch()['total'] ?? 0;

$variacao = 0;
if ($gastoMesAnterior > 0) {
    $$variacao = (($gastoMesAtual - $gastoMesAnterior) /$gastoMesAnterior) * 100;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Gasolina</title>
  <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        -webkit-tap-highlight-color: transparent;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding-bottom: 20px;
        overflow-x: hidden;
    }

    /* HEADER OTIMIZADO MOBILE */
    .header {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        padding: 15px 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .header-content {
        max-width: 100%;
        margin: 0 auto;
        padding: 0 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .header h1 {
        color: white;
        font-size: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 700;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 10px;
        color: white;
        font-size: 13px;
    }

    .user-info span {
        display: none; /* Esconde nome no mobile */
    }

    .btn-logout {
        padding: 8px 15px;
        background: rgba(255, 255, 255, 0.25);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.3s;
        white-space: nowrap;
    }

    .btn-logout:active {
        transform: scale(0.95);
        background: rgba(255, 255, 255, 0.35);
    }

    /* CONTAINER */
    .container {
        max-width: 100%;
        margin: 15px auto;
        padding: 0 12px;
    }

    /* ALERTAS - MOBILE FIRST */
    .alerts-section {
        margin-bottom: 15px;
    }

    .alert-box {
        background: white;
        padding: 12px 15px;
        border-radius: 10px;
        margin-bottom: 10px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        animation: slideIn 0.4s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .alert-box.warning {
        border-left: 4px solid #f59e0b;
        background: #fffbeb;
    }

    .alert-box.danger {
        border-left: 4px solid #dc2626;
        background: #fef2f2;
    }

    .alert-box.info {
        border-left: 4px solid #3b82f6;
        background: #eff6ff;
    }

    .alert-icon {
        font-size: 28px;
        flex-shrink: 0;
    }

    .alert-content h3 {
        color: #333;
        font-size: 14px;
        margin-bottom: 4px;
        font-weight: 600;
    }

    .alert-content p {
        color: #666;
        font-size: 12px;
        line-height: 1.5;
    }

    /* CARDS DE ESTATÍSTICAS - 2 COLUNAS NO MOBILE */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }

    .stat-card {
        background: white;
        padding: 15px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s;
    }

    .stat-card:active {
        transform: scale(0.98);
    }

    .stat-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 10px;
    }

    .stat-icon {
        font-size: 28px;
    }

    .stat-trend {
        display: flex;
        align-items: center;
        gap: 3px;
        font-size: 10px;
        font-weight: 700;
        padding: 3px 6px;
        border-radius: 8px;
    }

    .stat-trend.up {
        background: #fee2e2;
        color: #dc2626;
    }

    .stat-trend.down {
        background: #d1fae5;
        color: #059669;
    }

    .stat-label {
        color: #666;
        font-size: 11px;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 6px;
        letter-spacing: 0.3px;
    }

    .stat-value {
        color: #333;
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 4px;
        line-height: 1.2;
    }

    .stat-subtitle {
        color: #999;
        font-size: 10px;
        line-height: 1.3;
    }

    /* AÇÕES RÁPIDAS - 2 COLUNAS */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }

    .action-btn {
        background: white;
        padding: 15px;
        border-radius: 12px;
        text-decoration: none;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: all 0.2s;
        text-align: center;
        min-height: 110px;
    }

    .action-btn:active {
        transform: scale(0.97);
        box-shadow: 0 1px 5px rgba(0, 0, 0, 0.15);
    }

    .action-btn.primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .action-btn.success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }

    .action-btn.warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
    }

    .action-btn.info {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
    }

    .action-icon {
        font-size: 36px;
    }

    .action-content h3 {
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 2px;
    }

    .action-content p {
        font-size: 10px;
        opacity: 0.9;
        line-height: 1.3;
    }

    /* GRÁFICOS - EMPILHADOS NO MOBILE */
    .charts-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 15px;
        margin-bottom: 20px;
    }

    .chart-card {
        background: white;
        padding: 18px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .chart-card h2 {
        color: #333;
        font-size: 15px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
    }

    /* TABELA RESPONSIVA */
    .table-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin-bottom: 20px;
    }

    .table-header {
        padding: 15px;
        background: #f9fafb;
        border-bottom: 2px solid #e5e7eb;
    }

    .table-header h2 {
        color: #333;
        font-size: 16px;
        font-weight: 600;
    }

    /* Tabela com scroll horizontal */
    .table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 600px; /* Força scroll horizontal */
    }

    th {
        background: #f9fafb;
        padding: 10px 12px;
        text-align: left;
        font-size: 10px;
        color: #666;
        text-transform: uppercase;
        font-weight: 600;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    td {
        padding: 12px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 12px;
        white-space: nowrap;
    }

    td:first-child {
        font-weight: 600;
    }

    tr:active {
        background: #f9fafb;
    }

    .badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 600;
        white-space: nowrap;
    }

    .badge-gasolina {
        background: #dbeafe;
        color: #1e40af;
    }

    .badge-oleo {
        background: #fef3c7;
        color: #92400e;
    }

    /* SCROLL SUAVE */
    html {
        scroll-behavior: smooth;
    }

    /* OTIMIZAÇÕES PARA TOUCH */
    button, a, .stat-card, .action-btn {
        -webkit-tap-highlight-color: transparent;
        touch-action: manipulation;
    }

    /* LOADING E TRANSIÇÕES SUAVES */
    * {
        transition-property: background-color, color, border-color, transform, box-shadow;
        transition-duration: 0.2s;
        transition-timing-function: ease;
    }

    /* MEDIA QUERIES ESPECÍFICAS */

    /* LANDSCAPE MODE (A56 deitado) */
    @media (orientation: landscape) and (max-height: 450px) {
        .header {
            padding: 10px 0;
        }

        .header h1 {
            font-size: 16px;
        }

        .container {
            margin: 10px auto;
        }

        .stats-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .quick-actions {
            grid-template-columns: repeat(4, 1fr);
        }

        .action-btn {
            min-height: 90px;
            padding: 10px;
        }
    }

    /* TELAS MÉDIAS (Tablets pequenos) */
    @media (min-width: 600px) {
        .container {
            padding: 0 20px;
        }

        .stats-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .quick-actions {
            grid-template-columns: repeat(2, 1fr);
        }

        .user-info span {
            display: inline; /* Mostra nome em telas maiores */
        }
    }

    /* TABLETS */
    @media (min-width: 768px) {
        .header h1 {
            font-size: 24px;
        }

        .stats-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .stat-value {
            font-size: 24px;
        }

        .stat-label {
            font-size: 12px;
        }

        .quick-actions {
            grid-template-columns: repeat(4, 1fr);
        }

        .action-btn {
            flex-direction: row;
            justify-content: flex-start;
            padding: 18px;
            min-height: auto;
        }

        .charts-row {
            grid-template-columns: 2fr 1fr;
        }
    }

    /* DESKTOP */
    @media (min-width: 1024px) {
        .container {
            max-width: 1400px;
            padding: 0 20px;
        }

        .header-content {
            padding: 0 20px;
        }

        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        table {
            min-width: 100%;
        }
    }

    /* MELHORIAS VISUAIS PARA AMOLED */
    @media (prefers-color-scheme: dark) {
        /* Preparado para modo escuro futuro */
    }

    /* ANIMAÇÕES OTIMIZADAS PARA 120Hz */
    @media (prefers-reduced-motion: no-preference) {
        * {
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card, .action-btn {
            will-change: transform;
        }
    }

    /* OCULTAR SCROLLBAR MAS MANTER FUNCIONALIDADE */
    .table-wrapper::-webkit-scrollbar {
        height: 4px;
    }

    .table-wrapper::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .table-wrapper::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 10px;
    }

    .table-wrapper::-webkit-scrollbar-thumb:hover {
        background: #999;
    }

    /* INDICADOR DE SCROLL NA TABELA */
    .table-wrapper::after {
        content: '→';
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
        font-size: 20px;
        pointer-events: none;
        opacity: 0.5;
        animation: fadeInOut 2s infinite;
    }

    @keyframes fadeInOut {
        0%, 100% { opacity: 0.3; }
        50% { opacity: 0.7; }
    }

    /* ESPAÇAMENTO EXTRA PARA CONTEÚDO NÃO FICAR EMBAIXO DO HEADER */
    .container > *:first-child {
        margin-top: 0;
    }

    /* MELHORIAS PARA BOTÕES DE AÇÃO */
    .action-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.1);
        opacity: 0;
        transition: opacity 0.2s;
    }

    .action-btn:active::before {
        opacity: 1;
    }

    /* FEEDBACK VISUAL PARA CARDS */
    .stat-card {
        position: relative;
        overflow: hidden;
    }

    .stat-card::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(102, 126, 234, 0.1);
        transform: translate(-50%, -50%);
        transition: width 0.3s, height 0.3s;
    }

    .stat-card:active::after {
        width: 200px;
        height: 200px;
    }
</style>

</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1>🏍️ Dashboard de Controle</h1>
            <div class="user-info">
                <span>👤 <?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
                <a href="logout.php" class="btn-logout">🚪 Sair</a>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- ALERTAS -->
        <?php if ($alertaTrocaOleo ||$diasDesdeUltimo > 7): ?>
        <div class="alerts-section">
            <?php if ($alertaTrocaOleo): ?>
            <div class="alert-box danger">
                <div class="alert-icon">🛢️</div>
                <div class="alert-content">
                    <h3>Atenção! Troca de Óleo Necessária</h3>
                    <p>Já se passaram <?php echo number_format($kmDesdeUltimaTroca, 0, '', '.'); ?> km desde a última troca. Recomenda-se trocar o óleo!</p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($diasDesdeUltimo > 7): ?>
            <div class="alert-box warning">
                <div class="alert-icon">⏰</div>
                <div class="alert-content">
                    <h3>Faz tempo que você não abastece</h3>
                    <p>Último abastecimento foi há <?php echo $diasDesdeUltimo; ?> dias (<?php echo date('d/m/Y', strtotime($ultimoAbastecimento['data_abastecimento'])); ?>)</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ESTATÍSTICAS PRINCIPAIS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">💰</div>
                    <?php if ($variacao != 0): ?>
                    <div class="stat-trend <?php echo $variacao > 0 ? 'up' : 'down'; ?>">
                        <?php echo $variacao > 0 ? '↑' : '↓'; ?>
                        <?php echo abs(round($variacao)); ?>%
                    </div>
                    <?php endif; ?>
                </div>
                <div class="stat-label">Gasto Total</div>
                <div class="stat-value">R$ <?php echo number_format($totalGeral, 2, ',', '.'); ?></div>
                <div class="stat-subtitle">Gasolina + Manutenção</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">⛽</div>
                </div>
                <div class="stat-label">Consumo Médio</div>
                <div class="stat-value"><?php echo number_format($consumoMedio, 1, ',', '.'); ?> km/L</div>
                <div class="stat-subtitle"><?php echo number_format($totalGasolina['litros'] ?? 0, 1, ',', '.'); ?> litros consumidos</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">🛣️</div>
                </div>
                <div class="stat-label">Custo por KM</div>
                <div class="stat-value">R$ <?php echo number_format($custoPorKm, 2, ',', '.'); ?></div>
                <div class="stat-subtitle"><?php echo number_format($kmTotal, 0, '', '.'); ?> km rodados</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">💵</div>
                </div>
                <div class="stat-label">Preço Médio/Litro</div>
                <div class="stat-value">R$ <?php echo number_format($precoMedioLitro, 2, ',', '.'); ?></div>
                <div class="stat-subtitle">Baseado nos últimos abastecimentos</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">📅</div>
                </div>
                <div class="stat-label">Último Abastecimento</div>
                <div class="stat-value"><?php echo $diasDesdeUltimo ?? 0; ?> dias</div>
                <div class="stat-subtitle">
                    <?php 
                    if ($ultimoAbastecimento) {
                        echo date('d/m/Y', strtotime($ultimoAbastecimento['data_abastecimento']));
                    } else {
                        echo 'Nenhum registro';
                    }
                    ?>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">🛢️</div>
                </div>
                <div class="stat-label">KM desde Troca de Óleo</div>
                <div class="stat-value"><?php echo $kmDesdeUltimaTroca ? number_format($kmDesdeUltimaTroca, 0, '', '.') : '-'; ?></div>
                <div class="stat-subtitle">
                    <?php 
                    if ($ultimaTrocaOleo) {
                        echo 'Última: ' . date('d/m/Y', strtotime($ultimaTrocaOleo['data_troca']));
                    } else {
                        echo 'Nenhuma troca registrada';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- AÇÕES RÁPIDAS -->
        <div class="quick-actions">
            <a href="gasolina/cadastrar.php" class="action-btn primary">
                <div class="action-icon">⛽</div>
                <div class="action-content">
                    <h3>Cadastrar Abastecimento</h3>
                    <p>Registre um novo abastecimento</p>
                </div>
            </a>

            <a href="oleo/cadastrar.php" class="action-btn warning">
                <div class="action-icon">🛢️</div>
                <div class="action-content">
                    <h3>Cadastrar Troca de Óleo</h3>
                    <p>Registre a manutenção</p>
                </div>
            </a>

            <a href="gasolina/relatorio.php" class="action-btn success">
                <div class="action-icon">📊</div>
                <div class="action-content">
                    <h3>Relatórios</h3>
                    <p>Visualize estatísticas detalhadas</p>
                </div>
            </a>

            <a href="gasolina/listar.php" class="action-btn info">
                <div class="action-icon">📋</div>
                <div class="action-content">
                    <h3>Ver Todos os Registros</h3>
                    <p>Lista completa de abastecimentos</p>
                </div>
            </a>
             <a href="oleo/listar.php" class="action-btn info">
                <div class="action-icon">📋</div>
                <div class="action-content">
                    <h3>Ver Todos os Registros</h3>
                    <p>Lista completa de troca de Óleo</p>
                </div>
            </a>
        </div>

        <!-- GRÁFICOS -->
        <div class="charts-row">
            <div class="chart-card">
                <h2>📈 Gastos Mensais (Últimos 6 Meses)</h2>
                <canvas id="chartGastos" height="80"></canvas>
            </div>

            <div class="chart-card">
                <h2>💰 Gastos Mês Atual</h2>
                <div style="text-align: center; padding: 40px 0;">
                    <div style="font-size: 48px; font-weight: 700; color: #333; margin-bottom: 10px;">
                        R$ <?php echo number_format($gastoMesAtual, 2, ',', '.'); ?>
                    </div>
                    <div style="color: #666; font-size: 14px; margin-bottom: 20px;">
                        <?php echo date('F/Y'); ?>
                    </div>
                    <?php if ($gastoMesAnterior > 0): ?>
                    <div style="padding: 15px; background: #f9fafb; border-radius: 8px;">
                        <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Mês Anterior:</div>
                        <div style="font-size: 20px; font-weight: 600; color: #999;">
                            R$ <?php echo number_format($gastoMesAnterior, 2, ',', '.'); ?>
                        </div>
                        <div style="margin-top: 10px; font-size: 14px; font-weight: 600; color: <?php echo $variacao > 0 ? '#dc2626' : '#059669'; ?>">
                            <?php echo $variacao > 0 ? '↑' : '↓'; ?> <?php echo abs(round($variacao)); ?>%
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- TABELA DE ÚLTIMOS REGISTROS -->
        <div class="table-card">
            <div class="table-header">
                <h2>📋 Últimos 10 Registros</h2>
            </div>
            <?php if (count($todosRegistros) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                        <th>Há quantos dias</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($todosRegistros as $registro): 
                        $dataReg = new DateTime($registro['data']);
                        $hoje = new DateTime();
                        $diasAtras =$hoje->diff($dataReg)->days;
                    ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($registro['data'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $registro['tipo']; ?>">
                                    <?php echo $registro['icone']; ?> <?php echo ucfirst($registro['tipo']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($registro['descricao']); ?></td>
                            <td><strong>R$ <?php echo number_format($registro['valor'], 2, ',', '.'); ?></strong></td>
                            <td style="color: #999;"><?php echo $diasAtras == 0 ? 'Hoje' : ($diasAtras == 1 ? 'Ontem' : $diasAtras . ' dias atrás'); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<div style="padding: 60px; text-align: center; color: #999;">
<div style="font-size: 60px; margin-bottom: 20px;">📋</div>
<h3>Nenhum registro encontrado</h3>
<p>Comece cadastrando seu primeiro abastecimento!</p>
</div>
<?php endif; ?>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Gráfico de Gastos Mensais
    const ctx = document.getElementById('chartGastos').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($meses); ?>,
            datasets: [{
                label: 'Gastos (R$)',
                data: <?php echo json_encode($valores); ?>,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: '#667eea',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    callbacks: {
                        label: function(context) {
                            return 'R$ ' + context.parsed.y.toFixed(2).replace('.', ',');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + value.toFixed(0);
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
</script>
</body> 
</html> 