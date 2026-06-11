<?php
require_once __DIR__ . '/../../system/auth.php';
$page = pata_page_start();

if ($page['error'] === null && $page['notice'] === null) {
    if ($page['method'] === 'POST' && $page['action'] === 'refresh_service_records') {
        $page['notice'] = 'Listagem de fichas de atendimento atualizada.';
    }

    if ($page['method'] === 'PATCH' && $page['action'] === 'close_service_record') {
        $service_record_id = (int) ($_POST['service_record_id'] ?? 0);
        if ($service_record_id > 0) {
            try {
                $statement = pata_db()->prepare('
                    UPDATE "service_records"
                    SET "animal_found" = FALSE, "animal_collected" = FALSE, "investigation_date" = CURRENT_DATE
                    WHERE "id" = :id
                ');
                $statement->execute(['id' => $service_record_id]);
                $page['notice'] = "Ficha de atendimento #{$service_record_id} marcada como Não Encontrado / Ignorado.";
            } catch (Throwable $e) {
                $page['error'] = "Erro ao atualizar banco de dados: " . $e->getMessage();
            }
        }
    }
}

// Fetch pending records from the database (where animal_found is null)
$service_records = [];
try {
    $statement = pata_db()->query('SELECT * FROM "service_records" WHERE "animal_found" IS NULL ORDER BY "id" DESC');
    $service_records = $statement->fetchAll();
} catch (Throwable $e) {
    $page['error'] = "Erro ao buscar fichas: " . $e->getMessage();
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
                "Registrar", 
                "NOVA FICHA",
                "", 
                "",  
                "#212529",
                "#e8f4f8",
                "transparent",
                "/dispatch/form"
            ));

            echo(component_card(
                "Listagem de animais", 
                "EM QUARENTENA", 
                "", 
                "",  
                "#212529",
                "#e8f4f8",
                "transparent",
                "/pets/quarentena?from=dispatch"
            ));
            //echo(component_card(
            //    "Listagem de animais", 
            //    "NÃO RESGATADOS", 
            //    "", 
            //    "",  
            //    "#212529",
            //    "#e8f4f8",
            //    "transparent",
            //    "/dispatch/ignored"
            //));
        ?>
    </div>
    <hr style="border-color: #333; margin-bottom: 2rem;">
    <div class="incidents-wrapper">
        <div class="page-header">
            <h2>Fichas de Atendimento Pendentes</h2>
            <div class="header-actions">
                <form method="POST" action="<?php echo pata_form_action(); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                    <button type="submit" name="action" value="refresh_service_records" class="btn-action btn-secondary">🔄 Atualizar</button>
                </form>
            </div>
        </div>

        <div class="table-card">
            <table class="incidents-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Solicitante</th>
                        <th>Localização</th>
                        <th>Animal Informado</th>
                        <th style="text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($service_records)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #adb5bd; padding: 2rem;">
                            Nenhuma ficha de atendimento pendente.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($service_records as $record): ?>
                        <tr>
                            <td>
                                <strong>#<?php echo htmlspecialchars($record['record_number'] ?? 'SR-'.$record['id']); ?></strong>
                                <span class="text-muted"><?php echo htmlspecialchars($record['request_date']); ?></span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($record['requester_name']); ?></strong>
                                <span class="text-muted"><?php echo htmlspecialchars($record['requester_phone']); ?></span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($record['incident_address']); ?>
                                <span class="text-muted"><?php echo htmlspecialchars($record['incident_neighborhood'] ?? ''); ?></span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($record['reported_species']); ?></strong>
                                <span class="text-muted"><?php echo htmlspecialchars(implode(', ', array_filter([
                                    $record['reported_gender'],
                                    $record['reported_size'],
                                    $record['reported_color']
                                ]))); ?></span>
                            </td>
                            <td>
                                <div class="actions-cell">
                                    <a href="/dispatch/rescue?id=<?php echo $record['id']; ?>" class="btn-action btn-primary" title="Resgatar">Resgatar</a>
                                    <form method="POST" action="<?php echo pata_form_action(); ?>">
                                        <input type="hidden" name="_method" value="PATCH">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                                        <input type="hidden" name="service_record_id" value="<?php echo $record['id']; ?>">
                                        <button type="submit" name="action" value="close_service_record" class="btn-action btn-secondary" style="border-color: #fa5252; color: #fa5252;" title="Ignorar / Não Encontrado">Ignorar</button>
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
        background-color: #8a2b3e;
        color: white;
    }

    .btn-primary:hover {
        background-color: #702332;
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
