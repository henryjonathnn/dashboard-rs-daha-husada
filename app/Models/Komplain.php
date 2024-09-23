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
        'id', 'json', 'datetime_masuk', 'datetime_pengerjaan', 'datetime_selesai', 'petugas', 'is_pending',
    ];

    protected static $unitCategories;

    private static $indonesianMonths = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    public function scopeWithFormId($query, $formId)
    {
        return $query->where('form_id', $formId);
    }

    public static function getAvailableDates($formId)
    {
        return Cache::remember('available_dates', 120, function () use ($formId) {
            return self::withFormId($formId)
                ->select(DB::raw('DISTINCT YEAR(created_at) as year, MONTH(created_at) as month'))
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->get()
                ->map(function ($date) {
                    return [
                        'year' => $date->year,
                        'month' => $date->month,
                        'monthName' => self::$indonesianMonths[$date->month]
                    ];
                });
        });
    }

    public static function getComplaintsByMonthYear($month, $year, $formId)
    {
        return Cache::remember("complaints_{$formId}_{$year}_{$month}", 60, function () use ($month, $year, $formId) {
            return self::withFormId($formId)
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
                        'pesan' => $extractedData['pesan'] ?? 'N/A',
                        'status' => $status === 'Dalam Pengerjaan / Pengecekan Petugas' ? 'Dalam Pengerjaan' : $status,
                        'original_status' => $status,
                        'created_at' => $complaint->created_at->format('Y-m-d H:i:s'),
                    ];
                });
        });
    }

    public function getExtractedData()
    {
        static $fieldMap = [
            'text-1709615631557-0' => 'nama_pelapor',
            'text-1709615712000-0' => 'lokasi',
            'textarea-1709615813383-0' => 'pesan',
            'Status' => 'status',
            'is_pending' => 'is_pending',
        ];

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

        foreach ($formData as $item) {
            if (isset($item['name'])) {
                if (isset($fieldMap[$item['name']])) {
                    $extractedData[$fieldMap[$item['name']]] = $item['value'] ?? null;
                } elseif ($item['name'] === 'select-1722845859503-0' && isset($item['values'])) {
                    $extractedData['unit'] = collect($item['values'])->firstWhere('selected', 1)['label'] ?? null;
                } elseif (strpos(strtolower($item['name']), 'unit') !== false || strpos(strtolower($item['name']), 'ruangan') !== false) {
                    $extractedData['unit'] = $item['value'] ?? null;
                }
            }
        }

        if ($extractedData['is_pending'] == 1 && $this->datetime_selesai === null) {
            $extractedData['status'] = 'Pending';
        }

        return $extractedData;
    }

    public static function getUnitCategories()
    {
        if (!self::$unitCategories) {
            self::$unitCategories = [
                'Unit IGD' => ['Ambulance', 'IGD'],
                'Unit Rawat Jalan' => [
                    'Klinik Anak', 'Klinik Bedah', 'Klinik Gigi', 'Klinik Jantung',
                    'Klinik Konservasi', 'Klinik Kulit', 'Klinik Kusta', 'Klinik Mata',
                    'Klinik Obgyn', 'Klinik Ortopedy', 'Klinik Penyakit Dalam', 'Klinik TB',
                    'Klinik THT', 'Klinik Umum'
                ],
                'Unit Rawat Inap' => ['Irna Atas', 'Irna Bawah', 'IBS', 'VK', 'Perinatology'],
                'Unit Penunjang Medis' => ['Farmasi', 'Laboratorium', 'Admisi / Rekam Medis', 'Rehab Medik'],
                'Unit Lainnya' => [] 
            ];
        }
        return self::$unitCategories;
    }

    private static function getCategoryForUnit($unit)
    {
        $categories = self::getUnitCategories();
        foreach ($categories as $category => $units) {
            if (in_array($unit, $units)) {
                return $category;
            }
        }
        return 'Unit Lainnya';
    }
}