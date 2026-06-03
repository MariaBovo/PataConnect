<?php
require_once __DIR__ . '/../../system/auth.php';
$page = pata_page_start();

if ($page['error'] === null && $page['notice'] === null) {
    if ($page['method'] === 'POST' && $page['action'] === 'refresh_quarantine') {
        $page['notice'] = 'Listagem de quarentena atualizada.';
    }

    if ($page['method'] === 'POST' && $page['action'] === 'euthanasia_pet') {
        $pet_id = (int) ($_POST['pet_id'] ?? 0);
        if ($pet_id > 0) {
            try {
                $statement = pata_db()->prepare('
                    UPDATE "pets"
                    SET "euthanasia" = TRUE, "status" = \'Eutanasiado\'
                    WHERE "id" = :id
                ');
                $statement->execute(['id' => $pet_id]);
                $page['notice'] = "Registro de eutanásia salvo para o animal #{$pet_id}.";
            } catch (Throwable $e) {
                $page['error'] = "Erro ao registrar eutanásia: " . $e->getMessage();
            }
        }
    }

    if ($page['method'] === 'POST' && $page['action'] === 'release_from_quarantine') {
        $pet_id = (int) ($_POST['pet_id'] ?? 0);
        $block = trim((string) ($_POST['block'] ?? ''));
        $cage = trim((string) ($_POST['cage'] ?? ''));
        
        if ($pet_id > 0 && $block !== '' && $cage !== '') {
            try {
                $statement = pata_db()->prepare('
                    UPDATE "pets"
                    SET "status" = \'Ativo\', "block" = :block, "cage" = :cage
                    WHERE "id" = :id
                ');
                $statement->execute([
                    'id' => $pet_id,
                    'block' => $block,
                    'cage' => $cage
                ]);
                $page['notice'] = "Animal #{$pet_id} liberado para a moradia: Bloco {$block}, Gaiola/Baia {$cage}.";
            } catch (Throwable $e) {
                $page['error'] = "Erro ao liberar animal: " . $e->getMessage();
            }
        } else {
            $page['error'] = "Especifique o Bloco e a Gaiola para liberar o animal.";
        }
    }
}

// Fetch quarantine pets from database
$pets = [];
try {
    $statement = pata_db()->query('SELECT * FROM "pets" WHERE "status" = \'Quarentena\' AND "euthanasia" = FALSE ORDER BY "id" DESC');
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
$return_to = "/pets";
if (isset($_GET['from'])) {
    $return_to = htmlspecialchars($_GET['from']) == "dispatch" ? "/dispatch" : "/pets";
};

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
                $return_to
            ));
        ?>
    </div>
    <hr style="border-color: #333; margin-bottom: 2rem;">
    <div class="incidents-wrapper">
        <div class="page-header">
            <h2>Listagem de Animais em Quarentena</h2>
            <div class="header-actions">
                <form method="POST" action="<?php echo pata_form_action(); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                    <button type="submit" name="action" value="refresh_quarantine" class="btn-action btn-secondary">🔄 Atualizar</button>
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
                            Nenhum animal em quarentena no momento.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pets as $pet): ?>
                        <?php
                        // Calculate remaining days
                        $days_passed = 0;
                        if (!empty($pet['quarantine_start'])) {
                            $start_date = strtotime($pet['quarantine_start']);
                            $current_date = strtotime(date('Y-m-d'));
                            $days_passed = (int) (($current_date - $start_date) / 86400);
                        }
                        $remaining_days = max(0, (int) $pet['quarantine_days'] - $days_passed);
                        ?>
                        <tr>
                            <td>
                                <strong>#PET-<?php echo $pet['id']; ?></strong>
                                <span class="text-muted"><?php echo $remaining_days; ?> dias restantes</span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($pet['name']); ?></strong>
                                <?php if (!empty($pet['microchip'])): ?>
                                    <span class="text-muted" style="font-size:0.8rem; display:block;">Chip: <?php echo htmlspecialchars($pet['microchip']); ?></span>
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
                                <div class="actions-cell" style="display:flex; gap: 0.5rem; align-items:center; justify-content: flex-end; flex-wrap: wrap;">
                                    <!-- Liberar Form -->
                                    <form method="POST" action="<?php echo pata_form_action(); ?>" style="display:inline-flex; gap: 0.25rem; align-items: center; margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                                        <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
                                        
                                        <select name="block" class="form-control" style="padding: 0.3rem 0.5rem; background: #12141a; color: #fff; border: 1px solid #333; font-size: 0.85rem; border-radius: 4px; width: 100px;" required>
                                            <option value="" disabled selected>Bloco</option>
                                            <option value="Gatil">Gatil</option>
                                            <option value="Quadra A">Quadra A</option>
                                            <option value="Quadra B">Quadra B</option>
                                            <option value="Quadra C">Quadra C</option>
                                            <option value="Quadra D">Quadra D</option>
                                            <option value="Quadra E">Quadra E</option>
                                        </select>
                                        
                                        <input type="text" name="cage" placeholder="Gaiola" class="form-control" style="width: 70px; padding: 0.3rem 0.5rem; background: #12141a; color: #fff; border: 1px solid #333; font-size: 0.85rem; border-radius: 4px;" required>
                                        
                                        <button type="submit" name="action" value="release_from_quarantine" class="btn-action btn-primary" style="padding: 0.35rem 0.7rem; font-size: 0.85rem; border-radius: 4px;" title="Liberar da Quarentena">Liberar</button>
                                    </form>

                                    <!-- Eutanásia Form -->
                                    <form method="POST" action="<?php echo pata_form_action(); ?>" onsubmit="return confirm('Confirmar eutanásia para este animal?');" style="display:inline; margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                                        <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
                                        <button type="submit" name="action" value="euthanasia_pet" class="btn-action btn-secondary" style="border-color: #fa5252; color: #fa5252; padding: 0.35rem 0.7rem; font-size: 0.85rem; border-radius: 4px;" title="Marcar Eutanásia">Eutanásia</button>
                                    </form>

                                    <!-- Ficha Form -->
                                    <form method="POST" action="<?php echo pata_form_action(); ?>" style="display:inline; margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                                        <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
                                        <button type="submit" name="action" value="open_pet_record" class="btn-action btn-secondary" style="padding: 0.35rem 0.7rem; font-size: 0.85rem; border-radius: 4px;" title="Abrir Ficha">Ficha</button>
                                    </form>
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
