<?php

namespace App\Http\Controllers;

use App\Models\{UserInfo, RadUserGroup, Radcheck};
use App\Traits\{CheckerTrait,ValidatorTrait};
use Illuminate\Http\Request;
use DB;

class ApiController extends Controller
{
    const block_priority = -999;
    const unblock_priority = 0;
    const groupname_blocked = 'daloRADIUS-Disabled-Users';
    
    use ValidatorTrait, CheckerTrait;

    public function CheckRadusergroupUser(Request $request)
    {
        // ../api/rad-checkradusergroup
        // return search result of username in radusergroup table
        $query = RadUserGroup::where('username', $request['username'])
        ->first();
        if(isset($query)) {
            return response()->json([
                'success' => true,
                'user' => $request['username'],
                'status' => true,
            ],200);
        } else {
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => 'username '.$request['username'].' not found'
            ],400);
        }
    }
    public function CheckBlockUsername(Request $request) 
    {
        // ../api/rad-checkblock
        return $this->blockStatus($request);
    }

    public function UserInfo()
    {
        // ../api/rad-alluser
        $query =  UserInfo::get();
        return response()->json([
            'success' => true,
            'user' => $query,
        ],200);
    }

    public function FindByUsername(Request $request)
    {
        // ../api/rad-findusername
        try {
            $query = UserInfo::where('username', $request['username'])->firstOrFail();
            return response()->json([
                'success' => true,
                'user' => $query,
            ],200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'not_found',
                'user' => $request['username'],
                'message' => 'username not found'
            ],422);
        }
    }


    public function usernameCIDMatch(Request $request)
    {
        // ../rad-cid-match/{all (GET), search (POST)}
        // parameters for POST method : {cid: xxxxx..}
        $validation = $this->validatorCID($request);
        if($validation and $request->method() == 'POST') {
            return $validation;
        }else {
            return $this->usernameCID($request); 
        }
    }
    public function BlockUserConnection(Request $request)
    {
        /*
        ../api/rad-block
        ***PROCESS***
        1. /api/rad-block get POST request with CID parameters
        2. CID passed into usernameCIDMatch() to find correlates beetween CID and username
        3. if CID has > 1 username, block abort
        4. else username do status whether already block or not
        5. if status not blocked, do block. else throw error 
        */
        $match = $this->usernameCIDMatch($request);
        if($match->getData()->success == true) {
            if($match->getData()->count > 1){
                return response()->json([
                    'success' => false,
                    'cid' => $request['cid'],
                    'message' => 'CID has more than 1 username'
                ],422);
            }
            try {
                $user_check = new Request(['username'=> $match->getData()->user[0]->username]);
                $CheckBlock = $this->CheckBlockUsername($user_check);
                if($CheckBlock->getData()->status == true) {
                    return $CheckBlock;
                } 
                $query2 = RadUserGroup::insert(['username' => $user_check['username'], 
                'groupname' => ApiController::groupname_blocked,
                'priority' =>  ApiController::block_priority]);  
                return response()->json([
                    'success' => true,
                    'cid' => $request['cid'],
                    'user' => $user_check['username'],
                    'message' => 'Block user successfully'
                ],201);                 
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'cid' => $request['cid'],
                    'message' => 'Error on block process'
                ],400);
            }
        }else {
            return $match;
        }
    }
    public function UnblockUserConnection(Request $request)
    {
        // ../api/rad-unblock

        $match = $this->usernameCIDMatch($request);
        if($match->getData()->success == true) {
            if($match->getData()->count > 1){
                return response()->json([
                    'success' => false,
                    'cid' => $request['cid'],
                    'message' => 'CID has more than 1 username'
                ],422);
            }
            try {
                $user_check = new Request(['username'=> $match->getData()->user[0]->username]);
                $CheckBlock = $this->CheckBlockUsername($user_check);
                if($CheckBlock->getData()->status == false) {
                    return $CheckBlock;
                } 
                $query2 =  RadUserGroup::where('username', $user_check['username'])
                ->where('groupname', ApiController::groupname_blocked)
                ->where('priority', ApiController::block_priority)
                ->delete(); 
                return response()->json([
                    'success' => true,
                    'cid' => $request['cid'],
                    'user' => $user_check['username'],
                    'message' => 'Unblock user successfully'
                ],201);                 
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'cid' => $request['cid'],
                    'message' => 'Error on unblock process'
                ],400);
            }
        }else {
            return $match;
        }
    }
    
    public function groupStatusAll() 
    {
        // ../api/rad-groupstatus/all
        $query = 
        DB::select(DB::raw(
            "SELECT distinct(radcheck.username), userinfo.notes as cid,radcheck.value, radcheck.id,
            radusergroup.groupname as groupname, attribute,
            userinfo.firstname, userinfo.lastname , IFNULL(disabled.username,0) as disabled 
            FROM radcheck
        LEFT JOIN radusergroup ON radcheck.username=radusergroup.username
        LEFT JOIN userinfo ON radcheck.username=userinfo.username
        LEFT JOIN radusergroup disabled ON disabled.username=userinfo.username
            AND disabled.groupname = 'daloRADIUS-Disabled-Users'
        WHERE (radcheck.username=userinfo.username)
            AND Attribute IN ('Cleartext-Password', 'Auth-Type','User-Password', 'Crypt-Password', 
                'MD5-Password', 'SMD5-Password', 'SHA-Password', 'SSHA-Password', 'NT-Password', 
                'LM-Password', 'SHA1-Password', 'CHAP-Password', 'NS-MTA-MD5-Password')
            AND userinfo.notes <> ''
        GROUP by radcheck.Username ORDER BY id asc"));

        return response()->json([
            'success' => true,
            'count' => count($query),
            'results' => $query,
        ],200);  
    }

    public function groupStatusSearch(Request $request) 
    {
        // ../api/rad-groupstatus/search

        $query = 
        DB::select(DB::raw(
            "SELECT distinct(radcheck.username), userinfo.notes as cid, radcheck.value, radcheck.id,
            radusergroup.groupname as groupname, attribute,
            userinfo.firstname, userinfo.lastname , IFNULL(disabled.username,0) as disabled 
            FROM radcheck
        LEFT JOIN radusergroup ON radcheck.username=radusergroup.username
        LEFT JOIN userinfo ON radcheck.username=userinfo.username
        LEFT JOIN radusergroup disabled ON disabled.username=userinfo.username
            AND disabled.groupname = 'daloRADIUS-Disabled-Users'
        WHERE (radcheck.username=userinfo.username)
            AND userinfo.notes = ?
            AND Attribute IN ('Cleartext-Password', 'Auth-Type','User-Password', 'Crypt-Password', 
                'MD5-Password', 'SMD5-Password', 'SHA-Password', 'SSHA-Password', 'NT-Password', 
                'LM-Password', 'SHA1-Password', 'CHAP-Password', 'NS-MTA-MD5-Password')
        GROUP by radcheck.Username ORDER BY id asc"), array($request['cid'],));
    
        if(count($query) == 0) {
            return response()->json([
                'success' => false,
                'message' => 'CID '.$request['cid'].' not found',
            ],202); 
        } else {
            return response()->json([
                'success' => true,
                'results' => $query,
            ],200); 
        }
    }

    public function fetchUserGroup (Request $request) {
        // ../api/rad-usergroup/{all,search}
    }
}