<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceRecord;
use App\Models\User;
use App\Models\Client;
use App\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class ExportController extends Controller
{
    /**
     * 介護保険請求用CSV出力
     * GET /api/export/care-insurance/{month}
     */
    public function exportCareInsurance(Request $request, string $month)
    {
        try {
            // 月の形式を検証（YYYY-MM形式）
            if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                return response()->json([
                    'error' => '月の形式が正しくありません。YYYY-MM形式で指定してください。',
                    'provided' => $month,
                    'expected' => 'YYYY-MM',
                ], 400);
            }

            // 管理者のみアクセス可能
            if (Auth::user()->role !== 'admin') {
                return response()->json([
                    'error' => '管理者のみアクセス可能です。',
                ], 403);
            }

            // 対象月の開始日と終了日を計算
            $startDate = Carbon::parse($month . '-01')->startOfMonth();
            $endDate = Carbon::parse($month . '-01')->endOfMonth();

            // サービス記録を取得
            $serviceRecords = ServiceRecord::whereBetween('service_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->where('status', '実施済み')
                ->with(['user', 'client', 'serviceType'])
                ->orderBy('service_date')
                ->orderBy('service_start_time')
                ->get();

            // CSVデータを生成
            $csvData = $this->generateCareInsuranceCsv($serviceRecords, $month);

            // ファイル名を生成
            $filename = "care_insurance_{$month}.csv";

            Log::info('Care insurance CSV exported', [
                'month' => $month,
                'records_count' => $serviceRecords->count(),
                'exported_by' => Auth::user()->id,
            ]);

            // CSVファイルとしてダウンロード
            return Response::streamDownload(
                function () use ($csvData) {
                    echo $csvData;
                },
                $filename,
                [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ]
            );

        } catch (\Exception $e) {
            Log::error('Care insurance CSV export failed', [
                'month' => $month,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'CSV出力に失敗しました。',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 介護保険請求用CSVデータを生成
     */
    private function generateCareInsuranceCsv($serviceRecords, string $month): string
    {
        // BOM付きUTF-8でCSVヘッダーを作成
        $csv = "\xEF\xBB\xBF"; // BOM
        $csv .= "利用者名,実施状態,開始日時,終了日時,内容,サービス提供者,サービス時間(分),サービス時間(時間),サービスコード\n";

        foreach ($serviceRecords as $record) {
            $csv .= $this->formatCsvRow($record);
        }

        return $csv;
    }

    /**
     * CSV行をフォーマット
     */
    private function formatCsvRow($record): string
    {
        // 利用者名
        $clientName = $record->client->name ?? '不明';

        // 実施状態
        $status = $record->status === '実施済み' ? '実施済み' : 'キャンセル';

        // 開始日時（YYYY-MM-DD HH:MM形式）
        $startDateTime = Carbon::parse($record->service_start_time)->format('Y-m-d H:i');

        // 終了日時（YYYY-MM-DD HH:MM形式）
        $endDateTime = Carbon::parse($record->service_end_time)->format('Y-m-d H:i');

        // サービス内容
        $serviceContent = $record->serviceType->name ?? '不明';

        // サービス提供者
        $providerName = $record->user->name ?? '不明';

        // サービス時間（分）
        $serviceMinutes = $record->service_minutes;

        // サービス時間（時間） - 30分単位で計算
        $serviceHours = round($serviceMinutes / 60, 2);

        // サービスコード（介護保険コード）
        $serviceCode = $record->serviceType->care_insurance_code ?? '';

        // CSV行を構築（カンマ区切り、改行）
        $row = [
            $this->escapeCsvField($clientName),
            $this->escapeCsvField($status),
            $this->escapeCsvField($startDateTime),
            $this->escapeCsvField($endDateTime),
            $this->escapeCsvField($serviceContent),
            $this->escapeCsvField($providerName),
            $this->escapeCsvField($serviceMinutes),
            $this->escapeCsvField($serviceHours),
            $this->escapeCsvField($serviceCode),
        ];

        return implode(',', $row) . "\n";
    }

    /**
     * CSVフィールドをエスケープ
     */
    private function escapeCsvField($field): string
    {
        // フィールドにカンマ、改行、ダブルクォートが含まれる場合はダブルクォートで囲む
        if (strpos($field, ',') !== false || strpos($field, "\n") !== false || strpos($field, '"') !== false) {
            // ダブルクォートをエスケープ
            $field = str_replace('"', '""', $field);
            return '"' . $field . '"';
        }

        return $field;
    }

    /**
     * 月別サービス記録統計を取得
     * GET /api/export/care-insurance/{month}/stats
     */
    public function getCareInsuranceStats(Request $request, string $month)
    {
        try {
            // 月の形式を検証
            if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                return response()->json([
                    'error' => '月の形式が正しくありません。YYYY-MM形式で指定してください。',
                ], 400);
            }

            // 管理者のみアクセス可能
            if (Auth::user()->role !== 'admin') {
                return response()->json([
                    'error' => '管理者のみアクセス可能です。',
                ], 403);
            }

            // 対象月の開始日と終了日を計算
            $startDate = Carbon::parse($month . '-01')->startOfMonth();
            $endDate = Carbon::parse($month . '-01')->endOfMonth();

            // 統計データを取得
            $stats = $this->calculateCareInsuranceStats($startDate, $endDate);

            return response()->json([
                'month' => $month,
                'stats' => $stats,
                'generated_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Care insurance stats failed', [
                'month' => $month,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => '統計データの取得に失敗しました。',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 介護保険請求統計を計算
     */
    private function calculateCareInsuranceStats($startDate, $endDate): array
    {
        $totalRecords = ServiceRecord::whereBetween('service_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('status', '実施済み')
            ->count();

        $totalMinutes = ServiceRecord::whereBetween('service_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('status', '実施済み')
            ->sum('service_minutes');

        $totalHours = round($totalMinutes / 60, 2);

        $uniqueClients = ServiceRecord::whereBetween('service_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('status', '実施済み')
            ->distinct('client_id')
            ->count('client_id');

        $uniqueProviders = ServiceRecord::whereBetween('service_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('status', '実施済み')
            ->distinct('user_id')
            ->count('user_id');

        $serviceTypeStats = ServiceRecord::whereBetween('service_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('status', '実施済み')
            ->with('serviceType')
            ->get()
            ->groupBy('service_type_id')
            ->map(function ($records) {
                $serviceType = $records->first()->serviceType;
                return [
                    'service_type_name' => $serviceType->name ?? '不明',
                    'service_code' => $serviceType->care_insurance_code ?? '',
                    'count' => $records->count(),
                    'total_minutes' => $records->sum('service_minutes'),
                    'total_hours' => round($records->sum('service_minutes') / 60, 2),
                ];
            })
            ->values();

        return [
            'total_records' => $totalRecords,
            'total_minutes' => $totalMinutes,
            'total_hours' => $totalHours,
            'unique_clients' => $uniqueClients,
            'unique_providers' => $uniqueProviders,
            'service_type_breakdown' => $serviceTypeStats,
        ];
    }
}
