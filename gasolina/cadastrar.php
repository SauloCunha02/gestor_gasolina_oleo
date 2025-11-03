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
    $data =$_POST['data_abastecimento'];
    $km = (int)$_POST['km_atual'];
    $valor = (float)str_replace(',', '.',$_POST['valor_pago']);
    $litros = !empty($_POST['litros']) ? (float)str_replace(',', '.', $_POST['litros']) : null;
    $observacao = trim($_POST['observacao']);
    
    if (empty($data) || empty($km) || empty($valor)) {
        $erro = 'Preencha todos os campos obrigatórios!';
    } else {
        try {
            $sql = "INSERT INTO abastecimentos (data_abastecimento, km_atual, valor_pago, litros, observacao) 
                    VALUES (:data, :km, :valor, :litros, :obs)";
            
            executarQuery($sql, [
                ':data' => $data,
                ':km' => $km,
                ':valor' => $valor,
                ':litros' => $litros,
                ':obs' => $observacao
            ]);
            
            $sucesso = 'Abastecimento cadastrado com sucesso!';
            
            // Limpar campos
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
    <title>Cadastrar Abastecimento</title>
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
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
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
            <h1>⛽ Cadastrar Abastecimento</h1>
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
                        <label for="data_abastecimento">
                            Data do Abastecimento <span class="required">*</span>
                        </label>
                        <input 
                            type="date" 
                            id="data_abastecimento" 
                            name="data_abastecimento" 
                            value="<?php echo $_POST['data_abastecimento'] ?? date('Y-m-d'); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="km_atual">
                            KM Atual <span class="required">*</span>
                        </label>
                        <input 
                            type="number" 
                            id="km_atual" 
                            name="km_atual" 
                            placeholder="Ex: 15000"
                            value="<?php echo $_POST['km_atual'] ?? ''; ?>"
                            required
                        >
                        <small>Quilometragem atual da moto</small>
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
                            placeholder="Ex: 50.00"
                            value="<?php echo $_POST['valor_pago'] ?? ''; ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="litros">
                            Litros (Opcional)
                        </label>
                        <input 
                            type="text" 
                            id="litros" 
                            name="litros" 
                            placeholder="Ex: 10.5"
                            value="<?php echo $_POST['litros'] ?? ''; ?>"
                        >
                        <small>Quantidade de litros abastecida</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="observacao">Observações</label>
                    <textarea 
                        id="observacao" 
                        name="observacao" 
                        placeholder="Ex: Posto X, Via Expressa"
                    ><?php echo $_POST['observacao'] ?? ''; ?></textarea>
                </div>

                <button type="submit" class="btn-submit">💾 Salvar Abastecimento</button>
            </form>
        </div>
    </div>
</body>
</html>
