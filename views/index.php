<!DOCTYPE html>
<html>
<?php
require_once('./components/card.php');
require_once('./components/head.php');
?>
<body>
    <?php require('components/headnav.php');?>
    <hr style="border-color: #333; margin-bottom: 2rem;">

    <div class="dashboard-grid">
        <?php
            echo(component_card(
                "Listagem de resgates", 
                "12", 
                "🚐", 
                "Resgates em andamento", 
                "#e8f4f8", 
                "#212529",
                "transparent",
                "/dispatch"
            ));

            echo(component_card(
                "Listagem de animais", 
                "487", 
                "🐶", 
                "+2.5% nos últimos 6 meses", 
                "#e8f4f8", 
                "#212529",
                "transparent",
                "/pets"
            ));

            echo(component_card(
                "Almoxarífe", 
                "8140 items", 
                "👥", 
                "<b>Apenas <font color=red>2</font> itens X restantes</b>", 
                "#e8f4f8", 
                "#212529",
                "transparent",
                "/deposit"
            ));
        ?>
    </div>
    <hr>

    <div class="kennel-flow-dashboard">
        <div class="header-panel">
            <div class="legend">Fluxo de animais
                <div class="legend-item"><div class="legend-box bg-bull"></div> [↑ Adoção]</div>
                <div class="legend-item"><div class="legend-box bg-bear"></div> [↓ Resgate]</div>
                <div class="legend-item"><div class="legend-box bg-prediction"></div> [→ Predição]</div>
            </div>
        </div>

        <div class="chart-viewport">
            <!-- Y Axis -->
            <div class="y-axis">
                <div class="y-grid-lines">
                    <div class="y-grid-line" style="margin-top: calc( (450-450)/450 * 100%)"></div>
                    <div class="y-grid-line" style="margin-top: calc( (450-400)/450 * 100%)"></div>
                    <div class="y-grid-line" style="margin-top: calc( (450-350)/450 * 100%)"></div>
                    <div class="y-grid-line" style="margin-top: calc( (450-300)/450 * 100%)"></div>
                    <div class="y-grid-line" style="margin-top: calc( (450-250)/450 * 100%)"></div>
                    <div class="y-grid-line" style="margin-top: calc( (450-200)/450 * 100%)"></div>
                    <div class="y-grid-line" style="margin-top: calc( (450-150)/450 * 100%)"></div>
                    <div class="y-grid-line" style="margin-top: calc( (450-100)/450 * 100%)"></div>
                    <div class="y-grid-line" style="margin-top: calc( (450-50)/450 * 100%)"></div>
                    <div class="y-grid-line" style="margin-top: calc( (450-0)/450 * 100%)"></div>
                </div>
                <span>0</span><span>50</span><span>100</span><span>150</span><span>200</span><span>250</span><span>300</span><span>350</span><span>400</span><span>450</span>
            </div>

            <!-- Main Chart Area -->
            <div class="chart-container">
                <div class="prediction-overlay" style="left: 62.5%; width: 37.5%;"></div>
                <?php
                $sourceData = [
                    'Jan' => [85, 120],
                    'Fev' => [140, 95],
                    'Mar' => [160, 80],
                    'Abr' => [110, 150],
                    'Mai' => [90, 180],
                    'Jun' => [210, 140],
                    'Jul' => [230, 160],
                    'Ago' => [130, 200]
                ];

                $maxVolume = 450;

                foreach ($sourceData as $month => $flows) {
                    $intake = $flows[0];
                    $adoptions = $flows[1];
                    $totalVolume = $intake + $adoptions;
                    $netBalance = $adoptions - $intake;

                    // Percentages of total chart height
                    $barHeightPct = ($totalVolume / $maxVolume) * 100;
                    $intakePct = ($intake / $totalVolume) * 100;
                    $adoptionPct = ($adoptions / $totalVolume) * 100;

                    // Net Balance Display
                    $netClass = $netBalance >= 0 ? 'net-bull' : 'net-bear';
                    $netIcon = $netBalance >= 0 ? '↑' : '↓';
                    $formattedNet = ($netBalance >= 0 ? '+' : '') . $netBalance;

                    echo '<div class="chart-bar-wrapper">';
                    
                    // Net change indicator above the bar
                    echo '  <div class="net-change-indicator ' . $netClass . '">';
                    echo '    <span>' . $netIcon . '</span>';
                    echo '    <span>' . $formattedNet . '</span>';
                    echo '  </div>';

                    // The Stacked Volume Bar
                    echo '  <div class="bar-stacked" style="height: ' . $barHeightPct . '%;">';
                    // Intake segment (Bearish)
                    echo '    <div class="bar-segment segment-bear" style="height: ' . $intakePct . '%;"></div>';
                    // Adoption segment (Bullish)
                    echo '    <div class="bar-segment segment-bull" style="height: ' . $adoptionPct . '%;"></div>';
                    echo '  </div>';

                    // Month label on X axis
                    echo '  <div class="x-axis-label">' . $month . '</div>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>
</body>
<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
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