<?php
require_once __DIR__ . '/../../system/auth.php';
$page = pata_page_start();

$db = pata_db();

$selected_category = trim((string) ($_POST['filter_category'] ?? 'Todos'));
$selected_alert = trim((string) ($_POST['filter_alert'] ?? 'Todos'));

if ($page['error'] === null && $page['notice'] === null) {
    if ($page['method'] === 'POST' && $page['action'] === 'record_movement') {
        $item_id = (int) ($_POST['item_id'] ?? 0);
        $movement_type = trim((string) ($_POST['movement_type'] ?? ''));
        $quantity = (float) ($_POST['quantity'] ?? 0.0);
        $description = trim((string) ($_POST['description'] ?? ''));
        
        if ($item_id <= 0) {
            $page['error'] = 'Selecione um item válido.';
        } elseif (!in_array($movement_type, ['Entrada', 'Saída'], true)) {
            $page['error'] = 'Tipo de movimentação inválido.';
        } elseif ($quantity <= 0.0) {
            $page['error'] = 'A quantidade deve ser maior que zero.';
        } else {
            try {
                $db->beginTransaction();
                
                // Fetch current item details
                $stmt = $db->prepare('SELECT * FROM "deposit_items" WHERE "id" = :id LIMIT 1');
                $stmt->execute(['id' => $item_id]);
                $item = $stmt->fetch();
                
                if (!$item) {
                    throw new Exception('Item de estoque não encontrado.');
                }
                
                $current_qty = (float) $item['quantity'];
                $new_qty = $current_qty;
                
                if ($movement_type === 'Entrada') {
                    $new_qty += $quantity;
                } else {
                    if ($quantity > $current_qty) {
                        throw new Exception("Estoque insuficiente para {$item['name']}. Quantidade disponível: {$current_qty} {$item['unit']}.");
                    }
                    $new_qty -= $quantity;
                }
                
                // 1. Update deposit_items
                $stmt = $db->prepare('UPDATE "deposit_items" SET "quantity" = :qty WHERE "id" = :id');
                $stmt->execute(['qty' => $new_qty, 'id' => $item_id]);
                
                // 2. Insert deposit_movements
                $stmt = $db->prepare('
                    INSERT INTO "deposit_movements" ("item_id", "movement_type", "quantity", "user_id", "description")
                    VALUES (:item_id, :movement_type, :quantity, :user_id, :description)
                ');
                $user = pata_current_user();
                $stmt->execute([
                    'item_id' => $item_id,
                    'movement_type' => $movement_type,
                    'quantity' => $quantity,
                    'user_id' => $user['id'] ?? null,
                    'description' => $description !== '' ? $description : null
                ]);
                
                $db->commit();
                $page['notice'] = "Movimentação registrada com sucesso para {$item['name']} ({$movement_type} de {$quantity} {$item['unit']}).";
            } catch (Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $page['error'] = $e->getMessage();
            }
        }
    }
}

// Fetch stock items
$stock_items = [];
try {
    $query = 'SELECT * FROM "deposit_items"';
    $params = [];
    $where_clauses = [];
    
    if ($selected_category !== 'Todos') {
        $where_clauses[] = '"category" = :category';
        $params['category'] = $selected_category;
    }
    
    if ($selected_alert === 'Alerta') {
        $where_clauses[] = '"quantity" < "min_quantity"';
    }
    
    if (!empty($where_clauses)) {
        $query .= ' WHERE ' . implode(' AND ', $where_clauses);
    }
    
    $query .= ' ORDER BY "name" ASC';
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $stock_items = $stmt->fetchAll();
} catch (Throwable $e) {
    $page['error'] = "Erro ao buscar itens de estoque: " . $e->getMessage();
}

// Fetch all items for dropdown
$all_dropdown_items = [];
try {
    $all_dropdown_items = $db->query('SELECT * FROM "deposit_items" ORDER BY "name" ASC')->fetchAll();
} catch (Throwable $e) {
    // Fallback
}

// Fetch movements log
$movements = [];
try {
    $stmt = $db->query('
        SELECT m.*, i.name AS item_name, i.unit AS item_unit, u.full_name AS user_name
        FROM "deposit_movements" m
        JOIN "deposit_items" i ON m.item_id = i.id
        LEFT JOIN "users" u ON m.user_id = u.id
        ORDER BY m.id DESC
        LIMIT 30
    ');
    $movements = $stmt->fetchAll();
} catch (Throwable $e) {
    $page['error'] = "Erro ao buscar log de movimentações: " . $e->getMessage();
}

// Summary Metrics
$total_types = 0;
$total_alerts = 0;
try {
    $total_types = (int) $db->query('SELECT COUNT(*) FROM "deposit_items"')->fetchColumn();
    $total_alerts = (int) $db->query('SELECT COUNT(*) FROM "deposit_items" WHERE "quantity" < "min_quantity"')->fetchColumn();
} catch (Throwable $e) {
    // Fallback
}
?>
<!DOCTYPE html>
<html>
<?php
require_once(__DIR__ . '/../components/card.php');
require_once(__DIR__ . '/../components/head.php');
?>
<body>
    <?php require(__DIR__ . '/../components/headnav.php');?>
    <hr style="border-color: #333; margin-bottom: 2rem;">
    <div class="dashboard-grid">
        <?php
            echo(component_card(
                "", 
                "Voltar", 
                "", 
                "",  
                "#212529",
                "#e8f4f8",
                "transparent",
                "/"
            ));

            echo(component_card(
                "Categorias de Itens", 
                htmlspecialchars((string) $total_types), 
                "📦", 
                "itens cadastrados no total",  
                "#e8f4f8",
                "#212529",
                "transparent"
            ));

            echo(component_card(
                "Nível Crítico", 
                htmlspecialchars((string) $total_alerts), 
                "⚠️", 
                $total_alerts > 0 ? "<b><font color=#fa5252>{$total_alerts} itens</font> abaixo do mínimo</b>" : "Estoque regular",  
                "#e8f4f8",
                "#212529",
                "transparent"
            ));
        ?>
    </div>
    <hr style="border-color: #333; margin-bottom: 2rem;">
    
    <div class="deposit-wrapper">
        <div class="page-header">
            <h2>Almoxarifado & Controle de Estoque</h2>
        </div>

        <?php if ($page['error'] !== null): ?>
            <div class="page-feedback page-feedback-error" style="margin-bottom: 1.5rem; padding: 0.75rem; background: rgba(250,82,82,0.15); border: 1px solid #fa5252; color: #fa5252; border-radius: 6px; font-weight: bold; font-size: 0.9rem;">
                <?php echo htmlspecialchars($page['error']); ?>
            </div>
        <?php elseif ($page['notice'] !== null): ?>
            <div class="page-feedback page-feedback-success" style="margin-bottom: 1.5rem; padding: 0.75rem; background: rgba(68,166,106,0.15); border: 1px solid #44a66a; color: #44a66a; border-radius: 6px; font-weight: bold; font-size: 0.9rem;">
                <?php echo htmlspecialchars($page['notice']); ?>
            </div>
        <?php endif; ?>

        <div class="deposit-grid-layout">
            <!-- Left Side: Table & Filters -->
            <div class="left-panel">
                <div class="panel-card">
                    <div class="filters-header">
                        <h3>Níveis de Estoque Atual</h3>
                        <div class="filter-actions">
                            <!-- Category Filter -->
                            <form method="POST" action="<?php echo pata_form_action(); ?>" style="display:inline-block; margin: 0;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                                <input type="hidden" name="filter_alert" value="<?php echo htmlspecialchars($selected_alert); ?>">
                                <select name="filter_category" class="form-control" onchange="this.form.submit()" style="padding: 0.35rem 0.5rem; font-size: 0.85rem; border-radius: 4px; background: #12141a; color: #fff; border: 1px solid #333;">
                                    <option value="Todos" <?php echo $selected_category === 'Todos' ? 'selected' : ''; ?>>Todas Categorias</option>
                                    <option value="Vacina" <?php echo $selected_category === 'Vacina' ? 'selected' : ''; ?>>Vacinas</option>
                                    <option value="Ração" <?php echo $selected_category === 'Ração' ? 'selected' : ''; ?>>Rações</option>
                                    <option value="Limpeza" <?php echo $selected_category === 'Limpeza' ? 'selected' : ''; ?>>Limpeza</option>
                                    <option value="Outros" <?php echo $selected_category === 'Outros' ? 'selected' : ''; ?>>Outros</option>
                                </select>
                            </form>

                            <!-- Alert Filter -->
                            <form method="POST" action="<?php echo pata_form_action(); ?>" style="display:inline-block; margin: 0;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                                <input type="hidden" name="filter_category" value="<?php echo htmlspecialchars($selected_category); ?>">
                                <select name="filter_alert" class="form-control" onchange="this.form.submit()" style="padding: 0.35rem 0.5rem; font-size: 0.85rem; border-radius: 4px; background: #12141a; color: #fff; border: 1px solid #333;">
                                    <option value="Todos" <?php echo $selected_alert === 'Todos' ? 'selected' : ''; ?>>Todos Status</option>
                                    <option value="Alerta" <?php echo $selected_alert === 'Alerta' ? 'selected' : ''; ?>>Abaixo do Mínimo</option>
                                </select>
                            </form>
                        </div>
                    </div>

                    <table class="incidents-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Categoria</th>
                                <th>Qtd Atual</th>
                                <th>Qtd Mínima</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($stock_items)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #adb5bd; padding: 2rem;">
                                    Nenhum item de estoque encontrado com os filtros selecionados.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stock_items as $item): 
                                $is_low = (float) $item['quantity'] < (float) $item['min_quantity'];
                                $status_text = $is_low ? 'Abaixo do Mínimo' : 'Regular';
                                $status_badge = $is_low ? 'badge-high' : 'badge-low';
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                    <td><span class="text-muted"><?php echo htmlspecialchars($item['category']); ?></span></td>
                                    <td>
                                        <strong style="color: <?php echo $is_low ? '#fa5252' : '#e8f4f8'; ?>;">
                                            <?php echo $item['quantity']; ?>
                                        </strong> 
                                        <span style="font-size: 0.8rem; color: #868e96;"><?php echo htmlspecialchars($item['unit']); ?></span>
                                    </td>
                                    <td><?php echo $item['min_quantity']; ?> <span style="font-size: 0.8rem; color: #868e96;"><?php echo htmlspecialchars($item['unit']); ?></span></td>
                                    <td><span class="badge <?php echo $status_badge; ?>"><?php echo $status_text; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Movements Log -->
                <div class="panel-card" style="margin-top: 1.5rem;">
                    <h3>Histórico Recente de Movimentações</h3>
                    <table class="incidents-table" style="font-size: 0.9rem;">
                        <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>Item</th>
                                <th>Operação</th>
                                <th>Qtd</th>
                                <th>Responsável</th>
                                <th>Observação</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($movements)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #adb5bd; padding: 2rem;">
                                    Nenhuma movimentação registrada ainda.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($movements as $m): 
                                $type_class = $m['movement_type'] === 'Entrada' ? 'color: #44a66a;' : 'color: #fa5252;';
                                $type_label = $m['movement_type'] === 'Entrada' ? '➕ Entrada' : '➖ Saída';
                            ?>
                                <tr>
                                    <td><span class="text-muted"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($m['created_at']))); ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($m['item_name']); ?></strong></td>
                                    <td><strong style="<?php echo $type_class; ?>"><?php echo $type_label; ?></strong></td>
                                    <td><strong><?php echo $m['quantity']; ?></strong> <span class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars($m['item_unit']); ?></span></td>
                                    <td><?php echo htmlspecialchars($m['user_name'] ?? 'Sistema'); ?></td>
                                    <td><span style="font-style: italic; color: #adb5bd;"><?php echo htmlspecialchars($m['description'] ?? '-'); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right Side: Movement form -->
            <div class="right-panel">
                <div class="panel-card">
                    <h3>Registrar Movimentação</h3>
                    <p class="text-muted" style="font-size: 0.85rem; margin-top: -0.5rem; margin-bottom: 1.5rem;">Adicione ou dê baixa em itens do estoque.</p>

                    <form action="<?php echo pata_form_action(); ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                        
                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label for="item_id">Item de Estoque</label>
                            <select id="item_id" name="item_id" class="form-control" required>
                                <option value="" disabled selected>Selecione o item...</option>
                                <?php foreach ($all_dropdown_items as $dropdown_item): ?>
                                    <option value="<?php echo $dropdown_item['id']; ?>">
                                        <?php echo htmlspecialchars($dropdown_item['name']); ?> (<?php echo htmlspecialchars($dropdown_item['category']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label for="movement_type">Tipo de Operação</label>
                            <select id="movement_type" name="movement_type" class="form-control" required>
                                <option value="Entrada">Entrada (Abastecer estoque)</option>
                                <option value="Saída">Saída (Consumir/Utilizar)</option>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label for="quantity">Quantidade</label>
                            <input type="number" id="quantity" name="quantity" class="form-control" step="0.01" min="0.01" placeholder="ex.: 5.0" required>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label for="description">Observações / Motivo</label>
                            <textarea id="description" name="description" class="form-control" rows="3" placeholder="ex.: Abastecimento mensal, Uso veterinário no pet #5" style="resize: none; font-family: inherit;"></textarea>
                        </div>

                        <button type="submit" name="action" value="record_movement" class="btn-submit" style="margin-top: 0;">Lançar Movimentação</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
<style>
    .deposit-wrapper {
        max-width: 1200px;
        margin: 2rem auto;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #ffffff;
        padding: 0 1rem;
    }

    .page-header {
        margin-bottom: 1.5rem;
    }

    .page-header h2 {
        margin: 0;
        color: #e8f4f8;
        font-size: 1.75rem;
    }

    .deposit-grid-layout {
        display: grid;
        grid-template-columns: 2.2fr 1fr;
        gap: 1.5rem;
        align-items: start;
    }

    .panel-card {
        background-color: #1a1c23;
        border: 1px solid #333333;
        border-radius: 0.5rem;
        padding: 1.5rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }

    .filters-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        border-bottom: 1px solid #333;
        padding-bottom: 0.75rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .filters-header h3,
    .panel-card h3 {
        margin: 0;
        color: #e8f4f8;
        font-size: 1.15rem;
    }

    .filter-actions {
        display: flex;
        gap: 0.5rem;
    }

    .incidents-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .incidents-table th {
        background-color: #12141a;
        color: #adb5bd;
        padding: 0.75rem 1rem;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #333333;
    }

    .incidents-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #333333;
        vertical-align: middle;
        font-size: 0.92rem;
        color: #e8f4f8;
    }

    .incidents-table tr:hover {
        background-color: #242730;
    }

    .incidents-table tr:last-child td {
        border-bottom: none;
    }

    .badge {
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-block;
    }

    .badge-high {
        background-color: rgba(250, 82, 82, 0.15);
        color: #fa5252;
        border: 1px solid rgba(250, 82, 82, 0.3);
    }

    .badge-low {
        background-color: rgba(68, 166, 106, 0.15);
        color: #44a66a;
        border: 1px solid rgba(68, 166, 106, 0.3);
    }

    .text-muted {
        color: #868e96;
        font-size: 0.85rem;
        display: block;
        margin-top: 0.2rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        font-size: 0.8rem;
        font-weight: 700;
        color: #adb5bd;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-control {
        padding: 0.65rem 0.85rem;
        border-radius: 0.25rem;
        border: 1px solid #333333;
        background-color: #12141a;
        color: #ffffff;
        font-size: 0.95rem;
        font-family: inherit;
        transition: border-color 0.2s;
    }

    .form-control:focus {
        outline: none;
        border-color: #6c757d;
    }

    .btn-submit {
        background-color: #2b8a3e;
        color: white;
        border: none;
        padding: 0.8rem;
        font-size: 1rem;
        font-weight: 700;
        border-radius: 0.25rem;
        cursor: pointer;
        width: 100%;
        transition: background-color 0.2s;
    }

    .btn-submit:hover {
        background-color: #237032;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
        width: 100%;
    }

    @media (max-width: 900px) {
        .deposit-grid-layout {
            grid-template-columns: 1fr;
        }
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
</html>
