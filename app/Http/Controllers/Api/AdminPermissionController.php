<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPermissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Permission::query()->orderBy('name');
        $perPage = (int) $request->input('per_page');

        $data = $perPage >= 1 && $perPage <= 100
            ? $query->paginate($perPage)
            : $query->get();

        return $this->apiSuccess($data, 'Permissions fetched successfully.');
    }
}