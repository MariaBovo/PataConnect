<?php
require_once __DIR__ . '/../../system/auth.php';
$page = pata_page_start();

$service_record_id = (int) ($_GET['id'] ?? 0);
$record = null;

if ($service_record_id > 0) {
    try {
        $statement = pata_db()->prepare('SELECT * FROM "service_records" WHERE "id" = :id LIMIT 1');
        $statement->execute(['id' => $service_record_id]);
        $record = $statement->fetch();
    } catch (Throwable $e) {
        $page['error'] = "Erro ao buscar ficha de atendimento: " . $e->getMessage();
    }
}

if ($record === null) {
    $page['error'] = "Ficha de atendimento nao encontrada.";
}

$form_values = [
    'name' => '',
    'species' => $record['reported_species'] ?? '',
    'gender' => $record['reported_gender'] ?? '',
    'breed' => '',
    'size' => $record['reported_size'] ?? '',
    'color' => $record['reported_color'] ?? '',
    'microchip' => '',
    'quarantine_days' => '7',
];

if ($page['method'] === 'POST' && $record !== null) {
    foreach ($form_values as $field => $default) {
        $form_values[$field] = trim((string) ($_POST[$field] ?? $default));
    }

    if ($form_values['name'] === '' || $form_values['species'] === '') {
        $page['error'] = 'Preencha o nome e a especie do animal resgatado.';
    } else {
        try {
            $db = pata_db();
            $db->beginTransaction();

            // 1. Insert into pets
            $insertPet = $db->prepare('
                INSERT INTO "pets" (
                    "name", "species", "breed", "gender", "size", "color", "microchip", 
                    "quarantine_start", "quarantine_days", "status"
                ) VALUES (
                    :name, :species, :breed, :gender, :size, :color, :microchip, 
                    CURRENT_DATE, :quarantine_days, \'Quarentena\'
                )
            ');
            $insertPet->execute([
                'name' => $form_values['name'],
                'species' => $form_values['species'],
                'breed' => $form_values['breed'] !== '' ? $form_values['breed'] : null,
                'gender' => $form_values['gender'] !== '' ? $form_values['gender'] : null,
                'size' => $form_values['size'] !== '' ? $form_values['size'] : null,
                'color' => $form_values['color'] !== '' ? $form_values['color'] : null,
                'microchip' => $form_values['microchip'] !== '' ? $form_values['microchip'] : null,
                'quarantine_days' => (int) $form_values['quarantine_days'],
            ]);
            $pet_id = (int) $db->lastInsertId();

            // 2. Update service_records
            $updateRecord = $db->prepare('
                UPDATE "service_records"
                SET "animal_found" = TRUE, 
                    "animal_collected" = TRUE, 
                    "pet_id" = :pet_id, 
                    "investigation_date" = CURRENT_DATE
                WHERE "id" = :id
            ');
            $updateRecord->execute([
                'pet_id' => $pet_id,
                'id' => $service_record_id,
            ]);

            $db->commit();

            // Redirect back to dispatch with notice
            header('Location: /dispatch?notice=' . urlencode("Animal resgatado com sucesso! Vinculado a ficha #{$service_record_id}. Rex esta em quarentena."));
            exit;
        } catch (Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $page['error'] = 'Erro ao salvar resgate no banco de dados: ' . $error->getMessage();
        }
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
    <div class="registration-wrapper">
        <div class="form-card">
            <div class="header-actions">
                <a href="/dispatch" class="btn-action btn-secondary">Voltar</a>
            </div>
            <div class="form-header">
                <h2>Registrar Resgate do Animal</h2>
                <p>Confirme os dados e encaminhe o animal para a quarentena.</p>
            </div>

            <?php if ($record !== null): ?>
                <div class="panel" style="background: #12141a; border: 1px solid #333; border-radius: 6px; padding: 1rem; margin-bottom: 2rem;">
                    <h4 style="margin: 0 0 0.5rem 0; color: #7cc7aa;">Dados do Chamado Original (#<?php echo htmlspecialchars($record['record_number'] ?? 'SR-'.$record['id']); ?>)</h4>
                    <p style="margin: 0.25rem 0; font-size: 0.9rem;"><strong>Solicitante:</strong> <?php echo htmlspecialchars($record['requester_name']); ?></p>
                    <p style="margin: 0.25rem 0; font-size: 0.9rem;"><strong>Endereço:</strong> <?php echo htmlspecialchars($record['incident_address']); ?></p>
                    <p style="margin: 0.25rem 0; font-size: 0.9rem;"><strong>Descrição do Ocorrido:</strong> <span style="color: #adb5bd;"><?php echo htmlspecialchars($record['incident_description']); ?></span></p>
                </div>
            <?php endif; ?>

            <?php if ($page['error'] !== null): ?>
                <div class="page-feedback page-feedback-error" style="margin-bottom: 1.5rem;">
                    <?php echo htmlspecialchars($page['error']); ?>
                </div>
            <?php endif; ?>

            <?php if ($record !== null): ?>
                <form action="<?php echo pata_form_action(); ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                    
                    <h3 class="form-section-title">Dados do Animal Resgatado</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Nome Provisório / Definitivo</label>
                            <input type="text" id="name" name="name" class="form-control" placeholder="ex.: Rex" value="<?php echo htmlspecialchars($form_values['name']); ?>" required autofocus>
                        </div>
                        <div class="form-group">
                            <label for="microchip">Número do Microchip (Opcional)</label>
                            <input type="text" id="microchip" name="microchip" class="form-control" placeholder="ex.: 9002240001" value="<?php echo htmlspecialchars($form_values['microchip']); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="species">Espécie</label>
                            <select id="species" name="species" class="form-control" required>
                                <option value="Canino" <?php echo $form_values['species'] === 'Canino' ? 'selected' : ''; ?>>Canino</option>
                                <option value="Felino" <?php echo $form_values['species'] === 'Felino' ? 'selected' : ''; ?>>Felino</option>
                                <option value="Equino" <?php echo $form_values['species'] === 'Equino' ? 'selected' : ''; ?>>Equino</option>
                                <option value="Bovino" <?php echo $form_values['species'] === 'Bovino' ? 'selected' : ''; ?>>Bovino</option>
                                <option value="Outro" <?php echo $form_values['species'] === 'Outro' ? 'selected' : ''; ?>>Outro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="gender">Sexo</label>
                            <select id="gender" name="gender" class="form-control">
                                <option value="" <?php echo $form_values['gender'] === '' ? 'selected' : ''; ?>>Não informado</option>
                                <option value="Macho" <?php echo $form_values['gender'] === 'Macho' ? 'selected' : ''; ?>>Macho</option>
                                <option value="Fêmea" <?php echo $form_values['gender'] === 'Fêmea' ? 'selected' : ''; ?>>Fêmea</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="breed">Raça</label>
                            <input type="text" id="breed" name="breed" class="form-control" placeholder="ex.: Vira-lata, Pastor Alemão" value="<?php echo htmlspecialchars($form_values['breed']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="size">Porte</label>
                            <select id="size" name="size" class="form-control">
                                <option value="" <?php echo $form_values['size'] === '' ? 'selected' : ''; ?>>Não informado</option>
                                <option value="Pequeno" <?php echo $form_values['size'] === 'Pequeno' ? 'selected' : ''; ?>>Pequeno</option>
                                <option value="Médio" <?php echo $form_values['size'] === 'Médio' ? 'selected' : ''; ?>>Médio</option>
                                <option value="Grande" <?php echo $form_values['size'] === 'Grande' ? 'selected' : ''; ?>>Grande</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="color">Cor</label>
                            <input type="text" id="color" name="color" class="form-control" placeholder="ex.: Preto e Branco" value="<?php echo htmlspecialchars($form_values['color']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="quarantine_days">Dias de Quarentena</label>
                            <input type="number" id="quarantine_days" name="quarantine_days" class="form-control" min="1" max="90" value="<?php echo htmlspecialchars($form_values['quarantine_days']); ?>" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Confirmar Resgate e Iniciar Quarentena</button>
                </form>
            <?php endif; ?>
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
        text-align: end;
        margin-bottom: 1rem;
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
