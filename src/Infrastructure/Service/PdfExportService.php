<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;

class PdfExportService
{
    public function export(array $data, array $headers, string $filename = 'export', string $title = 'Liste'): Response
    {
        $html = $this->generateHtml($data, $headers, $title);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.pdf"',
            ]
        );
    }

    private function generateHtml(array $data, array $headers, string $title): string
    {
        $html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
        }
        h1 {
            text-align: center;
            color: #76aaaa;
            font-size: 18px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #76aaaa;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>' . htmlspecialchars($title) . '</h1>
    <p style="text-align: right; font-size: 9px; color: #666;">Généré le ' . date('d/m/Y à H:i') . '</p>
    <table>
        <thead>
            <tr>';

        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }

        $html .= '
            </tr>
        </thead>
        <tbody>';

        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . htmlspecialchars($cell ?? '') . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '
        </tbody>
    </table>
    <div class="footer">
        <p>Direction Générale des Impôts - Système de Gestion de Courrier</p>
    </div>
</body>
</html>';

        return $html;
    }
}
