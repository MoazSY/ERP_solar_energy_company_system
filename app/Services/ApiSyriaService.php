<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Payment_transactions;
use Exception;

class ApiSyriaService
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected ?string $beneficiaryGsm;
    protected ?string $beneficiaryShamcashAccount;
    protected ?float $usdToSypRate = null;
    protected float $fallbackUsdToSypRate = 13000.0;

    public function __construct()
    {
        $this->baseUrl = 'https://apisyria.com/api/v1';
        $this->apiKey = config('services.api_syria.key', env('API_SYRIA_KEY'));
        // $this->beneficiaryGsm = config('services.api_syria.beneficiary_gsm', env('API_SYRIA_BENEFICIARY_GSM'));
        // $this->beneficiaryShamcashAccount = config('services.api_syria.shamcash_beneficiary_account_address', env('API_SYRIA_SHAMCASH_BENEFICIARY_ACCOUNT_ADDRESS'));

        if (!$this->apiKey) {
            Log::warning('ApiSyriaService: missing API key configuration');
        }
    }

    public function transferCash(string $gsm, string $to_gsm, float $amount, string $pin_code): array
    {
        try {
            $balanceResponse = $this->checkBalance($gsm);
            if (!($balanceResponse['success'] ?? false)) {
                return [
                    'success' => false,
                    'message' => $balanceResponse['message'] ?? 'Unable to verify Syriatel balance before payment',
                    'data' => $balanceResponse['data'] ?? null,
                ];
            }

            $availableBalance = $this->extractNumericAmount($balanceResponse['data']['balance'] ?? null);
            if ($availableBalance === null) {
                return [
                    'success' => false,
                    'message' => 'Unable to read Syriatel balance before payment',
                    'data' => $balanceResponse['data'] ?? null,
                ];
            }

            if ($availableBalance < $amount) {
                return [
                    'success' => false,
                    'message' => 'Insufficient Syriatel balance for this payment',
                    'data' => [
                        'available_balance' => $availableBalance,
                        'required_amount' => $amount,
                    ],
                ];
            }

            $response = Http::asForm()->withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->post("{$this->baseUrl}?resource=syriatel&action=transfer_cash", [
                'gsm' => $gsm,
                'to_gsm' => $to_gsm,
                'amount' => $amount,
                'pin_code' => $pin_code,
            ]);

            return $this->handleResponse($response);
        } catch (Exception $e) {
            Log::error('ApiSyriaService transferCash exception', ['message' => $e->getMessage(), 'gsm' => $gsm, 'to_gsm' => $to_gsm]);
            return ['success' => false, 'message' => 'Payment gateway request failed', 'data' => null];
        }
    }

    public function transferShamcash(string $account_address, string $to_account_address, float $amount, string $pin_code): array
    {
        try {
            $payload = [
                'account_address' => $account_address,
                'to_account_address' => $to_account_address,
                'amount' => $amount,
                'pin_code' => $pin_code,
            ];

            $response = Http::asForm()->withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->post("{$this->baseUrl}?resource=shamcash&action=transfer_cash", $payload);

            $result = $this->handleResponse($response);
            if ($result['success'] ?? false) {
                $result['data'] = $this->normalizeShamcashAmounts($result['data'] ?? null);
            }

            return $result;
        } catch (Exception $e) {
            Log::error('ApiSyriaService transferShamcash exception', ['message' => $e->getMessage(), 'account_address' => $account_address]);
            return ['success' => false, 'message' => 'ShamCash payment gateway request failed', 'data' => null];
        }
    }

    public function checkBalance(string $gsm): array
    {
        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->baseUrl, [
                'resource' => 'syriatel',
                'action' => 'balance',
                'gsm' => $gsm,
            ]);

            return $this->handleResponse($response);
        } catch (Exception $e) {
            Log::error('ApiSyriaService checkBalance exception', ['message' => $e->getMessage(), 'gsm' => $gsm]);
            return ['success' => false, 'message' => 'Balance check request failed', 'data' => null];
        }
    }

    public function getHistory(string $gsm, string $period = '7'): array
    {
        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->baseUrl, [
                'resource' => 'syriatel',
                'action' => 'history',
                'gsm' => $gsm,
                'period' => $period,
            ]);

            return $this->handleResponse($response);
        } catch (Exception $e) {
            Log::error('ApiSyriaService getHistory exception', ['message' => $e->getMessage(), 'gsm' => $gsm, 'period' => $period]);
            return ['success' => false, 'message' => 'Transaction history request failed', 'data' => null];
        }
    }

    public function getStatus(): array
    {
        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->baseUrl, [
                'resource' => 'status',
            ]);

            return $this->handleResponse($response);
        } catch (Exception $e) {
            Log::error('ApiSyriaService getStatus exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => 'API status request failed', 'data' => null];
        }
    }

    public function listAccounts(): array
    {
        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->baseUrl, [
                'resource' => 'accounts',
                'action' => 'list',
            ]);

            return $this->handleResponse($response);
        } catch (Exception $e) {
            Log::error('ApiSyriaService listAccounts exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Accounts list request failed', 'data' => null];
        }
    }

  public function getLatestUsdToSypRate(): float
    {
        if ($this->usdToSypRate !== null) {
            return $this->usdToSypRate;
        }

        try {
            $response = Http::timeout(10)->get('https://lirascope.syria-cloud.sy/api/v1/rates/latest', [
                'currencies' => 'USD',
                'lang' => 'en',
            ]);

            if (!$response->successful()) {
                Log::warning('ApiSyriaService: failed to fetch latest USD exchange rate', ['status' => $response->status()]);
                return $this->fallbackUsdToSypRate;
            }

            $payload = $response->json();
            $effectiveRates = $payload['effectiveRates'] ?? $payload['marketRates'] ?? [];

            $usdRate = collect($effectiveRates)
                ->firstWhere('currency', 'USD');

            if (!$usdRate) {
                Log::warning('ApiSyriaService: USD exchange rate not found in LiraScope response', ['payload' => $payload]);
                return $this->fallbackUsdToSypRate;
            }

            $this->usdToSypRate = (float) ($usdRate['mid'] ?? $usdRate['sell'] ?? $usdRate['buy'] ?? 1.0);
            return $this->usdToSypRate;
        } catch (Exception $e) {
            Log::warning('ApiSyriaService: LiraScope USD rate lookup failed', ['message' => $e->getMessage()]);
            return $this->fallbackUsdToSypRate;
        }
    }

    public function convertAmountToSyp(float $amount, string $currency): float
    {
        if (strtoupper($currency) !== 'USD') {
            return $amount / 100;
        }

        return round($amount * $this->getLatestUsdToSypRate(), 2);
    }

