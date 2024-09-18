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
                    foreach ($item['values'] as $value) {
                        if (isset($value['selected']) && $value['selected'] == 1) {
                            $extractedData['unit'] = $value['label'] ?? null;
                            break;
                        }
                    }
                }
            }
        }

        // Logika untuk status Pending
        if ($extractedData['is_pending'] == 1 && $extractedData['datetime_selesai'] !== null) {
            $extractedData['status'] = 'Pending';
        }

        return $extractedData;
    }

    public static function getAvailableDates()
    {
        return self::withFormIdThree()
            ->select(DB::raw('DISTINCT YEAR(created_at) as year, MONTH(created_at) as month'))
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
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
    }
}
