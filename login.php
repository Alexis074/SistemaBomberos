<?php
date_default_timezone_set('America/Asuncion');
if (!function_exists('getBasePath')) {
    include __DIR__ . '/includes/config.php';
}
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/session.php';

$error = '';

// Si ya está logueado, redirigir al inicio
if (estaLogueado()) {
    header('Location: ' . $base_url . 'index.php');
    exit();
}

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identificador = isset($_POST['identificador']) ? trim($_POST['identificador']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($identificador) || empty($password)) {
        $error = 'Por favor, complete todos los campos.';
    } else {
        // Buscar por usuario o código de juramento
        $stmt = $pdo->prepare("
            SELECT u.id, u.usuario, u.password, u.nombre, u.rol, u.activo, u.bombero_id,
                   b.nombre as bombero_nombre, b.apellido as bombero_apellido
            FROM usuarios u
            LEFT JOIN bomberos b ON u.bombero_id = b.id
            WHERE (u.usuario = ? OR u.codigo_juramento = ?) AND u.activo = 1
        ");
        $stmt->execute(array($identificador, $identificador));
        $user = $stmt->fetch();
        
        if ($user) {
            // Verificar contraseña (comparación directa sin hash)
            if ($password === $user['password']) {
                // Iniciar sesión
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario'] = $user['usuario'];
                $_SESSION['nombre'] = $user['nombre'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['bombero_id'] = $user['bombero_id'];
                
                header('Location: ' . $base_url . 'index.php');
                exit();
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } else {
            $error = 'Usuario o código de juramento no encontrado.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Control de Asistencias de Bomberos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 50%, #7f1d1d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            background: white;
            padding: 50px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 100%;
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .login-header .icon {
            font-size: 64px;
            color: #dc2626;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        .login-header h1 {
            color: #1e293b;
            margin-bottom: 10px;
            font-size: 28px;
            font-weight: 700;
        }
        .login-header p {
            color: #64748b;
            font-size: 14px;
        }
        .login-form label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-weight: 600;
            font-size: 14px;
        }
        .login-form .input-group {
            position: relative;
            margin-bottom: 25px;
        }
        .login-form .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        .login-form input {
            width: 100%;
            padding: 14px 14px 14px 45px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            background: #f8fafc;
        }
        .login-form input:focus {
            outline: none;
            border-color: #dc2626;
            background: white;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }
        .login-form button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
        }
        .login-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4);
        }
        .login-form button:active {
            transform: translateY(0);
        }
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
            border: 1px solid #fecaca;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .login-info-box {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 2px solid #fecaca;
            border-radius: 12px;
            padding: 20px;
            margin-top: 30px;
        }
        .login-info-box h3 {
            color: #991b1b;
            margin: 0 0 15px 0;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .login-info-box .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
            color: #7f1d1d;
            font-size: 14px;
        }
        .login-info-box .info-item i {
            color: #dc2626;
            width: 20px;
        }
        .login-info-box .info-item strong {
            color: #991b1b;
            min-width: 140px;
        }
        .help-text {
            text-align: center;
            color: #64748b;
            font-size: 13px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="icon">
                <i class="fas fa-fire-extinguisher"></i>
            </div>
            <h1>Sistema de Control de Asistencias</h1>
            <p>Cuerpo de Bomberos Voluntarios</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="login-form">
            <label for="identificador">
                <i class="fas fa-user"></i> Usuario o Código de Juramento:
            </label>
            <div class="input-group">
                <i class="fas fa-id-card"></i>
                <input type="text" id="identificador" name="identificador" required autofocus 
                       placeholder="Ingrese usuario o código de juramento">
            </div>
            
            <label for="password">
                <i class="fas fa-lock"></i> Contraseña:
            </label>
            <div class="input-group">
                <i class="fas fa-key"></i>
                <input type="password" id="password" name="password" required 
                       placeholder="Ingrese su contraseña">
            </div>
            
            <button type="submit">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>
        
        <div class="login-info-box">
            <h3><i class="fas fa-info-circle"></i> Credenciales por defecto</h3>
            <div class="info-item">
                <i class="fas fa-user"></i>
                <strong>Usuario:</strong> <span>admin</span>
            </div>
            <div class="info-item">
                <i class="fas fa-lock"></i>
                <strong>Contraseña:</strong> <span>admin123</span>
            </div>
        </div>
        
        <p class="help-text">
            Puede ingresar con su usuario o código de juramento
        </p>
    </div>
</body>
</html>
