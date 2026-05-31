<?php

namespace App\Http\Controllers\Api\Tenants\Reports;

use App\Http\Controllers\Controller;
use App\Services\Tenants\ProductReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductReportController extends Controller
{
    public function __invoke(Request $request, ProductReportService $productReportService): Response|\Illuminate\Http\JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $reportData = $productReportService->generate($request->all());

            if ($request->wantsJson()) {
                return response()->json([
                    'data' => $reportData['reports'],
                    'header' => $reportData['header'],
                    'footer' => $reportData['footer'],
                ]);
            }

            $reports = $reportData['reports'];
            $footer = $reportData['footer'];
            $header = $reportData['header'];

            $pdf = Pdf::loadView('reports.product', compact('reports', 'footer', 'header'))
                ->setPaper('a4', 'landscape');
            $pdf->output();
            $domPdf = $pdf->getDomPDF();
            $canvas = $domPdf->getCanvas();
            $canvas->page_text(720, 570, 'Halaman {PAGE_NUM} dari {PAGE_COUNT}', null, 10, [0, 0, 0]);

            return $pdf->stream();
        } catch (Exception $e) {
            abort(500, 'Failed to generate product report: ' . $e->getMessage());
        }
    }
}
