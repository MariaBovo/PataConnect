<?php
require_once __DIR__ . '/../../system/auth.php';
$page = pata_page_start();

if ($page['error'] === null && $page['notice'] === null && $page['method'] === 'POST' && $page['action'] === 'hydrate_rescue_report') {
    $page['notice'] = 'Relatorio de fichas de atendimento hidratado nesta pagina.';
}
?>
<!DOCTYPE html>
<html>
<?php require_once(__DIR__ . '/../components/head.php'); ?>
<body>
    <?php require(__DIR__ . '/../components/headnav.php'); ?>

    <main class="report-wrapper">
        <div class="report-header">
            <div>
                <span class="eyebrow">Relatorios</span>
                <h1>Fichas de atendimento</h1>
            </div>
            <form method="POST" action="<?php echo pata_form_action(); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                <button type="submit" name="action" value="hydrate_rescue_report" class="btn-action">Atualizar</button>
            </form>
        </div>

        <section class="report-panel">
            <p>Nenhum dado consolidado de fichas de atendimento foi carregado para este relatorio.</p>
        </section>
    </main>
</body>
<style>
    .report-wrapper {
        max-width: 1100px;
        margin: 2rem auto;
        color: #f2f5f7;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .report-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .report-header h1 {
        margin: 0.2rem 0 0;
    }

    .eyebrow {
        color: #7cc7aa;
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .report-panel {
        background: #1a1c23;
        border: 1px solid #333333;
        border-radius: 8px;
        padding: 1.25rem;
    }

    .btn-action {
        background: transparent;
        color: #e8f4f8;
        border: 1px solid #6c757d;
        border-radius: 6px;
        padding: 0.55rem 0.85rem;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
    }

    .btn-action:hover {
        border-color: #7cc7aa;
    }
</style>
</html>
