<?php
require_once __DIR__ . '/../../system/auth.php';
$page = pata_page_start();

if ($page['error'] === null && $page['notice'] === null && $page['method'] === 'POST' && $page['action'] === 'refresh_forecast') {
    $page['notice'] = 'Previsao de estoque reidratada nesta pagina.';
}
?>
<!DOCTYPE html>
<html>
<?php
require_once('../components/card.php');
require_once('../components/head.php');

$forecast_path = __DIR__ . '/../../analytics/artifacts/stock_forecast.json';
$forecast = null;

if (file_exists($forecast_path)) {
    $forecast = json_decode(file_get_contents($forecast_path), true);
}

$summary = $forecast['summary'] ?? [];
$stock_forecast = $forecast['stock_forecast'] ?? [];
$daily_forecast = $forecast['daily_consumption_forecast'] ?? [];
$vaccine_protocol = $forecast['vaccine_protocol'] ?? [];
$model = $forecast['model'] ?? [];

$critical_items = $summary['critical_items'] ?? '-';
$warning_items = $summary['warning_items'] ?? '-';
$next_priority = $summary['next_purchase_priority'] ?? '-';
?>
<body>
    <?php require('../components/headnav.php');?>
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
                "Prioridade de compra",
                htmlspecialchars($next_priority),
                "📦",
                "item com menor cobertura",
                "#e8f4f8",
                "#212529",
                "transparent"
            ));

            echo(component_card(
                "Itens críticos",
                htmlspecialchars($critical_items),
                "⚠️",
                "comprar antes do prazo de reposição",
                "#e8f4f8",
                "#212529",
                "transparent"
            ));

            echo(component_card(
                "Itens em atenção",
                htmlspecialchars($warning_items),
                "📊",
                "monitorar na próxima compra",
                "#e8f4f8",
                "#212529",
                "transparent"
            ));
        ?>
    </div>

    <section class="stock-dashboard">
        <div class="dashboard-header">
            <div>
                <span class="eyebrow">Dashboard preditivo</span>
                <h1>Estoque Inteligente</h1>
            </div>
            <div class="model-status">
                <span>Gerado em <?php echo htmlspecialchars($forecast['generated_at'] ?? 'pendente'); ?></span>
                <span><?php echo htmlspecialchars($model['name'] ?? 'modelo pendente'); ?></span>
                <form method="POST" action="<?php echo pata_form_action(); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                    <button type="submit" name="action" value="refresh_forecast">Atualizar</button>
                </form>
            </div>
        </div>

        <?php if (!$forecast): ?>
            <div class="empty-state">
                Execute <code>python -m analytics.data_pipeline</code> para gerar o arquivo de previsão.
            </div>
        <?php else: ?>
            <div class="stock-grid">
                <?php foreach ($stock_forecast as $item):
                    $status = $item['status'];
                    $status_text = $status === 'critical' ? 'Crítico' : ($status === 'warning' ? 'Atenção' : 'Ok');
                    $coverage = min(100, max(4, ((float)$item['days_remaining'] / 30) * 100));
                ?>
                    <article class="stock-panel status-<?php echo htmlspecialchars($status); ?>">
                        <div class="stock-panel-header">
                            <div>
                                <span class="status-pill"><?php echo htmlspecialchars($status_text); ?></span>
                                <h2><?php echo htmlspecialchars($item['label']); ?></h2>
                            </div>
                            <strong><?php echo htmlspecialchars($item['days_remaining']); ?> dias</strong>
                        </div>

                        <div class="coverage-track">
                            <div class="coverage-fill" style="width: <?php echo $coverage; ?>%;"></div>
                        </div>

                        <div class="metric-list">
                            <div>
                                <span>Estoque atual</span>
                                <strong><?php echo htmlspecialchars($item['current_stock']); ?> <?php echo htmlspecialchars($item['unit']); ?></strong>
                            </div>
                            <div>
                                <span>Consumo médio</span>
                                <strong><?php echo htmlspecialchars($item['average_daily_consumption']); ?> <?php echo htmlspecialchars($item['unit']); ?>/dia</strong>
                            </div>
                            <div>
                                <span>Prazo de reposição</span>
                                <strong><?php echo htmlspecialchars($item['replenishment_days']); ?> dias</strong>
                            </div>
                            <div>
                                <span>Comprar sugerido</span>
                                <strong><?php echo htmlspecialchars($item['recommended_reorder_quantity']); ?> <?php echo htmlspecialchars($item['unit']); ?></strong>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="detail-layout">
                <div class="panel panel-wide">
                    <div class="panel-header">
                        <h2>Consumo previsto por dia</h2>
                        <span>próximos <?php echo htmlspecialchars($forecast['horizon_days']); ?> dias</span>
                    </div>
                    <table class="forecast-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Item</th>
                                <th>Consumo previsto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($daily_forecast, 0, 18) as $day): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($day['date']); ?></td>
                                    <td><?php echo htmlspecialchars($day['label']); ?></td>
                                    <td><?php echo htmlspecialchars($day['predicted_consumption']); ?> <?php echo htmlspecialchars($day['unit']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="panel">
                    <div class="panel-header">
                        <h2>Regras de vacina</h2>
                        <span>usadas no modelo</span>
                    </div>
                    <div class="protocol-list">
                        <?php foreach ($vaccine_protocol as $protocol): ?>
                            <div class="protocol-item">
                                <strong><?php echo htmlspecialchars($protocol['species']); ?></strong>
                                <span><?php echo htmlspecialchars($protocol['rule']); ?></span>
                                <em><?php echo htmlspecialchars(implode(', ', $protocol['vaccines'])); ?></em>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </section>
</body>
<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
        width: 100%;
    }

    .stock-dashboard {
        max-width: 1400px;
        margin: 2rem auto;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #f2f5f7;
    }

    .dashboard-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .eyebrow {
        color: #7cc7aa;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .dashboard-header h1 {
        margin: 0.2rem 0 0;
        font-size: 2rem;
    }

    .model-status {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: #adb5bd;
        font-size: 0.85rem;
    }

    .model-status span,
    .model-status button,
    .empty-state {
        background: #1a1c23;
        border: 1px solid #333333;
        border-radius: 0.5rem;
        padding: 0.6rem 0.8rem;
    }

    .model-status form {
        margin: 0;
    }

    .model-status button {
        color: #f2f5f7;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
    }

    .model-status button:hover {
        border-color: #7cc7aa;
    }

    .stock-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .stock-panel,
    .panel {
        background: #1a1c23;
        border: 1px solid #333333;
        border-radius: 0.5rem;
        padding: 1.25rem;
        min-width: 0;
    }

    .stock-panel-header {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: flex-start;
    }

    .stock-panel h2 {
        margin: 0.35rem 0 0;
        font-size: 1.35rem;
    }

    .stock-panel-header strong {
        font-size: 1.6rem;
    }

    .status-pill {
        display: inline-flex;
        padding: 0.25rem 0.55rem;
        border-radius: 0.25rem;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .status-critical {
        border-color: rgba(250, 82, 82, 0.65);
    }

    .status-critical .status-pill,
    .status-critical .coverage-fill {
        background: #fa5252;
        color: #ffffff;
    }

    .status-warning {
        border-color: rgba(252, 163, 17, 0.65);
    }

    .status-warning .status-pill,
    .status-warning .coverage-fill {
        background: #fca311;
        color: #12141a;
    }

    .status-ok .status-pill,
    .status-ok .coverage-fill {
        background: #44a66a;
        color: #ffffff;
    }

    .coverage-track {
        height: 10px;
        background: #101217;
        border-radius: 999px;
        overflow: hidden;
        margin: 1rem 0;
    }

    .coverage-fill {
        height: 100%;
        border-radius: 999px;
    }

    .metric-list {
        display: grid;
        gap: 0.8rem;
    }

    .metric-list div {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        border-bottom: 1px solid #333333;
        padding-bottom: 0.65rem;
    }

    .metric-list div:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .metric-list span,
    .panel-header span,
    .protocol-item span,
    .protocol-item em {
        color: #adb5bd;
    }

    .detail-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
    }

    .panel-header {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: baseline;
        margin-bottom: 1rem;
    }

    .panel-header h2 {
        margin: 0;
        font-size: 1.1rem;
    }

    .forecast-table {
        width: 100%;
        border-collapse: collapse;
    }

    .forecast-table th,
    .forecast-table td {
        padding: 0.8rem;
        border-bottom: 1px solid #333333;
        text-align: left;
    }

    .forecast-table th {
        color: #adb5bd;
        font-size: 0.78rem;
        text-transform: uppercase;
    }

    .protocol-list {
        display: grid;
        gap: 1rem;
    }

    .protocol-item {
        display: grid;
        gap: 0.35rem;
        border-bottom: 1px solid #333333;
        padding-bottom: 1rem;
    }

    .protocol-item:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .protocol-item em {
        font-style: normal;
        font-size: 0.86rem;
    }

    code {
        color: #7cc7aa;
    }

    @media (max-width: 900px) {
        .dashboard-grid,
        .stock-grid,
        .detail-layout {
            grid-template-columns: 1fr;
        }

        .dashboard-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .model-status {
            flex-wrap: wrap;
        }
    }
</style>
</html>
