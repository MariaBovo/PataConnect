<?php
require_once __DIR__ . '/../../system/auth.php';
$page = pata_page_start();

$selected_area = trim((string) ($_POST['kennel_area'] ?? 'Todos'));

if ($page['error'] === null && $page['notice'] === null) {
    if ($page['method'] === 'POST' && $page['action'] === 'filter_pets') {
        $page['notice'] = "Listagem de animais hidratada para {$selected_area}.";
    }

    if ($page['method'] === 'POST' && $page['action'] === 'refresh_pets') {
        $page['notice'] = 'Listagem de animais reidratada nesta pagina.';
    }

    if ($page['method'] === 'POST' && $page['action'] === 'open_pet_record') {
        $pet_id = (string) ($_POST['pet_id'] ?? '');
        $page['notice'] = "Ficha do animal {$pet_id} carregada nesta pagina.";
    }
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
                "Listagem de animais", 
                "EM QUARENTENA", 
                "", 
                "",  
                "#212529",
                "#e8f4f8",
                "transparent",
                "/pets/quarentena.php"
            ));

            echo(component_card(
                "Listagem de animais", 
                "ADOTADOS", 
                "", 
                "",  
                "#212529",
                "#e8f4f8",
                "transparent",
                "/pets/adotados.php"
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
                    <tr>
                        <td>
                            <strong>#PET-1042</strong>
                            <span class="text-muted">15 min atrás</span>
                        </td>
                        <td>
                            <strong>Armando</strong>
                            <span class="text-muted badge-high">Extremamente violento</span>
                        </td>
                        <td>
                            <strong>Canino</strong>
                        </td>
                        <td>
                            <strong>Fêmea</strong>
                        </td>
                        <td>
                            <strong>Yorkshire Terrierista</strong>
                        </td>
                        <td>
                            <strong>Pequeno</strong>
                            <span class="text-muted">Caramelo</span>
                        </td>
                        <td>
                            <div class="actions-cell">
                                <form method="POST" action="<?php echo pata_form_action(); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                                    <input type="hidden" name="pet_id" value="#PET-1042">
                                    <button type="submit" name="action" value="open_pet_record" class="btn-action btn-primary" title="Abrir Ficha">Abrir Ficha</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>#PET-2080</strong>
                            <span class="text-muted">15 min atrás</span>
                        </td>
                        <td>
                            <strong>Augusto</strong>
                            <span class="text-muted badge-low">dibas</span>
                        </td>
                        <td>
                            <strong>Canino</strong>
                        </td>
                        <td>
                            <strong>Macho</strong>
                        </td>
                        <td>
                            <strong>Vira-lata</strong>
                        </td>
                        <td>
                            <strong>Médio</strong>
                            <span class="text-muted">Preto</span>
                        </td>
                        <td>
                            <div class="actions-cell">
                                <form method="POST" action="<?php echo pata_form_action(); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                                    <input type="hidden" name="pet_id" value="#PET-2080">
                                    <button type="submit" name="action" value="open_pet_record" class="btn-action btn-primary" title="Abrir Ficha">Abrir Ficha</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>#PET-7429</strong>
                            <span class="text-muted">15 min atrás</span>
                        </td>
                        <td>
                            <strong>Antônia</strong>
                            <span class="text-muted badge-medium">Cêgo</span>
                        </td>
                        <td>
                            <strong>Canino</strong>
                        </td>
                        <td>
                            <strong>Macho</strong>
                        </td>
                        <td>
                            <strong>Golden Retriever</strong>
                        </td>
                        <td>
                            <strong>Grande</strong>
                            <span class="text-muted">Dourado</span>
                        </td>
                        <td>
                            <div class="actions-cell">
                                <form method="POST" action="<?php echo pata_form_action(); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                                    <input type="hidden" name="pet_id" value="#PET-7429">
                                    <button type="submit" name="action" value="open_pet_record" class="btn-action btn-primary" title="Abrir Ficha">Abrir Ficha</button>
                                </form>
                            </div>
                        </td>
                    </tr>
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
