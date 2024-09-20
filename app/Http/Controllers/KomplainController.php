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
        $availableDates = $this->getAvailableDates();

        $latestDate = $availableDates->first();
        $year = $request->input('year', $latestDate ? $latestDate['year'] : Carbon::now()->year);
        $month = $request->input('month', $latestDate ? $latestDate['month'] : Carbon::now()->month);

        return [
            'year' => (int)$year,
            'month' => (int)$month,
            'availableDates' => $availableDates,
        ];
    }

    private function getAvailableDates()
    {
        return Cache::remember('available_dates', 120, function () {
            return Komplain::getAvailableDates();
        });
    }

    public function index(Request $request)
    {
        try {
            $params = $this->getDateParameters($request);
            $complaints = $this->getComplaints($params['year'], $params['month']);

            return Inertia::render('Komplain/Index', [
                'complaints' => $complaints,
                'availableDates' => $params['availableDates'],
                'selectedDate' => [
                    'year' => $params['year'],
                    'month' => $params['month']
                ],
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
            $complaints = $this->getComplaintStats($params['year'], $params['month']);

            return response()->json([
                'totalComplaints' => $complaints['total'],
                'averageResponseTime' => $this->formatTimeDiff($complaints['averageTimes']['responseTime']),
                'averageProcessingTime' => $this->formatTimeDiff($complaints['averageTimes']['processingTime']),
                'statusCount' => $complaints['statusCount'],
            ]);
        } catch (\Exception $e) {
            Log::error('Error in KomplainController@getKomplainStatus: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while fetching complaint statistics.'], 500);
        }
    }

    public function getDetailStatus(Request $request): JsonResponse
    {
        try {
            $params = $this->getDateParameters($request);
            $complaints = $this->getDetailedComplaints($params['year'], $params['month']);

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
            $totalUnitStats = $this->getTotalUnitStats($params['year'], $params['month']);

            if (empty($totalUnitStats)) {
                return response()->json([
                    'message' => 'No data available for the selected month and year.',
                    'data' => []
                ], 200);
            }

            return response()->json($totalUnitStats);
        } catch (\Exception $e) {
            Log::error('Error in KomplainController@getTotalUnit: ' . $e->getMessage());
            return response()->json([
                'error' => 'An error occurred while fetching unit totals.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getPetugas(Request $request): JsonResponse
    {
        try {
            $params = $this->getDateParameters($request);
            $petugasStats = $this->getPetugasStats($params['year'], $params['month']);

            return response()->json($petugasStats);
        } catch (\Exception $e) {
            Log::error('Error in KomplainController@getPetugas: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while fetching petugas statistics.'], 500);
        }
    }

    private function getComplaints($year, $month)
    {
        return Cache::remember("complaints_{$year}_{$month}", 60, function () use ($year, $month) {
            return Komplain::getComplaintsByMonthYear($month, $year);
        });
    }

    private function getComplaintStats($year, $month)
    {
        return Cache::remember("complaint_stats_{$year}_{$month}", 60, function () use ($year, $month) {
            return Komplain::getComplaintStats($month, $year);
        });
    }

    private function getDetailedComplaints($year, $month)
    {
        return Cache::remember("detailed_complaints_{$year}_{$month}", 60, function () use ($year, $month) {
            return Komplain::getDetailedComplaints($month, $year);
        });
    }

    private function getTotalUnitStats($year, $month)
    {
        return Cache::remember("total_unit_stats_{$year}_{$month}", 60, function () use ($year, $month) {
            return Komplain::getTotalUnitStats($month, $year);
        });
    }

    private function getPetugasStats($year, $month)
    {
        return Cache::remember("petugas_stats_{$year}_{$month}", 60, function () use ($year, $month) {
            $complaints = Komplain::withFormIdThree()
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->get();

            $petugasStats = [];

            foreach ($complaints as $complaint) {
                $petugas = $complaint->petugas;
                if (!empty($petugas)) {
                    $petugasStats[$petugas] = ($petugasStats[$petugas] ?? 0) + 1;
                }
            }

            arsort($petugasStats);
            return $petugasStats;
        });
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
}