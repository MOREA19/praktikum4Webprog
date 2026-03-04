<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../config/database.php";

$database = new Database();
$db       = $database->getConnection();

// Parse URI
$request_uri  = $_SERVER['REQUEST_URI'];
$base_path    = '/praktikum4Webprog/api';
$path         = str_replace($base_path, '', parse_url($request_uri, PHP_URL_PATH));
$path         = trim($path, '/');
$segments     = explode('/', $path);

$resource = $segments[0] ?? '';
$id       = isset($segments[1]) && is_numeric($segments[1]) ? (int)$segments[1] : null;
$method   = $_SERVER['REQUEST_METHOD'];

// Route to handler
switch ($resource) {
    case 'products':
        handleProducts($db, $method, $id);
        break;
    default:
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Endpoint not found."]);
        break;
}

// ─── Products Handler ─────────────────────────────────────────────────────────

function handleProducts(PDO $db, string $method, ?int $id): void {
    switch ($method) {
        case 'GET':
            $id ? getProduct($db, $id) : getAllProducts($db);
            break;
        case 'POST':
            createProduct($db);
            break;
        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Product ID is required."]);
                return;
            }
            updateProduct($db, $id);
            break;
        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Product ID is required."]);
                return;
            }
            deleteProduct($db, $id);
            break;
        default:
            http_response_code(405);
            echo json_encode(["status" => "error", "message" => "Method not allowed."]);
            break;
    }
}

// ─── CRUD Functions ───────────────────────────────────────────────────────────

function getAllProducts(PDO $db): void {
    $stmt = $db->prepare("SELECT * FROM products ORDER BY created_at DESC");
    $stmt->execute();
    $products = $stmt->fetchAll();

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "total"  => count($products),
        "data"   => $products
    ]);
}

function getProduct(PDO $db, int $id): void {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch();

    if (!$product) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Product not found."]);
        return;
    }

    http_response_code(200);
    echo json_encode(["status" => "success", "data" => $product]);
}

function createProduct(PDO $db): void {
    $body = json_decode(file_get_contents("php://input"), true);

    $name        = trim($body['name'] ?? '');
    $category    = trim($body['category'] ?? '');
    $price       = $body['price'] ?? null;
    $stock       = $body['stock'] ?? null;
    $description = trim($body['description'] ?? '');

    if (empty($name) || empty($category) || $price === null || $stock === null) {
        http_response_code(422);
        echo json_encode([
            "status"  => "error",
            "message" => "Fields name, category, price, and stock are required."
        ]);
        return;
    }

    if (!is_numeric($price) || $price < 0) {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "Price must be a non-negative number."]);
        return;
    }

    if (!is_numeric($stock) || $stock < 0) {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "Stock must be a non-negative integer."]);
        return;
    }

    $stmt = $db->prepare(
        "INSERT INTO products (name, category, price, stock, description) VALUES (:name, :category, :price, :stock, :description)"
    );
    $stmt->bindParam(':name',        $name);
    $stmt->bindParam(':category',    $category);
    $stmt->bindParam(':price',       $price);
    $stmt->bindParam(':stock',       $stock,       PDO::PARAM_INT);
    $stmt->bindParam(':description', $description);
    $stmt->execute();

    $newId = $db->lastInsertId();

    http_response_code(201);
    echo json_encode([
        "status"  => "success",
        "message" => "Product created successfully.",
        "data"    => ["id" => (int)$newId]
    ]);
}

function updateProduct(PDO $db, int $id): void {
    // Verify exists
    $check = $db->prepare("SELECT id FROM products WHERE id = :id LIMIT 1");
    $check->bindParam(':id', $id, PDO::PARAM_INT);
    $check->execute();
    if (!$check->fetch()) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Product not found."]);
        return;
    }

    $body = json_decode(file_get_contents("php://input"), true);

    $name        = trim($body['name'] ?? '');
    $category    = trim($body['category'] ?? '');
    $price       = $body['price'] ?? null;
    $stock       = $body['stock'] ?? null;
    $description = trim($body['description'] ?? '');

    if (empty($name) || empty($category) || $price === null || $stock === null) {
        http_response_code(422);
        echo json_encode([
            "status"  => "error",
            "message" => "Fields name, category, price, and stock are required."
        ]);
        return;
    }

    $stmt = $db->prepare(
        "UPDATE products SET name = :name, category = :category, price = :price, stock = :stock, description = :description WHERE id = :id"
    );
    $stmt->bindParam(':name',        $name);
    $stmt->bindParam(':category',    $category);
    $stmt->bindParam(':price',       $price);
    $stmt->bindParam(':stock',       $stock,       PDO::PARAM_INT);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':id',          $id,          PDO::PARAM_INT);
    $stmt->execute();

    http_response_code(200);
    echo json_encode([
        "status"  => "success",
        "message" => "Product updated successfully.",
        "data"    => ["id" => $id]
    ]);
}

function deleteProduct(PDO $db, int $id): void {
    $check = $db->prepare("SELECT id FROM products WHERE id = :id LIMIT 1");
    $check->bindParam(':id', $id, PDO::PARAM_INT);
    $check->execute();
    if (!$check->fetch()) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Product not found."]);
        return;
    }

    $stmt = $db->prepare("DELETE FROM products WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    http_response_code(200);
    echo json_encode([
        "status"  => "success",
        "message" => "Product deleted successfully."
    ]);
}
