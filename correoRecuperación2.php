<?php
require 'conexión.php';

$conexion = getDatabaseConnection();

$correo = $_GET['correo'] ?? '';
$token = $_GET['token'] ?? '';

if (!$correo || !$token) {
  echo "Parámetros inválidos.";
  exit;
}

// Verificar que el token existe y no ha expirado
$stmt = mysqli_prepare($conexion, "SELECT expires_at FROM recuperaciones WHERE user_id = (SELECT id_usuario FROM usuario WHERE correo = ?) AND token = ?");
if (!$stmt) {
  echo "Error al preparar la consulta.";
  exit;
}

mysqli_stmt_bind_param($stmt, "ss", $correo, $token);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $expires_at);

if (!mysqli_stmt_fetch($stmt)) {
  echo "Token inválido o no encontrado.";
  mysqli_stmt_close($stmt);
  exit;
}

mysqli_stmt_close($stmt);

// Verificar expiración
if (strtotime($expires_at) < time()) {
  echo "El token ha expirado. Por favor, solicita una nueva recuperación.";
  exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nueva = $_POST['nueva'] ?? '';
  $confirmar = $_POST['confirmar'] ?? '';

  if (empty($nueva) || empty($confirmar)) {
    $error = "Por favor completa ambos campos.";
  } elseif ($nueva !== $confirmar) {
    $error = "Las contraseñas no coinciden.";
  } else {
    $hash = password_hash($nueva, PASSWORD_DEFAULT);

    // Actualizar contraseña
    $stmtUpd = mysqli_prepare($conexion, "UPDATE usuario SET contrasena = ? WHERE correo = ?");
    if ($stmtUpd) {
      mysqli_stmt_bind_param($stmtUpd, "ss", $hash, $correo);
      if (mysqli_stmt_execute($stmtUpd)) {
        $success = "Contraseña actualizada correctamente.";
      } else {
        $error = "Error al actualizar la contraseña.";
      }
      mysqli_stmt_close($stmtUpd);
    } else {
      $error = "Error al preparar la consulta de actualización.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <title>Restaurar contraseña</title>
  <style>
    :root {
      font-family: system-ui, Avenir, Helvetica, Arial, sans-serif;
      line-height: 1.5;
      font-weight: 400;

      color-scheme: light dark;
      color: rgba(255, 255, 255, 0.87);
      background: linear-gradient(to right, #ffffff, #0905ff), linear-gradient(to left, #292727, #b91ed8);
      background-blend-mode: multiply;
      font-synthesis: none;
      text-rendering: optimizeLegibility;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;

    }

    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background-repeat: no-repeat;
      background-attachment: fixed;
    }

    h2 {
      text-align: center;
      color: #0ff;
      font-size: 2.5rem;
      margin-bottom: 2rem;
      text-shadow:
        0 0 4px #0ff,
        0 0 8px #c0f,
        0 0 16px #c0f;
    }

    form {
      background: rgba(0, 0, 0, 0.7);
      padding: 2rem;
      border-radius: 20px;
      max-width: 400px;
      width: 100%;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.4);
      display: flex;
      flex-direction: column;
      gap: 1rem;
      animation: fadeInUp 0.5s ease;
    }

    label {
      font-weight: 600;
      color: #0ff;
      font-size: 1.1rem;
    }

    input[type="password"] {
      padding: 0.8em;
      font-size: 1em;
      border-radius: 8px;
      border: 1px solid #ccc;
      background-color: #1a1a1a;
      color: white;
      transition: border-color 0.3s ease;
    }

    input[type="password"]:focus {
      border-color: #646cff;
      outline: none;
      box-shadow: 0 0 5px #646cffaa;
    }

    button {
      border-radius: 8px;
      border: 1px solid transparent;
      padding: 0.6em 1.2em;
      font-size: 1em;
      font-weight: 500;
      font-family: inherit;
      background-color: #1a1a1a;
      cursor: pointer;
      transition: border-color 0.25s, transform 0.1s ease, box-shadow 0.1s ease, background-color 0.1s ease;
      margin: 0 auto;
      display: inline-block;
      text-align: center;
    }

    button:hover {
      border-color: #646cff;
    }

    button:focus,
    button:focus-visible {
      outline: 4px auto -webkit-focus-ring-color;
    }

    button:active {
      transform: scale(0.95);
      box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.3);
      background-color: #333;
    }

    a {
      display: inline-block;
      margin-top: 1.5rem;
      color: #646cff;
      text-decoration: none;
      font-weight: 600;
      text-align: center;
    }

    a:hover {
      text-decoration: underline;
    }

    .error {
      background-color: #ffcccc;
      color: #a00;
      padding: 10px;
      border: 1px solid #a00;
      border-radius: 6px;
      margin-bottom: 10px;
      text-align: center;
    }

    .success {
      background-color: #ccffcc;
      color: #080;
      padding: 10px;
      border: 1px solid #080;
      border-radius: 6px;
      margin-bottom: 10px;
      text-align: center;
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
</head>

<body>
  <div>
    <h2>Restaurar contraseña</h2>

    <?php if ($error): ?>
      <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="success"><?php echo htmlspecialchars($success); ?></div>
      <a href="http://localhost:5173">Volver a la página</a>
    <?php endif; ?>

    <?php if (!$success): ?>
      <form method="post" novalidate>
        <label for="nueva">Nueva contraseña:</label>
        <input type="password" id="nueva" name="nueva" required autocomplete="new-password" />

        <label for="confirmar">Confirmar nueva contraseña:</label>
        <input type="password" id="confirmar" name="confirmar" required autocomplete="new-password" />

        <button type="submit">Cambiar contraseña</button>
      </form>
    <?php endif; ?>
  </div>
</body>

</html>