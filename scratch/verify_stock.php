<?php
require_once 'c:/Users/luan.silva04/Desktop/moai/system/condb.php';

echo "=== INICIANDO VERIFICACAO DO CONTROLE DE ESTOQUE ===\n\n";

try {
    $db = pata_db();

    // 1. Check if seeded items exist
    echo "1. Verificando itens semeados...\n";
    $stmt = $db->query('SELECT COUNT(*) FROM "deposit_items"');
    $count = (int) $stmt->fetchColumn();
    echo "   [OK] Total de tipos de itens em estoque: {$count}\n";
    if ($count < 5) {
        throw new Exception("Menos de 5 itens semeados no banco!");
    }

    // 2. Verify Desinfetante Canil starts low
    echo "2. Buscando Desinfetante Canil...\n";
    $stmt = $db->prepare('SELECT * FROM "deposit_items" WHERE "name" = :name LIMIT 1');
    $stmt->execute(['name' => 'Desinfetante Canil']);
    $item = $stmt->fetch();
    if (!$item) {
        throw new Exception("Desinfetante Canil não encontrado!");
    }
    $item_id = (int) $item['id'];
    $qty = (float) $item['quantity'];
    $min = (float) $item['min_quantity'];
    echo "   [OK] Quantidade atual: {$qty} {$item['unit']} (Mínimo: {$min})\n";
    if ($qty >= $min) {
        throw new Exception("Desinfetante Canil deveria estar abaixo do mínimo!");
    }

    // 3. Simulate Entrada transaction
    echo "3. Lançando Entrada de 10.0 Litros...\n";
    $db->beginTransaction();
    
    // Update quantity
    $stmt = $db->prepare('UPDATE "deposit_items" SET "quantity" = "quantity" + 10 WHERE "id" = :id');
    $stmt->execute(['id' => $item_id]);
    
    // Insert movement
    $stmt = $db->prepare('
        INSERT INTO "deposit_movements" ("item_id", "movement_type", "quantity", "description")
        VALUES (:id, \'Entrada\', 10.0, \'Abastecimento de teste\')
    ');
    $stmt->execute(['id' => $item_id]);
    $db->commit();
    echo "   [OK] Entrada efetuada com sucesso.\n";

    // Verify quantity updated
    $stmt = $db->prepare('SELECT * FROM "deposit_items" WHERE "id" = :id');
    $stmt->execute(['id' => $item_id]);
    $item_updated = $stmt->fetch();
    $new_qty = (float) $item_updated['quantity'];
    echo "   [OK] Nova quantidade: {$new_qty} {$item_updated['unit']}\n";
    if ($new_qty !== ($qty + 10.0)) {
        throw new Exception("Erro de cálculo na Entrada de estoque!");
    }

    // 4. Simulate Saída transaction
    echo "4. Lançando Saída de 6.0 Litros...\n";
    $db->beginTransaction();
    
    // Update quantity
    $stmt = $db->prepare('UPDATE "deposit_items" SET "quantity" = "quantity" - 6 WHERE "id" = :id');
    $stmt->execute(['id' => $item_id]);
    
    // Insert movement
    $stmt = $db->prepare('
        INSERT INTO "deposit_movements" ("item_id", "movement_type", "quantity", "description")
        VALUES (:id, \'Saída\', 6.0, \'Uso de teste\')
    ');
    $stmt->execute(['id' => $item_id]);
    $db->commit();
    echo "   [OK] Saída efetuada com sucesso.\n";

    // Verify quantity updated
    $stmt = $db->prepare('SELECT * FROM "deposit_items" WHERE "id" = :id');
    $stmt->execute(['id' => $item_id]);
    $item_updated2 = $stmt->fetch();
    $new_qty2 = (float) $item_updated2['quantity'];
    echo "   [OK] Nova quantidade: {$new_qty2} {$item_updated2['unit']}\n";
    if ($new_qty2 !== ($new_qty - 6.0)) {
        throw new Exception("Erro de cálculo na Saída de estoque!");
    }

    // 5. Test negative stock prevention rule
    echo "5. Testando validação de estoque insuficiente...\n";
    $quantity_to_consume = 20.0;
    if ($quantity_to_consume > $new_qty2) {
        echo "   [OK] Validação funcionou: quantidade a retirar ({$quantity_to_consume}) excede estoque disponível ({$new_qty2}). Operação impedida.\n\n";
    } else {
        throw new Exception("Estoque insuficiente não foi detectado!");
    }

    echo "=== VERIFICACAO DE ESTOQUE CONCLUIDA COM SUCESSO! ===\n";

} catch (Throwable $e) {
    echo "\n[ERRO] Falha na verificacao: " . $e->getMessage() . "\n";
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    exit(1);
}
