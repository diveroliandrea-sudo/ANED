<?php
// Se il form è stato inviato elaboriamo la richiesta in POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/TesseraAnedGenerator.php';
    
    $dati = [
        'anno'        => $_POST['anno'] ?? date('Y'),
        'sezione'     => $_POST['sezione'] ?? '',
        'utente'      => $_POST['utente'] ?? '',
        'rappresenta' => $_POST['rappresenta'] ?? '',
        'campo'       => $_POST['campo'] ?? ''
    ];

    try {
        // Inizializza il generatore. Assicurati che le immagini esistano in c:\xampp\htdocs\ANED\
        $generator = new TesseraAnedGenerator('TesseraFronte.jpg', 'TesseraFamiliare.jpg');
        
        // Mostra il PDF direttamente nel browser ('I'). Per forzare il download usa 'D'.
        $nomeFile = 'Tessera_ANED_' . preg_replace('/[^a-zA-Z0-9]/', '_', $dati['utente']) . '.pdf';
        $generator->genera($dati, 'I', $nomeFile);
        exit;
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stampa Tessera ANED</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding-top: 40px; }
        .card { box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Generazione Tessera Iscritti ANED</h4>
                    </div>
                    <div class="card-body">
                        <!-- Il form punta alla stessa pagina con target="_blank" per aprire il PDF in una nuova tab -->
                        <form method="POST" action="stampa_tessera.php" target="_blank">
                            
                            <div class="mb-3">
                                <label for="anno" class="form-label">Anno di Iscrizione</label>
                                <input type="number" class="form-control" id="anno" name="anno" value="<?= date('Y') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="sezione" class="form-label">Sezione di</label>
                                <input type="text" class="form-control" id="sezione" name="sezione" placeholder="Es. Milano" required>
                            </div>

                            <div class="mb-3">
                                <label for="utente" class="form-label">Nome e Cognome Iscritto</label>
                                <input type="text" class="form-control" id="utente" name="utente" placeholder="Es. Mario Rossi" required>
                            </div>

                            <div class="mb-3">
                                <label for="rappresenta" class="form-label">Rappresenta (Nome Familiare)</label>
                                <input type="text" class="form-control" id="rappresenta" name="rappresenta" placeholder="Es. Luigi Rossi">
                            </div>

                            <div class="mb-3">
                                <label for="campo" class="form-label">Deportato/a nel campo di</label>
                                <input type="text" class="form-control" id="campo" name="campo" placeholder="Es. Mauthausen">
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mt-3">Genera Tessera PDF</button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>