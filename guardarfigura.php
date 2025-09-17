<?php
// guardar_figura.php
header('Content-Type: application/json; charset=utf-8');

try {
    // Ruta al archivo SQLite (figuras.db en la misma carpeta)
    $dbPath = __DIR__ . '/figuras.db';

    // Conexión a SQLite con PDO
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Crear la tabla si no existe
    $createSql = <<<SQL
    CREATE TABLE IF NOT EXISTS Figuras (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      nombre TEXT NOT NULL,
      tipo TEXT NOT NULL,
      color TEXT,
      tamano REAL,
      material TEXT,
      precio REAL
    );
    SQL;
    $pdo->exec($createSql);

    // --- Manejo de GET?action=list -> devolver lista de figuras ---
    if (isset($_GET['action']) && $_GET['action'] === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $rows = $pdo->query('SELECT id, nombre, tipo, color, tamano, material, precio FROM Figuras ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // --- Manejo de POST -> insertar nueva figura ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            throw new Exception('JSON inválido o no se recibieron datos.');
        }

        // Campos esperados
        $expected = ['nombre','tipo','color','tamano','material','precio'];
        foreach ($expected as $field) {
            if (!array_key_exists($field, $input)) {
                throw new Exception("Falta el campo: {$field}");
            }
        }

        // Normalizar datos
        $nombre   = trim($input['nombre']);
        $tipo     = trim($input['tipo']);
        $color    = trim($input['color']);
        $tamano   = ($input['tamano'] === '' || $input['tamano'] === null) ? null : floatval($input['tamano']);
        $material = trim($input['material']);
        $precio   = ($input['precio'] === '' || $input['precio'] === null) ? null : floatval($input['precio']);

        // Validaciones básicas
        if ($nombre === '' || $tipo === '') {
            throw new Exception('Nombre y tipo son obligatorios.');
        }

        // Preparar INSERT
        $sql = 'INSERT INTO Figuras (
                    nombre,
                    tipo,
                    color,
                    tamano,
                    material,
                    precio
                ) VALUES (
                    :nombre,
                    :tipo,
                    :color,
                    :tamano,
                    :material,
                    :precio
                )';
        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
        $stmt->bindValue(':color', $color !== '' ? $color : null, PDO::PARAM_STR);
        if ($tamano === null) {
            $stmt->bindValue(':tamano', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':tamano', $tamano);
        }
        $stmt->bindValue(':material', $material !== '' ? $material : null, PDO::PARAM_STR);
        if ($precio === null) {
            $stmt->bindValue(':precio', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':precio', $precio);
        }

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
