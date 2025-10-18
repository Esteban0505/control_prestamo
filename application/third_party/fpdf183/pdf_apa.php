<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH.'third_party/fpdf183/fpdf.php';

class PDF_APA extends FPDF
{
    protected $title = '';
    protected $author = '';
    protected $generation_date = '';
    protected $references = array();

    // Configuración APA
    protected $margin_left = 25.4; // 1 pulgada en mm
    protected $margin_right = 25.4;
    protected $margin_top = 25.4;
    protected $margin_bottom = 25.4;

    // Getters para propiedades protegidas
    public function getMarginLeft() {
        return $this->margin_left;
    }

    public function getMarginRight() {
        return $this->margin_right;
    }

    public function __construct($orientation='P', $unit='mm', $size='A4')
    {
        parent::__construct($orientation, $unit, $size);

        // Configurar márgenes APA
        $this->SetMargins($this->margin_left, $this->margin_top, $this->margin_right);
        $this->SetAutoPageBreak(true, $this->margin_bottom);

        // Configurar fuente por defecto
        $this->SetFont('Times', '', 12);

        // Metadata
        $this->generation_date = date('d/m/Y H:i:s');
    }

    /**
     * Establecer título del documento
     */
    public function setTitle($title, $isUTF8 = false)
    {
        $this->title = $title;
        parent::SetTitle($title, $isUTF8);
    }

    /**
     * Establecer autor del documento
     */
    public function setAuthor($author, $isUTF8 = false)
    {
        $this->author = $author;
        parent::SetAuthor($author, $isUTF8);
    }

    /**
     * Agregar referencia APA
     */
    public function addReference($reference)
    {
        $this->references[] = $reference;
    }

    /**
     * Crear portada APA
     */
    public function createCoverPage()
    {
        $this->AddPage();

        // Centrar contenido
        $this->SetY(80);

        // Título
        $this->SetFont('Times', 'B', 16);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 15, utf8_decode($this->title), 0, 1, 'C');
        $this->Ln(20);

        // Autor
        $this->SetFont('Times', '', 14);
        $this->Cell(0, 10, utf8_decode('Autor: ' . $this->author), 0, 1, 'C');
        $this->Ln(15);

        // Fecha de generación
        $this->SetFont('Times', '', 12);
        $this->Cell(0, 10, utf8_decode('Fecha de Generación: ' . $this->generation_date), 0, 1, 'C');
        $this->Ln(15);

        // Sistema
        $this->Cell(0, 10, utf8_decode('Sistema de Gestión de Préstamos'), 0, 1, 'C');
        $this->Ln(30);

        // Número de página en portada
        $this->SetY($this->GetPageHeight() - 30);
        $this->SetFont('Times', '', 10);
        $this->Cell(0, 10, '1', 0, 0, 'C');
    }

    /**
     * Crear página de referencias APA
     */
    public function createReferencesPage()
    {
        if (empty($this->references)) {
            return;
        }

        $this->AddPage();

        // Título de referencias
        $this->SetFont('Times', 'B', 14);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 15, utf8_decode('Referencias'), 0, 1, 'L');
        $this->Ln(5);

        // Lista de referencias
        $this->SetFont('Times', '', 12);
        $this->SetTextColor(0, 0, 0);

        foreach ($this->references as $ref) {
            $this->MultiCell(0, 6, utf8_decode($ref), 0, 'J');
            $this->Ln(2);
        }
    }

    /**
     * Header personalizado APA
     */
    public function Header()
    {
        if ($this->PageNo() > 1) {
            // Título abreviado en header
            $this->SetFont('Times', '', 10);
            $this->SetTextColor(128, 128, 128);
            $this->Cell(0, 10, utf8_decode($this->title), 0, 0, 'L');

            // Número de página
            $this->Cell(0, 10, $this->PageNo(), 0, 0, 'R');
            $this->Ln(15);
        }
    }

    /**
     * Footer personalizado APA
     */
    public function Footer()
    {
        if ($this->PageNo() > 1) {
            $this->SetY(-20);
            $this->SetFont('Times', '', 10);
            $this->SetTextColor(128, 128, 128);
            $this->Cell(0, 10, utf8_decode('Sistema de Gestión de Préstamos - ' . date('d/m/Y')), 0, 0, 'C');
        }
    }

    /**
     * Crear sección con formato APA
     */
    public function createSection($title, $level = 1)
    {
        $this->Ln(10);

        switch ($level) {
            case 1:
                $this->SetFont('Times', 'B', 14);
                $this->SetTextColor(0, 0, 0);
                break;
            case 2:
                $this->SetFont('Times', 'B', 13);
                $this->SetTextColor(0, 0, 0);
                break;
            case 3:
                $this->SetFont('Times', 'I', 12);
                $this->SetTextColor(0, 0, 0);
                break;
        }

        $this->Cell(0, 10, utf8_decode($title), 0, 1, 'L');
        $this->Ln(5);

        // Reset font
        $this->SetFont('Times', '', 12);
    }

    /**
     * Crear tabla con formato APA
     */
    public function createTable($headers, $data, $widths = null)
    {
        $this->Ln(5);

        // Calcular anchos si no se proporcionan
        if ($widths === null) {
            $pageWidth = $this->GetPageWidth() - $this->margin_left - $this->margin_right;
            $colCount = count($headers);
            $widths = array_fill(0, $colCount, $pageWidth / $colCount);
        }

        // Headers
        $this->SetFont('Times', 'B', 11);
        $this->SetFillColor(240, 240, 240);
        $this->SetTextColor(0, 0, 0);

        foreach ($headers as $i => $header) {
            $this->Cell($widths[$i], 10, utf8_decode($header), 1, 0, 'C', true);
        }
        $this->Ln();

        // Data
        $this->SetFont('Times', '', 10);
        $this->SetFillColor(255, 255, 255);
        $fill = false;

        foreach ($data as $row) {
            $this->SetFillColor($fill ? 248 : 255, $fill ? 248 : 255, $fill ? 248 : 255);
            foreach ($row as $i => $cell) {
                $align = is_numeric(str_replace([',', '.', '$', ' '], '', $cell)) ? 'R' : 'L';
                $this->Cell($widths[$i], 8, utf8_decode($cell), 1, 0, $align, $fill);
            }
            $this->Ln();
            $fill = !$fill;
        }

        $this->Ln(5);
    }

    /**
     * Agregar cita APA en el texto
     */
    public function addCitation($author, $year, $page = null)
    {
        $citation = "({$author}, {$year}";
        if ($page) {
            $citation .= ", p. {$page}";
        }
        $citation .= ")";

        return $citation;
    }

    /**
     * Formatear moneda según APA
     */
    public function formatCurrency($amount)
    {
        return '$' . number_format($amount, 0, ',', '.');
    }

    /**
     * Formatear fecha según APA
     */
    public function formatDate($date)
    {
        return date('d/m/Y', strtotime($date));
    }
}