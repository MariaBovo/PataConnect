<?php
require_once __DIR__ . '/../../system/auth.php';
$page = pata_page_start();

$pet_id = (int) ($_GET['pet_id'] ?? $_POST['pet_id'] ?? 0);
$pet = null;

if ($pet_id > 0) {
    try {
        $statement = pata_db()->prepare('SELECT * FROM "pets" WHERE "id" = :id LIMIT 1');
        $statement->execute(['id' => $pet_id]);
        $pet = $statement->fetch();
    } catch (Throwable $e) {
        $page['error'] = "Erro ao buscar animal: " . $e->getMessage();
    }
}

if ($pet === null) {
    $page['error'] = "Animal não encontrado.";
} elseif ($pet['status'] !== 'Ativo') {
    $page['error'] = "Este animal não está disponível para adoção (Status: {$pet['status']}).";
}

$form_values = [
    'cpf' => '',
    'full_name' => '',
    'phone_1' => '',
    'phone_2' => '',
    'email' => '',
    'zip_code' => '',
    'address' => '',
    'address_number' => '',
    'neighborhood' => '',
    'address_complement' => '',
    'city' => 'Rio Claro',
    'state' => 'SP',
    'signed_date' => date('Y-m-d'),
    'witness_1_name' => '',
    'witness_1_cpf' => '',
    'witness_2_name' => '',
    'witness_2_cpf' => ''
];

