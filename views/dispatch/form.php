<?php
require_once __DIR__ . '/../../system/auth.php';
$page = pata_page_start();

$form_values = [
    'requester_name' => '',
    'requester_phone' => '',
    'incident_address' => '',
    'incident_neighborhood' => '',
    'incident_landmark' => '',
    'incident_description' => '',
    'reported_species' => '',
    'reported_gender' => '',
    'reported_size' => '',
    'reported_color' => '',
];

if ($page['method'] === 'POST') {
    foreach ($form_values as $field => $default) {
        $form_values[$field] = trim((string) ($_POST[$field] ?? $default));
    }
}

function save_service_record(array $form_values): ?int
{
    $current_user = pata_current_user();
    $record_number = 'SR-' . date('Ymd-His');
    $query = '
        INSERT INTO "service_records" (
            "record_number",
            "request_date",
            "request_time",
            "requester_name",
            "requester_phone",
            "incident_address",
            "incident_neighborhood",
            "incident_landmark",
            "incident_description",
            "received_by_user_id",
            "reported_species",
            "reported_gender",
            "reported_size",
            "reported_color"
        ) VALUES (
            :record_number,
            CURRENT_DATE,
            CURRENT_TIME,
            :requester_name,
            :requester_phone,
            :incident_address,
            :incident_neighborhood,
            :incident_landmark,
            :incident_description,
            :received_by_user_id,
            :reported_species,
            :reported_gender,
            :reported_size,
            :reported_color
        )
    ';
    $statement = pata_db()->prepare($query);
    $statement->execute([
        'record_number' => $record_number,
        'requester_name' => $form_values['requester_name'],
        'requester_phone' => $form_values['requester_phone'],
        'incident_address' => $form_values['incident_address'],
        'incident_neighborhood' => $form_values['incident_neighborhood'] !== '' ? $form_values['incident_neighborhood'] : null,
        'incident_landmark' => $form_values['incident_landmark'] !== '' ? $form_values['incident_landmark'] : null,
        'incident_description' => $form_values['incident_description'],
        'received_by_user_id' => $current_user['id'] > 0 ? $current_user['id'] : null,
        'reported_species' => $form_values['reported_species'],
        'reported_gender' => $form_values['reported_gender'] !== '' ? $form_values['reported_gender'] : null,
        'reported_size' => $form_values['reported_size'] !== '' ? $form_values['reported_size'] : null,
        'reported_color' => $form_values['reported_color'] !== '' ? $form_values['reported_color'] : null,
    ]);

    $last_id = pata_db()->lastInsertId();

    return $last_id !== false && $last_id !== '' ? (int) $last_id : null;
}

