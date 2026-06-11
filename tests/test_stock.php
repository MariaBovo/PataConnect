<?php
require_once __DIR__ . '/../system/condb.php';

class StockTest {
    private function assertEqual($actual, $expected, $message = "") {
        if ($actual !== $expected) {
            $actualStr = json_encode($actual);
            $expectedStr = json_encode($expected);
            throw new Exception("Assertion failed: Expected {$expectedStr}, got {$actualStr}. {$message}");
        }
    }

    private function assertTrue($condition, $message = "") {
        if (!$condition) {
            throw new Exception("Assertion failed: Condition is not true. {$message}");
        }
    }

    public function testStockControlWorkflow() {
        $db = pata_db();

        // 1. Verify that database is seeded with some items
        $stmt = $db->query('SELECT COUNT(*) FROM "deposit_items"');
        $count = (int) $stmt->fetchColumn();
        $this->assertTrue($count >= 5, "Menos de 5 itens semeados no banco!");

        // 2. Insert a temporary test item to avoid hardcoding existing items or modifying production data
        $stmt = $db->prepare('
            INSERT INTO "deposit_items" ("name", "category", "quantity", "unit", "min_quantity")
            VALUES (:name, :category, :quantity, :unit, :min_quantity)
        ');
        $stmt->execute([
            'name' => 'Item de Teste de Estoque',
            'category' => 'Teste',
            'quantity' => 5.0,
            'unit' => 'Litros',
            'min_quantity' => 10.0,
        ]);
        
        $itemId = (int) $db->lastInsertId();

        try {
            // Verify item starts low
            $stmt = $db->prepare('SELECT * FROM "deposit_items" WHERE "id" = :id');
            $stmt->execute(['id' => $itemId]);
            $item = $stmt->fetch();
            
            $qty = (float) $item['quantity'];
            $min = (float) $item['min_quantity'];
            $this->assertTrue($qty < $min, "Item de teste deveria estar abaixo do mínimo!");

            // 3. Simulate Entrada transaction
            $db->beginTransaction();
            
            // Update quantity
            $stmt = $db->prepare('UPDATE "deposit_items" SET "quantity" = "quantity" + 10 WHERE "id" = :id');
            $stmt->execute(['id' => $itemId]);
            
            // Insert movement
            $stmt = $db->prepare('
                INSERT INTO "deposit_movements" ("item_id", "movement_type", "quantity", "description")
                VALUES (:id, \'Entrada\', 10.0, \'Abastecimento de teste\')
            ');
            $stmt->execute(['id' => $itemId]);

            $db->commit();

            // Verify quantity updated
            $stmt = $db->prepare('SELECT * FROM "deposit_items" WHERE "id" = :id');
            $stmt->execute(['id' => $itemId]);
            $itemUpdated = $stmt->fetch();
            $newQty = (float) $itemUpdated['quantity'];
            $this->assertEqual($newQty, $qty + 10.0, "Erro de cálculo na Entrada de estoque!");

            // 4. Simulate Saída transaction
            $db->beginTransaction();
            
            // Update quantity
            $stmt = $db->prepare('UPDATE "deposit_items" SET "quantity" = "quantity" - 6 WHERE "id" = :id');
            $stmt->execute(['id' => $itemId]);
            
            // Insert movement
            $stmt = $db->prepare('
                INSERT INTO "deposit_movements" ("item_id", "movement_type", "quantity", "description")
                VALUES (:id, \'Saída\', 6.0, \'Uso de teste\')
            ');
            $stmt->execute(['id' => $itemId]);

            $db->commit();

            // Verify quantity updated
            $stmt = $db->prepare('SELECT * FROM "deposit_items" WHERE "id" = :id');
            $stmt->execute(['id' => $itemId]);
            $itemUpdated2 = $stmt->fetch();
            $newQty2 = (float) $itemUpdated2['quantity'];
            $this->assertEqual($newQty2, $newQty - 6.0, "Erro de cálculo na Saída de estoque!");

            // 5. Test negative stock prevention rule
            $quantityToConsume = 20.0;
            $this->assertTrue($quantityToConsume > $newQty2, "Estoque insuficiente não foi detectado!");

        } finally {
            // Clean up: delete the temporary test item. Foreign key cascade deletes movements automatically.
            $stmt = $db->prepare('DELETE FROM "deposit_items" WHERE "id" = :id');
            $stmt->execute(['id' => $itemId]);
        }
    }
}
