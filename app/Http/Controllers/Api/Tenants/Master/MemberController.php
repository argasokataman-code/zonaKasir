<?php

namespace App\Http\Controllers\Api\Tenants\Master;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Member;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\QueryBuilder\QueryBuilder;

class MemberController extends Controller
{
    public function index(): JsonResponse
    {
        $members = QueryBuilder::for(Member::class)
            ->allowedFilters(['name', 'email'])
            ->orderByDesc('created_at')
            ->get();

        return $this->success($members);
    }

    public function store(Request $request): JsonResponse
    {
        $this->validate($request, $this->rules(new Member));
        
        try {
            DB::beginTransaction();
            
            $member = new Member();
            $member->fill($request->all());
            $member->save();
            
            DB::commit();
            
            return $this->success([], "success creating items");
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create member: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Member $member): JsonResponse
    {
        return $this->success($member);
    }

    public function update(Request $request, Member $member): JsonResponse
    {
        $this->validate($request, $this->rules($member));
        
        try {
            DB::beginTransaction();
            
            $member->fill($request->all());
            $member->save();
            
            DB::commit();
            
            return $this->success([], "success updating items");
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update member: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Member $member): JsonResponse
    {
        try {
            DB::beginTransaction();
            $member->delete();
            DB::commit();
            
            return $this->success([], "success deleting items");
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete member: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function rules(?Member $member): array
    {
        return [
            "name" => ["required", "min:3"],
            "email" => [Rule::unique("members")->ignore($member->id), "nullable"],
        ];
    }
}
