<?php
 
namespace App\Traits;
use Illuminate\Http\Request;
use Validator;
 
trait ValidatorTrait {
 
    public function validatorCID(Request $request) {
        $validator =  Validator::make($request->all(),[
            'cid' => 'required|string|exists:App\Models\UserInfo,notes|max:25',
        ]);

        if($validator->fails()){
            return response()->json([
                "success" => false,
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 422);
        }
    }
}