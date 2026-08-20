<?php
/**
 * Classe per la generazione dinamica della Tessera ANED
 */
require_once __DIR__ . '/vendor/autoload.php'; // Usa l'autoloader di Composer per fpdf

class TesseraAnedGenerator {
    private $pdf;
    private $imageFronte;
    private $imageFamiliare;

    /**
     * Costruttore della classe
     * @param string $pathFronte Percorso del file TesseraFronte.jpg
     * @param string $pathFamiliare Percorso del file TesseraFamiliare.jpg
     */
    public function __construct($pathFronte = 'TesseraFronte.jpg', $pathFamiliare = 'TesseraFamiliare.jpg') {
        if (!file_exists($pathFronte) || !file_exists($pathFamiliare)) {
            throw new Exception("Errore: Uno o entrambi i file di background non sono stati trovati. Assicurati di avere '{$pathFronte}' e '{$pathFamiliare}' nella cartella.");
        }
        $this->imageFronte = $pathFronte;
        $this->imageFamiliare = $pathFamiliare;
        
        $this->pdf = new \FPDF('P', 'mm', 'A4');
        $this->pdf->SetAutoPageBreak(false);
    }

    /**
     * Genera il PDF compilando dinamicamente i dati forniti
     * @param array $dati Array associativo contenente i parametri dinamici
     * @param string $outputMode 'I' (Inline nel browser), 'D' (Forza download), 'F' (Salva su server)
     * @param string $filename Nome del file di output
     */
    public function genera($dati, $outputMode = 'I', $filename = 'tessera_compilata.pdf') {
        $anno        = isset($dati['anno'])        ? $dati['anno']        : date('Y');
        $sezione     = isset($dati['sezione'])     ? $dati['sezione']     : '';
        $utente      = isset($dati['utente'])      ? $dati['utente']      : '';
        $rappresenta = isset($dati['rappresenta']) ? $dati['rappresenta'] : '';
        $campo       = isset($dati['campo'])       ? $dati['campo']       : '';

        // --- PAGINA 1: FRONTE TESSERA ---
        $this->pdf->AddPage();
        $this->pdf->Image($this->imageFronte, 20, 20, 170); // Immagine scalata proporzionalmente
        
        // Font Anno (Grande, Bold, Nero)
        $this->pdf->SetFont('Arial', 'B', 32);
        $this->pdf->SetTextColor(0, 0, 0);
        
        // Centrato sulla metà destra della tessera (metà immagine = X:105, larghezza restante: 85)
        $this->pdf->SetXY(110, 125);
        $this->pdf->Cell(85, 15, $anno, 0, 0, 'C');

        // --- PAGINA 2: RETRO / DATI FAMILIARE ---
        $this->pdf->AddPage();
        $this->pdf->Image($this->imageFamiliare, 20, 20, 170);
        
        // Font Dati (Dimensione calibrata sulle righe dei moduli, Regolare, Nero)
        $this->pdf->SetFont('Arial', '', 13);
        $this->pdf->SetTextColor(0, 0, 0);
        
        // Utilizziamo mb_convert_encoding() per mappare correttamente caratteri accentati su FPDF standard in PHP 8.2+
        // 1. Sezione di...
        $this->pdf->SetXY(136, 44);
        $this->pdf->Cell(64, 8, mb_convert_encoding($sezione, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        
        // 2. Nome e Cognome Utente (Inizia da inizio riga tratteggiata destra)
        $this->pdf->SetXY(115, 54);
        $this->pdf->Cell(85, 8, mb_convert_encoding($utente, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        
        // 3. Rappresenta... (Inizia da inizio riga tratteggiata destra)
        $this->pdf->SetXY(115, 77);
        $this->pdf->Cell(85, 8, mb_convert_encoding($rappresenta, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        
        // 4. Deportato/a nel campo di... (Inizia da inizio riga tratteggiata destra)
        $this->pdf->SetXY(115, 91);
        $this->pdf->Cell(85, 8, mb_convert_encoding($campo, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');

        return $this->pdf->Output($outputMode, $filename);
    }
}