<?php
session_start();

define('USUARIO_CORRETO', 'saulo');
define('SENHA_HASH', hash('sha256', 'senha123'));

if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
    header('Location: index.php');
    exit();
}

if (isset($_COOKIE['usuario_logado']) && $_COOKIE['usuario_logado'] === 'saulo') {
    $_SESSION['logado'] = true;
    $_SESSION['usuario'] = 'saulo';
    header('Location: index.php');
    exit();
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $lembrar = isset($_POST['lembrar']);
    
    if ($usuario === USUARIO_CORRETO && hash('sha256', $senha) === SENHA_HASH) {
        $_SESSION['logado'] = true;
        $_SESSION['usuario'] = $usuario;
        
        if ($lembrar) {
            setcookie('usuario_logado', $usuario, time() + (30 * 24 * 60 * 60), '/', '', false, true);
        }
        
        header('Location: index.php');
        exit();
        
    } else {
        $erro = 'Usuário ou senha incorretos!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gestão de Gasolina e Óleo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            width: 100%;
            max-width: 420px;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc2626;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 8px;
            cursor: pointer;
            accent-color: #667eea;
        }

        .checkbox-group label {
            color: #666;
            font-size: 14px;
            cursor: pointer;
            user-select: none;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .login-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e5e7eb;
        }

        .login-footer p {
            color: #9ca3af;
            font-size: 13px;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }

            .login-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🚗 Gestão de Gasolina e Óleo</h1>
            <p>Faça login para acessar o sistema</p>
        </div>
        
        <?php if ($erro): ?>
            <div class="alert-error">
                ⚠️ <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="usuario">Usuário</label>
                <input 
                    type="text" 
                    id="usuario" 
                    name="usuario" 
                    placeholder="Digite seu usuário" 
                    required 
                    autofocus
                    value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>"
                >
            </div>
            
            <div class="form-group">
                <label for="senha">Senha</label>
                <input 
                    type="password" 
                    id="senha" 
                    name="senha" 
                    placeholder="Digite sua senha" 
                    required
                >
            </div>
            
            <div class="checkbox-group">
                <input 
                    type="checkbox" 
                    id="lembrar" 
                    name="lembrar"
                    <?php echo isset($_POST['lembrar']) ? 'checked' : ''; ?>
                >
                <label for="lembrar">Lembrar login</label>
            </div>
            
            <button type="submit" class="btn-login">Entrar no Sistema</button>
        </form>
        
        <div class="login-footer">
            <p>Sistema de Gestão v1.0</p>
        </div>
    </div>
</body>
</html>