if ($page['method'] === 'POST' && $pet !== null) {
    foreach ($form_values as $field => $default) {
        $form_values[$field] = trim((string) ($_POST[$field] ?? $default));
    }

    // Clean CPFs for validation/storage consistency
    $clean_cpf = preg_replace('/\D/', '', $form_values['cpf']);
    $clean_w1_cpf = preg_replace('/\D/', '', $form_values['witness_1_cpf']);
    $clean_w2_cpf = preg_replace('/\D/', '', $form_values['witness_2_cpf']);

    if (empty($form_values['full_name']) || empty($form_values['phone_1'])) {
        $page['error'] = 'Preencha o nome completo e o telefone do tutor.';
    } elseif (strlen($clean_cpf) !== 11) {
        $page['error'] = 'CPF do tutor inválido. Deve conter 11 dígitos.';
    } else {
        try {
            $db = pata_db();
            $db->beginTransaction();

            // 1. Check or insert Tutor
            $stmt = $db->prepare('SELECT id FROM "tutors" WHERE "cpf" = :cpf LIMIT 1');
            $stmt->execute(['cpf' => $clean_cpf]);
            $tutor = $stmt->fetch();

            if ($tutor) {
                $tutor_id = (int) $tutor['id'];
                // Update tutor contact info
                $stmt = $db->prepare('
                    UPDATE "tutors"
                    SET "full_name" = :full_name, "phone_1" = :phone_1, "phone_2" = :phone_2,
                        "email" = :email, "address" = :address, "address_number" = :address_number,
                        "zip_code" = :zip_code, "neighborhood" = :neighborhood, "address_complement" = :address_complement,
                        "city" = :city, "state" = :state
                    WHERE "id" = :id
                ');
                $stmt->execute([
                    'full_name' => $form_values['full_name'],
                    'phone_1' => $form_values['phone_1'],
                    'phone_2' => $form_values['phone_2'] !== '' ? $form_values['phone_2'] : null,
                    'email' => $form_values['email'] !== '' ? $form_values['email'] : null,
                    'address' => $form_values['address'] !== '' ? $form_values['address'] : null,
                    'address_number' => $form_values['address_number'] !== '' ? $form_values['address_number'] : null,
                    'zip_code' => $form_values['zip_code'] !== '' ? $form_values['zip_code'] : null,
                    'neighborhood' => $form_values['neighborhood'] !== '' ? $form_values['neighborhood'] : null,
                    'address_complement' => $form_values['address_complement'] !== '' ? $form_values['address_complement'] : null,
                    'city' => $form_values['city'] !== '' ? $form_values['city'] : null,
                    'state' => $form_values['state'] !== '' ? $form_values['state'] : null,
                    'id' => $tutor_id
                ]);
            } else {
                $stmt = $db->prepare('
                    INSERT INTO "tutors" (
                        "cpf", "full_name", "phone_1", "phone_2", "email", "address", 
                        "address_number", "zip_code", "neighborhood", "address_complement", "city", "state"
                    ) VALUES (
                        :cpf, :full_name, :phone_1, :phone_2, :email, :address, 
                        :address_number, :zip_code, :neighborhood, :address_complement, :city, :state
                    )
                ');
                $stmt->execute([
                    'cpf' => $clean_cpf,
                    'full_name' => $form_values['full_name'],
                    'phone_1' => $form_values['phone_1'],
                    'phone_2' => $form_values['phone_2'] !== '' ? $form_values['phone_2'] : null,
                    'email' => $form_values['email'] !== '' ? $form_values['email'] : null,
                    'address' => $form_values['address'] !== '' ? $form_values['address'] : null,
                    'address_number' => $form_values['address_number'] !== '' ? $form_values['address_number'] : null,
                    'zip_code' => $form_values['zip_code'] !== '' ? $form_values['zip_code'] : null,
                    'neighborhood' => $form_values['neighborhood'] !== '' ? $form_values['neighborhood'] : null,
                    'address_complement' => $form_values['address_complement'] !== '' ? $form_values['address_complement'] : null,
                    'city' => $form_values['city'] !== '' ? $form_values['city'] : null,
                    'state' => $form_values['state'] !== '' ? $form_values['state'] : null
                ]);
                $tutor_id = (int) $db->lastInsertId();
            }

            // 2. Update pet's status and clear housing
            $stmt = $db->prepare('
                UPDATE "pets"
                SET "status" = \'Adotado\', "block" = NULL, "cage" = NULL
                WHERE "id" = :id
            ');
            $stmt->execute(['id' => $pet_id]);

            // 3. Create control record
            $stmt = $db->prepare('
                INSERT INTO "control_records" ("record_type", "tutor_id", "pet_id")
                VALUES (\'Adoção\', :tutor_id, :pet_id)
            ');
            $stmt->execute([
                'tutor_id' => $tutor_id,
                'pet_id' => $pet_id
            ]);
            $control_record_id = (int) $db->lastInsertId();

            // 4. Create responsibility term
            $stmt = $db->prepare('
                INSERT INTO "responsibility_terms" (
                    "control_record_id", "signed_date", 
                    "witness_1_name", "witness_1_cpf", 
                    "witness_2_name", "witness_2_cpf"
                ) VALUES (
                    :control_record_id, :signed_date, 
                    :w1_name, :w1_cpf, 
                    :w2_name, :w2_cpf
                )
            ');
            $stmt->execute([
                'control_record_id' => $control_record_id,
                'signed_date' => $form_values['signed_date'] !== '' ? $form_values['signed_date'] : date('Y-m-d'),
                'w1_name' => $form_values['witness_1_name'] !== '' ? $form_values['witness_1_name'] : null,
                'w1_cpf' => $clean_w1_cpf !== '' ? $clean_w1_cpf : null,
                'w2_name' => $form_values['witness_2_name'] !== '' ? $form_values['witness_2_name'] : null,
                'w2_cpf' => $clean_w2_cpf !== '' ? $clean_w2_cpf : null
            ]);

            $db->commit();

            header('Location: /pets/adotados?notice=' . urlencode("Adoção registrada com sucesso! {$pet['name']} agora está com o novo tutor."));
            exit;
        } catch (Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $page['error'] = 'Erro ao salvar adoção no banco de dados: ' . $error->getMessage();
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
                <a href="/pets" class="btn-action btn-secondary">Voltar</a>
            </div>
            <div class="form-header">
                <h2>Registrar Adoção</h2>
                <p>Insira as informações do tutor e assine o termo de responsabilidade.</p>
            </div>

            <?php if ($pet !== null): ?>
                <div class="panel" style="background: #12141a; border: 1px solid #333; border-radius: 6px; padding: 1rem; margin-bottom: 2rem;">
                    <h4 style="margin: 0 0 0.5rem 0; color: #7cc7aa;">Animal Selecionado (#PET-<?php echo htmlspecialchars((string) $pet['id']); ?>)</h4>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; font-size: 0.9rem;">
                        <p style="margin: 0;"><strong>Nome:</strong> <?php echo htmlspecialchars((string) $pet['name']); ?></p>
                        <p style="margin: 0;"><strong>Espécie:</strong> <?php echo htmlspecialchars((string) $pet['species']); ?></p>
                        <p style="margin: 0;"><strong>Raça:</strong> <?php echo htmlspecialchars((string) ($pet['breed'] ?? 'SRD')); ?></p>
                        <p style="margin: 0;"><strong>Porte:</strong> <?php echo htmlspecialchars((string) ($pet['size'] ?? 'Não informado')); ?></p>
                        <p style="margin: 0;"><strong>Cor:</strong> <?php echo htmlspecialchars((string) ($pet['color'] ?? 'Não informado')); ?></p>
                        <p style="margin: 0;"><strong>Microchip:</strong> <?php echo htmlspecialchars((string) ($pet['microchip'] ?? 'Nenhum')); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($page['error'] !== null): ?>
                <div class="page-feedback page-feedback-error" style="margin-bottom: 1.5rem; padding: 0.75rem; background: rgba(250,82,82,0.15); border: 1px solid #fa5252; color: #fa5252; border-radius: 6px; font-weight: bold; font-size: 0.9rem;">
                    <?php echo htmlspecialchars($page['error']); ?>
                </div>
            <?php endif; ?>

            <?php if ($pet !== null): ?>
                <form action="<?php echo pata_form_action(); ?>" method="POST" id="adoptionForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pata_csrf_token()); ?>">
                    <input type="hidden" name="pet_id" value="<?php echo $pet_id; ?>">
                    
                    <h3 class="form-section-title">Dados do Adotante (Tutor)</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cpf">CPF *</label>
                            <input type="text" id="cpf" name="cpf" class="form-control" placeholder="ex.: 000.000.000-00" value="<?php echo htmlspecialchars($form_values['cpf']); ?>" required autofocus>
                        </div>
                        <div class="form-group">
                            <label for="full_name">Nome Completo *</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" placeholder="ex.: Maria Silva" value="<?php echo htmlspecialchars($form_values['full_name']); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone_1">Telefone Principal *</label>
                            <input type="text" id="phone_1" name="phone_1" class="form-control" placeholder="ex.: (19) 99999-9999" value="<?php echo htmlspecialchars($form_values['phone_1']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone_2">Telefone Secundário (Opcional)</label>
                            <input type="text" id="phone_2" name="phone_2" class="form-control" placeholder="ex.: (19) 3521-0000" value="<?php echo htmlspecialchars($form_values['phone_2']); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label for="email">E-mail (Opcional)</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="ex.: tutor@email.com" value="<?php echo htmlspecialchars($form_values['email']); ?>">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="zip_code">CEP</label>
                            <input type="text" id="zip_code" name="zip_code" class="form-control" placeholder="ex.: 13500-000" value="<?php echo htmlspecialchars($form_values['zip_code']); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="flex: 3;">
                            <label for="address">Endereço (Rua/Av.)</label>
                            <input type="text" id="address" name="address" class="form-control" placeholder="ex.: Rua 14 B" value="<?php echo htmlspecialchars($form_values['address']); ?>">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="address_number">Número</label>
                            <input type="text" id="address_number" name="address_number" class="form-control" placeholder="ex.: 123" value="<?php echo htmlspecialchars($form_values['address_number']); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="neighborhood">Bairro</label>
                            <input type="text" id="neighborhood" name="neighborhood" class="form-control" placeholder="ex.: Centro" value="<?php echo htmlspecialchars($form_values['neighborhood']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="address_complement">Complemento</label>
                            <input type="text" id="address_complement" name="address_complement" class="form-control" placeholder="ex.: Apto 10" value="<?php echo htmlspecialchars($form_values['address_complement']); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="flex: 3;">
                            <label for="city">Cidade</label>
                            <input type="text" id="city" name="city" class="form-control" value="<?php echo htmlspecialchars($form_values['city']); ?>">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="state">Estado (UF)</label>
                            <input type="text" id="state" name="state" class="form-control" maxlength="2" value="<?php echo htmlspecialchars($form_values['state']); ?>">
                        </div>
                    </div>

                    <h3 class="form-section-title">Responsabilidade & Testemunhas</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="signed_date">Data de Assinatura</label>
                            <input type="date" id="signed_date" name="signed_date" class="form-control" value="<?php echo htmlspecialchars($form_values['signed_date']); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="witness_1_name">Testemunha 1 - Nome</label>
                            <input type="text" id="witness_1_name" name="witness_1_name" class="form-control" placeholder="Nome completo" value="<?php echo htmlspecialchars($form_values['witness_1_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="witness_1_cpf">Testemunha 1 - CPF</label>
                            <input type="text" id="witness_1_cpf" name="witness_1_cpf" class="form-control" placeholder="ex.: 000.000.000-00" value="<?php echo htmlspecialchars($form_values['witness_1_cpf']); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="witness_2_name">Testemunha 2 - Nome</label>
                            <input type="text" id="witness_2_name" name="witness_2_name" class="form-control" placeholder="Nome completo" value="<?php echo htmlspecialchars($form_values['witness_2_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="witness_2_cpf">Testemunha 2 - CPF</label>
                            <input type="text" id="witness_2_cpf" name="witness_2_cpf" class="form-control" placeholder="ex.: 000.000.000-00" value="<?php echo htmlspecialchars($form_values['witness_2_cpf']); ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Assinar Termo & Confirmar Adoção</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Simple client-side formatting masks
        const formatCPF = (val) => {
            return val.replace(/\D/g, '')
                      .replace(/(\d{3})(\d)/, '$1.$2')
                      .replace(/(\d{3})(\d)/, '$1.$2')
                      .replace(/(\d{3})(\d{1,2})/, '$1-$2')
                      .replace(/(-\d{2})\d+?$/, '$1');
        };

        const formatPhone = (val) => {
            const numbers = val.replace(/\D/g, '');
            if (numbers.length <= 10) {
                return numbers.replace(/(\d{2})(\d)/, '($1) $2')
                              .replace(/(\d{4})(\d)/, '$1-$2')
                              .replace(/(-\d{4})\d+?$/, '$1');
            } else {
                return numbers.replace(/(\d{2})(\d)/, '($1) $2')
                              .replace(/(\d{5})(\d)/, '$1-$2')
                              .replace(/(-\d{4})\d+?$/, '$1');
            }
        };

        document.getElementById('cpf')?.addEventListener('input', (e) => {
            e.target.value = formatCPF(e.target.value);
        });
        document.getElementById('witness_1_cpf')?.addEventListener('input', (e) => {
            e.target.value = formatCPF(e.target.value);
        });
        document.getElementById('witness_2_cpf')?.addEventListener('input', (e) => {
            e.target.value = formatCPF(e.target.value);
        });

        document.getElementById('phone_1')?.addEventListener('input', (e) => {
            e.target.value = formatPhone(e.target.value);
        });
        document.getElementById('phone_2')?.addEventListener('input', (e) => {
            e.target.value = formatPhone(e.target.value);
        });
    </script>
</body>
<style>
    .registration-wrapper {
        max-width: 800px;
        margin: 2rem auto;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #ffffff;
        padding: 0 1rem;
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
        color: #7cc7aa;
        font-size: 1.1rem;
        margin: 2rem 0 1.25rem 0;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #333;
        padding-bottom: 0.25rem;
    }

    .form-row {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 1.25rem;
        flex-wrap: wrap;
    }

    .form-group {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 250px;
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
