<?php
require_once __DIR__ . '/../../system/auth.php';
$page = pata_page_start();

if ($page['error'] === null && $page['notice'] === null) {
    if ($page['method'] === 'POST' && $page['action'] === 'refresh_service_records') {
        $page['notice'] = 'Fichas de atendimento reidratadas nesta pagina.';
    }

    if ($page['method'] === 'POST' && $page['action'] === 'service_record_details') {
        $service_record_id = (string) ($_POST['service_record_id'] ?? '');
        $page['notice'] = "Ficha de atendimento {$service_record_id} carregada nesta pagina.";
    }

    if ($page['method'] === 'PATCH' && $page['action'] === 'close_service_record') {
        $service_record_id = (string) ($_POST['service_record_id'] ?? '');
        $page['notice'] = "Ficha de atendimento {$service_record_id} marcada para encerramento nesta pagina.";
    }
}
?>
<!DOCTYPE html>
<html>
<?php
require_once('../components/card.php');
require_once('../components/head.php');
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
                "Registrar", 
                "NOVA FICHA",
                "", 
                "",  
                "#212529",
                "#e8f4f8",
                "transparent",
                "/dispatch/form.php"
            ));

            echo(component_card(
                "Listagem de animais", 
                "EM QUARENTENA", 
                "", 
                "",  
                "#212529",
                "#e8f4f8",
                "transparent",
                "/pets/quarentena.php?from=dispatch"
            ));
            echo(component_card(
                "Listagem de animais", 
                "NÃO RESGATADOS", 
                "", 
                "",  
                "#212529",
                "#e8f4f8",
                "transparent",
                "/dispatch/ignored.php"
            ));
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
                    <tr>
                        <td>
                            <strong>#SR-1042</strong>
                            <span class="text-muted">15 min atrás</span>
                        </td>
                        <td>
                            <strong>Maria Santos</strong>
                            <span class="text-muted">(19) 99999-0000</span>
                        </td>
                        <td>
                            Av. Brasil, 1500
                            <span class="text-muted">Centro</span>
                        </td>
                        <td>
                            <strong>Canino</strong>
                            <span class="text-muted">Macho, grande, caramelo</span>
                        </td>
                        <td>
                            <div class="actions-cell">
                                <form method="POST" action="<?php echo pata_form_action(); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                                    <input type="hidden" name="service_record_id" value="#SR-1042">
                                    <button type="submit" name="action" value="service_record_details" class="btn-action btn-secondary" title="Mais detalhes">Mais detalhes</button>
                                </form>
                                <form method="POST" action="<?php echo pata_form_action(); ?>">
                                    <input type="hidden" name="_method" value="PATCH">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                                    <input type="hidden" name="service_record_id" value="#SR-1042">
                                    <button type="submit" name="action" value="close_service_record" class="btn-action btn-primary" title="Encerrar">Encerrar</button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <strong>#SR-1041</strong>
                            <span class="text-muted">45 min atrás</span>
                        </td>
                        <td>
                            <strong>Joao Pereira</strong>
                            <span class="text-muted">(19) 98888-1111</span>
                        </td>
                        <td>
                            Rua M 4, 850
                            <span class="text-muted">Jardim Floridiana</span>
                        </td>
                        <td>
                            <strong>Felino</strong>
                            <span class="text-muted">Fêmea, pequeno, preto</span>
                        </td>
                        <td>
                            <div class="actions-cell">
                                <form method="POST" action="<?php echo pata_form_action(); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                                    <input type="hidden" name="service_record_id" value="#SR-1041">
                                    <button type="submit" name="action" value="service_record_details" class="btn-action btn-secondary" title="Mais detalhes">Mais detalhes</button>
                                </form>
                                <form method="POST" action="<?php echo pata_form_action(); ?>">
                                    <input type="hidden" name="_method" value="PATCH">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                                    <input type="hidden" name="service_record_id" value="#SR-1041">
                                    <button type="submit" name="action" value="close_service_record" class="btn-action btn-primary" title="Encerrar">Encerrar</button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <strong>#SR-1039</strong>
                            <span class="text-muted">2 horas atrás</span>
                        </td>
                        <td>
                            <strong>Lucia Almeida</strong>
                            <span class="text-muted">(19) 97777-2222</span>
                        </td>
                        <td>
                            Rua 14, 220
                            <span class="text-muted">Consolação</span>
                        </td>
                        <td>
                            <strong>Canino</strong>
                            <span class="text-muted">Nao informado</span>
                        </td>
                        <td>
                            <div class="actions-cell">
                                <form method="POST" action="<?php echo pata_form_action(); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                                    <input type="hidden" name="service_record_id" value="#SR-1039">
                                    <button type="submit" name="action" value="service_record_details" class="btn-action btn-secondary" title="Mais detalhes">Mais detalhes</button>
                                </form>
                                <form method="POST" action="<?php echo pata_form_action(); ?>">
                                    <input type="hidden" name="_method" value="PATCH">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                                    <input type="hidden" name="service_record_id" value="#SR-1039">
                                    <button type="submit" name="action" value="close_service_record" class="btn-action btn-primary" title="Encerrar">Encerrar</button>
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
