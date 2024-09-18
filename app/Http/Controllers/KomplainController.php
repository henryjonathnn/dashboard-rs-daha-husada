<?php

namespace App\Http\Controllers;

use App\Models\Komplain;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class KomplainController extends Controller
{
    public function index(Request $request)
    {
        $availableDates = Komplain::getAvailableDates();

        $latestDate = $availableDates->first();
        $selectedDate = $request->input('selectedDate', $latestDate ? "{$latestDate['year']}-{$latestDate['month']}" : Carbon::now()->format('Y-n'));

        list($year, $month) = explode('-', $selectedDate);

        $complaints = Komplain::getComplaintsByMonthYear($month, $year);

        return Inertia::render('Komplain/Index', [
            'complaints' => $complaints,
            'availableDates' => $availableDates,
            'initialComplaints' => $complaints // This is used for the initial render
        ]);
    }
}