<?php
session_start();

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: ../login.php');
    exit();
}

require_once '../config/database.php';

$sucesso = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data =$_POST['data_troca'];
    $km = (int)$_POST['km_troca'];
    $valor = (float)str_replace(',', '.',$_POST['valor_pago']);
    $tipo_oleo = trim($_POST['tipo_oleo']);
    $observacao = trim($_POST['observacao']);
    
    if (empty($data) || empty($km) || empty($valor)) {
        $erro = 'Preencha todos os campos obrigatórios!';
    } else {
        try {
            $sql = "INSERT INTO trocas_oleo (data_troca, km_troca, valor_pago, tipo_oleo, observacao) 
                    VALUES (:data, :km, :valor, :tipo, :obs)";
            
            executarQuery($sql, [
                ':data' => $data,
                ':km' => $km,
                ':valor' => $valor,
                ':tipo' => $tipo_oleo,
                ':obs' => $observacao
            ]);
            
            $sucesso = 'Troca de óleo cadastrada com sucesso!';
            $_POST = [];
            
        } catch (Exception $e) {
            $erro = 'Erro ao cadastrar: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Troca de Óleo</title>
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
            max-width: 1000px;
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
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 40px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group label .required {
            color: #dc2626;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-group small {
            display: block;
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 158, 11, 0.4);
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1>🛢️ Cadastrar Troca de Óleo</h1>
            <a href="../index.php" class="btn-back">← Voltar ao Dashboard</a>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <?php if ($sucesso): ?>
                <div class="alert alert-success">
                    ✅ <?php echo $sucesso; ?>
                </div>
            <?php endif; ?>

            <?php if ($erro): ?>
                <div class="alert alert-error">
                    ⚠️ <?php echo $erro; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="data_troca">
                            Data da Troca <span class="required">*</span>
                        </label>
                        <input 
                            type="date" 
                            id="data_troca" 
                            name="data_troca" 
                            value="<?php echo $_POST['data_troca'] ?? date('Y-m-d'); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="km_troca">
                            KM da Troca <span class="required">*</span>
                        </label>
                        <input 
                            type="number" 
                            id="km_troca" 
                            name="km_troca" 
                            placeholder="Ex: 5000"
                            value="<?php echo $_POST['km_troca'] ?? ''; ?>"
                            required
                        >
                        <small>Quilometragem no momento da troca</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="valor_pago">
                            Valor Pago (R$) <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="valor_pago" 
                            name="valor_pago" 
                            placeholder="Ex: 80.00"
                            value="<?php echo $_POST['valor_pago'] ?? ''; ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="tipo_oleo">
                            Tipo de Óleo
                        </label>
                        <select id="tipo_oleo" name="tipo_oleo">
                            <option value="">Selecione...</option>
                            <option value="Mineral 15W40" <?php echo (isset($_POST['tipo_oleo']) && $_POST['tipo_oleo'] === 'Mineral 15W40') ? 'selected' : ''; ?>>Mineral 15W40</option>
                            <option value="Semissintético 10W40" <?php echo (isset($_POST['tipo_oleo']) && $_POST['tipo_oleo'] === 'Semissintético 10W40') ? 'selected' : ''; ?>>Semissintético 10W40</option>
                            <option value="Sintético 5W40" <?php echo (isset($_POST['tipo_oleo']) && $_POST['tipo_oleo'] === 'Sintético 5W40') ? 'selected' : ''; ?>>Sintético 5W40</option>
                            <option value="Sintético 10W30" <?php echo (isset($_POST['tipo_oleo']) && $_POST['tipo_oleo'] === 'Sintético 10W30') ? 'selected' : ''; ?>>Sintético 10W30</option>
                            <option value="Outro" <?php echo (isset($_POST['tipo_oleo']) && $_POST['tipo_oleo'] === 'Outro') ? 'selected' : ''; ?>>Outro</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="observacao">Observações</label>
                    <textarea 
                        id="observacao" 
                        name="observacao" 
                        placeholder="Ex: Marca do óleo, troca de filtro, oficina..."
                    ><?php echo $_POST['observacao'] ?? ''; ?></textarea>
                </div>

                <button type="submit" class="btn-submit">💾 Salvar Troca de Óleo</button>
            </form>
        </div>
    </div>
</body>
</html>
