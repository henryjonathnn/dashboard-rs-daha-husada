<?php

namespace App\Services;

use App\Models\Komplain;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class UpdateService
{
    private const PETUGAS_LIST = ['Ganang', 'Agus', 'Ali Muhson', 'Virgie', 'Bayu', 'Adika Wicaksana'];
    private const PETUGAS_REPLACEMENTS = [
        'Adi' => 'Adika Wicaksana',
        'adika' => 'Adika Wicaksana',
        'wicaksana' => 'Adika Wicaksana',
        'Adikaka' => 'Adika Wicaksana',
        'adikaka' => 'Adika Wicaksana',
        'dika' => 'Adika Wicaksana',
        'Dika' => 'Adika Wicaksana',
        'dikq' => 'Adika Wicaksana',
        'Dikq' => 'Adika Wicaksana',
        'AAdika' => 'Adika Wicaksana',
        'virgie' => 'Virgie',
        'Vi' => 'Virgie',
        'vi' => 'Virgie',
        'ali' => 'Ali Muhson',
        'muhson' => 'Ali Muhson',
    ];

    private $formId;

    public function __construct($formId = 4)
    {
        $this->formId = $formId;
    }

    public function getDateParameters(Request $request)
    {
        $availableDates = Komplain::getAvailableDates($this->formId);

        $latestDate = $availableDates->first();
        $year = $request->input('year', $latestDate ? $latestDate['year'] : Carbon::now()->year);
        $month = $request->input('month', $latestDate ? $latestDate['month'] : Carbon::now()->month);

        return [
            'year' => (int)$year,
            'month' => (int)$month,
            'availableDates' => $availableDates,
        ];
    }

    public function getMonthOptions()
    {
        return Komplain::getAvailableDates($this->formId)
            ->pluck('month', 'monthName')
            ->unique()
            ->map(function ($month, $monthName) {
                return [
                    'value' => $month,
                    'label' => $monthName
                ];
            })
            ->sortBy('value')
            ->values();
    }

    public function getYearOptions()
    {
        return Komplain::getAvailableDates($this->formId)
            ->pluck('year')
            ->unique()
            ->map(function ($year) {
                return ['value' => $year, 'label' => (string)$year];
            })
            ->sortByDesc('value')
            ->values();
    }

    public function getDailyUpdateRequests($year, $month)
    {
        return Cache::remember("daily_update_requests_{$year}_{$month}", 60, function () use ($year, $month) {
            $complaints = Komplain::withFormId($this->formId)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->get();

            $daysInMonth = Carbon::create($year, $month)->daysInMonth;
            $dailyRequests = array_fill(1, $daysInMonth, 0);

            foreach ($complaints as $complaint) {
                $day = $complaint->created_at->day;
                $dailyRequests[$day]++;
            }

            return $dailyRequests;
        });
    }

    public function getUpdateStats($year, $month)
    {
        return Cache::remember("update_stats_{$this->formId}_{$year}_{$month}", 60, function () use ($year, $month) {
            $complaints = Komplain::getComplaintsByMonthYear($month, $year, $this->formId);

            $totalComplaints = $complaints->count();
            $statusCount = $complaints->groupBy('status')->map->count();

            $timeDiffs = $complaints->reduce(function ($carry, $complaint) {
                if ($complaint['datetime_masuk'] && $complaint['datetime_pengerjaan']) {
                    $carry['response'][] = Carbon::parse($complaint['datetime_masuk'])->diffInSeconds(Carbon::parse($complaint['datetime_pengerjaan']));
                }
                if ($complaint['datetime_pengerjaan'] && $complaint['datetime_selesai']) {
                    $carry['processing'][] = Carbon::parse($complaint['datetime_pengerjaan'])->diffInSeconds(Carbon::parse($complaint['datetime_selesai']));
                }
                return $carry;
            }, ['response' => [], 'processing' => []]);

            $averageResponseTime = !empty($timeDiffs['response']) ? array_sum($timeDiffs['response']) / count($timeDiffs['response']) : 0;
            $averageProcessingTime = !empty($timeDiffs['processing']) ? array_sum($timeDiffs['processing']) / count($timeDiffs['processing']) : 0;

            return [
                'total' => $totalComplaints,
                'statusCount' => $statusCount,
                'averageTimes' => [
                    'responseTime' => round($averageResponseTime),
                    'processingTime' => round($averageProcessingTime),
                ],
            ];
        });
    }

    public function getDetailedUpdate($year, $month)
    {
        return Cache::remember("detailed_update_{$this->formId}_{$year}_{$month}", 60, function () use ($year, $month) {
            $complaints = Komplain::getComplaintsByMonthYear($month, $year, $this->formId);

            return $complaints->groupBy('status')
                ->map(function ($group) {
                    return $group->map(function ($complaint) {
                        return [
                            'ID' => $complaint['id'],
                            'Pelapor' => $complaint['nama_pelapor'],
                            'Petugas' => $complaint['petugas'],
                            'Lokasi' => $complaint['lokasi'],
                            'Pesan' => $complaint['pesan'],
                            'Waktu Masuk' => $complaint['datetime_masuk'],
                            'Waktu Pengerjaan' => $complaint['datetime_pengerjaan'],
                            'Waktu Selesai' => $complaint['datetime_selesai'],
                            'Respon Time' => $this->formatTimeDiff(Carbon::parse($complaint['datetime_masuk'])->diffInSeconds(Carbon::parse($complaint['datetime_pengerjaan']))),
                            'Durasi Pengerjaan' => $this->formatTimeDiff(Carbon::parse($complaint['datetime_pengerjaan'])->diffInSeconds(Carbon::parse($complaint['datetime_selesai']))),
                        ];
                    });
                });
        });
    }


    public function getPetugasUpdateStats($year, $month)
    {
        return Cache::remember("petugas_update_stats_{$this->formId}_{$year}_{$month}", 60, function () use ($year, $month) {
            $complaints = Komplain::withFormId($this->formId)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->get();

            $petugasStats = array_fill_keys(self::PETUGAS_LIST, 0);

            foreach ($complaints as $complaint) {
                $normalizedPetugas = $this->normalizePetugasNames($complaint->petugas);
                foreach ($normalizedPetugas as $petugas) {
                    if (isset($petugasStats[$petugas])) {
                        $petugasStats[$petugas]++;
                    }
                }
            }

            arsort($petugasStats);
            return array_filter($petugasStats);
        });
    }


    private function normalizePetugasNames($petugas)
    {
        if (empty($petugas)) return [];

        $roughSplit = preg_split('/\s*[,&+]\s*|\s+dan\s+/i', $petugas);

        $normalizedList = [];
        foreach ($roughSplit as $namePart) {
            $words = preg_split('/\s+/', trim($namePart));
            $currentName = '';
            foreach ($words as $word) {
                $currentName .= ($currentName ? ' ' : '') . $word;
                $normalizedName = $this->normalizeSingleName($currentName);
                if ($normalizedName) {
                    $normalizedList[] = $normalizedName;
                    $currentName = '';
                }
            }
            if ($currentName) {
                $normalizedName = $this->normalizeSingleName($currentName);
                if ($normalizedName) {
                    $normalizedList[] = $normalizedName;
                }
            }
        }

        return array_unique($normalizedList);
    }

    private function normalizeSingleName($name)
    {
        $lowerName = strtolower(trim($name));

        foreach (self::PETUGAS_REPLACEMENTS as $key => $replacement) {
            if (strpos($lowerName, strtolower($key)) !== false) {
                return $replacement;
            }
        }

        foreach (self::PETUGAS_LIST as $validPetugas) {
            if (strtolower($validPetugas) === $lowerName) {
                return $validPetugas;
            }
        }

        return null;
    }

    public function formatTimeDiff($seconds)
    {
        $times = [
            86400 => 'hari',
            3600 => 'jam',
            60 => 'menit'
        ];

        $result = [];
        foreach ($times as $unit => $text) {
            if ($seconds >= $unit) {
                $qty = floor($seconds / $unit);
                $result[] = "$qty $text";
                $seconds %= $unit;
            }
        }

        return $result ? implode(' ', $result) : '0 menit';
    }
}
