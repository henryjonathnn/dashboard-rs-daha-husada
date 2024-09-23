<?php

namespace App\Http\Controllers;

use App\Models\Komplain;
use App\Services\UpdateService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;

class UpdateController extends Controller
{
    private $updateService;

    public function __construct(UpdateService $updateService)
    {
        $this->updateService = $updateService;
    }

    public function index(Request $request)
    {
        $params = $this->updateService->getDateParameters($request);
        $year = $params['year'];
        $month = $params['month'];

        $data = Cache::remember("update_data_{$year}_{$month}", 60, function () use ($year, $month) {
            return [
                'komplainStatus' => $this->updateService->getUpdateStats($year, $month),
                'detailStatus' => $this->updateService->getDetailedUpdate($year, $month),
                'dailyRequests' => $this->updateService->getDailyUpdateRequests($year, $month),
            ];
        });

        return Inertia::render('PermintaanUpdate', [
            'data_bulan' => $this->updateService->getMonthOptions(),
            'data_tahun' => $this->updateService->getYearOptions(),
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'totalData' => [
                'total_komplain' => $data['komplainStatus']['total'],
                'respon_time' => $this->updateService->formatTimeDiff($data['komplainStatus']['averageTimes']['responseTime']),
                'durasi_pengerjaan' => $this->updateService->formatTimeDiff($data['komplainStatus']['averageTimes']['processingTime']),
            ],
            'totalStatus' => $data['komplainStatus']['statusCount'],
            'detailStatus' => $data['detailStatus'],
            'dailyRequests' => $data['dailyRequests'],
        ]);
    }

    public function getUpdateStatus(Request $request)
    {
        $params = $this->updateService->getDateParameters($request);
        $complaints = $this->updateService->getUpdateStats($params['year'], $params['month']);

        return response()->json([
            'totalComplaints' => $complaints['total'],
            'averageResponseTime' => $this->updateService->formatTimeDiff($complaints['averageTimes']['responseTime']),
            'averageProcessingTime' => $this->updateService->formatTimeDiff($complaints['averageTimes']['processingTime']),
            'statusCount' => $complaints['statusCount'],
        ]);
    }

    public function getDetailStatus(Request $request)
    {
        $params = $this->updateService->getDateParameters($request);
        return response()->json($this->updateService->getDetailedUpdate($params['year'], $params['month']));
    }


    public function dataKinerja(Request $request)
    {
        $params = $this->updateService->getDateParameters($request);
        $year = $params['year'];
        $month = $params['month'];

        $data = Cache::remember("kinerja_data_update_{$year}_{$month}", 60, function () use ($year, $month) {
            return [
                'totalData' => $this->updateService->getUpdateStats($year, $month),
                'petugasData' => $this->updateService->getPetugasUpdateStats($year, $month),
            ];
        });

        return Inertia::render('KinerjaPermintaanUpdate', [
            'data_bulan' => $this->updateService->getMonthOptions(),
            'data_tahun' => $this->updateService->getYearOptions(),
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
        $params = $this->updateService->getDateParameters($request);
        return response()->json($this->updateService->getPetugasUpdateStats($params['year'], $params['month']));
    }
}