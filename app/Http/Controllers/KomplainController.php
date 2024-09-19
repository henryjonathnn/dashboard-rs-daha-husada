<?php

namespace App\Http\Controllers;

use App\Models\Komplain;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class KomplainController extends Controller
{
    private function getDateParameters(Request $request)
    {
        $availableDates = Cache::remember('available_dates', 120, function () {
            return Komplain::getAvailableDates();
        });
        
        $latestDate = $availableDates->first();
        $selectedDate = $request->input(
            'selectedDate',
            $latestDate ? "{$latestDate['year']}-{$latestDate['month']}" : Carbon::now()->format('Y-n')
        );

        [$year, $month] = explode('-', $selectedDate);

        return [
            'year' => $year,
            'month' => $month,
            'availableDates' => $availableDates,
        ];
    }

    public function index(Request $request)
    {
        try {
            $params = $this->getDateParameters($request);
            $complaints = Komplain::getComplaintsByMonthYear($params['month'], $params['year']);

            return Inertia::render('Komplain/Index', [
                'complaints' => $complaints,
                'availableDates' => $params['availableDates'],
            ]);
        } catch (\Exception $e) {
            Log::error('Error in KomplainController@index: ' . $e->getMessage());
            return Inertia::render('Error', ['message' => 'An error occurred. Please try again later.']);
        }
    }

    public function getKomplainStatus(Request $request): JsonResponse
    {
        try {
            $params = $this->getDateParameters($request);
            $complaints = Komplain::getComplaintStats($params['month'], $params['year']);

            return response()->json([
                'totalComplaints' => $complaints['total'],
                'averageResponseTime' => $this->formatTimeDiff($complaints['averageTimes']['responseTime']),
                'averageProcessingTime' => $this->formatTimeDiff($complaints['averageTimes']['processingTime']),
                'statusCount' => $complaints['statusCount'],
            ]);
        } catch (\Exception $e) {
            Log::error('Error in KomplainController@getComplaintStats: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while fetching complaint statistics.'], 500);
        }
    }

    public function getDetailStatus(Request $request): JsonResponse
    {
        try {
            $params = $this->getDateParameters($request);
            $complaints = Komplain::getDetailedComplaints($params['month'], $params['year']);

            return response()->json($complaints);
        } catch (\Exception $e) {
            Log::error('Error in KomplainController@getDetailStatus: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while fetching complaint details.'], 500);
        }
    }

    public function getTotalUnit(Request $request): JsonResponse
    {
        try {
            $params = $this->getDateParameters($request);
            $complaints = Komplain::getComplaintsByMonthYear($params['month'], $params['year']);

            $unitCategories = $this->getUnitCategories();
            $categoryCounts = $this->calculateCategoryCounts($complaints, $unitCategories);

            return response()->json($categoryCounts);
        } catch (\Exception $e) {
            Log::error('Error in KomplainController@getTotalUnit: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while fetching unit totals.'], 500);
        }
    }

    private function formatTimeDiff($seconds): string
    {
        $times = [
            86400 => 'hari',
            3600 => 'jam',
            60 => 'menit',
            1 => 'detik'
        ];

        $result = [];
        foreach ($times as $unit => $text) {
            if ($seconds >= $unit) {
                $qty = floor($seconds / $unit);
                $result[] = "$qty $text";
                $seconds %= $unit;
            }
        }

        return $result ? implode(' ', $result) : '0 detik';
    }

    private function getUnitCategories(): array
    {
        return [
            'Unit IGD' => ['Ambulance', 'IGD'],
            'Unit Rawat Jalan' => [
                'Klinik Anak', 'Klinik Bedah', 'Klinik Gigi', 'Klinik Jantung',
                'Klinik Konservasi', 'Klinik Kulit', 'Klinik Kusta', 'Klinik Mata',
                'Klinik Obgyn', 'Klinik Ortopedy', 'Klinik Penyakit Dalam', 'Klinik TB',
                'Klinik THT', 'Klinik Umum'
            ],
            'Unit Rawat Inap' => ['Irna Atas', 'Irna Bawah', 'IBS', 'VK', 'Perinatology'],
            'Unit Penunjang Medis' => ['Farmasi', 'Laboratorium', 'Admisi / Rekam Medis', 'Rehab Medik'],
            'Unit Lainnya' => ['Lainnya']
        ];
    }

    private function calculateCategoryCounts($complaints, $unitCategories): array
    {
        $categoryCounts = array_fill_keys(array_keys($unitCategories), [
            'total' => 0,
            'Terkirim' => 0,
            'Dalam Pengerjaan' => 0,
            'Selesai' => 0,
            'Pending' => 0
        ]);

        foreach ($complaints as $complaint) {
            $unit = $complaint['unit'] ?? 'Lainnya';
            $status = $complaint['status'];

            $categoryFound = false;
            foreach ($unitCategories as $category => $units) {
                if (in_array($unit, $units)) {
                    $categoryCounts[$category]['total']++;
                    $categoryCounts[$category][$status]++;
                    $categoryFound = true;
                    break;
                }
            }

            if (!$categoryFound) {
                $categoryCounts['Unit Lainnya']['total']++;
                $categoryCounts['Unit Lainnya'][$status]++;
            }
        }

        return $categoryCounts;
    }
}