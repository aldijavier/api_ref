<?php

namespace App\Http\Controllers;

use App\Models\{UserInfo, RadUserGroup, Radcheck};

use Illuminate\Http\Request;
use DB;

class ApiController extends Controller
{
    const block_priority = -999;
    const unblock_priority = 0;
    const groupname_blocked = 'daloRADIUS-Disabled-Users';
    
    public function CheckRadusergroupUser(Request $request)
    {
        // ../api/rad-checkradusergroup
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
            ],202);
        }
    }
    public function CheckBlock(Request $request) 
    {
        // ../api/rad-checkblock
        try {
            $CheckRadusergroupUser = $this->CheckRadusergroupUser($request);
            if($CheckRadusergroupUser->getData()->status == false) {
                return $CheckRadusergroupUser;
            }
            $query =  RadUserGroup::where('username', $request['username'])
            ->where('groupname', ApiController::groupname_blocked)
            ->where('priority', ApiController::block_priority)
            ->firstOrFail(); 
            return response()->json([
                'success' => true,
                'status' => true,
                'user' => $request['username'],
                'message' => 'username '.$request['username'].' already blocked'
            ],200);

        } catch (\Exception $e){
            return response()->json([
                'success' => true,
                'status' => false,
                'user' => $request['username'],
                'message' => 'username '.$request['username'].' already unblocked'
            ],202);
        }
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
        try {
            $query = UserInfo::where('username', $request['username'])->firstOrFail();
            return response()->json([
                'success' => true,
                'user' => $query,
            ],200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'user' => $request['username'],
                'message' => 'username not found'
            ],202);
        }
    }

    public function BlockUserConnection(Request $request)
    {
        // ../api/rad-block
        // return $this->FindByUsername($request['username']);
        // $find2 = json_decode($find->getContent());
        $find = $this->FindByUsername($request);
        if($find->getData()->success == true) {
            try {
                $CheckRadusergroupUser = $this->CheckRadusergroupUser($request);
                $CheckBlock = $this->CheckBlock($request);
                if($CheckBlock->getData()->success == false) {
                    return $CheckRadusergroupUser;
                }else if($CheckBlock->getData()->status ==  true) {
                    return $CheckBlock;                 
                } 
                $query2 = RadUserGroup::insert(['username' => $request['username'], 
                'groupname' => ApiController::groupname_blocked,
                'priority' =>  ApiController::block_priority]);                    
                return response()->json([
                    'success' => true,
                    'user' => $request['username'],
                    'message' => 'Block user successfully'
                ],200);          
            } catch (\Exception $e){
                return response()->json([
                    'success' => false,
                    'message' => 'Block user failed'
                ],202);
            }
        }
        else {
            return $find;
        }
    }
    public function UnblockUserConnection(Request $request)
    {
        // ../api/rad-unblock
        $find = $this->FindByUsername($request);
        if($find->getData()->success ==  true) {
            try {
                $CheckBlock = $this->CheckBlock($request);
                if($CheckBlock->getData()->status == false) {
                    return $CheckBlock;                  
                } else {
                    $query2 =  RadUserGroup::where('username', $request['username'])
                    ->where('groupname', ApiController::groupname_blocked)
                    ->where('priority', ApiController::block_priority)
                    ->delete();                 
                    return response()->json([
                        'success' => true,
                        'user' => $request['username'],
                        'message' => 'Unblock user successfully'
                    ],200);  
                }   
            } catch (\Exception $e){
                return response()->json([
                    'success' => false,
                    'message' => 'Block user fail'
                ],202);
            }
        }
        else {
            return $find;
        }
    
    }
    
    public function groupStatusAll() 
    {
        // $query = DB::table('radcheck')
        // ->leftJoin('radusergroup', 'radcheck.username', '=', 'radusergroup.username')
        // ->leftJoin('userinfo', 'radcheck.username', '=', 'userinfo.username')
        // ->leftJoin('radusergroup as disabled', function($join){
        //     $join->on('disabled.username', '=', 'userinfo.username')
        //     ->where('disabled.username', 'daloRADIUS-Disabled-Users');
            
        // })
        // ->select(DB::raw('distinct(radcheck.username), radcheck.value, radcheck.id, radusergroup.groupname as groupname, 
        //     attribute, userinfo.firstname, userinfo.lastname, IFNULL(disabled.username,0) as disabled'))
        // // ->where('radcheck.username', 'userinfo.username')
        // ->whereIn('Attribute', 
        //     array('Cleartext-Password', 'Auth-Type','User-Password', 'Crypt-Password', 
        //     'MD5-Password', 'SMD5-Password', 'SHA-Password', 'SSHA-Password', 'NT-Password', 
        //     'LM-Password', 'SHA1-Password', 'CHAP-Password', 'NS-MTA-MD5-Password'))            
        // ->groupBy('radcheck.username')
        // ->orderBy('id', 'asc')
        // ->get();

        $query = 
        DB::select(DB::raw(
            "SELECT distinct(radcheck.username),radcheck.value, radcheck.id,
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
        GROUP by radcheck.Username ORDER BY id asc"));

        return response()->json([
            'success' => true,
            'count' => count($query),
            'results' => $query,
        ],200);  
    }

    public function groupStatusSearch(Request $request) {

        $query = 
        DB::select(DB::raw(
            "SELECT distinct(radcheck.username),radcheck.value, radcheck.id,
            radusergroup.groupname as groupname, attribute,
            userinfo.firstname, userinfo.lastname , IFNULL(disabled.username,0) as disabled 
            FROM radcheck
        LEFT JOIN radusergroup ON radcheck.username=radusergroup.username
        LEFT JOIN userinfo ON radcheck.username=userinfo.username
        LEFT JOIN radusergroup disabled ON disabled.username=userinfo.username
            AND disabled.groupname = 'daloRADIUS-Disabled-Users'
        WHERE (radcheck.username=userinfo.username)
            AND radcheck.username = ?
            AND Attribute IN ('Cleartext-Password', 'Auth-Type','User-Password', 'Crypt-Password', 
                'MD5-Password', 'SMD5-Password', 'SHA-Password', 'SSHA-Password', 'NT-Password', 
                'LM-Password', 'SHA1-Password', 'CHAP-Password', 'NS-MTA-MD5-Password')
        GROUP by radcheck.Username ORDER BY id asc"), array($request['username'],));

        if(count($query) == 0) {
            return response()->json([
                'success' => false,
                'message' => 'username '.$request['username'].' not found',
            ],202); 
        } else {
            return response()->json([
                'success' => true,
                'results' => $query,
            ],200); 
        }
    }
}
