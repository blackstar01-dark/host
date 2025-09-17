<?php
// guardar_figura.php
header('Content-Type: application/json; charset=utf-8');

try {
    // Ruta al archivo SQLite (figuras.db en la misma carpeta)
    $dbPath = __DIR__ . '/figuras.db';

    // Conexión a SQLite con PDO
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Crear la tabla si no existe (con columna descripcion)
    $createSql = <<<SQL
    CREATE TABLE IF NOT EXISTS Figuras (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      nombre TEXT NOT NULL,
      tipo TEXT NOT NULL,
      precio REAL,
      descripcion TEXT
    );
    SQL;
    $pdo->exec($createSql);

    // --- Manejo de GET?action=list -> devolver lista de figuras ---
    if (isset($_GET['action']) && $_GET['action'] === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $rows = $pdo->query('SELECT id as codigo, nombre as Nombre, tipo as Categoria, precio as Precio, descripcion as Descripcion FROM Figuras ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // --- Manejo de POST -> insertar nueva figura ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            throw new Exception('JSON inválido o no se recibieron datos.');
        }

        // Campos esperados según formulario
        $expected = ['Nombre','Categoria','Precio','Descripcion'];
        foreach ($expected as $field) {
            if (!array_key_exists($field, $input)) {
                throw new Exception("Falta el campo: {$field}");
            }
        }

        // Normalizar datos
        $nombre   = trim($input['Nombre']);
        $tipo     = trim($input['Categoria']);
        $precio   = ($input['Precio'] === '' || $input['Precio'] === null) ? null : floatval($input['Precio']);
        $descripcion = trim($input['Descripcion']);

        // Validaciones básicas
        if ($nombre === '' || $tipo === '') {
            throw new Exception('Nombre y Categoría son obligatorios.');
        }

        // Preparar INSERT
        $sql = 'INSERT INTO Figuras (nombre, tipo, precio, descripcion) VALUES (:nombre, :tipo, :precio, :descripcion)';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
        $stmt->bindValue(':precio', $precio !== null ? $precio : null, $precio !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':descripcion', $descripcion !== '' ? $descripcion : null, $descripcion !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);

        $stmt->execute();
        $lastId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Figura registrada correctamente.',
            'last_insert_id' => $lastId
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Si llega otro método no permitido
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido. Usa GET(action=list) o POST.'], JSON_UNESCAPED_UNICODE);
    exit;

} catch (PDOException $pdoEx) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de base de datos: ' . $pdoEx->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
