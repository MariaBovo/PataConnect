<?php
declare(strict_types=1);

function component_animal_sheet(array $pet): string
{
    if (empty($pet['id'])) {
        return '';
    }

    // Fetch vaccines
    $vaccines = [];
    try {
        $statement = pata_db()->prepare('SELECT * FROM "pet_vaccines" WHERE "pet_id" = :pet_id ORDER BY "id" ASC');
        $statement->execute(['pet_id' => $pet['id']]);
        $vaccines = $statement->fetchAll();
    } catch (Throwable $e) {
        // Fallback
    }

    // Determine status badge class
    $status = $pet['status'] ?? 'Quarentena';
    $status_class = 'badge-low'; // Active
    if ($status === 'Quarentena') {
        $status_class = 'badge-medium';
    } elseif ($status === 'Eutanasiado') {
        $status_class = 'badge-high';
    } elseif ($status === 'Adotado') {
        $status_class = 'badge-adopted';
    }
    $status_label = htmlspecialchars((string) $status);

    // Escape values
    $id = (int) $pet['id'];
    $name = htmlspecialchars((string) ($pet['name'] ?? 'Sem Nome'));
    $species = htmlspecialchars((string) ($pet['species'] ?? 'Não informado'));
    $gender = htmlspecialchars((string) ($pet['gender'] ?? 'Não informado'));
    $breed = htmlspecialchars((string) ($pet['breed'] ?? 'SRD'));
    $size = htmlspecialchars((string) ($pet['size'] ?? 'Não informado'));
    $color = htmlspecialchars((string) ($pet['color'] ?? 'Não informado'));
    $microchip = htmlspecialchars((string) ($pet['microchip'] ?? 'Nenhum'));
    $created_at = htmlspecialchars((string) ($pet['created_at'] ?? ''));
    $date_formatted = $created_at !== '' ? date('d/m/Y H:i', strtotime($created_at)) : 'N/A';

    $block = htmlspecialchars((string) ($pet['block'] ?? 'Não alojado'));
    $cage = htmlspecialchars((string) ($pet['cage'] ?? 'Nenhum'));

    // Quarantine details
    $quarantine_html = '';
    if ($status === 'Quarentena') {
        $days_passed = 0;
        if (!empty($pet['quarantine_start'])) {
            $start_date = strtotime($pet['quarantine_start']);
            $current_date = strtotime(date('Y-m-d'));
            $days_passed = (int) (($current_date - $start_date) / 86400);
        }
        $remaining_days = max(0, (int) ($pet['quarantine_days'] ?? 7) - $days_passed);
        $quarantine_html = <<<HTML
        <div class="sheet-data-item">
            <span class="sheet-label">Tempo em Quarentena</span>
            <span class="sheet-value" style="color: #fca311; font-weight: bold;">{$remaining_days} dias restantes</span>
        </div>
HTML;
    }

    // Euthanasia flag
    $euthanasia_html = '';
    if ($pet['euthanasia']) {
        $euthanasia_html = <<<HTML
        <div style="margin-top: 1rem; padding: 0.75rem; background: rgba(250, 82, 82, 0.15); border: 1px solid rgba(250, 82, 82, 0.3); border-radius: 6px; color: #fa5252; font-weight: 700; text-align: center; font-size: 0.9rem;">
            ⚠️ Este animal foi submetido a eutanásia.
        </div>
HTML;
    }

    // Adoption details
    $adoption_html = '';
    if ($status === 'Adotado') {
        $tutor = null;
        $term = null;
        try {
            $db = pata_db();
            // Fetch tutor and control record info
            $stmt = $db->prepare('
                SELECT t.*, cr.created_at AS adoption_date, cr.id AS control_record_id
                FROM "control_records" cr
                JOIN "tutors" t ON cr.tutor_id = t.id
                WHERE cr.pet_id = :pet_id AND cr.record_type = \'Adoção\'
                LIMIT 1
            ');
            $stmt->execute(['pet_id' => $pet['id']]);
            $tutor = $stmt->fetch();

            if ($tutor) {
                // Fetch witness details if available
                $stmt = $db->prepare('SELECT * FROM "responsibility_terms" WHERE "control_record_id" = :cr_id LIMIT 1');
                $stmt->execute(['cr_id' => $tutor['control_record_id']]);
                $term = $stmt->fetch();
            }
        } catch (Throwable $e) {
            // Fallback
        }

        if ($tutor) {
            $tutor_name = htmlspecialchars((string) $tutor['full_name']);
            $tutor_cpf = htmlspecialchars((string) $tutor['cpf']);
            // Mask CPF for privacy/readability
            if (strlen($tutor_cpf) === 11) {
                $tutor_cpf = substr($tutor_cpf, 0, 3) . '.' . substr($tutor_cpf, 3, 3) . '.' . substr($tutor_cpf, 6, 3) . '-' . substr($tutor_cpf, 9, 2);
            }
            $tutor_phone = htmlspecialchars((string) $tutor['phone_1']);
            $tutor_email = htmlspecialchars((string) ($tutor['email'] ?? 'Não informado'));
            
            $addr_parts = [];
            if (!empty($tutor['address'])) {
                $addr_parts[] = $tutor['address'];
            }
            if (!empty($tutor['address_number'])) {
                $addr_parts[] = $tutor['address_number'];
            }
            if (!empty($tutor['neighborhood'])) {
                $addr_parts[] = $tutor['neighborhood'];
            }
            $tutor_address = htmlspecialchars(implode(', ', $addr_parts));
            if ($tutor_address === '') {
                $tutor_address = 'Não informado';
            }
            
            $adopt_date = date('d/m/Y H:i', strtotime($tutor['adoption_date']));

            $witnesses_html = '';
            if ($term && (!empty($term['witness_1_name']) || !empty($term['witness_2_name']))) {
                $witnesses_html = '<div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px dashed #2d3039; font-size: 0.85rem;">';
                if (!empty($term['witness_1_name'])) {
                    $w1_name = htmlspecialchars((string) $term['witness_1_name']);
                    $witnesses_html .= "<div class='sheet-data-item' style='margin-bottom:0.4rem;'><span class='sheet-label'>Testemunha 1</span><span class='sheet-value' style='font-size:0.85rem;'>{$w1_name}</span></div>";
                }
                if (!empty($term['witness_2_name'])) {
                    $w2_name = htmlspecialchars((string) $term['witness_2_name']);
                    $witnesses_html .= "<div class='sheet-data-item'><span class='sheet-label'>Testemunha 2</span><span class='sheet-value' style='font-size:0.85rem;'>{$w2_name}</span></div>";
                }
                $witnesses_html .= '</div>';
            }

            $adoption_html = <<<HTML
            <section class="sheet-section">
                <h4 class="sheet-section-title">Dados da Adoção & Tutor</h4>
                <div class="sheet-grid">
                    <div class="sheet-data-item" style="grid-column: span 2;">
                        <span class="sheet-label">Tutor Responsável</span>
                        <span class="sheet-value" style="color: #7950f2; font-weight: bold; font-size: 1.05rem;">{$tutor_name}</span>
                    </div>
                    <div class="sheet-data-item">
                        <span class="sheet-label">CPF</span>
                        <span class="sheet-value">{$tutor_cpf}</span>
                    </div>
                    <div class="sheet-data-item">
                        <span class="sheet-label">Telefone</span>
                        <span class="sheet-value">{$tutor_phone}</span>
                    </div>
                    <div class="sheet-data-item" style="grid-column: span 2;">
                        <span class="sheet-label">E-mail</span>
                        <span class="sheet-value">{$tutor_email}</span>
                    </div>
                    <div class="sheet-data-item" style="grid-column: span 2;">
                        <span class="sheet-label">Endereço</span>
                        <span class="sheet-value" style="font-size: 0.9rem;">{$tutor_address}</span>
                    </div>
                    <div class="sheet-data-item" style="grid-column: span 2;">
                        <span class="sheet-label">Data de Adoção</span>
                        <span class="sheet-value">{$adopt_date}</span>
                    </div>
                </div>
                {$witnesses_html}
            </section>
HTML;
        }
    }

    // Vaccines table/list
    $vaccines_html = '<p class="sheet-empty-text">Nenhuma vacina registrada para este animal.</p>';
    if (!empty($vaccines)) {
        $vaccines_html = '<ul class="sheet-vaccines-list">';
        foreach ($vaccines as $vac) {
            $type = htmlspecialchars((string) $vac['vaccine_type']);
            $admin = $vac['is_administered'] ? 'Aplicada' : 'Pendente';
            $admin_date = !empty($vac['administered_date']) ? date('d/m/Y', strtotime($vac['administered_date'])) : 'Pendente';
            $admin_class = $vac['is_administered'] ? 'vac-applied' : 'vac-pending';
            
            $vaccines_html .= <<<HTML
            <li>
                <div class="vac-info">
                    <strong class="vac-type">{$type}</strong>
                    <span class="vac-date">Data: {$admin_date}</span>
                </div>
                <span class="vac-badge {$admin_class}">{$admin}</span>
            </li>
HTML;
        }
        $vaccines_html .= '</ul>';
    }

    // Single static CSS print check
    static $style_printed = false;
    $style = '';
    if (!$style_printed) {
        $style = <<<HTML
        <style>
            .animal-sheet-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(10, 12, 16, 0.75);
                backdrop-filter: blur(8px);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            .animal-sheet-card {
                background: #1a1c23;
                border: 1px solid #333333;
                border-radius: 12px;
                width: 100%;
                max-width: 600px;
                box-shadow: 0 20px 45px rgba(0, 0, 0, 0.5);
                display: flex;
                flex-direction: column;
                overflow: hidden;
                color: #f2f5f7;
            }
            .sheet-header {
                padding: 1.5rem;
                background: #12141a;
                border-bottom: 1px solid #333333;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .sheet-title-area {
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }
            .sheet-title {
                margin: 0;
                font-size: 1.4rem;
                font-weight: 800;
                color: #e8f4f8;
            }
            .sheet-close-btn {
                background: transparent;
                border: 0;
                color: #868e96;
                font-size: 1.8rem;
                font-weight: 700;
                cursor: pointer;
                line-height: 1;
                padding: 0 0.5rem;
                transition: color 0.2s;
            }
            .sheet-close-btn:hover {
                color: #fa5252;
            }
            .sheet-body {
                padding: 1.5rem;
                max-height: 70vh;
                overflow-y: auto;
            }
            .sheet-section {
                margin-bottom: 1.5rem;
            }
            .sheet-section-title {
                font-size: 0.9rem;
                font-weight: 800;
                text-transform: uppercase;
                color: #7cc7aa;
                border-bottom: 1px solid #2d3039;
                padding-bottom: 0.4rem;
                margin-top: 0;
                margin-bottom: 0.75rem;
                letter-spacing: 0.5px;
            }
            .sheet-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            .sheet-data-item {
                display: flex;
                flex-direction: column;
                gap: 0.25rem;
            }
            .sheet-label {
                font-size: 0.78rem;
                color: #868e96;
                text-transform: uppercase;
                font-weight: 700;
            }
            .sheet-value {
                font-size: 0.95rem;
                color: #e8f4f8;
                font-weight: 600;
            }
            .sheet-empty-text {
                color: #868e96;
                font-size: 0.9rem;
                margin: 0;
                font-style: italic;
            }
            .sheet-vaccines-list {
                list-style: none;
                padding: 0;
                margin: 0;
                display: grid;
                gap: 0.55rem;
            }
            .sheet-vaccines-list li {
                background: #12141a;
                border: 1px solid #2d3039;
                border-radius: 6px;
                padding: 0.65rem 0.85rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .vac-info {
                display: flex;
                flex-direction: column;
                gap: 0.15rem;
            }
            .vac-type {
                font-size: 0.9rem;
                color: #e8f4f8;
            }
            .vac-date {
                font-size: 0.75rem;
                color: #868e96;
            }
            .vac-badge {
                font-size: 0.75rem;
                font-weight: 700;
                padding: 0.25rem 0.5rem;
                border-radius: 20px;
                text-transform: uppercase;
            }
            .vac-applied {
                background: rgba(43, 138, 62, 0.15);
                color: #40c057;
                border: 1px solid rgba(43, 138, 62, 0.3);
            }
            .vac-pending {
                background: rgba(252, 163, 17, 0.15);
                color: #fca311;
                border: 1px solid rgba(252, 163, 17, 0.3);
            }
            .badge-adopted {
                background-color: rgba(121, 80, 242, 0.15);
                color: #7950f2;
                border: 1px solid rgba(121, 80, 242, 0.3);
            }
        </style>
HTML;
        $style_printed = true;
    }

    return <<<HTML
    {$style}
    <div class="animal-sheet-overlay" onclick="if (event.target === this) this.remove();">
        <div class="animal-sheet-card">
            <header class="sheet-header">
                <div class="sheet-title-area">
                    <span class="badge {$status_class}">{$status_label}</span>
                    <h3 class="sheet-title">{$name}</h3>
                    <span class="text-muted" style="font-size:0.9rem; font-weight:700;">#PET-{$id}</span>
                </div>
                <button type="button" class="sheet-close-btn" onclick="this.closest('.animal-sheet-overlay').remove();" aria-label="Fechar">&times;</button>
            </header>
            <main class="sheet-body">
                
                <section class="sheet-section">
                    <h4 class="sheet-section-title">Identificação</h4>
                    <div class="sheet-grid">
                        <div class="sheet-data-item">
                            <span class="sheet-label">Espécie</span>
                            <span class="sheet-value">{$species}</span>
                        </div>
                        <div class="sheet-data-item">
                            <span class="sheet-label">Sexo</span>
                            <span class="sheet-value">{$gender}</span>
                        </div>
                        <div class="sheet-data-item">
                            <span class="sheet-label">Raça</span>
                            <span class="sheet-value">{$breed}</span>
                        </div>
                        <div class="sheet-data-item">
                            <span class="sheet-label">Porte / Cor</span>
                            <span class="sheet-value">{$size} ({$color})</span>
                        </div>
                        <div class="sheet-data-item" style="grid-column: span 2;">
                            <span class="sheet-label">Número do Microchip</span>
                            <span class="sheet-value" style="font-family: monospace; letter-spacing:0.5px;">{$microchip}</span>
                        </div>
                    </div>
                </section>

                <section class="sheet-section">
                    <h4 class="sheet-section-title">Alojamento & Histórico</h4>
                    <div class="sheet-grid">
                        <div class="sheet-data-item">
                            <span class="sheet-label">Bloco / Área</span>
                            <span class="sheet-value" style="color: #7cc7aa;">{$block}</span>
                        </div>
                        <div class="sheet-data-item">
                            <span class="sheet-label">Gaiola / Baia</span>
                            <span class="sheet-value">{$cage}</span>
                        </div>
                        <div class="sheet-data-item">
                            <span class="sheet-label">Data de Entrada</span>
                            <span class="sheet-value">{$date_formatted}</span>
                        </div>
                        {$quarantine_html}
                    </div>
                    {$euthanasia_html}
                </section>

                {$adoption_html}

                <section class="sheet-section" style="margin-bottom: 0;">
                    <h4 class="sheet-section-title">Controle Sanitário (Vacinas)</h4>
                    {$vaccines_html}
                </section>

            </main>
        </div>
    </div>
HTML;
}
