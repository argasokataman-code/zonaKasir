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
            ->select('id', 'name', 'email', 'code', 'phone', 'point', 'created_at')
            ->allowedFilters(['name', 'email'])
            ->orderByDesc('created_at')
            ->simplePaginate($this->resolvePerPage(request()) ?? 15);

        return $this->buildResponse()
            ->setData($members)
            ->present();
    }

    public function store(Request $request): JsonResponse
    {
        $this->validate($request, $this->rules(new Member));
        
        try {
            
            $member = new Member();
            $member->fill($request->all());
            $member->save();
            
            
            return $this->buildResponse()
                ->setData($member)
                ->setCode(201)
                ->setMessage('Member created successfully')
                ->present();
        } catch (Exception $e) {
            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to create member: ' . $e->getMessage())
                ->present();
        }
    }

    public function show(Member $member): JsonResponse
    {
        return $this->buildResponse()
            ->setData($member)
            ->present();
    }

    public function update(Request $request, Member $member): JsonResponse
    {
        $this->validate($request, $this->rules($member));
        
        try {
            
            $member->fill($request->all());
            $member->save();
            
            
            return $this->buildResponse()
                ->setData($member)
                ->setMessage('Member updated successfully')
                ->present();
        } catch (Exception $e) {
            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to update member: ' . $e->getMessage())
                ->present();
        }
    }

    public function destroy(Member $member): JsonResponse
    {
        try {
            $member->forceDelete();
            
            return $this->buildResponse()
                ->setMessage('Member deleted successfully')
                ->present();
        } catch (Exception $e) {
            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to delete member: ' . $e->getMessage())
                ->present();
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
