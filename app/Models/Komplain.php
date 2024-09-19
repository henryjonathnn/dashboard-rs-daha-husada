<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

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
        $cacheKey = "complaints_{$year}_{$month}";
        return Cache::remember($cacheKey, 60 * 60, function () use ($month, $year) {
            return self::withFormIdThree()
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->get()
                ->map(function ($complaint) {
                    $extractedData = $complaint->getExtractedData();
                    $status = $extractedData['status'] ?? 'N/A';

                    if ($complaint->is_pending == 1 && $status !== 'Selesai') {
                        $status = 'Pending';
                    }

                    return [
                        'id' => $complaint->id,
                        'json' => $complaint->json,
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
        });
    }

    public static function getComplaintStats($month, $year)
    {
        $complaints = self::getComplaintsByMonthYear($month, $year);

        $totalComplaints = $complaints->count();
        $statusCount = ['Terkirim' => 0, 'Dalam Pengerjaan' => 0, 'Selesai' => 0, 'Pending' => 0];
        $totalResponseTime = $totalProcessingTime = $countResponseTime = $countProcessingTime = 0;

        foreach ($complaints as $complaint) {
            $statusCount[$complaint['status']] = ($statusCount[$complaint['status']] ?? 0) + 1;

            if ($complaint['datetime_masuk'] && $complaint['datetime_pengerjaan']) {
                $totalResponseTime += Carbon::parse($complaint['datetime_masuk'])->diffInSeconds(Carbon::parse($complaint['datetime_pengerjaan']));
                $countResponseTime++;
            }

            if ($complaint['datetime_pengerjaan'] && $complaint['datetime_selesai']) {
                $totalProcessingTime += Carbon::parse($complaint['datetime_pengerjaan'])->diffInSeconds(Carbon::parse($complaint['datetime_selesai']));
                $countProcessingTime++;
            }
        }

        $averageResponseTime = $countResponseTime > 0 ? round($totalResponseTime / $countResponseTime) : 0;
        $averageProcessingTime = $countProcessingTime > 0 ? round($totalProcessingTime / $countProcessingTime) : 0;

        return [
            'total' => $totalComplaints,
            'statusCount' => $statusCount,
            'averageTimes' => [
                'responseTime' => $averageResponseTime,
                'processingTime' => $averageProcessingTime,
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
            })
            ->toArray();
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

    private static function formatTimeDiff($seconds): string
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
