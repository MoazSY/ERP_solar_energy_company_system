<?php

namespace App\Services;

use App\Repositories\ApiSyriaToolsRepositoryInterface;

class ApiSyriaToolsService
{
    protected $apiSyriaToolsRepositoryInterface;

    public function __construct(ApiSyriaToolsRepositoryInterface $apiSyriaToolsRepositoryInterface)
    {
        $this->apiSyriaToolsRepositoryInterface = $apiSyriaToolsRepositoryInterface;
    }

    public function api_status()
    {
        return $this->apiSyriaToolsRepositoryInterface->api_status();
    }

    public function api_accounts()
    {
        return $this->apiSyriaToolsRepositoryInterface->api_accounts();
    }

    public function shamcash_balance($accountAddress)
    {
        return $this->apiSyriaToolsRepositoryInterface->shamcash_balance($accountAddress);
    }

    public function shamcash_logs($accountAddress)
    {
        return $this->apiSyriaToolsRepositoryInterface->shamcash_logs($accountAddress);
    }

    public function shamcash_find_transaction($accountAddress, $tx)
    {
        return $this->apiSyriaToolsRepositoryInterface->shamcash_find_transaction($accountAddress, $tx);
    }
}
