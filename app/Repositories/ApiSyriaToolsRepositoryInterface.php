<?php

namespace App\Repositories;

interface ApiSyriaToolsRepositoryInterface
{
    public function api_status();
    public function api_accounts();
    public function shamcash_balance($accountAddress);
    public function shamcash_logs($accountAddress);
    public function shamcash_find_transaction($accountAddress, $tx);
}
