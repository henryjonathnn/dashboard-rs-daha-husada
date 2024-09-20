<?php

namespace App\Http\Controllers;

use App\Models\Komplain;
use App\Services\KomplainService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;

class KomplainController extends Controller
{
    private $komplainService;

    public function __construct(KomplainService $komplainService)
    {
        $this->komplainService = $komplainService;
    }

    public function index(Request $request)
    {
        $params = $this->komplainService->getDateParameters($request);
        $year = $params['year'];
        $month = $params['month'];

        $data = Cache::remember("komplain_data_{$year}_{$month}", 60, function () use ($year, $month) {
            return [
                'komplainStatus' => $this->komplainService->getComplaintStats($year, $month),
                'detailStatus' => $this->komplainService->getDetailedComplaints($year, $month),
                'totalUnit' => $this->komplainService->getTotalUnitStats($year, $month),
            ];
        });

        return Inertia::render('Komplain', [
            'data_bulan' => $this->komplainService->getMonthOptions(),
            'data_tahun' => $this->komplainService->getYearOptions(),
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'totalData' => [
                'total_komplain' => $data['komplainStatus']['total'],
                'respon_time' => $this->komplainService->formatTimeDiff($data['komplainStatus']['averageTimes']['responseTime']),
                'durasi_pengerjaan' => $this->komplainService->formatTimeDiff($data['komplainStatus']['averageTimes']['processingTime']),
            ],
            'totalStatus' => $data['komplainStatus']['statusCount'],
            'detailStatus' => $data['detailStatus'],
            'totalUnit' => $data['totalUnit'],
        ]);
    }

    public function getKomplainStatus(Request $request)
    {
        $params = $this->komplainService->getDateParameters($request);
        $complaints = $this->komplainService->getComplaintStats($params['year'], $params['month']);

        return response()->json([
            'totalComplaints' => $complaints['total'],
            'averageResponseTime' => $this->komplainService->formatTimeDiff($complaints['averageTimes']['responseTime']),
            'averageProcessingTime' => $this->komplainService->formatTimeDiff($complaints['averageTimes']['processingTime']),
            'statusCount' => $complaints['statusCount'],
        ]);
    }

    public function getDetailStatus(Request $request)
    {
        $params = $this->komplainService->getDateParameters($request);
        return response()->json($this->komplainService->getDetailedComplaints($params['year'], $params['month']));
    }

    public function getTotalUnit(Request $request)
    {
        $params = $this->komplainService->getDateParameters($request);
        $totalUnitStats = $this->komplainService->getTotalUnitStats($params['year'], $params['month']);

        return response()->json($totalUnitStats ?: [
            'message' => 'No data available for the selected month and year.',
            'data' => []
        ]);
    }

    public function getDetailUnit(Request $request)
    {
        $params = $this->komplainService->getDateParameters($request);
        $detailedUnitStats = $this->komplainService->getDetailedUnitStats($params['year'], $params['month']);

        return response()->json($detailedUnitStats ?: [
            'message' => 'No data available for the selected month and year.',
            'data' => []
        ]);
    }

    public function dataUnit(Request $request)
    {
        $params = $this->komplainService->getDateParameters($request);
        $year = $params['year'];
        $month = $params['month'];

        $data = Cache::remember("unit_data_{$year}_{$month}", 60, function () use ($year, $month) {
            return [
                'totalUnit' => $this->komplainService->getTotalUnitStats($year, $month),
                'detailUnit' => $this->komplainService->getDetailedUnitStats($year, $month),
            ];
        });

        return Inertia::render('DataUnit', [
            'data_bulan' => $this->komplainService->getMonthOptions(),
            'data_tahun' => $this->komplainService->getYearOptions(),
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'totalUnit' => $data['totalUnit'],
            'detailUnit' => $data['detailUnit'],
        ]);
    }

    public function dataKinerja(Request $request)
    {
        $params = $this->komplainService->getDateParameters($request);
        $year = $params['year'];
        $month = $params['month'];

        $data = Cache::remember("kinerja_data_{$year}_{$month}", 60, function () use ($year, $month) {
            return [
                'totalData' => $this->komplainService->getComplaintStats($year, $month),
                'petugasData' => $this->komplainService->getPetugasStats($year, $month),
            ];
        });

        return Inertia::render('DataKinerja', [
            'data_bulan' => $this->komplainService->getMonthOptions(),
            'data_tahun' => $this->komplainService->getYearOptions(),
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'totalData' => [
                'total_komplain' => $data['totalData']['total'],
            ],
            'petugasData' => $data['petugasData'],
        ]);
    }

    public function getPetugas(Request $request)
    {
        $params = $this->komplainService->getDateParameters($request);
        return response()->json($this->komplainService->getPetugasStats($params['year'], $params['month']));
    }
}