if ($page['error'] === null && $page['notice'] === null && $page['method'] === 'POST' && $page['action'] === 'create_service_record') {
    $required_fields = ['requester_name', 'requester_phone', 'incident_address', 'incident_description', 'reported_species'];

    foreach ($required_fields as $field) {
        if ($form_values[$field] === '') {
            $page['error'] = 'Preencha todos os campos obrigatorios da ficha de atendimento.';
            break;
        }
    }

    if ($page['error'] === null) {
        try {
            $saved_id = save_service_record($form_values);
            $page['notice'] = $saved_id !== null
                ? "Ficha de atendimento #{$saved_id} salva em service_records."
                : 'Ficha de atendimento salva em service_records.';
        } catch (Throwable $error) {
            $page['notice'] = 'Ficha hidratada nesta pagina. O banco ainda nao gravou o registro.';
        }
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
    <div class="registration-wrapper">
        <div class="form-card">
            <div class="header-actions">
                <a href="/dispatch" class="btn-action btn-secondary">Voltar</a>
            </div>
            <div class="form-header">
                <h2>Ficha de Atendimento ao Municipe</h2>
                <p>Registre a solicitacao, o local da ocorrencia e os dados informados do animal.</p>
            </div>
            <form action="<?php echo pata_form_action(); ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                <input type="hidden" name="action" value="create_service_record">
                
                <h3 class="form-section-title">Dados da Solicitação</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="requester_name">Nome do Solicitante</label>
                        <input type="text" id="requester_name" name="requester_name" class="form-control" placeholder="ex.: Maria Santos" value="<?php echo htmlspecialchars($form_values['requester_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="requester_phone">Telefone</label>
                        <input type="tel" id="requester_phone" name="requester_phone" class="form-control" placeholder="(00) 00000-0000" value="<?php echo htmlspecialchars($form_values['requester_phone']); ?>" required>
                    </div>
                </div>

                <h3 class="form-section-title">Local do Incidente</h3>
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label for="incident_address">Endereco</label>
                    <input type="text" id="incident_address" name="incident_address" class="form-control" placeholder="Nome da rua, numero e complemento" value="<?php echo htmlspecialchars($form_values['incident_address']); ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="incident_neighborhood">Bairro</label>
                        <input type="text" id="incident_neighborhood" name="incident_neighborhood" class="form-control" placeholder="ex.: Centro" value="<?php echo htmlspecialchars($form_values['incident_neighborhood']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="incident_landmark">Ponto de Referencia</label>
                        <input type="text" id="incident_landmark" name="incident_landmark" class="form-control" placeholder="ex.: Proximo a escola" value="<?php echo htmlspecialchars($form_values['incident_landmark']); ?>">
                    </div>
                </div>

                <h3 class="form-section-title">Animal Informado</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="reported_species">Especie</label>
                        <select id="reported_species" name="reported_species" class="form-control" required>
                            <option value="" disabled <?php echo $form_values['reported_species'] === '' ? 'selected' : ''; ?>>Selecione a especie</option>
                            <option value="Canino" <?php echo $form_values['reported_species'] === 'Canino' ? 'selected' : ''; ?>>Canino</option>
                            <option value="Felino" <?php echo $form_values['reported_species'] === 'Felino' ? 'selected' : ''; ?>>Felino</option>
                            <option value="Equino" <?php echo $form_values['reported_species'] === 'Equino' ? 'selected' : ''; ?>>Equino</option>
                            <option value="Bovino" <?php echo $form_values['reported_species'] === 'Bovino' ? 'selected' : ''; ?>>Bovino</option>
                            <option value="Outro" <?php echo $form_values['reported_species'] === 'Outro' ? 'selected' : ''; ?>>Outro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="reported_gender">Sexo</label>
                        <select id="reported_gender" name="reported_gender" class="form-control">
                            <option value="" <?php echo $form_values['reported_gender'] === '' ? 'selected' : ''; ?>>Nao informado</option>
                            <option value="Macho" <?php echo $form_values['reported_gender'] === 'Macho' ? 'selected' : ''; ?>>Macho</option>
                            <option value="Fêmea" <?php echo $form_values['reported_gender'] === 'Fêmea' ? 'selected' : ''; ?>>Fêmea</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="reported_size">Porte</label>
                        <select id="reported_size" name="reported_size" class="form-control">
                            <option value="" <?php echo $form_values['reported_size'] === '' ? 'selected' : ''; ?>>Nao informado</option>
                            <option value="Pequeno" <?php echo $form_values['reported_size'] === 'Pequeno' ? 'selected' : ''; ?>>Pequeno</option>
                            <option value="Médio" <?php echo $form_values['reported_size'] === 'Médio' ? 'selected' : ''; ?>>Médio</option>
                            <option value="Grande" <?php echo $form_values['reported_size'] === 'Grande' ? 'selected' : ''; ?>>Grande</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="reported_color">Cor</label>
                        <input type="text" id="reported_color" name="reported_color" class="form-control" placeholder="ex.: Caramelo" value="<?php echo htmlspecialchars($form_values['reported_color']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="incident_description">Descricao da Ocorrencia</label>
                    <textarea id="incident_description" name="incident_description" class="form-control" placeholder="Descreva a situacao, condicao do animal e instrucoes para a equipe." required><?php echo htmlspecialchars($form_values['incident_description']); ?></textarea>
                </div>

                <button type="submit" class="btn-submit">Registrar Ficha de Atendimento</button>
            </form>
        </div>
    </div>
</body>
<style>
    .registration-wrapper {
        max-width: 800px;
        margin: 2rem auto;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #ffffff;
    }

    .form-card {
        background-color: #1a1c23;
        border: 1px solid #333333;
        border-radius: 0.5rem;
        padding: 2.5rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }

    .form-header {
        margin-bottom: 2rem;
        border-bottom: 1px solid #333333;
        padding-bottom: 1rem;
    }

    .form-header h2 {
        margin: 0 0 0.5rem 0;
        color: #e8f4f8;
        font-size: 1.5rem;
    }

    .form-header p {
        margin: 0;
        color: #868e96;
        font-size: 0.9rem;
    }

    .form-section-title {
        color: #e8f4f8;
        font-size: 1.1rem;
        margin: 1.5rem 0 1rem 0;
        font-weight: 600;
    }

    .form-row {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 1.25rem;
    }

    .form-group {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        font-size: 0.8rem;
        font-weight: 700;
        color: #adb5bd;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-control {
        padding: 0.75rem 1rem;
        border-radius: 0.25rem;
        border: 1px solid #333333;
        background-color: #12141a;
        color: #ffffff;
        font-size: 1rem;
        font-family: inherit;
        transition: border-color 0.2s;
    }

    .form-control:focus {
        outline: none;
        border-color: #6c757d;
    }

    textarea.form-control {
        resize: vertical;
        min-height: 100px;
    }

    .urgency-group {
        display: flex;
        gap: 1.5rem;
        margin-top: 0.5rem;
    }

    .urgency-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.95rem;
        color: #e8f4f8;
        cursor: pointer;
    }

    .urgency-label input[type="radio"] {
        accent-color: #e8f4f8;
        width: 1.2rem;
        height: 1.2rem;
        cursor: pointer;
    }

    .btn-submit {
        background-color: #2b8a3e;
        color: white;
        border: none;
        padding: 1rem;
        font-size: 1.1rem;
        font-weight: 700;
        border-radius: 0.25rem;
        cursor: pointer;
        width: 100%;
        margin-top: 2rem;
        transition: background-color 0.2s;
    }

    .btn-submit:hover {
        background-color: #237032;
    }

    .header-actions {
        text-align:end;
        gap: 1rem;
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
