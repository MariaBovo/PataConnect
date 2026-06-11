<?php
require_once __DIR__ . '/../system/auth.php';
$page = pata_page_start();

$total_items = 0;
$low_stock_count = 0;
$open_requests_count = 0;
$active_pets_count = 0;
$quarantine_pets_count = 0;

try {
    $db = pata_db();
    $total_items = (int) $db->query('SELECT COUNT(*) FROM "deposit_items"')->fetchColumn();
    $low_stock_count = (int) $db->query('SELECT COUNT(*) FROM "deposit_items" WHERE "quantity" < "min_quantity"')->fetchColumn();
    $open_requests_count = (int) $db->query('SELECT COUNT(*) FROM "service_records" WHERE "animal_found" IS NULL')->fetchColumn();
    $active_pets_count = (int) $db->query('SELECT COUNT(*) FROM "pets" WHERE "status" = \'Ativo\' AND "euthanasia" = FALSE')->fetchColumn();
    $quarantine_pets_count = (int) $db->query('SELECT COUNT(*) FROM "pets" WHERE "status" = \'Quarentena\' AND "euthanasia" = FALSE')->fetchColumn();
} catch (Throwable $e) {
    // Fallback
}
?>
<!DOCTYPE html>
<html>
<?php
require_once(__DIR__ . '/components/card.php');
require_once(__DIR__ . '/components/head.php');
?>
<body>
    <?php require(__DIR__ . '/components/headnav.php');?>
    <hr style="border-color: #333; margin-bottom: 2rem;">

    <div class="dashboard-grid">
        <?php
            echo(component_card(
                "Fichas de atendimento",
                (string) $open_requests_count, 
                "🚐", 
                "Solicitações em aberto",
                "#e8f4f8", 
                "#212529",
                "transparent",
                "/dispatch"
            ));

            $pets_desc = "Sem animais em quarentena";
            if ($quarantine_pets_count > 0) {
                $pets_desc = "<b>{$quarantine_pets_count} em quarentena</b>";
            }

            echo(component_card(
                "Listagem de animais", 
                (string) $active_pets_count, 
                "🐶", 
                $pets_desc, 
                "#e8f4f8", 
                "#212529",
                "transparent",
                "/pets"
            ));

            $almoxarife_desc = "Todos os níveis normais";
            if ($low_stock_count > 0) {
                $almoxarife_desc = "<b><font color=#fa5252>{$low_stock_count}</font> itens abaixo do mínimo</b>";
            }

            echo(component_card(
                "Almoxarífe", 
                "{$total_items} itens", 
                "👥", 
                $almoxarife_desc, 
                "#e8f4f8", 
                "#212529",
                "transparent",
                "/deposit"
            ));

            echo(component_card(
                "Estoque Inteligente",
                "Previsões IA",
                "📦",
                "dias restantes e alerta de compra",
                "#e8f4f8",
                "#212529",
                "transparent",
                "/an"
            ));
        ?>
    </div>
    <hr>

    <!-- <div class="kennel-flow-dashboard">
        <?php
        $forecast_path = __DIR__ . '/../analytics/artifacts/stock_forecast.json';
        $forecast = null;
        if (file_exists($forecast_path)) {
            $forecast = json_decode(file_get_contents($forecast_path), true);
        }

        $sourceData = [];
        if ($forecast && isset($forecast['daily_consumption_forecast'])) {
            $daily = $forecast['daily_consumption_forecast'];
            $grouped = [];
            foreach ($daily as $row) {
                $date = $row['date'];
                $item = $row['item'];
                $val = (float) $row['predicted_consumption'];
                if (!isset($grouped[$date])) {
                    $grouped[$date] = [
                        'feed_kg' => 0.0,
                        'bleach_liters' => 0.0,
                        'vaccine_doses' => 0.0
                    ];
                }
                $grouped[$date][$item] = $val;
            }
            
            ksort($grouped);
            $sliced = array_slice($grouped, 0, 8, true);
            
            $months_pt = [
                '01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr', '05' => 'Mai', '06' => 'Jun',
                '07' => 'Jul', '08' => 'Ago', '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez'
            ];
            foreach ($sliced as $dateStr => $items) {
                $parts = explode('-', $dateStr);
                $day = $parts[2] ?? '';
                $m = $parts[1] ?? '';
                $label = $day . '/' . ($months_pt[$m] ?? $m);
                $sourceData[$label] = [
                    'feed_kg' => $items['feed_kg'],
                    'bleach_liters' => $items['bleach_liters'],
                    'vaccine_doses' => $items['vaccine_doses']
                ];
            }
        }

        if (empty($sourceData)) {
            $sourceData = [
                '11/Jun' => ['feed_kg' => 28.5, 'bleach_liters' => 12.0, 'vaccine_doses' => 0.0],
                '12/Jun' => ['feed_kg' => 29.0, 'bleach_liters' => 11.5, 'vaccine_doses' => 1.0],
                '13/Jun' => ['feed_kg' => 28.2, 'bleach_liters' => 12.5, 'vaccine_doses' => 0.0],
                '14/Jun' => ['feed_kg' => 30.1, 'bleach_liters' => 13.0, 'vaccine_doses' => 0.0],
                '15/Jun' => ['feed_kg' => 31.0, 'bleach_liters' => 14.5, 'vaccine_doses' => 2.0],
                '16/Jun' => ['feed_kg' => 29.5, 'bleach_liters' => 12.0, 'vaccine_doses' => 0.0],
                '17/Jun' => ['feed_kg' => 30.4, 'bleach_liters' => 11.0, 'vaccine_doses' => 0.0],
                '18/Jun' => ['feed_kg' => 31.2, 'bleach_liters' => 12.8, 'vaccine_doses' => 1.0]
            ];
        }

        $maxVolume = 10;
        foreach ($sourceData as $label => $values) {
            $total = array_sum($values);
            if ($total > $maxVolume) {
                $maxVolume = $total;
            }
        }
        $maxVolume = ceil($maxVolume * 1.1);
        $y_steps = 10;
        $step_size = ceil($maxVolume / $y_steps);
        $step_size = max(1, $step_size);
        $maxVolume = $step_size * $y_steps;
        ?>
        <div class="header-panel">
            <div class="legend">Consumo Diário de Insumos (Previsão IA)
                <div class="legend-item"><div class="legend-box bg-bull"></div> Ração (kg)</div>
                <div class="legend-item"><div class="legend-box bg-bear"></div> Água Sanitária (L)</div>
                <div class="legend-item"><div class="legend-box bg-prediction"></div> Vacinas (doses)</div>
            </div>
        </div>

        <div class="chart-viewport">
            <div class="y-axis">
                <div class="y-grid-lines">
                    <?php for ($i = $y_steps; $i >= 0; $i--): ?>
                        <div class="y-grid-line" style="margin-top: calc( (<?php echo $maxVolume; ?> - <?php echo $i * $step_size; ?>)/<?php echo $maxVolume; ?> * 100%)"></div>
                    <?php endfor; ?>
                </div>
                <?php for ($i = $y_steps; $i >= 0; $i--): ?>
                    <span><?php echo $i * $step_size; ?></span>
                <?php endfor; ?>
            </div>

            <div class="chart-container">
                <div class="prediction-overlay" style="left: 0%; width: 100%;"></div>
                <?php
                foreach ($sourceData as $label => $values) {
                    $feed = $values['feed_kg'];
                    $bleach = $values['bleach_liters'];
                    $vaccine = $values['vaccine_doses'];
                    $totalVolume = $feed + $bleach + $vaccine;

                    // Percentages of total chart height
                    $barHeightPct = ($totalVolume / $maxVolume) * 100;
                    $feedPct = ($feed / $totalVolume) * 100;
                    $bleachPct = ($bleach / $totalVolume) * 100;
                    $vaccinePct = ($vaccine / $totalVolume) * 100;

                    echo '<div class="chart-bar-wrapper">';
                    
                    // Total label above the bar
                    echo '  <div class="net-change-indicator net-bull" style="color: var(--text-primary);">';
                    echo '    <span>' . round($totalVolume, 1) . '</span>';
                    echo '  </div>';

                    // The Stacked Volume Bar
                    echo '  <div class="bar-stacked" style="height: ' . $barHeightPct . '%;">';
                    // Feed segment (Green)
                    echo '    <div class="bar-segment segment-bull" style="height: ' . $feedPct . '%;" title="Ração: ' . round($feed, 1) . ' kg"></div>';
                    // Bleach segment (Red)
                    echo '    <div class="bar-segment segment-bear" style="height: ' . $bleachPct . '%;" title="Água Sanitária: ' . round($bleach, 1) . ' L"></div>';
                    // Vaccine segment (Orange)
                    echo '    <div class="bar-segment segment-prediction" style="height: ' . $vaccinePct . '%;" title="Vacinas: ' . round($vaccine, 1) . ' doses"></div>';
                    echo '  </div>';

                    // Date label on X axis
                    echo '  <div class="x-axis-label">' . $label . '</div>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>-->
</body>
<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
        width: 100%;
    }
    .dashboard-analytics {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
        width: 100%;
    }
    :root {
        --bg-color: #12181f;
        --border-color: #242c38;
        --grid-color: #1f2832;
        --text-primary: #f2f5f7;
        --text-secondary: #8b9bb4;
        --bull-color: #44a66a;
        --bear-color: #dc5151;
        --base-unit: 8px;
        --chart-height: 40vh;
        --font-stack: 'Courier New', Courier, monospace;
    }

    .kennel-flow-dashboard {
        background-color: var(--bg-color);
        color: var(--text-primary);
        font-family: var(--font-stack);
        padding: 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        margin: 20px auto;
        border: 1px solid var(--border-color);
        max-width: 1400px;
    }

    .header-panel {
        text-align: center;
        margin-bottom: 24px;
    }

    .header-panel h1 {
        margin: 0 0 10px 0;
        font-size: 20px;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    .legend {
        display: flex;
        justify-content: center;
        gap: 20px;
        font-size: 14px;
        color: var(--text-secondary);
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .legend-box {
        width: 16px;
        height: 16px;
        border-radius: 2px;
    }

    .bg-bull { background-color: var(--bull-color); }
    .bg-bear { background-color: var(--bear-color); }
    .bg-prediction { background-color: #ff9900; }

    .chart-viewport {
        height: var(--chart-height);
        position: relative;
        border: 1px solid var(--border-color);
        background-color: #0b0f14;
        display: grid;
        grid-template-columns: 50px 1fr; /* Y-axis | Chart */
        padding-top: 20px;
        padding-bottom: 30px; /* Space for X-axis */
    }

    .y-axis {
        display: flex;
        flex-direction: column-reverse;
        justify-content: space-between;
        align-items: flex-end;
        padding-right: 10px;
        font-size: 12px;
        color: var(--text-secondary);
        position: relative;
        margin-bottom: -10px;
    }

    .y-grid-lines {
        position: absolute;
        width: 100%;
        height: 100%;
        left: 0;
        top: 0;
        display: flex;
        flex-direction: column;
    }
    .y-grid-line {
        height: 1px;
        background-color: var(--grid-color);
        width: 100%;
    }

    .chart-container {
        display: grid;
        grid-template-columns: repeat(8, 1fr);
        align-items: flex-end;
        position: relative;
        overflow-x: auto;
        padding-left: 10px;
        padding-right: 10px;
    }

    .chart-bar-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        height: 100%;
        justify-content: flex-end;
        position: relative;
    }

    .bar-stacked {
        width: 32px;
        border-radius: 4px;
        display: flex;
        flex-direction: column-reverse;
        justify-content: flex-end;
        overflow: hidden;
        border: 1px solid transparent;
        transition: transform 0.2s;
    }
    
    .bar-stacked:hover {
        transform: scaleY(1.05);
        border-color: rgba(255,255,255,0.2);
    }

    .bar-segment {
        width: 100%;
        opacity: 0.8;
    }
    .segment-bear { background-color: var(--bear-color); }
    .segment-bull { background-color: var(--bull-color); }
    .segment-prediction { background-color: #ff9900; }

    .net-change-indicator {
        position: absolute;
        top: -24px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 12px;
        display: flex;
        align-items: center;
        gap: 4px;
        white-space: nowrap;
    }
    .net-bull { color: var(--bull-color); }
    .net-bear { color: var(--bear-color); }

    .x-axis-label {
        position: absolute;
        bottom: -22px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 12px;
        color: var(--text-secondary);
    }
    .prediction-overlay {
        position: absolute;
        top: 0;
        bottom: 0;
        background-color: rgba(255, 153, 0, 0.15); /* Transparent orange tint */
        border-left: 1px dashed #ff9900;
        border-right: 1px dashed #ff9900;
        pointer-events: none; /* Ensures tooltips and hover effects still work */
        z-index: 5; /* Places it above the grid but below the data labels if needed */
    }
</style>
</html>
