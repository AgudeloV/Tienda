<?php
include "conexion.php";

// Obtener datos del usuario
$usuario = $conn->query("SELECT * FROM usuarios WHERE id=1")->fetch_assoc();

// Agregar saldo
if (isset($_POST["agregar_saldo"])) {
    $monto = floatval($_POST["monto"]);
    $conn->query("UPDATE usuarios SET saldo = saldo + $monto WHERE id=1");
    header("Location: index.php");
}

// Crear pedido
if (isset($_POST["crear_pedido"])) {
    $conn->query("INSERT INTO pedidos (id_usuario, total) VALUES (1, 0)");
    $idPedido = $conn->insert_id;

    foreach ($_POST["producto"] as $i => $idProd) {
        $cant = intval($_POST["cantidad"][$i]);
        if ($cant < 1) continue;

        $p = $conn->query("SELECT precio FROM productos WHERE id=$idProd")->fetch_assoc();
        $subtotal = $p["precio"] * $cant;

        $conn->query("INSERT INTO pedido_items (id_pedido, id_producto, cantidad, subtotal)
                      VALUES ($idPedido, $idProd, $cant, $subtotal)");
    }

    // Recalcular total
    $conn->query("UPDATE pedidos 
                  SET total = (SELECT SUM(subtotal) FROM pedido_items WHERE id_pedido=$idPedido)
                  WHERE id=$idPedido");

    header("Location: index.php");
}

// Eliminar pedido
if (isset($_GET["borrar"])) {
    $id = intval($_GET["borrar"]);
    $conn->query("DELETE FROM pedido_items WHERE id_pedido=$id");
    $conn->query("DELETE FROM pedidos WHERE id=$id");
    header("Location: index.php");
}

// Obtener productos
$productos = $conn->query("SELECT * FROM productos");

// Obtener pedidos
$pedidos = $conn->query("
    SELECT p.*, 
           (SELECT SUM(cantidad) FROM pedido_items WHERE id_pedido=p.id) AS items
    FROM pedidos p ORDER BY p.id DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Pedidos - Tienda Electrónica</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

<h1>📦 Gestión de Pedidos</h1>

<div class="saldo-box">
    <h3>Usuario: <strong><?= $usuario["nombre"] ?></strong></h3>
    <p>Saldo actual: <strong>$<?= number_format($usuario["saldo"], 2) ?></strong></p>

    <form method="post">
        <input type="number" name="monto" step="0.01" placeholder="Monto a añadir">
        <button name="agregar_saldo">Añadir saldo</button>
    </form>
</div>

<hr>

<h2>🛒 Crear Nuevo Pedido</h2>

<form method="post" class="pedido-form">
    <div id="productos-container">
        <div class="item">
            <select name="producto[]">
                <?php while($p = $productos->fetch_assoc()): ?>
                    <option value="<?= $p["id"] ?>">
                        <?= $p["nombre"] ?> - $<?= $p["precio"] ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <input type="number" name="cantidad[]" value="1" min="1">
        </div>
    </div>

    <button type="button" onclick="addProduct()">+ Añadir producto</button>
    <button name="crear_pedido" class="crear">Crear pedido</button>
</form>

<hr>

<h2>📋 Pedidos Existentes</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Fecha</th>
        <th>Items</th>
        <th>Total</th>
        <th>Acciones</th>
    </tr>

    <?php while($p = $pedidos->fetch_assoc()): ?>
    <tr>
        <td><?= $p["id"] ?></td>
        <td><?= $p["fecha"] ?></td>
        <td><?= $p["items"] ?></td>
        <td>$<?= number_format($p["total"], 2) ?></td>
        <td>
            <a class="btn borrar" href="?borrar=<?= $p["id"] ?>">Borrar</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

</div>

<script>
function addProduct() {
    const cont = document.getElementById("productos-container");
    const div = document.createElement("div");
    div.classList.add("item");
    div.innerHTML = `<?= str_replace("\n", "", addslashes('
        <select name="producto[]">
            <?php
            $productos2 = $conn->query("SELECT * FROM productos");
            while($p = $productos2->fetch_assoc()): ?>
                <option value="'.$p["id"].'">
                    '.$p["nombre"].' - $'.$p["precio"].'
                </option>
            <?php endwhile; ?>
        </select>
        <input type="number" name="cantidad[]" value="1" min="1">
    ')) ?>`;
    cont.appendChild(div);
}
</script>

</body>
</html>
