<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Komplain extends Model
{
    use HasFactory;

    protected $table = 'form_values';

    protected $fillable = [
        'id',
        'json',
        'datetime_masuk',
        'datetime_pengerjaan',
        'datetime_selesai',
        'petugas',
        'is_pending',
    ];

    public function scopeWithFormIdThree($query)
    {
        return $query->where('form_id', 3);
    }

    public static function getAvailableDates()
    {
        return self::withFormIdThree()
            ->select(DB::raw('DISTINCT YEAR(created_at) as year, MONTH(created_at) as month'))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->map(function ($date) {
                return [
                    'year' => $date->year,
                    'month' => $date->month,
                    'monthName' => Carbon::create()->month($date->month)->format('F')
                ];
            });
    }

    public static function getComplaintsByMonthYear($month, $year)
    {
        return self::withFormIdThree()
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get()
            ->map(function ($complaint) {
                $extractedData = $complaint->getExtractedData();
                $status = $extractedData['status'] ?? 'N/A';

                if ($complaint->is_pending == 1 && $status !== 'Selesai') {
                    $status = 'Pending';
                }

                return [
                    'id' => $complaint->id,
                    'datetime_masuk' => $complaint->datetime_masuk,
                    'datetime_pengerjaan' => $complaint->datetime_pengerjaan,
                    'datetime_selesai' => $complaint->datetime_selesai,
                    'petugas' => $complaint->petugas,
                    'is_pending' => $complaint->is_pending,
                    'nama_pelapor' => $extractedData['nama_pelapor'] ?? 'N/A',
                    'unit' => $extractedData['unit'] ?? 'N/A',
                    'lokasi' => $extractedData['lokasi'] ?? 'N/A',
                    'status' => $status,
                    'created_at' => $complaint->created_at->format('Y-m-d H:i:s'),
                ];
            });
    }

    public static function getComplaintStats($month, $year)
    {
        $complaints = self::getComplaintsByMonthYear($month, $year);

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
    }

    public static function getDetailedComplaints($month, $year)
    {
        $complaints = self::getComplaintsByMonthYear($month, $year);

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
                        'Respon Time' => self::formatTimeDiff(Carbon::parse($complaint['datetime_masuk'])->diffInSeconds(Carbon::parse($complaint['datetime_pengerjaan']))),
                        'Durasi Pengerjaan' => self::formatTimeDiff(Carbon::parse($complaint['datetime_pengerjaan'])->diffInSeconds(Carbon::parse($complaint['datetime_selesai']))),
                    ];
                });
            });
    }

    public static function getTotalUnitStats($month, $year)
    {
        $complaints = self::getComplaintsByMonthYear($month, $year);

        $unitCategories = self::getUnitCategories();

        return $complaints->groupBy('unit')
            ->map(function ($unitComplaints, $unit) use ($unitCategories) {
                $category = self::getCategoryForUnit($unit, $unitCategories);
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
    }

    private static function getUnitCategories()
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

    private static function getCategoryForUnit($unit, $unitCategories)
    {
        foreach ($unitCategories as $category => $units) {
            if (in_array($unit, $units)) {
                return $category;
            }
        }
        return 'Unit Lainnya';
    }

    public function getExtractedData()
    {
        $jsonData = json_decode($this->json, true);

        if (!$jsonData || !is_array($jsonData) || empty($jsonData[0])) {
            return [
                'nama_pelapor' => null,
                'unit' => null,
                'lokasi' => null,
                'status' => null,
                'is_pending' => 0,
            ];
        }

        $formData = $jsonData[0];
        $extractedData = [
            'nama_pelapor' => null,
            'unit' => null,
            'lokasi' => null,
            'status' => null,
            'is_pending' => 0,
        ];

        $fieldMap = [
            'text-1709615631557-0' => 'nama_pelapor',
            'text-1709615712000-0' => 'lokasi',
            'Status' => 'status',
            'is_pending' => 'is_pending',
        ];

        foreach ($formData as $item) {
            if (isset($item['name'])) {
                if (isset($fieldMap[$item['name']])) {
                    $extractedData[$fieldMap[$item['name']]] = $item['value'] ?? null;
                } elseif ($item['name'] === 'select-1722845859503-0' && isset($item['values'])) {
                    $extractedData['unit'] = collect($item['values'])->firstWhere('selected', 1)['label'] ?? null;
                }
            }
        }

        if ($extractedData['is_pending'] == 1 && $this->datetime_selesai === null) {
            $extractedData['status'] = 'Pending';
        }

        return $extractedData;
    }

    private static function formatTimeDiff($seconds)
    {
        $units = [
            86400 => 'hari',
            3600 => 'jam',
            60 => 'menit',
            1 => 'detik'
        ];

        $result = [];
        foreach ($units as $unit => $text) {
            if ($seconds >= $unit) {
                $quantity = floor($seconds / $unit);
                $result[] = "$quantity $text";
                $seconds %= $unit;
            }
        }

        return $result ? implode(' ', $result) : '0 detik';
    }
}