<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\KomplainService;
use App\Services\UpdateService;
use Inertia\Inertia;
use App\Models\Komplain;

class DashboardController extends Controller
{
    protected $komplainService;
    protected $updateService;

    public function __construct(KomplainService $komplainService, UpdateService $updateService)
    {
        $this->komplainService = $komplainService;
        $this->updateService = $updateService;
    }

    public function index(Request $request)
    {
        $komplainParams = $this->komplainService->getDateParameters($request);
        $updateParams = $this->updateService->getDateParameters($request);

        $year = $komplainParams['year'];
        $month = $komplainParams['month'];

        $komplainStats = $this->komplainService->getComplaintStats($year, $month);
        $updateStats = $this->updateService->getUpdateStats($year, $month);

        $lastKomplainEntry = Komplain::where('form_id', 3)
            ->orderBy('datetime_masuk', 'desc')
            ->first();

        $lastUpdateEntry = Komplain::where('form_id', 4)
            ->orderBy('datetime_masuk', 'desc')
            ->first();

        $komplainData = [
            'totalData' => [
                'total_komplain' => $komplainStats['total'],
                'respon_time' => $this->komplainService->formatTimeDiff($komplainStats['averageTimes']['responseTime']),
                'durasi_pengerjaan' => $this->komplainService->formatTimeDiff($komplainStats['averageTimes']['processingTime']),
            ],
            'totalStatus' => $komplainStats['statusCount'],
            'detailStatus' => $this->komplainService->getDetailedComplaints($year, $month),
            'data_bulan' => $this->komplainService->getMonthOptions(),
            'data_tahun' => $this->komplainService->getYearOptions(),
            'lastUpdateTime' => $lastKomplainEntry ? $lastKomplainEntry->datetime_masuk : null,
        ];

        $updateData = [
            'totalData' => [
                'total_update' => $updateStats['total'],
                'respon_time' => $this->komplainService->formatTimeDiff($updateStats['averageTimes']['responseTime']),
                'durasi_pengerjaan' => $this->komplainService->formatTimeDiff($updateStats['averageTimes']['processingTime']),
            ],
            'totalStatus' => $updateStats['statusCount'],
            'detailStatus' => $this->updateService->getDetailedUpdate($year, $month),
            'lastUpdateTime' => $lastUpdateEntry ? $lastUpdateEntry->datetime_masuk : null,
        ];

        return Inertia::render('Dashboard', [
            'komplainData' => $komplainData,
            'updateData' => $updateData,
            'selectedYear' => $year,
            'selectedMonth' => $month,
        ]);
    }
}