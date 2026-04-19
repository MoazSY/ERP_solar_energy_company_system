<?php

namespace App\Repositories;

use App\Repositories\ApiSyriaToolsRepositoryInterface;
use App\Services\ApiSyriaService;

class ApiSyriaToolsRepository implements ApiSyriaToolsRepositoryInterface
{
    protected $apiSyriaService;

    public function __construct(ApiSyriaService $apiSyriaService)
    {
        $this->apiSyriaService = $apiSyriaService;
    }

    public function api_status()
    {
        return $this->apiSyriaService->getStatus();
    }

    public function api_accounts()
    {
        return $this->apiSyriaService->listAccounts();
    }

    public function shamcash_balance($accountAddress)
    {
        return $this->apiSyriaService->getShamcashBalance($accountAddress);
    }

    public function shamcash_logs($accountAddress)
    {
        return $this->apiSyriaService->getShamcashLogs($accountAddress);
    }

    public function shamcash_find_transaction($accountAddress, $tx)
    {
        return $this->apiSyriaService->findShamcashTransaction($accountAddress, $tx);
    }
}
