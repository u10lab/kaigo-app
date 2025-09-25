<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceRecord;
use App\Models\User;
use App\Models\Client;
use App\Models\ServiceType;
use App\Models\ExportHistory;
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

            // サービス記録を取得（利用者絞り込み対応）
            $query = ServiceRecord::whereBetween('service_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->where('status', '実施済み')
                ->with(['user', 'client', 'serviceType']);

            // 利用者IDでの絞り込み
            $clientId = $request->get('client_id');
            if ($clientId) {
                $query->where('client_id', $clientId);
            }

            $serviceRecords = $query->orderBy('service_date')
                ->orderBy('service_start_time')
                ->get();

            // CSVデータを生成
            $csvData = $this->generateCareInsuranceCsv($serviceRecords, $month);

            // ファイル名を生成（利用者名を含める場合）
            $filename = "care_insurance_{$month}";
            $clientName = '';
            if ($clientId) {
                $client = $serviceRecords->first()?->client;
                if ($client) {
                    $clientName = $client->name;
                    $filename .= "_{$clientName}";
                }
            }
            $filename .= '.csv';

            // エクスポート履歴を記録
            ExportHistory::create([
                'export_type' => 'care_insurance',
                'period' => $month,
                'record_count' => $serviceRecords->count(),
                'file_name' => $filename,
                'exported_by' => Auth::user()->id,
                'exported_at' => now(),
            ]);

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
                    'Content-Encoding' => 'UTF-8',
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
        // BOM付きUTF-8でCSVヘッダーを作成（要件に合わせたヘッダー）
        $csv = "\xEF\xBB\xBF"; // BOM
        $csv .= "利用者名,実施,開始日時,終了日時,サービス内容\n";

        foreach ($serviceRecords as $record) {
            $csv .= $this->formatCsvRow($record);
        }

        // 確実にUTF-8エンコーディングに変換
        $csv = mb_convert_encoding($csv, 'UTF-8', 'auto');

        return $csv;
    }

    /**
     * CSV行をフォーマット
     */
    private function formatCsvRow($record): string
    {
        // 利用者名
        $clientName = $record->client->name ?? '不明';

        // 実施（要件では「実施」列のみ）
        $status = '実施済み'; // statusが'実施済み'のもののみ取得しているため固定

        // 開始日時（YYYY-MM-DD HH:MM形式）
        $startDateTime = Carbon::parse($record->service_start_time)->format('Y-m-d H:i');

        // 終了日時（YYYY-MM-DD HH:MM形式）
        $endDateTime = Carbon::parse($record->service_end_time)->format('Y-m-d H:i');

        // サービス内容
        $serviceContent = $record->serviceType->name ?? '不明';

        // CSV行を構築（要件に合わせて5項目のみ）
        $row = [
            $this->escapeCsvField($clientName),
            $this->escapeCsvField($status),
            $this->escapeCsvField($startDateTime),
            $this->escapeCsvField($endDateTime),
            $this->escapeCsvField($serviceContent),
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

    /**
     * ファイル再ダウンロード
     * GET /api/export/download/{historyId}
     */
    public function downloadFile(Request $request, int $historyId)
    {
        try {
            $user = Auth::user();
            Log::info('File download requested', [
                'history_id' => $historyId,
                'user_id' => $user ? $user->id : 'null',
                'user_name' => $user ? $user->name : 'null',
                'user_role' => $user ? $user->role : 'null',
                'user_email' => $user ? $user->email : 'null',
                'is_authenticated' => $user ? 'yes' : 'no',
                'auth_check' => Auth::check()
            ]);

            // 管理者のみアクセス可能
            if (!$user || $user->role !== 'admin') {
                Log::warning('File download denied - insufficient permissions', [
                    'history_id' => $historyId,
                    'user_id' => $user ? $user->id : 'null',
                    'user_role' => $user ? $user->role : 'null',
                    'reason' => !$user ? 'user_not_found' : 'insufficient_role'
                ]);
                return response()->json([
                    'error' => '管理者のみアクセス可能です。',
                    'debug_info' => [
                        'user_found' => $user ? 'yes' : 'no',
                        'user_role' => $user ? $user->role : 'null',
                        'required_role' => 'admin'
                    ]
                ], 403);
            }

            // エクスポート履歴を取得
            $exportHistory = ExportHistory::findOrFail($historyId);
            
            Log::info('Export history found', [
                'history_id' => $historyId,
                'period' => $exportHistory->period,
                'file_name' => $exportHistory->file_name,
            ]);

            // ファイルが存在するかチェック（実際のファイルシステムでは実装が必要）
            // 今回は履歴から再生成する方式で実装
            $startDate = Carbon::parse($exportHistory->period . '-01')->startOfMonth();
            $endDate = Carbon::parse($exportHistory->period . '-01')->endOfMonth();

            // ファイル名から利用者情報を抽出
            $clientId = null;
            if (strpos($exportHistory->file_name, '_') !== false) {
                // ファイル名の形式: care_insurance_YYYY-MM_利用者名.csv
                $parts = explode('_', $exportHistory->file_name);
                Log::info('File name parts', [
                    'file_name' => $exportHistory->file_name,
                    'parts' => $parts,
                    'parts_count' => count($parts)
                ]);
                
                if (count($parts) >= 4) {
                    // care_insurance_2025-09_山田一郎.csv の場合
                    // parts[0] = "care", parts[1] = "insurance", parts[2] = "2025-09", parts[3] = "山田一郎.csv"
                    $clientName = str_replace('.csv', '', $parts[3]);
                    Log::info('Extracted client name', ['client_name' => $clientName]);
                    
                    $client = Client::where('name', $clientName)->first();
                    if ($client) {
                        $clientId = $client->id;
                        Log::info('Client found by name', [
                            'client_name' => $clientName,
                            'client_id' => $clientId
                        ]);
                    } else {
                        Log::warning('Client not found by name', ['client_name' => $clientName]);
                    }
                } else {
                    Log::info('File name does not contain client name (all clients export)');
                }
            }

            // サービス記録を取得
            $query = ServiceRecord::whereBetween('service_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->where('status', '実施済み')
                ->with(['user', 'client', 'serviceType']);

            if ($clientId) {
                $query->where('client_id', $clientId);
                Log::info('Filtering by client_id', ['client_id' => $clientId]);
            } else {
                Log::info('No client filter applied (all clients)');
            }

            $serviceRecords = $query->orderBy('service_date')
                ->orderBy('service_start_time')
                ->get();
                
            // 取得されたサービス記録の利用者を確認
            $clientNames = $serviceRecords->pluck('client.name')->unique()->values();
            Log::info('Service records found for download', [
                'history_id' => $historyId,
                'records_count' => $serviceRecords->count(),
                'client_id' => $clientId,
                'client_names_in_records' => $clientNames->toArray()
            ]);

            // CSVデータを生成
            $csvData = $this->generateCareInsuranceCsv($serviceRecords, $exportHistory->period);

            Log::info('CSV data generated for download', [
                'history_id' => $historyId,
                'csv_length' => strlen($csvData),
                'file_name' => $exportHistory->file_name,
            ]);

            // CSVファイルとしてダウンロード
            return response($csvData, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Encoding' => 'UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $exportHistory->file_name . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);

        } catch (\Exception $e) {
            Log::error('File download failed', [
                'history_id' => $historyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'ファイルのダウンロードに失敗しました。',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * エクスポート履歴を取得
     * GET /api/export/history
     */
    public function getExportHistory(Request $request)
    {
        try {
            // 管理者のみアクセス可能
            if (Auth::user()->role !== 'admin') {
                return response()->json([
                    'error' => '管理者のみアクセス可能です。',
                ], 403);
            }

            // エクスポート履歴を取得（最新順）
            $exportHistory = ExportHistory::with('user')
                ->orderBy('exported_at', 'desc')
                ->get()
                ->map(function ($history) {
                    return [
                        'id' => $history->id,
                        'export_type' => $history->export_type,
                        'period' => $history->period,
                        'record_count' => $history->record_count,
                        'file_name' => $history->file_name,
                        'exported_by' => $history->user->name ?? 'Unknown',
                        'exported_at' => $history->exported_at->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json([
                'export_history' => $exportHistory,
                'generated_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Export history failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'エクスポート履歴の取得に失敗しました。',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
