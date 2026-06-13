<?php

namespace App\Http\Controllers;

use App\Services\Tenants\ProductReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ProductReportController extends Controller
{
    public function __invoke(Request $request, ProductReportService $sellingReportService): Response
    {
        try {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
            ]);

            $reportData = $sellingReportService->generate($request->only(['start_date', 'end_date']));
            $reports = $reportData['reports'];
            $footer = $reportData['footer'];
            $header = $reportData['header'];

            $pdf = Pdf::loadView('reports.product', compact('reports', 'footer', 'header'))
                ->setPaper('a4', 'landscape');
            $pdf->output();
            $domPdf = $pdf->getDomPDF();
            $canvas = $domPdf->getCanvas();
            $canvas->page_text(720, 570, 'Halaman {PAGE_NUM} dari {PAGE_COUNT}', null, 10, [0, 0, 0]);
            if ($request->ajax()) {
                return $pdf->download('product-report.pdf');
            }

            return $pdf->stream();
        } catch (Exception $e) {
            Log::error('Failed to generate product report: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            abort(500, 'Failed to generate product report');
        }
    }
}
