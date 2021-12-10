<?php

namespace App\Http\Controllers;

use App\Referral;
use App\Referral_Agent;
use App\Referral_Ext;
use Illuminate\Http\Request;
use DB;
use Browser;

class ApiController extends Controller
{
    public function GetAllReferralPromo()
    {
        try{
            $query =  Referral::where('status', '=', 1)->get();
            return response()->json([
                'success' => true,
                'promocode' => $query,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'not_found',
                'message' => 'Code already expired or not found'
            ], 422);
        }
    }

    public function GetAllReferralAgent()
    {
        try{
            $query =  Referral_Agent::where('status', '=', 1)->get();
            return response()->json([
                'success' => true,
                'promocode' => $query,
            ] ,200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'not_found',
                'message' => 'Code already expired or not found'
            ],422);
        }
    }
    
    public function GetAllReferralAgentExt()
    {
        try{
            $query =  Referral_Ext::where('status', '=', 1)->get();
            return response()->json([
                'success' => true,
                'promocode' => $query,
            ] ,200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'not_found',
                'message' => 'Code already expired or not found'
            ],422);
        }
    }
}