<?php

namespace App\Http\Controllers;

use App\Models\Komplain;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class KomplainController extends Controller
{
    public function index(Request $request)
    {
        try {
            $availableDates = Komplain::getAvailableDates();
            $latestDate = $availableDates->first();
            $selectedDate = $request->input(
                'selectedDate',
                $latestDate ? "{$latestDate['year']}-{$latestDate['month']}" : Carbon::now()->format('Y-n')
            );

            [$year, $month] = explode('-', $selectedDate);

            $complaints = Komplain::getComplaintsByMonthYear($month, $year);

            return Inertia::render('Komplain/Index', [
                'complaints' => $complaints,
                'availableDates' => $availableDates,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in KomplainController@index: ' . $e->getMessage());
            return Inertia::render('Error', ['message' => 'An error occurred. Please try again later.']);
        }
    }

    public function getKomplaintStatus(Request $request): JsonResponse
    {
        try {
            $year = $request->input('year', Carbon::now()->year);
            $month = $request->input('month', Carbon::now()->month);

            $complaints = Komplain::getComplaintStats($month, $year);

            $totalComplaints = $complaints['total'];
            $statusCount = $complaints['statusCount'];
            $averageTimes = $complaints['averageTimes'];

            return response()->json([
                'totalComplaints' => $totalComplaints,
                'averageResponseTime' => $this->formatTimeDiff($averageTimes['responseTime']),
                'averageProcessingTime' => $this->formatTimeDiff($averageTimes['processingTime']),
                'statusCount' => $statusCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in KomplainController@getComplaintStats: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while fetching complaint statistics.'], 500);
        }
    }

    public function getDetailStatus(Request $request): JsonResponse
    {
        try {
            $year = $request->input('year', Carbon::now()->year);
            $month = $request->input('month', Carbon::now()->month);

            $complaints = Komplain::getDetailedComplaints($month, $year);

            $allStatuses = ['Terkirim', 'Dalam Pengerjaan', 'Selesai', 'Pending'];
            foreach ($allStatuses as $status) {
                if (!isset($complaints[$status])) {
                    $complaints[$status] = [];
                }
            }

            return response()->json($complaints);
        } catch (\Exception $e) {
            Log::error('Error in KomplainController@getDetailStatus: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while fetching complaint details.'], 500);
        }
    }

    private function formatTimeDiff($seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        $parts = [];
        if ($days > 0) $parts[] = $days . ' hari';
        if ($hours > 0) $parts[] = $hours . ' jam';
        if ($minutes > 0) $parts[] = $minutes . ' menit';
        if ($seconds > 0) $parts[] = $seconds . ' detik';

        return implode(' ', $parts) ?: '0 detik';
    }
}