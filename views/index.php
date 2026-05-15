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
                "/dispatch/index.php"
            ));

            echo(component_card(
                "Listagem de animais", 
                "487", 
                "🐶", 
                "+2.5% nos últimos 6 meses", 
                "#e8f4f8", 
                "#212529",
                "transparent",
                "/listagem"
            ));

            echo(component_card(
                "Almoxarífe", 
                "8140 items", 
                "👥", 
                "<b>Apenas <font color=red>2</font> itens X restantes</b>", 
                "#e8f4f8", 
                "#212529",
                "transparent",
                "/almoxarife"
            ));
        ?>
    </div>

</body>
<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
        width: 100%;
    }
</style>
</html>