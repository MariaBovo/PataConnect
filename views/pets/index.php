<?php
require_once __DIR__ . '/../../system/auth.php';
$page = pata_page_start();

$selected_area = trim((string) ($_POST['kennel_area'] ?? 'Todos'));

if ($page['error'] === null && $page['notice'] === null) {
    if ($page['method'] === 'POST' && $page['action'] === 'filter_pets') {
        $page['notice'] = "Listagem de animais filtrada para {$selected_area}.";
    }

    if ($page['method'] === 'POST' && $page['action'] === 'refresh_pets') {
        $page['notice'] = 'Listagem de animais atualizada.';
    }
}

// Fetch active pets from database
$pets = [];
try {
    $query = 'SELECT * FROM "pets" WHERE "status" = \'Ativo\' AND "euthanasia" = FALSE';
    $params = [];
    if ($selected_area !== 'Todos') {
        $query .= ' AND "block" = :block';
        $params['block'] = $selected_area;
    }
    $query .= ' ORDER BY "id" DESC';
    $statement = pata_db()->prepare($query);
    $statement->execute($params);
    $pets = $statement->fetchAll();
} catch (Throwable $e) {
    $page['error'] = "Erro ao buscar animais: " . $e->getMessage();
}

$selected_pet = null;
if ($page['method'] === 'POST' && $page['action'] === 'open_pet_record') {
    $pet_id = (int) ($_POST['pet_id'] ?? 0);
    if ($pet_id > 0) {
        try {
            $stmt = pata_db()->prepare('SELECT * FROM "pets" WHERE "id" = :id LIMIT 1');
            $stmt->execute(['id' => $pet_id]);
            $selected_pet = $stmt->fetch();
        } catch (Throwable $e) {
            $page['error'] = "Erro ao buscar ficha do animal: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<?php
require_once(__DIR__ . '/../components/card.php');
require_once(__DIR__ . '/../components/head.php');
require_once(__DIR__ . '/../components/animal_sheet.php');
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
                "Listagem de animais", 
                "EM QUARENTENA", 
                "", 
                "",  
                "#212529",
                "#e8f4f8",
                "transparent",
                "/pets/quarentena"
            ));

            echo(component_card(
                "Listagem de animais", 
                "ADOTADOS", 
                "", 
                "",  
                "#212529",
                "#e8f4f8",
                "transparent",
                "/pets/adotados"
            ));
        ?>
    </div>
    <hr style="border-color: #333; margin-bottom: 2rem;">
    <div class="incidents-wrapper">
        <div class="page-header">
            <h2>Listagem de Animais</h2>
            <div class="header-actions">
                <?php foreach (['Gatil', 'Quadra A', 'Quadra B', 'Quadra C', 'Quadra D', 'Quadra E'] as $kennel_area): ?>
                    <form method="POST" action="<?php echo pata_form_action(); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                        <input type="hidden" name="kennel_area" value="<?php echo htmlspecialchars($kennel_area); ?>">
                        <button type="submit" name="action" value="filter_pets" class="btn-action btn-secondary"><?php echo htmlspecialchars($kennel_area); ?></button>
                    </form>
                <?php endforeach; ?>
                <form method="POST" action="<?php echo pata_form_action(); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                    <button type="submit" name="action" value="refresh_pets" class="btn-action btn-secondary">🔄 Atualizar</button>
                </form>
            </div>
        </div>

        <div class="table-card">
            <table class="incidents-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Espécie</th>
                        <th>Sexo</th>
                        <th>Raça</th>
                        <th>Porte / Cor</th>
                        <th style="text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($pets)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #adb5bd; padding: 2rem;">
                            Nenhum animal cadastrado nesta área.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pets as $pet): ?>
                        <tr>
                            <td>
                                <strong>#PET-<?php echo $pet['id']; ?></strong>
                                <span class="text-muted"><?php echo htmlspecialchars(date('d/m/Y', strtotime($pet['created_at']))); ?></span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($pet['name']); ?></strong>
                                <?php if (!empty($pet['microchip'])): ?>
                                    <span class="text-muted" style="font-size: 0.8rem; display:block;">Chip: <?php echo htmlspecialchars($pet['microchip']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($pet['species']); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($pet['gender'] ?? 'Não informado'); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($pet['breed'] ?? 'SRD'); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($pet['size'] ?? 'Não informado'); ?></strong>
                                <span class="text-muted"><?php echo htmlspecialchars($pet['color'] ?? ''); ?></span>
                            </td>
                            <td>
                                <div class="actions-cell" style="display:flex; flex-direction:column; align-items:flex-end;">
                                    <span style="color: #7cc7aa; font-weight: bold; font-size: 0.9rem;"><?php echo htmlspecialchars($pet['block']); ?></span>
                                    <span class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars($pet['cage']); ?></span>
                                    <div style="display: flex; gap: 0.25rem; margin-top: 0.25rem;">
                                        <form method="POST" action="<?php echo pata_form_action(); ?>" style="margin: 0;">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                                            <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
                                            <input type="hidden" name="kennel_area" value="<?php echo htmlspecialchars($selected_area); ?>">
                                            <button type="submit" name="action" value="open_pet_record" class="btn-action btn-secondary" style="padding: 0.2rem 0.5rem; font-size: 0.8rem; border-radius: 4px;" title="Abrir Ficha">Ficha</button>
                                        </form>
                                        <a href="/pets/adotar?pet_id=<?php echo $pet['id']; ?>" class="btn-action btn-primary" style="padding: 0.2rem 0.5rem; font-size: 0.8rem; border-radius: 4px; text-decoration: none;" title="Adotar Animal">Adotar</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    if ($selected_pet) {
        echo component_animal_sheet($selected_pet);
    }
    ?>
</body>
<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
        width: 100%;
    }
    .incidents-wrapper {
        max-width: 1200px;
        margin: 2rem auto;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #ffffff;
        padding: 0 1rem;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .page-header h2 {
        margin: 0;
        color: #e8f4f8;
        font-size: 1.75rem;
    }

    .header-actions {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .header-actions form,
    .actions-cell form {
        margin: 0;
    }

    .table-card {
        background-color: #1a1c23;
        border: 1px solid #333333;
        border-radius: 0.5rem;
        overflow: hidden; /* Keeps the rounded corners on the table */
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }

    .incidents-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .incidents-table th {
        background-color: #12141a;
        color: #adb5bd;
        padding: 1rem 1.5rem;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #333333;
    }

    .incidents-table td {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #333333;
        vertical-align: middle;
        font-size: 0.95rem;
        color: #e8f4f8;
    }

    .incidents-table tr:hover {
        background-color: #242730;
    }

    .incidents-table tr:last-child td {
        border-bottom: none;
    }

    .badge {
        padding: 0.3rem 0.6rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-block;
    }

    .badge-high {
        background-color: rgba(250, 82, 82, 0.15);
        color: #fa5252;
        border: 1px solid rgba(250, 82, 82, 0.3);
    }

    .badge-medium {
        background-color: rgba(252, 163, 17, 0.15);
        color: #fca311;
        border: 1px solid rgba(252, 163, 17, 0.3);
    }

    .badge-low {
        background-color: rgba(43, 138, 62, 0.15);
        color: #40c057;
        border: 1px solid rgba(43, 138, 62, 0.3);
    }

    .text-muted {
        color: #868e96;
        font-size: 0.85rem;
        display: block;
        margin-top: 0.2rem;
    }

    .actions-cell {
        display: flex;
        gap: 0.5rem;
        justify-content: flex-end;
    }

    .btn-action {
        padding: 0.4rem 0.8rem;
        border-radius: 0.25rem;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .btn-primary {
        background-color: #2b8a3e;
        color: white;
    }

    .btn-primary:hover {
        background-color: #237032;
    }

    .btn-secondary {
        background-color: transparent;
        color: #e8f4f8;
        border: 1px solid #6c757d;
    }

    .btn-secondary:hover {
        background-color: #333333;
        color: white;
        border-color: #adb5bd;
    }
</style>
</html>
