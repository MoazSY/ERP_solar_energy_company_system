<?php

namespace App\Http\Controllers;

use App\Services\ApiSyriaToolsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiSyriaToolsController extends \App\Http\Controllers\Controller
{
    protected $apiSyriaToolsService;

    public function __construct(ApiSyriaToolsService $apiSyriaToolsService)
    {
        $this->apiSyriaToolsService = $apiSyriaToolsService;
    }

    public function api_status()
    {
        $result = $this->apiSyriaToolsService->api_status();

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function api_accounts()
    {
        $result = $this->apiSyriaToolsService->api_accounts();

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function shamcash_balance(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'account_address' => 'required|string',
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->apiSyriaToolsService->shamcash_balance($request->account_address);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function shamcash_logs(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'account_address' => 'required|string',
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->apiSyriaToolsService->shamcash_logs($request->account_address);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function shamcash_find_transaction(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'account_address' => 'required|string',
            'tx' => 'required|string',
        ]);

        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()], 422);
        }

        $result = $this->apiSyriaToolsService->shamcash_find_transaction(
            $request->account_address,
            $request->tx
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
