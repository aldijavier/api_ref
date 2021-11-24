<?php
 
namespace App\Traits;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
 
trait ValidatorTrait {
 
    public function validatorCID(Request $request) {
        try {
            $query = DB::table('userinfo')->where('notes', $request['cid'])->get();
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                'cid' => $request['cid'],
                "error" => 'validation_error',
                "message" => $request['cid']. ' not found',
            ], 422);
        }
    }
}