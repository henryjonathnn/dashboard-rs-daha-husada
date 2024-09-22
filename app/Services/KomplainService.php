<?php

namespace App\Services;

use App\Models\Komplain;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class KomplainService
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

    public function getDateParameters(Request $request)
    {
        $availableDates = Komplain::getAvailableDates();

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
        return [
            ['value' => 1, 'label' => 'Januari'],
            ['value' => 2, 'label' => 'Februari'],
            ['value' => 3, 'label' => 'Maret'],
            ['value' => 4, 'label' => 'April'],
            ['value' => 5, 'label' => 'Mei'],
            ['value' => 6, 'label' => 'Juni'],
            ['value' => 7, 'label' => 'Juli'],
            ['value' => 8, 'label' => 'Agustus'],
            ['value' => 9, 'label' => 'September'],
            ['value' => 10, 'label' => 'Oktober'],
            ['value' => 11, 'label' => 'November'],
            ['value' => 12, 'label' => 'Desember'],
        ];
    }

    public function getYearOptions()
    {
        $currentYear = Carbon::now()->year;
        $years = range($currentYear - 5, $currentYear);
        return array_map(function($year) {
            return ['value' => $year, 'label' => (string)$year];
        }, $years);
    }

    public function getComplaintStats($year, $month)
    {
        return Cache::remember("complaint_stats_{$year}_{$month}", 60, function () use ($year, $month) {
            $complaints = Komplain::getComplaintsByMonthYear($month, $year);
            
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

    public function getDetailedComplaints($year, $month)
    {
        return Cache::remember("detailed_complaints_{$year}_{$month}", 60, function () use ($year, $month) {
            $complaints = Komplain::getComplaintsByMonthYear($month, $year);

            return $complaints->groupBy('status')
                ->map(function ($group) {
                    return $group->map(function ($complaint) {
                        return [
                            'ID' => $complaint['id'],
                            'Pelapor' => $complaint['nama_pelapor'],
                            'Petugas' => $complaint['petugas'],
                            'Lokasi' => $complaint['lokasi'],
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

    public function getTotalUnitStats($year, $month)
    {
        return Cache::remember("total_unit_stats_{$year}_{$month}", 60, function () use ($year, $month) {
            $complaints = Komplain::getComplaintsByMonthYear($month, $year);
            $unitCategories = Komplain::getUnitCategories();
    
            return $complaints->groupBy('unit')
                ->map(function ($unitComplaints, $unit) use ($unitCategories) {
                    $category = $this->getCategoryForUnit($unit, $unitCategories);
                    return [
                        'category' => $category,
                        'total' => $unitComplaints->count(),
                        'statusCount' => $unitComplaints->groupBy('status')->map->count(),
                    ];
                })
                ->groupBy('category')
                ->map(function ($categoryComplaints) {
                    return [
                        'total' => $categoryComplaints->sum('total'),
                        'Terkirim' => $categoryComplaints->sum('statusCount.Terkirim'),
                        'Dalam Pengerjaan' => $categoryComplaints->sum('statusCount.Dalam Pengerjaan'),
                        'Selesai' => $categoryComplaints->sum('statusCount.Selesai'),
                        'Pending' => $categoryComplaints->sum('statusCount.Pending'),
                    ];
                });
        });
    }

    public function getDetailedUnitStats($year, $month)
    {
        return Cache::remember("detailed_unit_stats_{$year}_{$month}", 60, function () use ($year, $month) {
            $complaints = Komplain::getComplaintsByMonthYear($month, $year);
            $unitCategories = Komplain::getUnitCategories();
    
            $detailedStats = collect($unitCategories)->mapWithKeys(function ($units, $category) {
                return [$category => collect()];
            });
    
            foreach ($complaints as $complaint) {
                $unit = $complaint['unit'];
                $category = $this->getCategoryForUnit($unit, $unitCategories);
                $status = $complaint['status'];
    
                if ($category === 'Unit Lainnya') {
                    $unit = 'Lainnya';
                }
    
                $detailedStats[$category] = $detailedStats[$category]->pipe(function ($categoryCollection) use ($unit, $status) {
                    $unitStats = $categoryCollection->get($unit, [
                        'Total' => 0,
                        'Terkirim' => 0,
                        'Dalam Pengerjaan' => 0,
                        'Selesai' => 0,
                        'Pending' => 0,
                    ]);
    
                    $unitStats['Total']++;
                    $unitStats[$status]++;
    
                    return $categoryCollection->put($unit, $unitStats);
                });
            }
    
            return $detailedStats->map(function ($category) {
                return $category->filter(function ($unit) {
                    return $unit['Total'] > 0;
                });
            })->filter(function ($category) {
                return $category->isNotEmpty();
            });
        });
    }

    public function getPetugasStats($year, $month)
    {
        return Cache::remember("petugas_stats_{$year}_{$month}", 60, function () use ($year, $month) {
            $complaints = Komplain::withFormIdThree()
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

    private function getCategoryForUnit($unit, $unitCategories)
    {
        foreach ($unitCategories as $category => $units) {
            if (in_array($unit, $units)) {
                return $category;
            }
        }
        return 'Unit Lainnya';
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