public function convertSypToCurrency(float $amountSyp, string $targetCurrency): float
{
    if (strtoupper($targetCurrency) === 'SYP') {
        return $amountSyp;
    }

       if ($targetCurrency === 'USD') {
        return round($amountSyp * $this->getLatestUsdToSypRate(), 2);
    }
 Log::warning('Unknown currency, treating as SYP', ['currency' => $targetCurrency]);
    return $amountSyp;

}

   public function getShamcashBalance(string $accountAddress, bool $normalize = false): array
    {
        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->baseUrl, [
                'resource' => 'shamcash',
                'action' => 'balance',
                'account_address' => $accountAddress,
            ]);

            $result = $this->handleResponse($response);
            if ($normalize && ($result['success'] ?? false)) {
                $result['data'] = $this->normalizeShamcashAmounts($result['data'] ?? null);
            }

            return $result;
        } catch (Exception $e) {
            Log::error('ApiSyriaService getShamcashBalance exception', ['message' => $e->getMessage(), 'account_address' => $accountAddress]);
            return ['success' => false, 'message' => 'ShamCash balance request failed', 'data' => null];
        }
    }



public function getShamcashLogs(string $accountAddress, bool $normalize = false): array
    {
        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->baseUrl, [
                'resource' => 'shamcash',
                'action' => 'logs',
                'account_address' => $accountAddress,
            ]);

            $result = $this->handleResponse($response);
            if ($normalize && ($result['success'] ?? false)) {
                $result['data'] = $this->normalizeShamcashAmounts($result['data'] ?? null);
            }

            return $result;
        } catch (Exception $e) {
            Log::error('ApiSyriaService getShamcashLogs exception', ['message' => $e->getMessage(), 'account_address' => $accountAddress]);
            return ['success' => false, 'message' => 'ShamCash logs request failed', 'data' => null];
        }
    }




  public function findShamcashTransaction(string $accountAddress, string $tx, bool $normalize = false): array
    {
        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->baseUrl, [
                'resource' => 'shamcash',
                'action' => 'find_tx',
                'account_address' => $accountAddress,
                'tx' => $tx,
            ]);

            $result = $this->handleResponse($response);
            if ($normalize && ($result['success'] ?? false)) {
                $result['data'] = $this->normalizeShamcashAmounts($result['data'] ?? null);
            }

            return $result;
        } catch (Exception $e) {
            Log::error('ApiSyriaService findShamcashTransaction exception', [
                'message' => $e->getMessage(),
                'account_address' => $accountAddress,
                'tx' => $tx,
            ]);
            return ['success' => false, 'message' => 'ShamCash transaction lookup request failed', 'data' => null];
        }
    }


  public function extractExternalIdFromEntry(array $entry): ?string
    {
        // المفاتيح ذات الأولوية (تطابق ما يستخدم في التخزين)
        $priorityKeys = ['tran_id','transaction_no', 'billcode', 'transaction_id', 'tx_id', 'id'];
        foreach ($priorityKeys as $key) {
            if (isset($entry[$key]) && !empty($entry[$key])) {
                return trim(strtolower((string) $entry[$key]));
            }
        }

        // مفاتيح إضافية كحل أخير
        $additionalKeys = ['txn_id', 'reference_id', 'payment_id', 'external_id'];
        foreach ($additionalKeys as $key) {
            if (isset($entry[$key]) && !empty($entry[$key])) {
                return trim(strtolower((string) $entry[$key]));
            }
        }

        // Fallback آمن
        Log::warning('ApiSyriaService: No known transaction ID found, using fallback hash', ['entry_keys' => array_keys($entry)]);
        return 'tx_' . md5(json_encode($entry));
    }





    private function isTransactionAlreadyUsed(string $externalId): bool
    {
        if (empty($externalId)) {
            return false;
        }

        try {
            return Payment_transactions::where('external_id', $externalId)
                ->where('status', 'paid')
                ->exists();
        } catch (\Exception $e) {
            Log::error('Error checking transaction usage', [
                'external_id' => $externalId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }


/**
 * التحقق من صحة الدفع من سجلات ShamCash
 * 
 * التعديلات النهائية:
 * 1. استخدام getShamcashLogs مع normalize = false للحصول على المبالغ الأصلية
 * 2. استخراج العملة وتحويل المبلغ إلى SYP قبل المقارنة
 * 3. استخراج external_id من tran_id (المفتاح الصحيح)
 * 4. استخدام account كمفتاح لاستخراج حساب المرسل
 * 5. تشديد شرط senderMatches ليكون صارماً عند وجود مرسل متوقع
 * 6. تبسيط accountMatches بالاعتماد على normalizedLogsAccount
 * 7. التحقق من عدم استخدام المعاملة مسبقاً
 */
public function verifyShamcashPaymentFromLogs(
    string $targetAccountAddress,
    float $requiredAmount,
    ?string $expectedSenderAccountAddress = null
): array {
    // توحيد المدخلات (تحويل إلى حروف صغيرة وإزالة المسافات)
    $normalizedTarget = trim(strtolower($targetAccountAddress));
    $normalizedExpectedSender = $expectedSenderAccountAddress !== null
        ? trim(strtolower($expectedSenderAccountAddress))
        : null;

    // تحديد الحسابات التي سنجلب سجلاتها
    $accountsToCheck = [];
    if ($normalizedExpectedSender !== null && $normalizedExpectedSender !== '') {
        $accountsToCheck[] = $expectedSenderAccountAddress;
    }
    $accountsToCheck[] = $targetAccountAddress;
    $accountsToCheck = array_values(array_unique(array_filter($accountsToCheck)));

    $lastErrorMessage = null;
    $hadSuccessfulLogsCall = false;

    // التكرار على الحسابات المطلوب فحصها
    foreach ($accountsToCheck as $logsAccount) {
        // جلب السجلات دون تطبيع المبالغ (normalize = false)
        $logsResponse = $this->getShamcashLogs($logsAccount, false);
        if (!($logsResponse['success'] ?? false)) {
            $lastErrorMessage = $logsResponse['message'] ?? 'Unable to verify ShamCash logs';
            continue;
        }

        $hadSuccessfulLogsCall = true;
        $normalizedLogsAccount = trim(strtolower((string) $logsAccount));
        $entries = $this->flattenShamcashLogEntries($logsResponse['data'] ?? []);
        $debugLoggedEntries = 0;

        // التكرار على كل معاملة في السجلات
        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            // ============================================================
            // 1. استخراج بيانات المعاملة من السجل
            // ============================================================

            // حساب المستلم (يحاول عدة مفاتيح)
            $entryTargetAccount = trim(strtolower((string) (
                $entry['to_account_address']
                ?? $entry['target_account']
                ?? $entry['destination_account']
                ?? $entry['account_to']
                ?? $entry['to']
                ?? ''
            )));

            // حساب المرسل (مع إضافة 'account' كمفتاح أساسي)
            $entrySenderAccount = trim(strtolower((string) (
                $entry['from_account_address']
                ?? $entry['source_account']
                ?? $entry['sender_account']
                ?? $entry['account_from']
                ?? $entry['from']
                ?? $entry['account']  // 🔑 المفتاح الجديد - يحتوي على حساب المرسل في ShamCash
                ?? ''
            )));

            // المبلغ الخام (كما هو في السجل)
            $entryAmountRaw = $entry['amount']
                ?? $entry['value']
                ?? $entry['sum']
                ?? 0;
            $entryAmount = $this->extractNumericAmount($entryAmountRaw) ?? 0.0;

            // العملة (مع افتراض SYP إذا لم توجد)
            $entryCurrency = strtoupper(trim((string) (
                $entry['currency']
                ?? $entry['currency_code']
                ?? $entry['currency_symbol']
                ?? 'SYP'
            )));

            // تحويل المبلغ إلى SYP
            $entryAmountInSyp = $this->convertSypToCurrency($entryAmount, $entryCurrency);

            // حالة المعاملة
            $entryStatus = strtolower((string) (
                $entry['status']
                ?? $entry['state']
                ?? $entry['transaction_status']
                ?? ''
            ));

            // ============================================================
            // 2. التحقق من الشروط
            // ============================================================

            // 2.1 مطابقة المبلغ (بعد التحويل إلى SYP)
            $amountMatches = $entryAmountInSyp + 0.00001 >= $requiredAmount;

            // 2.2 مطابقة الحالة
            $statusMatches = $entryStatus === '' ||
                str_contains($entryStatus, 'success') ||
                str_contains($entryStatus, 'complete') ||
                str_contains($entryStatus, 'paid') ||
                str_contains($entryStatus, 'received') ||
                str_contains($entryStatus, 'done');

            // 2.3 مطابقة الحساب (نعتمد على الحساب الذي جلبنا السجلات منه)
            $accountMatches = ($normalizedLogsAccount === $normalizedTarget);
            if ($normalizedExpectedSender !== null && $normalizedExpectedSender !== '') {
                $accountMatches = $accountMatches || ($normalizedLogsAccount === $normalizedExpectedSender);
            }

            // 2.4 مطابقة المرسل (صارم - يجب أن يتطابق إذا كان متوقعاً)
            $senderMatches = true;
            if ($normalizedExpectedSender !== null && $normalizedExpectedSender !== '') {
                $senderMatches = ($entrySenderAccount === $normalizedExpectedSender);
            }

            // ============================================================
            // 3. التحقق من عدم استخدام المعاملة مسبقاً
            // ============================================================
            $externalId = $this->extractExternalIdFromEntry($entry);
            $isTransactionUsed = $this->isTransactionAlreadyUsed($externalId);

            // ============================================================
            // 4. تسجيل التصحيح (أول 5 معاملات فقط)
            // ============================================================
            if ($debugLoggedEntries < 5) {
                Log::info('ShamCash verify debug', [
                    'logs_account' => $normalizedLogsAccount,
                    'required_amount' => $requiredAmount,
                    'entry_amount_raw' => $entryAmount,
                    'entry_currency' => $entryCurrency,
                    'entry_amount_syp' => $entryAmountInSyp,
                    'target_expected' => $normalizedTarget,
                    'sender_expected' => $normalizedExpectedSender,
                    'entry_target_account' => $entryTargetAccount,
                    'entry_sender_account' => $entrySenderAccount,
                    'entry_status' => $entryStatus,
                    'external_id' => $externalId,
                    'is_transaction_used' => $isTransactionUsed,
                    'amount_matches' => $amountMatches,
                    'account_matches' => $accountMatches,
                    'sender_matches' => $senderMatches,
                    'status_matches' => $statusMatches,
                ]);
                $debugLoggedEntries++;
            }

            // ============================================================
            // 5. قبول المعاملة إذا تحققت جميع الشروط
            // ============================================================
            if ($amountMatches && $accountMatches && $senderMatches && $statusMatches && !$isTransactionUsed) {
                return [
                    'success' => true,
                    'matched_log' => $entry,
                    'external_id' => $externalId,
                ];
            }
        }
    }

    // ============================================================
    // 6. لم نجد معاملة صالحة
    // ============================================================
    if (!$hadSuccessfulLogsCall) {
        return [
            'success' => false,
            'message' => $lastErrorMessage ?? 'Unable to verify ShamCash logs',
        ];
    }

    return [
        'success' => false,
        'message' => 'You did not complete the required ShamCash payment yet',
    ];
}






    private function flattenShamcashLogEntries(mixed $payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        if (isset($payload[0])) {
            return $payload;
        }

        foreach (['logs', 'transactions', 'items', 'data', 'result'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return $this->flattenShamcashLogEntries($payload[$key]);
            }
        }

        return [$payload];
    }

    private function extractNumericAmount(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = preg_replace('/[^\d.\-]/', '', $value);
        if ($normalized === '' || $normalized === null || !is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    private function normalizeShamcashAmounts(mixed $payload): mixed
    {
        if (!is_array($payload)) {
            return $payload;
        }

        $amountKeys = ['amount', 'balance', 'fee', 'total', 'value', 'sum'];
        $normalized = [];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $normalized[$key] = $this->normalizeShamcashAmounts($value);
                continue;
            }

            if (in_array((string) $key, $amountKeys, true)) {
                $numericValue = $this->extractNumericAmount($value);
                if ($numericValue !== null) {
                    $normalized[$key] = $numericValue ;
                    continue;
                }
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    protected function handleResponse($response): array
    {
        if ($response->successful()) {
            $body = $response->json();
            return [
                'success' => $body['success'] ?? true,
                'message' => $body['message'] ?? 'Success',
                'data' => $body['data'] ?? $body,
            ];
        }

        $status = $response->status();
        $responseBody = $response->json();
        $message = match ($status) {
            400 => 'Bad request: invalid parameters',
            401 => 'Unauthorized: API key invalid or missing',
            403 => 'Forbidden: access denied',
            404 => 'Not found: invalid resource or account',
            405 => 'Method not allowed for this endpoint',
            429 => 'Rate limited: too many requests',
            500 => 'Server error: API Syria service unavailable',
            502 => 'Provider error from Syriatel or ShamCash',
            default => 'Unexpected API Syria error',
        };

        Log::warning('ApiSyriaService handleResponse error', ['status' => $status, 'body' => $response->body()]);

        return ['success' => false, 'message' => $message, 'data' => $responseBody];
    }

    public function getBeneficiaryGsm(): ?string
    {
        return $this->beneficiaryGsm;
    }

    public function getBeneficiaryShamcashAccount(): ?string
    {
        return $this->beneficiaryShamcashAccount;
    }
}
